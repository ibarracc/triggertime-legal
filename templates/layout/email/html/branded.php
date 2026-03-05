<?php
/**
 * Branded email layout for TriggerTime.
 *
 * @var \Cake\View\View $this
 * @var string $content
 */
?>
<!DOCTYPE html>
<html lang="<?= $this->get('locale', 'en') ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TriggerTime</title>
</head>
<body style="margin:0;padding:0;background-color:#f4f4f7;font-family:Inter,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f7;">
        <tr>
            <td align="center" style="padding:24px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
                    <!-- Header -->
                    <tr>
                        <td style="background-color:#0A0A0F;padding:24px 32px;border-radius:12px 12px 0 0;text-align:center;">
                            <span style="font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:24px;font-weight:700;color:#C1FF72;letter-spacing:0.5px;">TriggerTime</span>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="background-color:#ffffff;padding:40px 32px;">
                            <?= $content ?>
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f8fa;padding:24px 32px;border-radius:0 0 12px 12px;text-align:center;border-top:1px solid #e8e8eb;">
                            <p style="margin:0 0 8px;font-size:13px;color:#8A8A9A;">
                                &copy; <?= date('Y') ?> TriggerTime. <?= __('All rights reserved.') ?>
                            </p>
                            <p style="margin:0;font-size:12px;color:#a0a0ad;">
                                <a href="https://triggertime.es/privacy" style="color:#8A8A9A;text-decoration:underline;"><?= __('Privacy Policy') ?></a>
                                &nbsp;&middot;&nbsp;
                                <a href="https://triggertime.es/terms" style="color:#8A8A9A;text-decoration:underline;"><?= __('Terms of Service') ?></a>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
