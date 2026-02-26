<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\AppController;
use Cake\Core\Configure;
use Cake\Log\Log;
use Stripe\Webhook;

class WebhooksController extends AppController
{

    public function stripe()
    {
        $this->request->allowMethod(['post']);

        $payload = (string)$this->request->getBody();
        $sigHeader = $this->request->getHeaderLine('stripe-signature');
        $webhookSecret = Configure::read('Stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::error('Stripe Webhook Secret is not configured.', ['payments']);
            return $this->response->withStatus(400)->withStringBody('Webhook Secret missing');
        }

        try {
            $event = Webhook::constructEvent(
                $payload,
                $sigHeader,
                $webhookSecret
            );
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe Webhook: Invalid payload. ' . $e->getMessage(), ['payments']);
            return $this->response->withStatus(400)->withStringBody('Invalid payload');
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe Webhook: Invalid signature. ' . $e->getMessage(), ['payments']);
            return $this->response->withStatus(400)->withStringBody('Invalid signature');
        }

        Log::info('Stripe Webhook Received: ' . $event->type, ['payments']);

        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;

                if (isset($session->customer) && isset($session->subscription)) {
                    $usersTable = $this->fetchTable('Users');
                    $user = $usersTable->find()->where(['stripe_customer_id' => $session->customer])->first();
                    Log::debug('Checkout Webhook: Searching for customer ' . $session->customer . '. Found: ' . ($user ? 'Yes (ID:' . $user->id . ')' : 'No'), ['payments']);

                    if ($user) {
                        $subscriptionsTable = $this->fetchTable('Subscriptions');
                        Log::debug('Checkout Webhook: Table columns: ' . json_encode($subscriptionsTable->getSchema()->columns()), ['payments']);
                        $subscription = $subscriptionsTable->find()->where(['user_id' => $user->id])->first();

                        if (!$subscription) {
                            $subscription = $subscriptionsTable->newEmptyEntity();
                            $subscription->id = \Cake\Utility\Text::uuid();
                            $subscription->user_id = $user->id;
                            Log::debug('Checkout Webhook: Created new subscription entity with ID: ' . $subscription->id, ['payments']);
                        }

                        try {
                            $secretKey = Configure::read('Stripe.secret_key');
                            $stripe = new \Stripe\StripeClient($secretKey);
                            $stripeSubscription = $stripe->subscriptions->retrieve($session->subscription);

                            $subscription->stripe_subscription_id = $session->subscription;
                            $subscription->plan = 'pro';
                            $subscription->status = 'active';
                            $subscription->max_devices_allowed = Configure::read("Subscriptions.{$subscription->plan}.max_devices_allowed");

                            Log::debug('Checkout Webhook: Subscription object keys: ' . json_encode(array_keys($stripeSubscription->toArray())), ['payments']);

                            // In Stripe API 2025-03-31+, current_period_start/end moved to subscription items
                            $firstItem = $stripeSubscription->items->data[0] ?? null;
                            $start = $firstItem->current_period_start ?? $stripeSubscription->current_period_start ?? null;
                            $end = $firstItem->current_period_end ?? $stripeSubscription->current_period_end ?? null;

                            if ($start) {
                                $subscription->current_period_start = \Cake\I18n\FrozenTime::createFromTimestamp((int)$start);
                                Log::debug('Checkout Webhook: Setting Start: ' . $subscription->current_period_start->format('Y-m-d H:i:s'), ['payments']);
                            }
                            if ($end) {
                                $subscription->current_period_end = \Cake\I18n\FrozenTime::createFromTimestamp((int)$end);
                                Log::debug('Checkout Webhook: Setting End: ' . $subscription->current_period_end->format('Y-m-d H:i:s'), ['payments']);
                            }

                            // Store whether the subscription is set to cancel at end of period
                            $subscription->cancel_at_period_end = !empty($stripeSubscription->cancel_at_period_end)
                                || (!empty($stripeSubscription->cancel_at));

                            Log::debug('Checkout Webhook: Dirty fields after dates: ' . json_encode($subscription->getDirty()), ['payments']);

                            if ($subscriptionsTable->save($subscription)) {
                                Log::info('Checkout Webhook: Subscription successfully updated for user ' . $user->id, ['payments']);
                                // Reload to check actual DB values
                                $checkSub = $subscriptionsTable->get($subscription->id);
                                Log::debug('Checkout Webhook: Verified DB Values - Start: ' . ($checkSub->current_period_start ? $checkSub->current_period_start->format('Y-m-d H:i:s') : 'NULL') . ', End: ' . ($checkSub->current_period_end ? $checkSub->current_period_end->format('Y-m-d H:i:s') : 'NULL'), ['payments']);
                            } else {
                                Log::error('Checkout Webhook: Failed to save subscription. Errors: ' . json_encode($subscription->getErrors()), ['payments']);
                            }

                            // Link device from upgrade token metadata
                            if (isset($session->metadata->device_uuid)) {
                                $deviceUuid = $session->metadata->device_uuid;
                                $devicesTable = $this->fetchTable('Devices');
                                $device = $devicesTable->find()->where(['device_uuid' => $deviceUuid])->first();

                                if (!$device) {
                                    $device = $devicesTable->newEmptyEntity();
                                    $device->id = \Cake\Utility\Text::uuid();
                                    $device->device_uuid = $deviceUuid;
                                }
                                $device->user_id = $user->id;
                                $devicesTable->save($device);


                                // Mark upgrade token as used
                                $tokensTable = $this->fetchTable('UpgradeTokens');
                                $token = $tokensTable->find()
                                    ->where(['device_uuid' => $deviceUuid, 'type' => 'upgrade', 'is_used' => false])
                                    ->first();

                                if ($token) {
                                    $token->is_used = true;
                                    $tokensTable->save($token);
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to retrieve subscription during checkout.session.completed: ' . $e->getMessage(), ['payments']);
                        }
                    }
                }

                Log::info('Stripe Checkout Completed for customer: ' . $session->customer, ['payments']);
                break;
            case 'customer.subscription.updated':
                $stripeSubscription = $event->data->object;

                $subscriptionsTable = $this->fetchTable('Subscriptions');
                $subscription = $subscriptionsTable->find()->where(['stripe_subscription_id' => $stripeSubscription->id])->first();

                if ($subscription) {
                    $subscription->status = $stripeSubscription->status === 'active' ? 'active' : 'canceled';

                    // In Stripe API 2025-03-31+, current_period_start/end moved to subscription items
                    $firstItem = $stripeSubscription->items->data[0] ?? null;
                    $start = $firstItem->current_period_start ?? $stripeSubscription->current_period_start ?? null;
                    $end = $firstItem->current_period_end ?? $stripeSubscription->current_period_end ?? null;

                    if ($start) {
                        $subscription->current_period_start = \Cake\I18n\FrozenTime::createFromTimestamp((int)$start);
                    }
                    if ($end) {
                        $subscription->current_period_end = \Cake\I18n\FrozenTime::createFromTimestamp((int)$end);
                    }

                    // Store whether the subscription is set to cancel at end of period
                    $subscription->cancel_at_period_end = !empty($stripeSubscription->cancel_at_period_end)
                        || (!empty($stripeSubscription->cancel_at));
                    if ($subscriptionsTable->save($subscription)) {
                        Log::info('Subscription Updated Webhook: Success for ' . $stripeSubscription->id, ['payments']);
                    } else {
                        Log::error('Subscription Updated Webhook: Save Failed. Errors: ' . json_encode($subscription->getErrors()), ['payments']);
                    }
                }

                Log::info('Stripe Subscription Updated: ' . $stripeSubscription->id, ['payments']);
                break;
            case 'invoice.payment_succeeded':
                $invoice = $event->data->object;

                if (isset($invoice->subscription)) {
                    $subscriptionsTable = $this->fetchTable('Subscriptions');
                    $subscription = $subscriptionsTable->find()->where(['stripe_subscription_id' => $invoice->subscription])->first();

                    if (!$subscription && isset($invoice->customer)) {
                        // If not found by subscription ID, try finding user by customer ID
                        $usersTable = $this->fetchTable('Users');
                        $user = $usersTable->find()->where(['stripe_customer_id' => $invoice->customer])->first();
                        if ($user) {
                            $subscription = $subscriptionsTable->find()->where(['user_id' => $user->id])->first();
                        }
                    }

                    if ($subscription) {
                        try {
                            $secretKey = Configure::read('Stripe.secret_key');
                            $stripe = new \Stripe\StripeClient($secretKey);
                            $stripeSubscription = $stripe->subscriptions->retrieve($invoice->subscription);

                            $subscription->stripe_subscription_id = $invoice->subscription; // Ensure it's set

                            // In Stripe API 2025-03-31+, current_period_start/end moved to subscription items
                            $firstItem = $stripeSubscription->items->data[0] ?? null;
                            $start = $firstItem->current_period_start ?? $stripeSubscription->current_period_start ?? null;
                            $end = $firstItem->current_period_end ?? $stripeSubscription->current_period_end ?? null;

                            if ($start) {
                                $subscription->current_period_start = \Cake\I18n\FrozenTime::createFromTimestamp((int)$start);
                            }
                            if ($end) {
                                $subscription->current_period_end = \Cake\I18n\FrozenTime::createFromTimestamp((int)$end);
                            }

                            // Store whether the subscription is set to cancel at end of period
                            $subscription->cancel_at_period_end = !empty($stripeSubscription->cancel_at_period_end)
                                || (!empty($stripeSubscription->cancel_at));
                            $subscription->status = 'active';
                            $subscription->plan = 'pro';
                            if ($subscriptionsTable->save($subscription)) {
                                Log::info('Invoice Paid Webhook: Subscription dates updated: ' . $invoice->subscription, ['payments']);
                            } else {
                                Log::error('Invoice Paid Webhook: Save Failed. Errors: ' . json_encode($subscription->getErrors()), ['payments']);
                            }
                        } catch (\Exception $e) {
                            Log::error('Failed to update subscription dates on invoice.payment_succeeded: ' . $e->getMessage(), ['payments']);
                        }
                    }
                }
                break;
            case 'customer.subscription.deleted':
                $stripeSubscription = $event->data->object;

                $subscriptionsTable = $this->fetchTable('Subscriptions');
                $subscription = $subscriptionsTable->find()->where(['stripe_subscription_id' => $stripeSubscription->id])->first();

                if ($subscription) {
                    $subscription->status = 'canceled';
                    $subscriptionsTable->save($subscription);
                }

                Log::info('Stripe Subscription Deleted: ' . $stripeSubscription->id, ['payments']);
                break;
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode(['received' => true]));
    }
}
