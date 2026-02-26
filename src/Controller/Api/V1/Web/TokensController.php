<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\FrozenTime;

class TokensController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->UpgradeTokens = $this->fetchTable('UpgradeTokens');
    }

    public function verify(string $tokenString)
    {
        $this->request->allowMethod(['get']);

        $token = $this->UpgradeTokens->find()
            ->where(['token_string' => $tokenString])
            ->first();

        if (!$token || $token->is_used) {
            throw new BadRequestException('Invalid or expired token');
        }

        if ($token->expires_at < new FrozenTime()) {
            throw new BadRequestException('Token has expired');
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'device_uuid' => $token->device_uuid
            ]));
    }
}
