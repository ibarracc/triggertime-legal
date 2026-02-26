<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Stripe\StripeClient;

/**
 * Stripe Subscriptions integration
 */
class SubscriptionsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

    public function createCheckout()
    {
        $this->request->allowMethod(['post']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $user = $this->fetchTable('Users')->get($userId);
        \Cake\Log\Log::debug('SubscriptionsController::createCheckout for user: ' . $userId);

        $secretKey = Configure::read('Stripe.secret_key');
        if (!$secretKey) {
            \Cake\Log\Log::error('Stripe Secret Key missing from configuration');
            throw new BadRequestException('Stripe is not configured correctly.');
        }

        $stripe = new StripeClient($secretKey);

        // Create or retrieve customer
        if (!$user->stripe_customer_id) {
            \Cake\Log\Log::debug('Creating Stripe customer for user: ' . $user->email);
            try {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                ]);
                $user->stripe_customer_id = $customer->id;
                $this->fetchTable('Users')->save($user);
            } catch (\Exception $e) {
                \Cake\Log\Log::error('Stripe Customer Creation Failed: ' . $e->getMessage());
                throw new BadRequestException('Failed to create billing profile: ' . $e->getMessage());
            }
        }

        $upgradeTokenString = $this->request->getData('upgrade_token');
        $sessionMetadata = [];

        if ($upgradeTokenString) {
            $tokensTable = $this->fetchTable('UpgradeTokens');

            $token = $tokensTable->find()
                ->where(['token_string' => $upgradeTokenString, 'type' => 'upgrade'])
                ->first();

            if ($token && !$token->is_used) {
                // Pass the device_uuid through Stripe metadata to the webhook
                $sessionMetadata['device_uuid'] = $token->device_uuid;
            }
        }

        // Real Stripe Checkout URL generation
        try {
            \Cake\Log\Log::debug('Creating Stripe Checkout session');
            $sessionParams = [
                'customer' => $user->stripe_customer_id,
                'success_url' => env('APP_FULL_BASE_URL', 'https://triggertime.ddev.site') . '/checkout-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_FULL_BASE_URL', 'https://triggertime.ddev.site') . '/dashboard/subscription',
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'usd',
                            'product_data' => [
                                'name' => 'Pro+ Subscription',
                            ],
                            'unit_amount' => 499,
                            'recurring' => [
                                'interval' => 'month',
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'mode' => 'subscription',
            ];

            if (!empty($sessionMetadata)) {
                $sessionParams['metadata'] = $sessionMetadata;
            }

            $session = $stripe->checkout->sessions->create($sessionParams);
            \Cake\Log\Log::debug('Stripe Session Created: ' . $session->id);
        } catch (\Exception $e) {
            \Cake\Log\Log::error('Stripe Checkout Session Creation Failed: ' . $e->getMessage());
            throw new BadRequestException('Failed to initiate checkout: ' . $e->getMessage());
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'url' => $session->url
            ]));
    }

    public function portal()
    {
        $this->request->allowMethod(['post']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $user = $this->fetchTable('Users')->get($userId);

        if (!$user->stripe_customer_id) {
            throw new BadRequestException('No Stripe customer found for this user. Please complete a payment first.');
        }

        $secretKey = Configure::read('Stripe.secret_key');
        if (!$secretKey) {
            throw new BadRequestException('Stripe is not configured correctly.');
        }

        $stripe = new StripeClient($secretKey);

        // Real Stripe Billing Portal URL generation
        $session = $stripe->billingPortal->sessions->create([
            'customer' => $user->stripe_customer_id,
            'return_url' => env('APP_FULL_BASE_URL', 'https://triggertime.ddev.site') . '/dashboard/subscription',
        ]);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'url' => $session->url
            ]));
    }
}
