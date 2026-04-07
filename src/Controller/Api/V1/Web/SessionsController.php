<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use Cake\Http\Exception\NotFoundException;

/**
 * @property \App\Model\Table\SyncSessionsTable $SyncSessions
 */
class SessionsController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->SyncSessions = $this->fetchTable('SyncSessions');
    }

    /**
     * List all sessions belonging to the authenticated user.
     *
     * Query params:
     *   sort       date_desc|date_asc (default date_desc)
     *   discipline filter by discipline_name
     *   type       filter by type
     *   page       (default 1)
     *   limit      (default 20, max 100)
     */
    public function index()
    {
        $this->request->allowMethod(['get']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $page = max(1, (int)($this->request->getQuery('page') ?? 1));
        $limit = min(100, max(1, (int)($this->request->getQuery('limit') ?? 20)));
        $sort = $this->request->getQuery('sort') ?? 'date_desc';
        $discipline = $this->request->getQuery('discipline');
        $type = $this->request->getQuery('type');

        $query = $this->SyncSessions->find()
            ->where([
                'SyncSessions.user_id' => $userId,
                'SyncSessions.deleted_at IS' => null,
            ]);

        if ($discipline !== null && $discipline !== '') {
            $query->where(['SyncSessions.discipline_name' => $discipline]);
        }

        if ($type !== null && $type !== '') {
            $query->where(['SyncSessions.type' => $type]);
        }

        if ($sort === 'date_asc') {
            $query->orderBy(['SyncSessions.session_date' => 'ASC']);
        } else {
            $query->orderBy(['SyncSessions.session_date' => 'DESC']);
        }

        $total = $query->count();
        $offset = ($page - 1) * $limit;
        $sessions = $query->limit($limit)->offset($offset)->all();
        $pages = (int)ceil($total / $limit);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'sessions' => $sessions,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => $pages,
                ],
            ]));
    }

    /**
     * View a single session with nested series, shots, and strings.
     */
    public function view(string $uuid)
    {
        $this->request->allowMethod(['get']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $session = $this->SyncSessions->find()
            ->where([
                'SyncSessions.uuid' => $uuid,
                'SyncSessions.user_id' => $userId,
                'SyncSessions.deleted_at IS' => null,
            ])
            ->contain([
                'SyncSeries' => function ($q) {
                    return $q
                        ->where(['SyncSeries.deleted_at IS' => null])
                        ->orderBy(['SyncSeries.series_number_within_phase' => 'ASC'])
                        ->contain([
                            'SyncShots' => function ($sq) {
                                return $sq->where(['SyncShots.deleted_at IS' => null]);
                            },
                        ]);
                },
                'SyncStrings' => function ($q) {
                    return $q
                        ->where(['SyncStrings.deleted_at IS' => null])
                        ->orderBy(['SyncStrings.string_number_within_phase' => 'ASC']);
                },
            ])
            ->first();

        if (!$session) {
            throw new NotFoundException('Session not found');
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'session' => $session,
            ]));
    }
}
