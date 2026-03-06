<?php
/**
 * Branded email layout for TriggerTime.
 *
 * @var \Cake\View\View $this
 */
$logoUrl = 'https://triggertime.es/triggertime.png';
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
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,0.10);">

                    <!-- Dark header with logo -->
                    <tr>
                        <td style="background-color:#0A0A0F;padding:28px 40px;">
                            <a href="https://triggertime.es" style="text-decoration:none;display:inline-flex;align-items:center;gap:12px;">
                                <img src="<?= h($logoUrl) ?>" alt="TriggerTime" width="44" height="44" style="border-radius:12px;display:inline-block;vertical-align:middle;box-shadow:0 4px 12px rgba(0,0,0,0.4);">
                                <span style="font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:20px;font-weight:800;color:#F0F0F5;letter-spacing:-0.3px;vertical-align:middle;">Trigger<span style="color:#C1FF72;">Time</span></span>
                            </a>
                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="background-color:#0A0A0F;padding:0 40px;">
                            <div style="height:1px;background-color:#1C1C26;"></div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td style="background-color:#ffffff;padding:40px 40px 32px;">
                            <?= $this->fetch('content') ?>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8f8fa;padding:20px 40px;text-align:center;border-top:1px solid #e8e8eb;">
                            <p style="margin:0 0 6px;font-size:12px;color:#8A8A9A;">
                                &copy; <?= date('Y') ?> TriggerTime. <?= __('All rights reserved.') ?>
                            </p>
                            <p style="margin:0;font-size:12px;color:#8A8A9A;">
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
