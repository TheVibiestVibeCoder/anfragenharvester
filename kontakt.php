<?php require_once __DIR__ . '/views/partials/site_chrome.php'; ?>
<?php
// Contact form handling
$success = false;
$error = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '' || $email === '' || $subject === '' || $message === '') {
        $error = true;
        $errorMessage = 'Bitte fuellen Sie alle Felder aus.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = true;
        $errorMessage = 'Bitte geben Sie eine gueltige E-Mail-Adresse ein.';
    } else {
        $to = 'markus@disinfoconsulting.eu';
        $emailSubject = '[Parlaments-Anfragen Dashboard Kontakt] ' . $subject;

        $emailBody = "Neue Nachricht vom Kontaktformular\n\n";
        $emailBody .= "Name: " . $name . "\n";
        $emailBody .= "E-Mail: " . $email . "\n";
        $emailBody .= "Betreff: " . $subject . "\n\n";
        $emailBody .= "Nachricht:\n" . $message . "\n";

        $headers = [
            'From: noreply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
            'Reply-To: ' . $email,
            'X-Mailer: PHP/' . phpversion(),
            'Content-Type: text/plain; charset=UTF-8'
        ];

        if (mail($to, $emailSubject, $emailBody, implode("\r\n", $headers))) {
            $success = true;
            $name = $email = $subject = $message = '';
        } else {
            $error = true;
            $errorMessage = 'Beim Versenden ist ein Fehler aufgetreten. Bitte spaeter erneut versuchen.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kontakt | Parlaments-Anfragen Dashboard</title>

    <meta name="description" content="Kontaktseite fuer Fragen und Feedback zum Parlaments-Anfragen Dashboard.">
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="subpages.css">
</head>
<body class="subsite-body">

<?php site_render_floating_header(); ?>

<main class="subsite-main">
    <div class="container-custom page-wrap">
        <section class="page-hero">
            <p class="page-kicker">Rueckmeldung</p>
            <h1 class="page-title">Kontakt</h1>
            <p class="page-intro">Fragen, Hinweise oder Feedback zum Dashboard? Wir freuen uns ueber jede Rueckmeldung.</p>
        </section>

        <div class="page-stack">
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <strong>Nachricht gesendet.</strong> Vielen Dank, wir melden uns so bald wie moeglich.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <strong>Fehler:</strong> <?php echo htmlspecialchars($errorMessage); ?>
                </div>
            <?php endif; ?>

            <section class="panel">
                <h2 class="panel-title">Nachricht senden</h2>
                <form method="POST" action="" class="form-stack">
                    <div>
                        <label for="name" class="form-label">Name *</label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-input"
                            required
                            value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                            placeholder="Ihr Name"
                        >
                    </div>

                    <div>
                        <label for="email" class="form-label">E-Mail *</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            class="form-input"
                            required
                            value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                            placeholder="ihre.email@adresse.at"
                        >
                    </div>

                    <div>
                        <label for="subject" class="form-label">Betreff *</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            class="form-input"
                            required
                            value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>"
                            placeholder="Kurzbeschreibung"
                        >
                    </div>

                    <div>
                        <label for="message" class="form-label">Nachricht *</label>
                        <textarea
                            id="message"
                            name="message"
                            class="form-textarea"
                            required
                            placeholder="Ihre Nachricht"
                        ><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                    </div>

                    <div class="btn-row">
                        <button type="submit" class="btn btn-primary">Nachricht senden</button>
                    </div>

                    <p class="note mono">* Pflichtfelder</p>
                </form>
            </section>
        </div>
    </div>
</main>

<?php
site_render_footer([
    'links' => [
        ['href' => 'index.php', 'label' => 'Dashboard'],
        ['href' => 'impressum.php', 'label' => 'Impressum'],
        ['href' => 'mailingliste.php', 'label' => 'Newsletter']
    ],
    'rightLines' => [
        'QUELLE: PARLAMENT.GV.AT',
        'LAST UPDATE: ' . date('d.m.Y H:i')
    ]
]);
?>

</body>
</html>
