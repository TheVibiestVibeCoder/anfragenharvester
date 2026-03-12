<?php
// ==========================================
// DAILY EMAIL SENDER FOR MAILING LIST
// ==========================================
// This script should be run via cron at 20:00 daily
// Example cron: 0 20 * * * /usr/bin/php /path/to/send-daily-emails.php

require_once __DIR__ . '/MailingListDB.php';
require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/party.php';
require_once __DIR__ . '/app/inquiry_helpers.php';
require_once __DIR__ . '/app/parliament_api.php';

// Error handling
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/email-sender-errors.log');

// ==========================================
// HELPER FUNCTIONS
// ==========================================

function getNewEntries() {
    $partyColors = app_party_colors();
    $partyNames = app_party_names();

    // Fetch data from Parliament API
    $gpCodes = ['XXVIII', 'XXVII', 'XXVI', 'XXV', 'BR'];
    $allRows = app_fetch_parliament_rows($gpCodes, ['J', 'JPR'], 30);

    if (empty($allRows)) {
        error_log('Failed to fetch data rows from Parliament API');
        return [];
    }

    $newEntries = [];
    $cutoffDate = new DateTime('24 hours ago');

    foreach ($allRows as $row) {
        $title = trim((string) app_get_row_value($row, 6, 'TITEL'));
        $dateStr = trim((string) app_get_row_value($row, 4, 'DATUM'));
        $partyJson = app_get_row_value($row, 21, 'PARTIE');
        $rowLink = app_get_row_value($row, 14, 'LINK');
        $inquiryNumber = trim((string) app_get_row_value($row, 7, 'NPARL'));

        if ($dateStr === '') {
            continue;
        }

        $entryDate = DateTime::createFromFormat('d.m.Y', $dateStr);
        if (!$entryDate) {
            try {
                $entryDate = new DateTime($dateStr);
            } catch (Exception $e) {
                continue;
            }
        }

        if ($entryDate < $cutoffDate) {
            continue;
        }

        $partyCode = app_get_party_code($partyJson);
        $partyName = isset($partyNames[$partyCode]) ? $partyNames[$partyCode] : $partyNames['OTHER'];
        $partyColor = isset($partyColors[$partyCode]) ? $partyColors[$partyCode] : $partyColors['OTHER'];

        $link = app_build_inquiry_link($rowLink);
        $title = $title !== '' ? $title : ('Anfrage ' . ($inquiryNumber !== '' ? $inquiryNumber : '(ohne Titel)'));

        $newEntries[] = [
            'date' => $entryDate->format('d.m.Y'),
            'date_obj' => $entryDate,
            'title' => $title,
            'party' => $partyCode,
            'party_name' => $partyName,
            'party_color' => $partyColor,
            'link' => $link,
            'nparl' => $inquiryNumber
        ];
    }

    usort($newEntries, function($a, $b) {
        return $b['date_obj'] <=> $a['date_obj'];
    });

    foreach ($newEntries as &$entry) {
        unset($entry['date_obj']);
    }
    unset($entry);

    return $newEntries;
}

function generateEmailHTML($entries) {
    $entryCount = count($entries);
    $date = date('d.m.Y');

    ob_start();
    ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parlaments-Anfragen Tracker</title>
</head>
<body style="margin: 0; padding: 0; font-family: 'Inter', Helvetica, Arial, sans-serif; background-color: #000000; color: #ffffff;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #000000; width: 100%;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                <table width="600" cellpadding="0" cellspacing="0" style="max-width: 600px; width: 100%; background-color: #000000;">
                    
                    <tr>
                        <td style="padding: 40px 20px 20px 20px; text-align: center; border-bottom: 2px solid #ffffff;">
                            <div style="font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #666666; letter-spacing: 2px; margin-bottom: 10px; text-transform: uppercase;">
                                Tägliches Update &bull; <?php echo $date; ?>
                            </div>
                            <h1 style="margin: 0; font-family: 'Impact', 'Arial Narrow', sans-serif; font-size: 42px; line-height: 1; text-transform: uppercase; color: #ffffff; letter-spacing: 1px;">
                                Parlaments<br>Anfragen-Tracker
                            </h1>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 40px 20px;">
                            <?php if ($entryCount > 0): ?>
                                <div style="margin-bottom: 40px; text-align: left;">
                                    <div style="border-left: 2px solid #ffffff; padding-left: 15px;">
                                        <p style="margin: 0; font-size: 18px; color: #ffffff; font-weight: bold;">
                                            <?php echo $entryCount; ?> neue Anfrage<?php echo $entryCount > 1 ? 'n' : ''; ?>
                                        </p>
                                        <p style="margin: 5px 0 0 0; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #888888;">
                                            DATENSATZ AKTUALISIERT
                                        </p>
                                    </div>
                                </div>

                                <?php foreach ($entries as $index => $entry): ?>
                                    <div style="margin-bottom: 0; padding-bottom: 25px; border-bottom: 1px solid #333333; padding-top: 25px;">
                                        <div style="font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #666666; margin-bottom: 8px; letter-spacing: 1px;">
                                            <?php echo $entry['date']; ?> 
                                            <span style="color: #444;">|</span> 
                                            <?php echo !empty($entry['nparl']) ? $entry['nparl'] : '---'; ?>
                                            <span style="color: #444;">|</span> 
                                            <span style="color: <?php echo $entry['party_color']; ?>; font-weight: bold;">
                                                <?php echo htmlspecialchars($entry['party_name']); ?>
                                            </span>
                                        </div>

                                        <div style="margin-bottom: 15px;">
                                            <a href="<?php echo htmlspecialchars($entry['link']); ?>" style="text-decoration: none; color: #ffffff; font-size: 16px; line-height: 1.4; font-weight: normal; display: block;">
                                                <?php echo htmlspecialchars($entry['title']); ?>
                                            </a>
                                        </div>

                                        <?php if (!empty($entry['link'])): ?>
                                            <table cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td>
                                                        <a href="<?php echo htmlspecialchars($entry['link']); ?>" style="display: inline-block; font-family: 'Courier New', Courier, monospace; font-size: 11px; color: #ffffff; text-decoration: none; border: 1px solid #333333; padding: 5px 10px; text-transform: uppercase;">
                                                            Dokument öffnen &rarr;
                                                        </a>
                                                    </td>
                                                </tr>
                                            </table>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                            <?php else: ?>
                                <div style="text-align: center; padding: 40px 0; border: 1px solid #222222; background-color: #111111;">
                                    <h2 style="margin: 0 0 15px 0; font-family: 'Impact', 'Arial Narrow', sans-serif; font-size: 28px; color: #333333; text-transform: uppercase;">
                                        Keine Aktivitäten
                                    </h2>
                                    <p style="margin: 0; font-family: 'Courier New', Courier, monospace; font-size: 12px; color: #666666;">
                                        <?php
                                        $funnyMessages = [
                                            "SYSTEM STATUS: SILENT",
                                            "PARLAMENT: PAUSED",
                                            "NO DATA DETECTED",
                                            "ANFRAGE-GENERATOR: OFFLINE"
                                        ];
                                        echo $funnyMessages[array_rand($funnyMessages)];
                                        ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 30px 20px; border-top: 2px solid #ffffff; background-color: #000000;">
                            <table width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="left" style="font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #555555; line-height: 1.6;">
                                        SYSTEM OPERATIONAL<br>
                                        <span style="color: #22c55e;">●</span> ONLINE
                                    </td>
                                    <td align="right" style="font-family: 'Courier New', Courier, monospace; font-size: 10px; color: #555555; line-height: 1.6;">
                                        SOURCE: PARLAMENT.GV.AT<br>
                                        <a href="https://<?php echo $_SERVER['HTTP_HOST'] ?? 'ngo-business.com'; ?>" style="color: #888888; text-decoration: none;">DASHBOARD ÖFFNEN</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="2" align="center" style="padding-top: 30px; font-family: sans-serif; font-size: 10px; color: #333333;">
                                        &copy; <?php echo date('Y'); ?> Parlaments-Anfragen Tracker. <a href="https://<?php echo $_SERVER['HTTP_HOST'] ?? 'ngo-business.com'; ?>/impressum.php" style="color: #333333; text-decoration: underline;">Impressum</a>.
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
    <?php
    return ob_get_clean();
}

function generateEmailSubject($entryCount) {
    if ($entryCount > 0) {
        return "⚠️ $entryCount neue Anfrage" . ($entryCount > 1 ? 'n' : '') . " | Parlaments-Anfragen Tracker";
    } else {
        return "Status: Keine neuen Anfragen | Parlaments-Anfragen Tracker";
    }
}

function sendEmailToSubscribers($subscribers, $subject, $htmlBody) {
    $successCount = 0;
    $failCount = 0;

    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'From: Parlaments-Anfragen Tracker <noreply@' . ($_SERVER['HTTP_HOST'] ?? 'ngo-business.com') . '>',
        'X-Mailer: PHP/' . phpversion()
    ];

    $headersString = implode("\r\n", $headers);

    foreach ($subscribers as $subscriber) {
        $email = $subscriber['email'];

        try {
            if (mail($email, $subject, $htmlBody, $headersString)) {
                $successCount++;
            } else {
                $failCount++;
                error_log("Failed to send email to: $email");
            }
        } catch (Exception $e) {
            $failCount++;
            error_log("Exception sending email to $email: " . $e->getMessage());
        }
    }

    return [
        'success' => $successCount,
        'failed' => $failCount,
        'total' => count($subscribers)
    ];
}

// ==========================================
// MAIN EXECUTION
// ==========================================

try {
    echo "=== Parlaments-Anfragen Tracker - Daily Email Sender ===\n";
    echo "Starting at: " . date('Y-m-d H:i:s') . "\n\n";

    // Initialize database
    $db = new MailingListDB();

    // Get active subscribers
    $subscribers = $db->getActiveSubscribers();
    $subscriberCount = count($subscribers);

    echo "Active subscribers: $subscriberCount\n";

    if ($subscriberCount === 0) {
        echo "No active subscribers. Exiting.\n";
        exit(0);
    }

    // Fetch new entries from last 24 hours
    echo "Fetching new entries from Parliament API...\n";
    $newEntries = getNewEntries();
    $entryCount = count($newEntries);

    echo "Found $entryCount new entries in the last 24 hours.\n";

    // Generate email
    $subject = generateEmailSubject($entryCount);
    $htmlBody = generateEmailHTML($newEntries);

    echo "Sending emails to $subscriberCount subscribers...\n";

    // Send emails
    $result = sendEmailToSubscribers($subscribers, $subject, $htmlBody);

    echo "Emails sent: {$result['success']} successful, {$result['failed']} failed\n";

    // Log email sending
    $db->logEmailSending($subscriberCount, $entryCount > 0, $entryCount, $result['success'] > 0);

    // Update last email sent for all subscribers
    foreach ($subscribers as $subscriber) {
        $db->updateLastEmailSent($subscriber['email']);
    }

    echo "\n=== Completed successfully at: " . date('Y-m-d H:i:s') . " ===\n";

} catch (Exception $e) {
    error_log("Daily email sender error: " . $e->getMessage());
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
