<?php
declare(strict_types=1);

namespace App\Mailer;

use App\Model\Entity\User;
use Cake\I18n\I18n;
use Cake\Mailer\Mailer;
use Cake\Routing\Router;

class UserMailer extends Mailer
{
    /**
     * Welcome + email activation for normal signups.
     *
     * @param \App\Model\Entity\User $user The user entity.
     * @param string $activationUrl The activation URL.
     * @return void
     */
    public function welcomeActivation(User $user, string $activationUrl): void
    {
        $previousLocale = I18n::getLocale();
        I18n::setLocale($user->language ?? 'en');

        $this
            ->setTo($user->email)
            ->setSubject(__('TriggerTime - {0}', __('Activate Your Account')))
            ->setEmailFormat('html')
            ->setViewVars([
                'activationUrl' => $activationUrl,
                'firstName' => $user->first_name,
                'locale' => $user->language ?? 'en',
            ])
            ->viewBuilder()
            ->setTemplate('welcome_activation')
            ->setLayout('branded');

        I18n::setLocale($previousLocale);
    }

    /**
     * Welcome email for SSO users (no activation needed).
     *
     * @param \App\Model\Entity\User $user The user entity.
     * @param string $provider The SSO provider name.
     * @return void
     */
    public function welcomeSso(User $user, string $provider): void
    {
        $previousLocale = I18n::getLocale();
        I18n::setLocale($user->language ?? 'en');

        $baseUrl = env('FRONTEND_URL', Router::fullBaseUrl());

        $this
            ->setTo($user->email)
            ->setSubject(__('TriggerTime - {0}', __('Welcome!')))
            ->setEmailFormat('html')
            ->setViewVars([
                'dashboardUrl' => $baseUrl . '/dashboard',
                'firstName' => $user->first_name,
                'provider' => $provider,
                'locale' => $user->language ?? 'en',
            ])
            ->viewBuilder()
            ->setTemplate('welcome_sso')
            ->setLayout('branded');

        I18n::setLocale($previousLocale);
    }

    /**
     * Password reset email.
     *
     * @param \App\Model\Entity\User $user The user entity.
     * @param string $resetUrl The password reset URL.
     * @return void
     */
    public function passwordReset(User $user, string $resetUrl): void
    {
        $previousLocale = I18n::getLocale();
        I18n::setLocale($user->language ?? 'en');

        $this
            ->setTo($user->email)
            ->setSubject(__('TriggerTime - {0}', __('Reset Your Password')))
            ->setEmailFormat('html')
            ->setViewVars([
                'resetUrl' => $resetUrl,
                'firstName' => $user->first_name,
                'locale' => $user->language ?? 'en',
            ])
            ->viewBuilder()
            ->setTemplate('password_reset')
            ->setLayout('branded');

        I18n::setLocale($previousLocale);
    }
}
