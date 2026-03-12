<?php require_once __DIR__ . '/views/partials/site_chrome.php'; ?>
<?php
require_once __DIR__ . '/MailingListDB.php';

$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

$success = false;
$error = false;
$errorMessage = '';

if ($email === '' || $token === '') {
    $error = true;
    $errorMessage = 'Ungueltiger Abmelde-Link.';
}

$expectedToken = hash('sha256', $email . 'ngo-unsubscribe-salt-2026');

if (!$error && $token !== $expectedToken) {
    $error = true;
    $errorMessage = 'Ungueltiger Token. Bitte den Link aus der E-Mail verwenden.';
}

if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new MailingListDB();

        if ($db->unsubscribe($email)) {
            $success = true;
        } else {
            $error = true;
            $errorMessage = 'Diese Adresse wurde nicht gefunden oder ist bereits abgemeldet.';
        }
    } catch (Exception $e) {
        $error = true;
        $errorMessage = 'Ein Fehler ist aufgetreten. Bitte spaeter erneut versuchen.';
        error_log('Unsubscribe error: ' . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter abmelden | Parlaments-Anfragen Dashboard</title>

    <meta name="description" content="Newsletter-Abmeldung">
    <meta name="robots" content="noindex, nofollow">

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
        ['href' => 'mailingliste.php', 'label' => 'Newsletter']
    ]
]);
?>

<main class="subsite-main">
    <div class="container-custom page-wrap">

        <?php if ($success): ?>
            <section class="page-hero">
                <p class="page-kicker">Newsletter Service</p>
                <h1 class="page-title">Abmeldung bestaetigt</h1>
                <p class="page-intro">Sie erhalten ab sofort keine weiteren Newsletter-E-Mails.</p>
            </section>

            <div class="page-stack">
                <div class="alert alert-success">
                    <strong>Erfolgreich abgemeldet.</strong> Die Adresse wurde aus der Versandliste entfernt.
                </div>
                <section class="panel">
                    <div class="btn-row">
                        <a href="mailingliste.php" class="btn btn-secondary">Erneut anmelden</a>
                        <a href="index.php" class="btn btn-primary">Zum Dashboard</a>
                    </div>
                </section>
            </div>

        <?php elseif ($error): ?>
            <section class="page-hero">
                <p class="page-kicker">Newsletter Service</p>
                <h1 class="page-title">Abmeldung nicht moeglich</h1>
            </section>

            <div class="page-stack">
                <div class="alert alert-error">
                    <strong>Fehler:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
                <section class="panel">
                    <div class="btn-row">
                        <a href="kontakt.php" class="btn btn-secondary">Kontakt</a>
                        <a href="index.php" class="btn btn-primary">Zum Dashboard</a>
                    </div>
                </section>
            </div>

        <?php else: ?>
            <section class="page-hero">
                <p class="page-kicker">Newsletter Service</p>
                <h1 class="page-title">Newsletter abmelden</h1>
                <p class="page-intro">Moechten Sie sich wirklich vom Newsletter abmelden?</p>
            </section>

            <div class="page-stack">
                <section class="panel">
                    <p class="detail-label">Betroffene E-Mail-Adresse</p>
                    <p class="detail-value mono"><?php echo htmlspecialchars($email); ?></p>
                    <p class="panel-muted" style="margin-top: 0.75rem;">Nach der Abmeldung erhalten Sie keine taeglichen Updates mehr.</p>

                    <form method="POST" style="margin-top: 1rem;">
                        <div class="btn-row">
                            <button type="submit" class="btn btn-danger">Jetzt abmelden</button>
                            <a href="index.php" class="btn btn-secondary">Abbrechen</a>
                        </div>
                    </form>
                </section>
            </div>
        <?php endif; ?>

    </div>
</main>

<?php
site_render_footer([
    'aboutHtml' => 'Das Parlaments-Anfragen Dashboard analysiert parlamentarische Anfragen aus Nationalrat und Bundesrat.',
    'noticeHtml' => null,
    'links' => [
        ['href' => 'index.php', 'label' => 'Dashboard'],
        ['href' => 'impressum.php', 'label' => 'Impressum'],
        ['href' => 'kontakt.php', 'label' => 'Kontakt']
    ],
    'rightLines' => [
        'NEWSLETTER SERVICE'
    ]
]);
?>

</body>
</html>
