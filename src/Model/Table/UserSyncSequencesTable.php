<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;

class UserSyncSequencesTable extends Table
{
    /**
     * @inheritDoc
     */
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('user_sync_sequences');
        $this->setPrimaryKey('user_id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'INNER',
        ]);
    }
}
