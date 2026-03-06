<?php
/**
 * Welcome + Email Activation template.
 *
 * @var \Cake\View\View $this
 * @var string $activationUrl
 * @var string $firstName
 */
$name = !empty($firstName) ? $firstName : __('there');
?>
<h1 style="margin:0 0 16px;font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:26px;font-weight:700;color:#0A0A0F;letter-spacing:-0.3px;">
    <?= __('Welcome to TriggerTime!') ?>
</h1>
<p style="margin:0 0 12px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Hi {0}, thanks for creating your account.', h($name)) ?>
</p>
<p style="margin:0 0 28px;font-size:16px;line-height:1.6;color:#4a4a5a;">
    <?= __('Please verify your email address by clicking the button below to activate your account:') ?>
</p>
<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 28px;">
    <tr>
        <td style="background-color:#C1FF72;border-radius:12px;">
            <a href="<?= h($activationUrl) ?>" style="display:inline-block;padding:14px 32px;font-family:Inter,'Helvetica Neue',Arial,sans-serif;font-size:16px;font-weight:600;color:#0A0A0F;text-decoration:none;">
                <?= __('Activate Your Account') ?>
            </a>
        </td>
    </tr>
</table>
<p style="margin:0 0 8px;font-size:13px;line-height:1.5;color:#8A8A9A;">
    <?= __('This link expires in 7 days. If you did not create an account, you can safely ignore this email.') ?>
</p>
<p style="margin:0;font-size:12px;color:#8A8A9A;word-break:break-all;">
    <?= __('If the button doesn\'t work, copy and paste this URL into your browser:') ?><br>
    <a href="<?= h($activationUrl) ?>" style="color:#C1693C;"><?= h($activationUrl) ?></a>
</p>
