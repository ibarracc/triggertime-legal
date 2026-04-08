<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Log\Log;
use Exception;
use Stripe\StripeClient;

/**
 * Stripe Subscriptions integration
 */
class SubscriptionsController extends AppController
{
    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * Create a Stripe Checkout session for upgrading to a Pro subscription.
     */
    public function createCheckout()
    {
        $this->request->allowMethod(['post']);

        $payload = $this->request->getAttribute('jwt_payload');
        $userId = $payload['sub'];

        $user = $this->fetchTable('Users')->get($userId);
        Log::debug('SubscriptionsController::createCheckout for user: ' . $userId);

        $secretKey = Configure::read('Stripe.secret_key');
        if (!$secretKey) {
            Log::error('Stripe Secret Key missing from configuration');
            throw new BadRequestException('Stripe is not configured correctly.');
        }

        $stripe = new StripeClient($secretKey);

        // Create or retrieve customer
        $needsNewCustomer = false;
        if (!$user->stripe_customer_id) {
            $needsNewCustomer = true;
        } else {
            // Verify the stored customer still exists in Stripe (may be from a different environment)
            try {
                $stripe->customers->retrieve($user->stripe_customer_id);
            } catch (Exception $e) {
                Log::warning(
                    'Stored Stripe customer ' . $user->stripe_customer_id
                    . ' not found, creating new one: ' . $e->getMessage(),
                );
                $needsNewCustomer = true;
            }
        }

        if ($needsNewCustomer) {
            Log::debug('Creating Stripe customer for user: ' . $user->email);
            try {
                $customer = $stripe->customers->create([
                    'email' => $user->email,
                    'name' => ($user->first_name ?? '') . ' ' . ($user->last_name ?? ''),
                ]);
                $user->stripe_customer_id = $customer->id;
                $this->fetchTable('Users')->save($user);
            } catch (Exception $e) {
                Log::error('Stripe Customer Creation Failed: ' . $e->getMessage());
                throw new BadRequestException('Failed to create billing profile: ' . $e->getMessage());
            }
        }

        $upgradeTokenString = $this->request->getData('upgrade_token');
        // Fallback to user's pending upgrade token from registration flow
        if (!$upgradeTokenString) {
            $upgradeTokenString = $user->pending_upgrade_token;
        }
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
            Log::debug('Creating Stripe Checkout session');
            $sessionParams = [
                'customer' => $user->stripe_customer_id,
                'success_url' => env('APP_FULL_BASE_URL', 'https://triggertime.ddev.site')
                    . '/checkout-success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => env('APP_FULL_BASE_URL', 'https://triggertime.ddev.site')
                    . '/dashboard/subscription',
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
                'allow_promotion_codes' => true,
            ];

            if (!empty($sessionMetadata)) {
                $sessionParams['metadata'] = $sessionMetadata;
            }

            $session = $stripe->checkout->sessions->create($sessionParams);
            Log::debug('Stripe Session Created: ' . $session->id);
        } catch (Exception $e) {
            Log::error('Stripe Checkout Session Creation Failed: ' . $e->getMessage());
            throw new BadRequestException('Failed to initiate checkout: ' . $e->getMessage());
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'url' => $session->url,
            ]));
    }

    /**
     * Create a Stripe Billing Portal session for managing subscriptions.
     */
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
            ->withStringBody((string)json_encode([
                'success' => true,
                'url' => $session->url,
            ]));
    }
}
