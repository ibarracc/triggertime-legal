<?php
/**
 * Password reset email template.
 *
 * @var \Cake\View\View $this
 * @var string $resetUrl
 * @var string $firstName
 */
$name = !empty($firstName) ? $firstName : __('there');
?>
<h1 style="margin:0 0 16px;font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#1a1a2e;">
    <?= __('Reset Your Password') ?>
</h1>
<p style="margin:0 0 16px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Hi {0}, we received a request to reset your password.', h($name)) ?>
</p>
<p style="margin:0 0 24px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Click the button below to set a new password:') ?>
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 auto 24px;">
    <tr>
        <td style="background-color:#C1FF72;border-radius:12px;">
            <a href="<?= h($resetUrl) ?>" style="display:inline-block;padding:14px 32px;font-family:Inter,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;color:#0A0A0F;text-decoration:none;">
                <?= __('Reset Password') ?>
            </a>
        </td>
    </tr>
</table>
<p style="margin:0 0 8px;font-size:14px;line-height:1.5;color:#8A8A9A;">
    <?= __('This link expires in 24 hours. If you didn\'t request a password reset, you can safely ignore this email.') ?>
</p>
<p style="margin:0;font-size:13px;color:#a0a0ad;word-break:break-all;">
    <?= __('If the button doesn\'t work, copy and paste this URL into your browser:') ?><br>
    <a href="<?= h($resetUrl) ?>" style="color:#C1693C;"><?= h($resetUrl) ?></a>
</p>
