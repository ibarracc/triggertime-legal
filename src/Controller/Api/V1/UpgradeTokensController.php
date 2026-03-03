<?php
declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\I18n\DateTime;
use Cake\Utility\Text;

/**
 * @property \App\Model\Table\UpgradeTokensTable $UpgradeTokens
 */
class UpgradeTokensController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->UpgradeTokens = $this->fetchTable('UpgradeTokens');
    }

    /**
     * Generate an upgrade token for a device and return the upgrade URL.
     */
    public function generate()
    {
        $this->request->allowMethod(['post']);

        $deviceUuid = $this->request->getData('device_uuid');

        if (!$deviceUuid) {
            throw new BadRequestException('Missing device_uuid');
        }

        // Clean up old 'upgrade' tokens for this device
        $this->UpgradeTokens->deleteAll(['device_uuid' => $deviceUuid, 'type' => 'upgrade']);

        // Generate UUID token
        $tokenUuid = Text::uuid();

        $token = $this->UpgradeTokens->newEmptyEntity();
        $token->id = Text::uuid();
        $token->token_string = $tokenUuid;
        $token->type = 'upgrade';
        $token->device_uuid = $deviceUuid;
        $token->expires_at = (new DateTime())->addMinutes(15);
        $token->is_used = false;

        $this->UpgradeTokens->save($token);

        // Build the URL, we assume base URL from configure or env, or hardcoded per spec
        $upgradeUrl = Configure::read('App.fullBaseUrl') . '/upgrade?token=' . $tokenUuid;

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'token' => $tokenUuid,
                'url' => $upgradeUrl,
                'expires_at' => $token->expires_at,
            ]));
    }

    /**
     * Generate a short alphanumeric link code for device account linking.
     */
    public function generateLinkCode()
    {
        $this->request->allowMethod(['post']);

        $deviceUuid = $this->request->getData('device_uuid');

        if (!$deviceUuid) {
            throw new BadRequestException('Missing device_uuid');
        }

        // Clean up old 'link' tokens for this device
        $this->UpgradeTokens->deleteAll(['device_uuid' => $deviceUuid, 'type' => 'link']);

        // Generate 6-digit alphanum
        $tokenString = strtoupper(substr(str_shuffle(str_repeat('0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', 3)), 0, 6));

        $token = $this->UpgradeTokens->newEmptyEntity();
        $token->id = Text::uuid();
        $token->token_string = $tokenString;
        $token->type = 'link';
        $token->device_uuid = $deviceUuid;
        $token->expires_at = (new DateTime())->addMinutes(15);
        $token->is_used = false;

        $this->UpgradeTokens->save($token);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'link_code' => $tokenString,
                'expires_at' => $token->expires_at,
            ]));
    }
}
