<?php

declare(strict_types=1);

namespace App\Controller\Api\V1\Web;

use App\Controller\AppController;
use App\Service\JwtService;
use Cake\Core\Configure;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\UnauthorizedException;
use Cake\Utility\Text;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;

class AuthController extends AppController
{
    private $Authentication;

    public function initialize(): void
    {
        parent::initialize();
        $this->Authentication = $this->fetchTable('Users');
    }

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
            'role' => $user->role
        ]);

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user
            ]));
    }

    public function register()
    {
        $this->request->allowMethod(['post']);

        $email = $this->request->getData('email');
        $password = $this->request->getData('password');
        $firstName = $this->request->getData('first_name');
        $lastName = $this->request->getData('last_name');
        $language = $this->request->getData('language', 'en');

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
        $sub->max_devices_allowed = Configure::read("Subscriptions.{$sub->plan}.max_devices_allowed"); // Unlimited devices
        $sub->current_period_start = FrozenTime::now();
        $subs->save($sub);

        // Also check if they have any B2B licenses matching their email, and auto-link user_id
        $licenses = $this->fetchTable('ActivationLicenses');
        $licenses->updateAll(
            ['user_id' => $user->id],
            ['email' => $user->email, 'user_id IS' => null]
        );

        $jwt = new JwtService();
        $token = $jwt->generateToken([
            'sub' => $user->id,
            'email' => $user->email,
            'role' => $user->role
        ]);

        $user->subscriptions = [$sub];

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'token' => $token,
                'user' => $user
            ]));
    }

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
            ->contain(['Subscriptions', 'Devices'])
            ->first();

        // Check assigned licenses from club
        $licenses = $this->fetchTable('ActivationLicenses')->find()
            ->where(['user_id' => $userId])
            ->all();

        // Check assigned instances (for club admins)
        $instances = $this->fetchTable('Instances')->find()
            ->where(['club_admin_id' => $userId])
            ->all();

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'user' => $user,
                'b2b_licenses' => $licenses,
                'instances' => $instances
            ]));
    }

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
            $reset->expires_at = FrozenTime::now()->addHours(24);
            $Passwords->save($reset);

            // Construct frontend reset URL
            $frontendUrl = env('FRONTEND_URL', \Cake\Routing\Router::fullBaseUrl());
            $resetLink = $frontendUrl . '/reset-password/' . $token;

            // Send Email
            try {
                $mailer = new Mailer('default');
                $mailer->setTo($user->email)
                    ->setSubject('TriggerTime - Password Reset')
                    ->deliver("You requested a password reset. Please click on the link below to set a new password:\n\n" . $resetLink);
            } catch (\Exception $e) {
                // Log and continue, or fail depending on strictness
                \Cake\Log\Log::error('Mail sending failed: ' . $e->getMessage());
            }
        }

        return $this->response->withType('application/json')
            ->withStringBody(json_encode([
                'success' => true,
                'message' => 'If your email is registered, you will receive a password reset link shortly.'
            ]));
    }

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
            'expires_at >=' => FrozenTime::now()
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
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Password has been successfully reset'
                ]));
        }

        throw new BadRequestException('Failed to reset password');
    }

    public function updateProfile()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');

        $user = $this->Authentication->get($payload['sub']);
        $user->first_name = $this->request->getData('first_name');
        $user->last_name = $this->request->getData('last_name');
        $user->language = $this->request->getData('language', $user->language);

        if ($this->Authentication->save($user)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'user' => $user
                ]));
        }

        throw new BadRequestException('Failed to update profile');
    }

    public function updatePassword()
    {
        $this->request->allowMethod(['post']);
        $payload = $this->request->getAttribute('jwt_payload');

        $currentPassword = $this->request->getData('current');
        $newPassword = $this->request->getData('new');

        if (!$currentPassword || !$newPassword) {
            throw new BadRequestException('Current and new password are required');
        }

        $user = $this->Authentication->get($payload['sub']);

        if (!password_verify($currentPassword, $user->password_hash)) {
            throw new BadRequestException('Incorrect current password');
        }

        $user->password_hash = $newPassword;

        if ($this->Authentication->save($user)) {
            return $this->response->withType('application/json')
                ->withStringBody(json_encode([
                    'success' => true,
                    'message' => 'Password updated successfully'
                ]));
        }

        throw new BadRequestException('Failed to update password');
    }
}
