<?php
/**
 * Welcome email for SSO users (no activation needed).
 *
 * @var \Cake\View\View $this
 * @var string $dashboardUrl
 * @var string $firstName
 * @var string $provider
 */
$name = !empty($firstName) ? $firstName : __('there');
?>
<h1 style="margin:0 0 16px;font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#1a1a2e;">
    <?= __('Welcome to TriggerTime!') ?>
</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Hi {0}, your account has been created using {1}.', h($name), h(ucfirst($provider))) ?>
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('You\'re all set! Start exploring your dashboard to manage your devices and subscriptions.') ?>
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td style="background-color:#C1FF72;border-radius:12px;">
            <a href="<?= h($dashboardUrl) ?>" style="display:inline-block;padding:14px 32px;font-family:Inter,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;color:#0A0A0F;text-decoration:none;">
                <?= __('Go to Dashboard') ?>
            </a>
        </td>
    </tr>
</table>
