<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Admin;

use App\Controller\AppController;

/**
 * @property \App\Model\Table\SubscriptionsTable $Subscriptions
 */
class SubscriptionsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->Subscriptions = $this->fetchTable('Subscriptions');
    }

    public function index()
    {
        $this->request->allowMethod(['get']);
        $subscriptions = $this->Subscriptions->find()->contain(['Users'])->all();
        return $this->response->withType('application/json')->withStringBody(json_encode([
            'success' => true,
            'subscriptions' => $subscriptions
        ]));
    }
}
