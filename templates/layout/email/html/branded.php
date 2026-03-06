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
<body style="margin:0;padding:0;background-color:#0A0A0F;font-family:Inter,'Helvetica Neue',Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0A0A0F;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:0 0 24px;">
                            <a href="https://triggertime.es" style="text-decoration:none;display:inline-flex;align-items:center;gap:12px;">
                                <img src="<?= h($logoUrl) ?>" alt="TriggerTime" width="48" height="48" style="border-radius:12px;display:inline-block;vertical-align:middle;box-shadow:0 4px 12px rgba(0,0,0,0.3);">
                                <span style="font-family:Outfit,'Helvetica Neue',Arial,sans-serif;font-size:22px;font-weight:800;color:#F0F0F5;letter-spacing:-0.3px;vertical-align:middle;">Trigger<span style="color:#C1FF72;">Time</span></span>
                            </a>
                        </td>
                    </tr>

                    <!-- Card -->
                    <tr>
                        <td style="background-color:#13131A;border-radius:16px;border:1px solid #1C1C26;overflow:hidden;">

                            <!-- Content -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="padding:40px 40px 32px;">
                                        <?= $this->fetch('content') ?>
                                    </td>
                                </tr>

                                <!-- Footer divider -->
                                <tr>
                                    <td style="padding:0 40px;">
                                        <div style="height:1px;background-color:#1C1C26;"></div>
                                    </td>
                                </tr>

                                <!-- Footer -->
                                <tr>
                                    <td style="padding:20px 40px;text-align:center;">
                                        <p style="margin:0 0 8px;font-size:12px;color:#8A8A9A;">
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
            </td>
        </tr>
    </table>
</body>
</html>
