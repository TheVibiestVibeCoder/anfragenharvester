<?php require_once __DIR__ . '/views/partials/site_chrome.php'; ?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impressum | Parlaments-Anfragen Dashboard</title>

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
            <p class="page-kicker">Rechtliche Information</p>
            <h1 class="page-title">Impressum</h1>
            <p class="page-intro">Informationen gemaess ECG, MedienG, GewO und UGB.</p>
        </section>

        <div class="page-stack">
            <section class="panel">
                <h2 class="panel-title">Vereinsdaten</h2>
                <div class="grid-2">
                    <article class="detail-card" style="grid-column: 1 / -1;">
                        <p class="detail-label">Vollstaendiger Name</p>
                        <p class="detail-value">Disinfo Awareness - Verein zur Aufklaerung ueber Desinformation und FIMI (Foreign Information Manipulation Interference) zur Staerkung der Informationsresilienz</p>
                    </article>
                    <article class="detail-card">
                        <p class="detail-label">ZVR-Zahl</p>
                        <p class="detail-value mono">1154237575</p>
                    </article>
                    <article class="detail-card">
                        <p class="detail-label">Kontakt</p>
                        <p class="detail-value"><a href="mailto:kontakt@ngo-business.com">kontakt@ngo-business.com</a></p>
                    </article>
                    <article class="detail-card">
                        <p class="detail-label">Zustellanschrift</p>
                        <p class="detail-value">Staudingergasse 8/6<br>1200 Wien<br>Oesterreich</p>
                    </article>
                    <article class="detail-card" style="grid-column: 1 / -1;">
                        <p class="detail-label">Zustaendige Behoerde</p>
                        <p class="detail-value">Landespolizeidirektion Wien, Referat Vereins-, Versammlungs- und Medienrechtsangelegenheiten</p>
                    </article>
                </div>
            </section>

            <section class="panel">
                <h2 class="panel-title">Rechtliches</h2>
                <div class="grid-3">
                    <article class="detail-card">
                        <h3 class="panel-subtitle">Urheberrecht</h3>
                        <p class="legal-text">Die Inhalte dieser Webseite unterliegen, soweit rechtlich moeglich, Schutzrechten. Jede Verwendung oder Verbreitung von bereitgestelltem Material bedarf einer schriftlichen Zustimmung.</p>
                        <p class="legal-text">Urheberrechte Dritter werden mit Sorgfalt beachtet. Bei Hinweisen auf Rechtsverletzungen entfernen wir betroffene Inhalte umgehend.</p>
                    </article>
                    <article class="detail-card">
                        <h3 class="panel-subtitle">Haftungsausschluss</h3>
                        <p class="legal-text">Trotz sorgfaeltiger Kontrolle uebernimmt der Betreiber keine Haftung fuer Inhalte externer Links. Fuer verlinkte Seiten sind ausschliesslich deren Betreiber verantwortlich.</p>
                        <p class="legal-text">Bei Hinweisen auf rechtswidrige Inhalte verlinkter Seiten werden diese nach Pruefung entfernt.</p>
                    </article>
                    <article class="detail-card">
                        <h3 class="panel-subtitle">Zweck</h3>
                        <p class="legal-text">Information ueber die Taetigkeit des Vereins sowie Foerderung der Medienkompetenz und Resilienz gegen Desinformation.</p>
                    </article>
                </div>
            </section>
        </div>
    </div>
</main>

<?php
site_render_footer([
    'links' => [
        ['href' => 'index.php', 'label' => 'Dashboard'],
        ['href' => 'kontakt.php', 'label' => 'Kontakt'],
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
