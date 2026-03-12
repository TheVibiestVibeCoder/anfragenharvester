<?php require_once __DIR__ . '/views/partials/site_chrome.php'; ?>
<?php
require_once __DIR__ . '/MailingListDB.php';

$success = false;
$error = false;
$errorMessage = '';
$db = null;

function getClientIP() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIPs = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
        $firstIP = trim($forwardedIPs[0]);
        if (filter_var($firstIP, FILTER_VALIDATE_IP)) {
            $ip = $firstIP;
        }
    }

    return $ip;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new MailingListDB();
        $clientIP = getClientIP();

        if ($db->checkRateLimit($clientIP, 'signup', 3, 60)) {
            $error = true;
            $errorMessage = 'Zu viele Anmeldeversuche. Bitte in einer Stunde erneut versuchen.';
        } else {
            $db->logRateLimitAttempt($clientIP, 'signup');

            $email = trim($_POST['email'] ?? '');
            $gdprConsent = isset($_POST['gdpr_consent']) && $_POST['gdpr_consent'] === '1';

            if ($email === '') {
                $error = true;
                $errorMessage = 'Bitte geben Sie Ihre E-Mail-Adresse ein.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $error = true;
                $errorMessage = 'Bitte geben Sie eine gueltige E-Mail-Adresse ein.';
            } elseif (!$gdprConsent) {
                $error = true;
                $errorMessage = 'Bitte stimmen Sie der Datenverarbeitung zu.';
            } else {
                try {
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
                    $result = $db->addSubscriber($email, $clientIP, $userAgent);

                    if ($result['success']) {
                        $success = true;
                        $email = '';
                    }
                } catch (Exception $e) {
                    $error = true;
                    $errorMessage = $e->getMessage();
                }
            }
        }

        if (rand(1, 100) === 1) {
            $db->cleanOldRateLimits(24);
        }
    } catch (Exception $e) {
        $error = true;
        $errorMessage = 'Ein Fehler ist aufgetreten. Bitte spaeter erneut versuchen.';
        error_log('Mailing list signup error: ' . $e->getMessage());
    }
}

$subscriberCount = 0;
try {
    if (!$db) {
        $db = new MailingListDB();
    }
    $subscriberCount = $db->getSubscriberCount();
} catch (Exception $e) {
    error_log('Error getting subscriber count: ' . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter | Parlaments-Anfragen Dashboard</title>

    <meta name="description" content="Taegliche Updates zu neuen parlamentarischen Anfragen per Newsletter.">
    <meta name="robots" content="index, follow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="subpages.css">
</head>
<body class="subsite-body">

<?php
site_render_bar_header([
    'brandText' => 'Parlaments-Anfragen Dashboard',
    'navLinks' => [
        ['href' => 'index.php', 'label' => 'Dashboard'],
        ['href' => 'kontakt.php', 'label' => 'Kontakt'],
        ['href' => 'impressum.php', 'label' => 'Impressum']
    ]
]);
?>

<main class="subsite-main">
    <div class="container-custom page-wrap">
        <section class="page-hero">
            <p class="page-kicker">Update Service</p>
            <h1 class="page-title">Newsletter</h1>
            <p class="page-intro">Erhalten Sie taeglich Updates zu neuen parlamentarischen Anfragen.</p>
            <p class="badge"><?php echo number_format($subscriberCount, 0, ',', '.'); ?> aktive Abonnenten</p>
        </section>

        <div class="page-stack">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Erfolgreich angemeldet.</strong> Sie erhalten ab sofort taegliche Updates um 20:00 Uhr, wenn neue Anfragen vorliegen.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Fehler:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <section class="panel">
                <h2 class="panel-title">Anmeldung</h2>
                <form method="POST" class="form-stack">
                    <div>
                        <label for="email" class="form-label">E-Mail-Adresse</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            placeholder="ihre.email@beispiel.at"
                            value="<?php echo htmlspecialchars($email ?? ''); ?>"
                            required
                        >
                    </div>

                    <label class="checkbox-row">
                        <input type="checkbox" name="gdpr_consent" value="1" required>
                        <span>
                            Ich stimme der Speicherung meiner E-Mail-Adresse fuer den Versand des Newsletters zu.
                            Die <a href="impressum.php" class="link-muted">Datenschutzhinweise</a> habe ich zur Kenntnis genommen.
                        </span>
                    </label>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary">Jetzt anmelden</button>
                    </div>
                </form>
            </section>

            <section class="panel">
                <h2 class="panel-title">Was Sie erhalten</h2>
                <ul class="list-clean">
                    <li>Taegliche Zusammenfassung neuer Anfragen um 20:00 Uhr.</li>
                    <li>Klare Liste statt langer Einzelmeldungen.</li>
                    <li>Abmeldung jederzeit ueber Link in jeder E-Mail.</li>
                    <li>Kein Verkauf oder Weitergabe von Daten.</li>
                </ul>
                <p class="note mono" style="margin-top: 0.8rem;">Rate-Limit aktiv: maximal 3 Anmeldeversuche pro Stunde.</p>
            </section>
        </div>
    </div>
</main>

<?php
site_render_footer([
    'links' => [
        ['href' => 'impressum.php', 'label' => 'Impressum'],
        ['href' => 'kontakt.php', 'label' => 'Kontakt'],
        ['href' => 'index.php', 'label' => 'Dashboard']
    ],
    'rightLines' => [
        'NEWSLETTER SERVICE',
        'DAILY DELIVERY: 20:00'
    ]
]);
?>

</body>
</html>
