<?php require_once __DIR__ . '/views/partials/site_chrome.php'; ?>
<?php
// ==========================================
// NEWSLETTER UNSUBSCRIBE PAGE
// ==========================================

require_once __DIR__ . '/MailingListDB.php';

// Get parameters
$email = $_GET['email'] ?? '';
$token = $_GET['token'] ?? '';

$success = false;
$error = false;
$errorMessage = '';

// Validate parameters
if (empty($email) || empty($token)) {
    $error = true;
    $errorMessage = 'Ungültiger Abmelde-Link.';
}

// Verify token (simple hash based on email)
$expectedToken = hash('sha256', $email . 'ngo-unsubscribe-salt-2026');

if (!$error && $token !== $expectedToken) {
    $error = true;
    $errorMessage = 'Ungültiger Token. Bitte verwenden Sie den Link aus Ihrer Email.';
}

// Process unsubscribe
if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = new MailingListDB();

        if ($db->unsubscribe($email)) {
            $success = true;
        } else {
            $error = true;
            $errorMessage = 'Diese E-Mail-Adresse wurde nicht gefunden oder ist bereits abgemeldet.';
        }
    } catch (Exception $e) {
        $error = true;
        $errorMessage = 'Ein Fehler ist aufgetreten. Bitte versuchen Sie es später erneut.';
        error_log('Unsubscribe error: ' . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Newsletter Abmelden | "NGO Business" Tracker</title>

    <meta name="description" content="Vom Newsletter abmelden">
    <meta name="robots" content="noindex, nofollow">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        'bebas': ['"Bebas Neue"', 'cursive'],
                        'sans': ['"Inter"', 'sans-serif'],
                        'mono': ['"JetBrains Mono"', 'monospace'],
                    },
                    colors: {
                        'brand-black': '#050505',
                        'brand-gray': '#1a1a1a',
                    }
                }
            }
        }
    </script>

    <style>
        :root {
            --bg-color: #000000;
            --text-color: #ffffff;
            --border-color: #333333;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .container-custom {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        .btn-danger {
            background-color: #EF4444;
            color: white;
            padding: 1rem 2rem;
            border: none;
            border-radius: 4px;
            font-family: 'Bebas Neue', cursive;
            font-size: 1.25rem;
            letter-spacing: 0.05em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
        }

        .btn-danger:hover {
            background-color: #DC2626;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
        }

        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1.5rem;
            font-family: 'Inter', sans-serif;
        }

        .alert-success {
            background-color: rgba(34, 197, 94, 0.1);
            border: 1px solid #22C55E;
            color: #22C55E;
        }

        .alert-error {
            background-color: rgba(239, 68, 68, 0.1);
            border: 1px solid #EF4444;
            color: #EF4444;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php
site_render_bar_header([
    'brandText' => 'PARLAMENTS-ANFRAGEN DASHBOARD',
    'navLinks' => [
        ['href' => 'index.php', 'label' => 'Dashboard'],
        ['href' => 'mailingliste.php', 'label' => 'Newsletter']
    ]
]);
?>

    <!-- Main Content -->
    <main class="flex-1 py-12 md:py-20">
        <div class="container-custom">
            <div class="max-w-2xl mx-auto">

                <?php if ($success): ?>
                    <!-- Success Message -->
                    <div class="text-center">
                        <h1 class="text-5xl md:text-6xl font-bebas tracking-wider mb-6 uppercase text-green-500">
                            ✓ Erfolgreich Abgemeldet
                        </h1>

                        <div class="alert alert-success text-left">
                            <strong>Sie wurden erfolgreich vom Newsletter abgemeldet.</strong><br>
                            Sie erhalten ab sofort keine weiteren Emails mehr von uns.
                        </div>

                        <div class="bg-brand-gray border border-gray-800 rounded-lg p-8 mt-8">
                            <p class="text-gray-300 mb-6">
                                Schade, dass Sie gehen! 😢<br>
                                Sie können sich jederzeit wieder anmelden, falls Sie das Tracking vermissen.
                            </p>
                            <a href="mailingliste.php" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors font-mono text-sm">
                                Erneut anmelden
                            </a>
                            <a href="index.php" class="inline-block ml-4 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors font-mono text-sm">
                                Zum Dashboard
                            </a>
                        </div>
                    </div>

                <?php elseif ($error): ?>
                    <!-- Error Message -->
                    <div class="text-center">
                        <h1 class="text-5xl md:text-6xl font-bebas tracking-wider mb-6 uppercase text-red-500">
                            ⚠ Fehler
                        </h1>

                        <div class="alert alert-error text-left">
                            <strong>Fehler beim Abmelden</strong><br>
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>

                        <div class="bg-brand-gray border border-gray-800 rounded-lg p-8 mt-8">
                            <p class="text-gray-300 mb-6">
                                Bitte verwenden Sie den Abmelde-Link aus Ihrer Newsletter-Email.<br>
                                Bei weiteren Problemen kontaktieren Sie uns bitte.
                            </p>
                            <a href="kontakt.php" class="inline-block px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors font-mono text-sm">
                                Kontakt aufnehmen
                            </a>
                            <a href="index.php" class="inline-block ml-4 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded transition-colors font-mono text-sm">
                                Zum Dashboard
                            </a>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- Confirmation Form -->
                    <div class="text-center">
                        <h1 class="text-5xl md:text-6xl font-bebas tracking-wider mb-4 uppercase">
                            Newsletter Abmelden
                        </h1>
                        <p class="text-lg text-gray-400 font-sans mb-8">
                            Möchten Sie sich wirklich vom Newsletter abmelden?
                        </p>
                    </div>

                    <div class="bg-brand-gray border border-gray-800 rounded-lg p-8 shadow-2xl">
                        <div class="mb-6">
                            <p class="text-gray-300 mb-4">
                                <strong>E-Mail-Adresse:</strong><br>
                                <span class="font-mono text-blue-400"><?php echo htmlspecialchars($email); ?></span>
                            </p>
                            <p class="text-sm text-gray-500">
                                Wenn Sie sich abmelden, erhalten Sie keine täglichen Updates mehr über neue parlamentarische Anfragen zum Thema NGO-Business.
                            </p>
                        </div>

                        <form method="POST">
                            <button type="submit" class="btn-danger w-full">
                                Jetzt Abmelden
                            </button>
                        </form>

                        <div class="mt-6 text-center">
                            <a href="index.php" class="text-sm text-gray-500 hover:text-white transition-colors underline">
                                Abbrechen und zurück zur Startseite
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </main>

    <!-- Footer -->
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

