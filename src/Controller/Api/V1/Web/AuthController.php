<?php
declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use App\Mailer\UserMailer;
use App\Model\Table\UsersTable;
use App\Service\EmailVerificationService;
use App\Service\JwtService;
use App\Service\SocialAuthService;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\I18n\DateTime;
use Cake\Log\Log;
use Cake\Routing\Router;
use Cake\Utility\Text;
use Throwable;

class AuthController extends AppController
{
    /**
     * @var \App\Model\Table\UsersTable
     */
    private UsersTable $Authentication;

    /**
     * @inheritDoc
     */
    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication = $this->fetchTable('Users');
    }

    /**
     * Authenticate a user and return a JWT token.
     */
    public function login()
    {
        $this->request->allowMethod(['post']);

        $email = $this->request->getData('email');
        $password = $this->request->getData('password');

        if (!$email || !$password) {
            throw new BadRequestException('Email and password are required');
        }

        $user = $this->Authentication->find()
            ->where(['email' => $email])
            ->contain(['Subscriptions', 'Devices'])
            ->first();

        if (!$user || !password_verify($password, $user->password_hash)) {
            throw new UnauthorizedException('Invalid email or password');
        }

        $jwt = new JwtService();
        $token = $jwt->generateToken([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user,
            ]));
    }

    /**
     * Register a new user account and return a JWT token.
     */
    public function register()
    {
        $this->request->allowMethod(['post']);

        $email = $this->request->getData('email');
        $password = $this->request->getData('password');
        $firstName = $this->request->getData('first_name');
        $lastName = $this->request->getData('last_name');
        $language = $this->request->getData('language', 'en');
        $marketingOptin = (bool)$this->request->getData('marketing_optin', false);

        if (!$email || !$password) {
            throw new BadRequestException('Email and password are required');
        }

        $existing = $this->Authentication->find()->where(['email' => $email])->first();
        if ($existing) {
            throw new BadRequestException('Email already in use');
        }

        $user = $this->Authentication->newEmptyEntity();

        // Generate UUID explicitly in code for cross-DB compatibility if gen_random_uuid fails missing extensions
        $user->id = Text::uuid();
        $user->email = $email;
        $user->password_hash = $password; // Entity setter hashes this
        $user->role = 'user';
        $user->first_name = $firstName;
        $user->last_name = $lastName;
        $user->language = $language;
        $user->marketing_optin = $marketingOptin;

        if (!$this->Authentication->save($user)) {
            throw new BadRequestException('Could not create user account');
        }

        // Auto-create free subscription
        $subs = $this->fetchTable('Subscriptions');
        $sub = $subs->newEmptyEntity();
        $sub->id = Text::uuid();
        $sub->user_id = $user->id;
        $sub->plan = 'free';
        $sub->status = 'active';
        $sub->max_devices_allowed = Configure::read("Subscriptions.{$sub->plan}.max_devices_allowed"); // Unlimited
        $sub->current_period_start = DateTime::now();
        $subs->save($sub);

        // Also check if they have any B2B licenses matching their email, and auto-link user_id
        $licenses = $this->fetchTable('ActivationLicenses');
        $licenses->updateAll(
            ['user_id' => $user->id],
            ['email' => $user->email, 'user_id IS' => null],
        );

        // Send welcome + activation email
        try {
            $verificationService = new EmailVerificationService();
            $activationUrl = $verificationService->generateSignedUrl($user->id);
            $mailer = new UserMailer();
            $mailer->welcomeActivation($user, $activationUrl);
            $mailer->deliver();
        } catch (Throwable $e) {
            Log::error('Welcome email failed: ' . $e->getMessage());
        }

        $jwt = new JwtService();
        $token = $jwt->generateToken([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        $user->subscriptions = [$sub];

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user,
            ]));
    }

    /**
     * Verify a user's email via HMAC-signed URL.
     *
     * GET /api/v1/web/auth/verify-email?uid=...&exp=...&sig=...
     */
    public function verifyEmail()
    {
        $this->request->allowMethod(['get']);

        $uid = $this->request->getQuery('uid');
        $exp = $this->request->getQuery('exp');
        $sig = $this->request->getQuery('sig');

        if (!$uid || !$exp || !$sig) {
            throw new BadRequestException('Missing verification parameters');
        }

        $service = new EmailVerificationService();
        $userId = $service->verifySignedUrl((string)$uid, (string)$exp, (string)$sig);

        if (!$userId) {
            throw new BadRequestException('Invalid or expired verification link');
        }

        $user = $this->Authentication->find()->where(['id' => $userId])->first();
        if (!$user) {
            throw new BadRequestException('User not found');
        }

        if ($user->email_verified_at === null) {
            $user->email_verified_at = DateTime::now();
            $this->Authentication->save($user);
        }

        $frontendUrl = env('FRONTEND_URL', Router::fullBaseUrl());

        return $this->redirect($frontendUrl . '/dashboard?verified=1');
    }

    /**
     * Resend the email verification link.
     */
    public function resendVerification()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');
        if (!$payload) {
            throw new UnauthorizedException('Missing or invalid token payload');
        }

        $user = $this->Authentication->get($payload['sub']);

        if ($user->email_verified_at !== null) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'message' => 'Email already verified',
                ]));
        }

        try {
            $verificationService = new EmailVerificationService();
            $activationUrl = $verificationService->generateSignedUrl($user->id);
            $mailer = new UserMailer();
            $mailer->welcomeActivation($user, $activationUrl);
            $mailer->deliver();
        } catch (Throwable $e) {
            Log::error('Verification email failed: ' . $e->getMessage());
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Verification email sent',
            ]));
    }

    /**
     * Return the authenticated user's profile data.
     */
    public function me()
    {
        $this->request->allowMethod(['get']);

        $payload = $this->request->getAttribute('jwt_payload');
        if (!$payload) {
            throw new UnauthorizedException('Missing or invalid token payload');
        }

        $userId = $payload['sub'];

        $user = $this->Authentication->find()
            ->where(['id' => $userId])
            ->contain(['Subscriptions', 'Devices', 'SocialAccounts'])
            ->first();

        // Check assigned licenses from club
        $licenses = $this->fetchTable('ActivationLicenses')->find()
            ->where(['user_id' => $userId])
            ->all();

        // Check assigned instances (for club admins)
        $instances = $this->fetchTable('Instances')->find()
            ->where(['club_admin_id' => $userId])
            ->all();

        // Determine if user can delete account (no active paid subscription)
        $canDeleteAccount = true;
        if ($user->subscriptions) {
            foreach ($user->subscriptions as $sub) {
                if ($sub->status === 'active' && $sub->plan !== 'free') {
                    if (!$sub->cancel_at_period_end || $sub->current_period_end > DateTime::now()) {
                        $canDeleteAccount = false;
                        break;
                    }
                }
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'user' => $user,
                'has_password' => !empty($user->password_hash),
                'can_delete_account' => $canDeleteAccount,
                'b2b_licenses' => $licenses,
                'instances' => $instances,
            ]));
    }

    /**
     * Send a password reset email to the given address.
     */
    public function forgotPassword()
    {
        $this->request->allowMethod(['post']);
        $email = $this->request->getData('email');
        if (!$email) {
            throw new BadRequestException('Email is required');
        }

        $user = $this->Authentication->find()->where(['email' => $email])->first();
        if ($user && empty($user->deleted_at)) {
            $Passwords = $this->fetchTable('PasswordResets');

            // Generate a secure token
            $token = bin2hex(random_bytes(32));

            $reset = $Passwords->newEmptyEntity();
            $reset->id = Text::uuid();
            $reset->user_id = $user->id;
            $reset->token = $token;
            $reset->expires_at = DateTime::now()->addHours(24);
            $Passwords->save($reset);

            // Construct frontend reset URL
            $frontendUrl = env('FRONTEND_URL', Router::fullBaseUrl());
            $resetLink = $frontendUrl . '/reset-password/' . $token;

            // Send Email
            try {
                $userMailer = new UserMailer();
                $userMailer->passwordReset($user, $resetLink);
                $userMailer->deliver();
            } catch (Throwable $e) {
                Log::error('Password reset email failed: ' . $e->getMessage());
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'If your email is registered, you will receive a password reset link shortly.',
            ]));
    }

    /**
     * Reset a user's password using a valid reset token.
     */
    public function resetPassword()
    {
        $this->request->allowMethod(['post']);
        $token = $this->request->getData('token');
        $password = $this->request->getData('password');

        if (!$token || !$password) {
            throw new BadRequestException('Token and new password are required');
        }

        $Passwords = $this->fetchTable('PasswordResets');
        $reset = $Passwords->find()->where([
            'token' => $token,
            'used' => false,
            'expires_at >=' => DateTime::now(),
        ])->first();

        if (!$reset) {
            throw new BadRequestException('Invalid or expired reset token');
        }

        $user = $this->Authentication->get($reset->user_id);
        $user->password_hash = $password; // Using entity setter

        if ($this->Authentication->save($user)) {
            $reset->used = true;
            $Passwords->save($reset);

            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'message' => 'Password has been successfully reset',
                ]));
        }

        throw new BadRequestException('Failed to reset password');
    }

    /**
     * Authenticate or register a user via social provider (Google/Apple).
     */
    public function socialLogin()
    {
        $this->request->allowMethod(['post']);

        $provider = $this->request->getData('provider');
        $idToken = $this->request->getData('id_token');

        if (!$provider || !$idToken) {
            throw new BadRequestException('Provider and id_token are required');
        }

        $socialAuth = new SocialAuthService();
        $claims = $socialAuth->verifyIdToken($provider, $idToken);

        if (!$claims) {
            throw new UnauthorizedException('Invalid or expired social token');
        }

        $socialAccounts = $this->fetchTable('SocialAccounts');

        // 1. Check if social account already linked
        $existing = $socialAccounts->find()
            ->where([
                'provider' => $provider,
                'provider_uid' => $claims['sub'],
            ])
            ->first();

        if ($existing) {
            // Existing linked user — log them in
            $user = $this->Authentication->find()
                ->where(['id' => $existing->user_id])
                ->contain(['Subscriptions', 'Devices'])
                ->first();
        } else {
            // 2. Check if email matches an existing user
            $user = $this->Authentication->find()
                ->where(['email' => $claims['email']])
                ->contain(['Subscriptions', 'Devices'])
                ->first();

            if ($user) {
                // Link social account to existing user
                $social = $socialAccounts->newEmptyEntity();
                $social->id = Text::uuid();
                $social->user_id = $user->id;
                $social->provider = $provider;
                $social->provider_uid = $claims['sub'];
                $socialAccounts->save($social);
            } else {
                // 3. Create new user
                $firstName = $claims['first_name'] ?? $this->request->getData('first_name');
                $lastName = $claims['last_name'] ?? $this->request->getData('last_name');

                $user = $this->Authentication->newEmptyEntity();
                $user->id = Text::uuid();
                $user->email = $claims['email'];
                $user->role = 'user';
                $user->first_name = $firstName;
                $user->last_name = $lastName;
                $user->language = $this->request->getData('language', 'en');
                $user->marketing_optin = (bool)$this->request->getData('marketing_optin', false);
                $user->email_verified_at = DateTime::now();

                if (!$this->Authentication->save($user)) {
                    throw new BadRequestException('Could not create user account');
                }

                // Link social account
                $social = $socialAccounts->newEmptyEntity();
                $social->id = Text::uuid();
                $social->user_id = $user->id;
                $social->provider = $provider;
                $social->provider_uid = $claims['sub'];
                $socialAccounts->save($social);

                // Auto-create free subscription
                $subs = $this->fetchTable('Subscriptions');
                $sub = $subs->newEmptyEntity();
                $sub->id = Text::uuid();
                $sub->user_id = $user->id;
                $sub->plan = 'free';
                $sub->status = 'active';
                $sub->max_devices_allowed = Configure::read("Subscriptions.{$sub->plan}.max_devices_allowed");
                $sub->current_period_start = DateTime::now();
                $subs->save($sub);

                // Auto-link B2B licenses
                $licenses = $this->fetchTable('ActivationLicenses');
                $licenses->updateAll(
                    ['user_id' => $user->id],
                    ['email' => $user->email, 'user_id IS' => null],
                );

                // Send welcome email for SSO users
                try {
                    $mailer = new UserMailer();
                    $mailer->welcomeSso($user, $provider);
                    $mailer->deliver();
                } catch (Throwable $e) {
                    Log::error('SSO welcome email failed: ' . $e->getMessage());
                }

                $user->subscriptions = [$sub];
            }
        }

        $jwt = new JwtService();
        $token = $jwt->generateToken([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
        ]);

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user,
            ]));
    }

    /**
     * Update the authenticated user's profile information.
     */
    public function updateProfile()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');

        $user = $this->Authentication->get($payload['sub']);
        $user->first_name = $this->request->getData('first_name');
        $user->last_name = $this->request->getData('last_name');
        $user->language = $this->request->getData('language', $user->language);
        if ($this->request->getData('marketing_optin') !== null) {
            $user->marketing_optin = (bool)$this->request->getData('marketing_optin');
        }

        if ($this->Authentication->save($user)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'user' => $user,
                ]));
        }

        throw new BadRequestException('Failed to update profile');
    }

    /**
     * Change the authenticated user's password.
     *
     * SSO-only users (null password_hash) can set a password without providing a current one.
     */
    public function updatePassword()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');

        $currentPassword = $this->request->getData('current');
        $newPassword = $this->request->getData('new');

        if (!$newPassword) {
            throw new BadRequestException('New password is required');
        }

        $user = $this->Authentication->get($payload['sub']);

        // If user has a password, require current password verification
        if (!empty($user->password_hash)) {
            if (!$currentPassword) {
                throw new BadRequestException('Current password is required');
            }
            if (!password_verify($currentPassword, $user->password_hash)) {
                throw new BadRequestException('Incorrect current password');
            }
        }

        $user->password_hash = $newPassword;

        if ($this->Authentication->save($user)) {
            return $this->response->withType('application/json')
                ->withStringBody((string)json_encode([
                    'success' => true,
                    'message' => 'Password updated successfully',
                ]));
        }

        throw new BadRequestException('Failed to update password');
    }

    /**
     * Link a social provider to the authenticated user's account.
     */
    public function connectSocial()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');

        $provider = $this->request->getData('provider');
        $idToken = $this->request->getData('id_token');

        if (!$provider || !$idToken) {
            throw new BadRequestException('Provider and id_token are required');
        }

        $socialAuth = new SocialAuthService();
        $claims = $socialAuth->verifyIdToken($provider, $idToken);

        if (!$claims) {
            throw new UnauthorizedException('Invalid or expired social token');
        }

        $user = $this->Authentication->get($payload['sub']);
        $socialAccounts = $this->fetchTable('SocialAccounts');

        // Check if this social account is already linked to another user
        $existing = $socialAccounts->find()
            ->where([
                'provider' => $provider,
                'provider_uid' => $claims['sub'],
            ])
            ->first();

        if ($existing) {
            if ($existing->user_id === $user->id) {
                throw new BadRequestException('This account is already connected');
            }
            throw new BadRequestException('This social account is linked to a different user');
        }

        // Check if user already has this provider linked
        $alreadyLinked = $socialAccounts->find()
            ->where([
                'user_id' => $user->id,
                'provider' => $provider,
            ])
            ->first();

        if ($alreadyLinked) {
            throw new BadRequestException('You already have a ' . ucfirst($provider) . ' account connected');
        }

        $social = $socialAccounts->newEmptyEntity();
        $social->id = Text::uuid();
        $social->user_id = $user->id;
        $social->provider = $provider;
        $social->provider_uid = $claims['sub'];

        if (!$socialAccounts->save($social)) {
            throw new BadRequestException('Failed to connect social account');
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Social account connected',
            ]));
    }

    /**
     * Disconnect a social provider from the authenticated user's account.
     */
    public function disconnectSocial()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');

        $provider = $this->request->getData('provider');
        if (!$provider) {
            throw new BadRequestException('Provider is required');
        }

        $user = $this->Authentication->get($payload['sub']);

        $socialAccounts = $this->fetchTable('SocialAccounts');
        $account = $socialAccounts->find()
            ->where([
                'user_id' => $user->id,
                'provider' => $provider,
            ])
            ->first();

        if (!$account) {
            throw new BadRequestException('Social account not found');
        }

        // Prevent disconnecting if user has no password and this is their only social account
        if (empty($user->password_hash)) {
            $remaining = $socialAccounts->find()
                ->where(['user_id' => $user->id])
                ->count();
            if ($remaining <= 1) {
                throw new BadRequestException('Cannot disconnect your only login method. Set a password first.');
            }
        }

        if (!$socialAccounts->delete($account)) {
            throw new BadRequestException('Failed to disconnect social account');
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Social account disconnected',
            ]));
    }

    /**
     * Soft-delete the authenticated user's account.
     *
     * Requires email confirmation and no active paid subscription.
     */
    public function deleteAccount()
    {
        $this->request->allowMethod(['delete']);
        $payload = $this->request->getAttribute('jwt_payload');

        $email = $this->request->getData('email');
        if (!$email) {
            throw new BadRequestException('Email confirmation is required');
        }

        $user = $this->Authentication->find()
            ->where(['id' => $payload['sub']])
            ->contain(['Subscriptions'])
            ->first();

        if (!$user) {
            throw new UnauthorizedException('User not found');
        }

        // Verify email matches
        if (strtolower($email) !== strtolower($user->email)) {
            throw new BadRequestException('Email does not match your account');
        }

        // Check for active paid subscription
        if ($user->subscriptions) {
            foreach ($user->subscriptions as $sub) {
                if ($sub->status === 'active' && $sub->plan !== 'free') {
                    if (!$sub->cancel_at_period_end || $sub->current_period_end > DateTime::now()) {
                        throw new BadRequestException(
                            'Cannot delete account with an active paid subscription. '
                            . 'Please cancel your subscription and wait for it to expire.',
                        );
                    }
                }
            }
        }

        // Soft-delete user
        if (!$this->Authentication->delete($user)) {
            throw new BadRequestException('Failed to delete account');
        }

        return $this->response->withType('application/json')
            ->withStringBody((string)json_encode([
                'success' => true,
                'message' => 'Your account has been deactivated and will be permanently deleted in 30 days.',
            ]));
    }
}
