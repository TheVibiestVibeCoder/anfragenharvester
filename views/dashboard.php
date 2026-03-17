<!DOCTYPE html>
<html lang="de" prefix="og: https://ogp.me/ns#">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <?php
    // SEO-Optimized Dynamic Title
    $seoTitle = "Parlaments-Anfragen Dashboard Österreich | " . $rangeLabel . " | Parlamentarische Anfragen Live";
    $seoDescription = "Tagesaktuelles Monitoring parlamentarischer Anfragen im österreichischen Parlament.";
    $seoKeywords = "parlamentarische anfragen, nationalrat, bundesrat, anfragen dashboard, politik monitoring österreich";
    $currentUrl = "https://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    $canonicalUrl = "https://" . $_SERVER['HTTP_HOST'] . strtok($_SERVER["REQUEST_URI"], '?');
    ?>

    <title><?php echo htmlspecialchars($seoTitle); ?></title>

    <meta name="title" content="<?php echo htmlspecialchars($seoTitle); ?>">
    <meta name="description" content="<?php echo htmlspecialchars($seoDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoKeywords); ?>">
    <meta name="author" content="Parlaments-Anfragen Dashboard">
    <meta name="robots" content="index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1">
    <meta name="language" content="German">
    <meta name="revisit-after" content="1 days">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    <meta name="geo.region" content="AT">
    <meta name="geo.placename" content="Österreich">
    <meta name="geo.position" content="47.516231;14.550072">
    <meta name="ICBM" content="47.516231, 14.550072">

    <link rel="canonical" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Parlaments-Anfragen Dashboard">
    <meta property="og:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($seoDescription); ?>">
    <meta property="og:locale" content="de_AT">
    <meta property="og:updated_time" content="<?php echo date('c'); ?>">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:url" content="<?php echo htmlspecialchars($currentUrl); ?>">
    <meta name="twitter:title" content="<?php echo htmlspecialchars($seoTitle); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($seoDescription); ?>">

    <meta name="theme-color" content="#f7f4ea">
    <meta name="msapplication-TileColor" content="#f7f4ea">
    <meta name="application-name" content="Parlaments-Anfragen Dashboard">
    <meta name="apple-mobile-web-app-title" content="Parlaments-Anfragen Dashboard">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">

    <link rel="alternate" hreflang="de-at" href="<?php echo htmlspecialchars($currentUrl); ?>">
    <link rel="alternate" hreflang="de" href="<?php echo htmlspecialchars($currentUrl); ?>">
    <link rel="alternate" hreflang="x-default" href="<?php echo htmlspecialchars($canonicalUrl); ?>">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="preconnect" href="https://cdn.tailwindcss.com">
    <link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
    <link rel="dns-prefetch" href="https://www.parlament.gv.at">

    <link rel="preload" href="https://fonts.gstatic.com/s/bebasneue/v20/UcC73FwrK3iLTeHuS_nVMrMxCp50SjIw2boKoduKmMEVuI6fMZhrib2Bg-4.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">
    <link rel="preload" href="https://fonts.gstatic.com/s/inter/v24/tDbv2o-flEEny0FZhsfKu5WU5zr3E_BX0zS8.woff2" as="font" type="font/woff2" crossorigin fetchpriority="high">

    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Inter:wght@300;400;500;600&family=JetBrains+Mono:wght@400;700&display=swap" rel="stylesheet"></noscript>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="styles.css">

    <script>
        // Lazy load Chart.js with minimal overhead
        (function() {
            var loaded = false;
            var loading = false;

            window.loadChartJS = function() {
                if (loaded || loading) return Promise.resolve();
                loading = true;

                return new Promise(function(resolve, reject) {
                    var s = document.createElement('script');
                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
                    s.onload = function() { loaded = true; resolve(); };
                    s.onerror = reject;
                    document.head.appendChild(s);
                });
            };

            // Lightweight intersection observer - only set up when idle
            function setupObserver() {
                if (!('IntersectionObserver' in window)) {
                    loadChartJS();
                    return;
                }

                var observer = new IntersectionObserver(function(entries) {
                    for (var i = 0; i < entries.length; i++) {
                        if (entries[i].isIntersecting) {
                            loadChartJS();
                            observer.disconnect();
                            break;
                        }
                    }
                }, { rootMargin: '100px' });

                var canvases = document.querySelectorAll('canvas');
                for (var i = 0; i < canvases.length; i++) {
                    observer.observe(canvases[i]);
                }
            }

            // Defer observer setup to idle time
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    if ('requestIdleCallback' in window) {
                        requestIdleCallback(setupObserver, { timeout: 2000 });
                    } else {
                        setTimeout(setupObserver, 1);
                    }
                });
            } else {
                if ('requestIdleCallback' in window) {
                    requestIdleCallback(setupObserver, { timeout: 2000 });
                } else {
                    setTimeout(setupObserver, 1);
                }
            }
        })();
    </script>

    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@graph": [
            {
                "@type": "Organization",
                "name": "Parlaments-Anfragen Dashboard",
                "url": "<?php echo htmlspecialchars($canonicalUrl); ?>",
                "logo": "<?php echo htmlspecialchars($canonicalUrl); ?>",
                "description": "Echtzeit-Tracking und Analyse parlamentarischer Anfragen im österreichischen Parlament",
                "areaServed": {
                    "@type": "Country",
                    "name": "Österreich"
                },
                "knowsAbout": ["Parlamentarische Anfragen", "Transparenz", "Politisches Monitoring", "Nationalrat", "Bundesrat"],
                "keywords": "parlamentarische anfragen, nationalrat, bundesrat, politik monitoring"
            },
            {
                "@type": "WebSite",
                "name": "Parlaments-Anfragen Dashboard",
                "url": "<?php echo htmlspecialchars($canonicalUrl); ?>",
                "description": "<?php echo htmlspecialchars($seoDescription); ?>",
                "inLanguage": "de-AT",
                "isAccessibleForFree": true,
                "keywords": "<?php echo htmlspecialchars($seoKeywords); ?>"
            },
            {
                "@type": "WebPage",
                "name": "<?php echo htmlspecialchars($seoTitle); ?>",
                "url": "<?php echo htmlspecialchars($currentUrl); ?>",
                "description": "<?php echo htmlspecialchars($seoDescription); ?>",
                "inLanguage": "de-AT",
                "isPartOf": {
                    "@type": "WebSite",
                    "url": "<?php echo htmlspecialchars($canonicalUrl); ?>"
                },
                "about": {
                    "@type": "Thing",
                    "name": "Parlamentarische Anfragen",
                    "description": "Monitoring und Analyse parlamentarischer Anfragen in Österreich"
                },
                "datePublished": "<?php echo date('c', strtotime('-1 year')); ?>",
                "dateModified": "<?php echo date('c'); ?>",
                "keywords": "<?php echo htmlspecialchars($seoKeywords); ?>"
            },
            {
                "@type": "Dataset",
                "name": "Parlamentarische Anfragen <?php echo $rangeLabel; ?>",
                "description": "Echtzeit-Datensatz von <?php echo $totalCount; ?> parlamentarischen Anfragen aus dem österreichischen Parlament (<?php echo $rangeLabel; ?>)",
                "url": "<?php echo htmlspecialchars($currentUrl); ?>",
                "keywords": "<?php echo htmlspecialchars($seoKeywords); ?>",
                "creator": {
                    "@type": "Organization",
                    "name": "Parlaments-Anfragen Dashboard"
                },
                "datePublished": "<?php echo date('c', strtotime('-1 year')); ?>",
                "dateModified": "<?php echo date('c'); ?>",
                "temporalCoverage": "<?php echo $cutoffDate->format('Y-m-d'); ?>/<?php echo $endDate->format('Y-m-d'); ?>",
                "distribution": {
                    "@type": "DataDownload",
                    "contentUrl": "<?php echo htmlspecialchars($currentUrl); ?>",
                    "encodingFormat": "text/html"
                },
                "includedInDataCatalog": {
                    "@type": "DataCatalog",
                    "name": "Parlament Österreich Daten"
                },
                "spatialCoverage": {
                    "@type": "Place",
                    "name": "Österreich"
                }
            },
            {
                "@type": "BreadcrumbList",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Home",
                        "item": "<?php echo htmlspecialchars($canonicalUrl); ?>"
                    },
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "Parlamentarische Anfragen <?php echo $rangeLabel; ?>",
                        "item": "<?php echo htmlspecialchars($currentUrl); ?>"
                    }
                ]
            }
        ]
    }
    </script>
     
</head>
<body class="dashboard-page flex flex-col min-h-screen">

<header class="site-header w-full z-50" aria-hidden="true"></header>

    <div id="page-loader" class="page-loader" role="status" aria-live="polite" aria-label="Daten werden geladen">
        <div class="page-loader-card">
            <p class="page-loader-kicker">Datenaufbau</p>
            <h2 class="page-loader-title">Die Akten werden geladen</h2>
            <p class="page-loader-copy">Bitte kurz warten. Wir bereiten die Parlamentsdaten für diesen Zeitraum auf.</p>

            <div class="page-loader-stats">
                <div class="page-loader-stat">
                    <span class="page-loader-label">Datensätze im Zeitraum</span>
                    <strong id="loader-total-target"><?php echo number_format($totalCount, 0, ',', '.'); ?></strong>
                </div>
                <div class="page-loader-stat">
                    <span class="page-loader-label">Aktuell geladen</span>
                    <strong id="loader-count-current">0</strong>
                </div>
                <div class="page-loader-stat">
                    <span class="page-loader-label">Akten auf dieser Seite</span>
                    <strong><?php echo number_format(count($displayResults), 0, ',', '.'); ?></strong>
                </div>
            </div>

            <div class="page-loader-bar" aria-hidden="true">
                <span id="loader-bar-fill"></span>
            </div>
        </div>
    </div>

    <section class="hero-shell">

        <div class="hero-inner">
            <article class="hero-article">
                <header class="hero-copy mb-6 md:mb-8">
                    <span class="hero-kicker">Die Analyse</span>
                    <h1 class="hero-title" style="font-family: 'Bebas Neue', sans-serif;">
                        Parlamentarische<br>Anfragen im Fokus
                    </h1>
                </header>

            </article>
        </div>

        <div class="hero-scroll-wrap">
             <a href="#tracker" class="hero-scroll group">
                <span class="hero-scroll-label">Zum Anfragen-Tracker</span>
                <div class="hero-scroll-icon">
                    <svg class="w-3 h-3 md:w-4 md:h-4 animate-bounce" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"></path>
                    </svg>
                </div>
            </a>
        </div>
    </section>

    <main id="tracker" class="dashboard-main container-custom">

        <header class="tracker-header flex flex-col lg:flex-row justify-between items-start lg:items-end mb-12 lg:mb-16 xl:mb-20 border-b-2 border-white pb-8">
            <div class="mb-8 lg:mb-0">
                <h2 class="text-5xl md:text-6xl lg:text-7xl xl:text-8xl text-white leading-none">Anfragen Tracker</h2>
            </div>

            <?php
            $exportParams = $_GET;
            $exportParams['range'] = $timeRange;
            unset($exportParams['page']);
            $exportParams['format'] = 'xlsx';
            $exportUrl = 'export.php?' . http_build_query($exportParams);
            ?>

            <div class="w-full flex flex-col items-start gap-3">
                <form method="GET" class="time-filter-form w-full">
                    <div class="time-filter-row">
                        <div class="time-filter-field time-filter-field--range">
                            <label for="time-range-select" class="time-filter-label">Zeitraum wählen</label>
                            <select id="time-range-select" name="range" onchange="this.form.from.value=''; this.form.to.value=''; this.form.submit();" class="time-filter-select" aria-label="Zeitraum für Anfragen auswählen">
                                <option value="1week" <?php echo $timeRange === '1week' ? 'selected' : ''; ?>>LETZTE WOCHE</option>
                                <option value="1month" <?php echo $timeRange === '1month' ? 'selected' : ''; ?>>LETZTER MONAT</option>
                                <option value="3months" <?php echo $timeRange === '3months' ? 'selected' : ''; ?>>3 MONATE</option>
                                <option value="6months" <?php echo $timeRange === '6months' ? 'selected' : ''; ?>>6 MONATE</option>
                                <option value="12months" <?php echo $timeRange === '12months' ? 'selected' : ''; ?>>12 MONATE</option>
                                <option value="1year" <?php echo $timeRange === '1year' ? 'selected' : ''; ?>>LETZTES JAHR</option>
                                <option value="3years" <?php echo $timeRange === '3years' ? 'selected' : ''; ?>>3 JAHRE</option>
                                <option value="5years" <?php echo $timeRange === '5years' ? 'selected' : ''; ?>>5 JAHRE</option>
                            </select>
                        </div>

                        <div class="time-filter-field">
                            <label for="custom-from" class="time-filter-label">Von</label>
                            <input id="custom-from" type="date" name="from" value="<?php echo htmlspecialchars($customFrom); ?>" class="time-filter-input">
                        </div>

                        <div class="time-filter-field">
                            <label for="custom-to" class="time-filter-label">Bis</label>
                            <input id="custom-to" type="date" name="to" value="<?php echo htmlspecialchars($customTo); ?>" class="time-filter-input">
                        </div>

                        <div class="time-filter-actions">
                            <button type="submit" class="time-filter-btn">Zeitraum anwenden</button>
                            <?php if (!empty($isCustomRange)): ?>
                                <a href="?range=<?php echo urlencode($timeRange); ?>" class="time-filter-reset">Reset</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </form>

                <a href="<?php echo htmlspecialchars($exportUrl); ?>" class="inline-flex items-center border border-emerald-700 text-emerald-400 px-3 py-2 text-xs font-mono uppercase tracking-wide hover:bg-emerald-700 hover:text-black transition-colors">
                    Excel Export
                </a>
            </div>
        </header>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 lg:gap-10 xl:gap-16 mb-16 lg:mb-20">
            
            <div class="lg:col-span-4 flex flex-col gap-8 lg:gap-10 xl:gap-12">
                <div class="border-l-4 border-white pl-6 py-2">
                    <div class="stat-label">Gesamtanzahl</div>
                    <div class="stat-value"><?php echo number_format($totalCount); ?></div>
                    <div class="text-sm font-sans text-gray-400 mt-2 italic">Anfragen im gewählten Zeitraum erfasst.</div>
                </div>

                <div>
                    <div class="stat-label mb-6">Verteilung nach Parteien</div>
                    <div class="space-y-4">
                        <?php 
                        // Sort party stats high to low for better visual list
                        arsort($partyStats);
                        foreach ($partyStats as $code => $count): 
                            if ($count === 0 && $code !== 'OTHER') continue;
                            $percentage = $totalCount > 0 ? ($count / $totalCount) * 100 : 0;
                        ?>
                        <div class="flex items-center gap-4 group">
                            <div class="w-12 text-sm font-bold text-gray-300"><?php echo isset($partyMap[$code]) ? $partyMap[$code] : $code; ?></div>
                            <div class="flex-grow h-8 bg-gray-900 relative overflow-hidden">
                                <div class="h-full bg-<?php echo $code; ?> transition-all duration-1000" style="width: <?php echo $percentage; ?>%;"></div>
                            </div>
                            <div class="w-12 text-right font-mono text-xs text-gray-500"><?php echo $count; ?></div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="lg:col-span-8">
                <div class="investigative-box !border-t-2 !py-0 !border-b-0">
                    <div class="flex justify-between items-end mb-4 md:mb-6 pt-4">
                        <div class="flex items-center">
                            <h2 class="text-2xl md:text-3xl text-white">Zeitlicher Verlauf</h2>
                            <button class="info-btn" onclick="openModal('timeline')" aria-label="Information zum zeitlichen Verlauf">i</button>
                        </div>
                    </div>
                    <div class="h-[250px] sm:h-[300px] md:h-[350px] w-full relative">
                        <canvas id="timelineChart" 
                                role="img" 
                                aria-label="Liniendiagramm: Zeitlicher Verlauf parlamentarischer Anfragen"
                                aria-describedby="timeline-desc"></canvas>
                        <p id="timeline-desc" class="sr-only">Diagramm zeigt Verlauf der Anfragen über <?php echo $rangeLabel; ?>.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 lg:gap-10 xl:gap-16 mb-16 lg:mb-20">
            
            <div class="investigative-box">
                <div class="flex items-start mb-4">
                    <h2 class="investigative-header mb-0">Kampfbegriffe<br><span class="text-gray-500 text-base md:text-lg font-sans font-normal">Die Sprache der Anfragen</span></h2>
                    <button class="info-btn" onclick="openModal('kampfbegriffe')" aria-label="Information zu Kampfbegriffen">i</button>
                </div>
                
                <div class="grid grid-cols-1 gap-3 md:gap-4">
                    <?php foreach ($topKampfbegriffe as $index => $item): ?>
                        <?php if ($index >= 10) break; // Only show top 10 for cleaner look ?>
                        <?php 
                        arsort($item['partyBreakdown']);
                        $dominantParty = array_key_first($item['partyBreakdown']);
                        ?>
                        <div class="flex flex-wrap items-baseline justify-between border-b border-gray-800 pb-2 group hover:border-gray-600 transition-colors gap-2">
                            <div class="flex items-baseline gap-3">
                                <span class="text-xs font-mono text-gray-600">0<?php echo $index + 1; ?></span>
                                <span class="text-base md:text-lg lg:text-xl font-bold text-white group-hover:text-<?php echo $dominantParty; ?> transition-colors break-all">
                                    <?php echo htmlspecialchars($item['word']); ?>
                                </span>
                            </div>
                            <div class="flex items-center gap-2 ml-auto">
                                <span class="hidden sm:inline text-xs font-mono text-gray-500 uppercase">Dominanz:</span>
                                <span class="text-[10px] md:text-xs font-bold px-1 bg-<?php echo $dominantParty; ?> text-black">
                                    <?php echo $partyMap[$dominantParty]; ?>
                                </span>
                                <span class="text-xs md:text-sm font-mono text-gray-400 ml-2"><?php echo $item['count']; ?>×</span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="investigative-box">
                <div class="flex items-start mb-4">
                    <h2 class="investigative-header mb-0">The Flood Wall<br><span class="text-gray-500 text-base md:text-lg font-sans font-normal">Kumulative Belastung</span></h2>
                    <button class="info-btn" onclick="openModal('floodwall')" aria-label="Information zur Flood Wall">i</button>
                </div>
                <div class="h-[300px] md:h-[400px] w-full relative">
                    <canvas id="floodWallChart" 
                            role="img" 
                            aria-label="Kumulative Belastungskurve" 
                            aria-describedby="floodwall-desc"></canvas>
                    <p id="floodwall-desc" class="sr-only">Diagramm zeigt die kumulative Anzahl der Anfragen.</p>
                </div>
            </div>
        </div>

        <div class="investigative-box mb-20 lg:mb-24">
            <div class="flex items-start mb-4">
                <h2 class="investigative-header mb-0">Der Kalender<br><span class="text-gray-500 text-base md:text-lg font-sans font-normal">Intensität nach Tagen</span></h2>
                <button class="info-btn" onclick="openModal('calendar')" aria-label="Information zum Kalender">i</button>
            </div>
             <div class="h-[250px] sm:h-[300px] w-full relative">
                <canvas id="spamCalendarChart" 
                        role="img" 
                        aria-label="Heatmap der Anfragen" 
                        aria-describedby="calendar-desc"></canvas>
                <p id="calendar-desc" class="sr-only">Heatmap der täglichen Anfragen.</p>
            </div>
        </div>

        <div class="mb-24">
            <div class="flex justify-between items-end border-b-4 border-white pb-4 mb-8">
                <h2 class="text-4xl md:text-5xl lg:text-5xl xl:text-6xl text-white">Die Akten</h2>
                <div class="text-xs md:text-sm font-mono text-gray-500">
                    SEITE <?php echo $page; ?> / <?php echo $totalPages; ?>
                </div>
            </div>

            <?php if (empty($displayResults)): ?>
                <div class="py-20 text-center border-b border-gray-800">
                    <h3 class="text-gray-500 font-sans italic text-xl">Keine Daten in diesem Bereich gefunden.</h3>
                </div>
            <?php else: ?>
                <div class="flex flex-col">
                    <div class="hidden md:grid grid-cols-12 gap-6 text-xs font-mono text-gray-500 pb-2 uppercase tracking-widest border-b border-gray-800 mb-2">
                        <div class="col-span-2">Datum</div>
                        <div class="col-span-1">Partei</div>
                        <div class="col-span-7">Betreff</div>
                        <div class="col-span-2 text-right">Status</div>
                    </div>

                    <?php foreach ($displayResults as $result): ?>
                        <?php
                        $akten = isset($result['akten']) && is_array($result['akten']) ? $result['akten'] : [];
                        $people = isset($akten['people']) && is_array($akten['people']) ? $akten['people'] : [];
                        $topics = isset($akten['topics']) && is_array($akten['topics']) ? $akten['topics'] : [];
                        $headwords = isset($akten['headwords']) && is_array($akten['headwords']) ? $akten['headwords'] : [];
                        $eurovoc = isset($akten['eurovoc']) && is_array($akten['eurovoc']) ? $akten['eurovoc'] : [];
                        $stageOrder = isset($akten['stage_order']) && is_array($akten['stage_order']) ? $akten['stage_order'] : ['einlangen', 'uebermittlung', 'mitteilung', 'beantwortung'];
                        $stageMap = isset($akten['stages']) && is_array($akten['stages']) ? $akten['stages'] : [];
                        $currentStageLabel = isset($akten['current_stage_label']) ? trim((string) $akten['current_stage_label']) : '';
                        if ($currentStageLabel === '') {
                            $currentStageLabel = !empty($result['answered']) ? 'Schriftliche Beantwortung' : 'Einlangen im Nationalrat';
                        }

                        $submittedByPeople = [];
                        $submittedToPeople = [];
                        foreach ($people as $person) {
                            if (!is_array($person)) {
                                continue;
                            }
                            $hasGovFlag = array_key_exists('is_government', $person);
                            $isGovernment = $hasGovFlag ? !empty($person['is_government']) : false;
                            $personRole = trim((string) (isset($person['role']) ? $person['role'] : ''));

                            if ($personRole === 'recipient' || ($hasGovFlag && $isGovernment)) {
                                $submittedToPeople[] = $person;
                                continue;
                            }

                            if ($personRole === 'initiator') {
                                $submittedByPeople[] = $person;
                                continue;
                            }

                            $submittedByPeople[] = $person;
                        }
                        $aktenKey = isset($result['akten_key']) ? trim((string) $result['akten_key']) : '';
                        if ($aktenKey === '') {
                            $dateIso = isset($result['date_obj']) && $result['date_obj'] instanceof DateTime ? $result['date_obj']->format('Y-m-d') : '';
                            $aktenKey = sha1((string) ($result['link'] ?? '') . '|' . (string) ($result['number'] ?? '') . '|' . $dateIso);
                        }
                        $aktenDetailsId = 'akten-details-' . $aktenKey;
                        ?>
                        <div class="result-item grid grid-cols-1 md:grid-cols-12 gap-3 md:gap-6 items-start group" data-akten-key="<?php echo htmlspecialchars($aktenKey); ?>">
                            
                            <div class="flex justify-between items-baseline md:hidden mb-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-mono text-gray-400"><?php echo $result['date']; ?></span>
                                    <span class="text-[10px] text-gray-600"><?php echo $result['number']; ?></span>
                                </div>
                                <span class="text-xs font-bold text-<?php echo $result['party']; ?>"><?php echo $partyMap[$result['party']]; ?></span>
                            </div>

                            <div class="hidden md:block md:col-span-2 font-mono text-sm text-gray-400">
                                <?php echo $result['date']; ?>
                                <div class="text-xs text-gray-600 mt-1"><?php echo $result['number']; ?></div>
                            </div>

                            <div class="hidden md:block md:col-span-1">
                                <span class="text-sm font-bold text-<?php echo $result['party']; ?>">
                                    <?php echo $partyMap[$result['party']]; ?>
                                </span>
                            </div>

                            <div class="md:col-span-7">
                                <a href="<?php echo htmlspecialchars($result['link']); ?>" target="_blank" class="text-base md:text-lg text-white font-sans leading-snug hover:underline decoration-1 underline-offset-4 decoration-gray-500 block">
                                    <?php echo htmlspecialchars($result['title']); ?>
                                </a>
                                <div class="akten-details-shell" data-details-shell>
                                    <button
                                        type="button"
                                        class="akten-details-toggle"
                                        data-details-toggle
                                        aria-expanded="false"
                                        aria-controls="<?php echo htmlspecialchars($aktenDetailsId); ?>"
                                    >
                                        <span class="akten-details-toggle-text">Details anzeigen</span>
                                        <span class="akten-details-toggle-icon" aria-hidden="true">+</span>
                                    </button>

                                    <div id="<?php echo htmlspecialchars($aktenDetailsId); ?>" class="akten-details-content" data-details-content hidden>
                                        <div class="akten-meta-block">
                                            <div class="akten-meta-line">
                                                <span class="akten-meta-label">Aktueller Stand im Verfahren</span>
                                                <span class="akten-meta-value akten-status-pill"><?php echo htmlspecialchars($currentStageLabel); ?></span>
                                            </div>
                                            <?php if (!empty($submittedByPeople)): ?>
                                                <div class="akten-chip-row">
                                                    <span class="akten-chip-label">Eingebracht von</span>
                                                    <div class="akten-person-list">
                                                        <?php foreach (array_slice($submittedByPeople, 0, 8) as $person): ?>
                                                            <?php
                                                            $personName = isset($person['name']) ? trim((string) $person['name']) : '';
                                                            $personUrl = isset($person['url']) ? trim((string) $person['url']) : '';
                                                            if ($personName === '') {
                                                                continue;
                                                            }
                                                            ?>
                                                            <div class="akten-person-item">
                                                                <span class="akten-person-main">
                                                                    <?php if ($personUrl !== ''): ?>
                                                                        <a href="<?php echo htmlspecialchars($personUrl); ?>" target="_blank" rel="noopener noreferrer" class="underline decoration-1 underline-offset-2 decoration-gray-500"><?php echo htmlspecialchars($personName); ?></a>
                                                                    <?php else: ?>
                                                                        <?php echo htmlspecialchars($personName); ?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($submittedToPeople)): ?>
                                                <div class="akten-chip-row">
                                                    <span class="akten-chip-label">Eingebracht an</span>
                                                    <div class="akten-person-list">
                                                        <?php foreach (array_slice($submittedToPeople, 0, 8) as $person): ?>
                                                            <?php
                                                            $personName = isset($person['name']) ? trim((string) $person['name']) : '';
                                                            $personUrl = isset($person['url']) ? trim((string) $person['url']) : '';
                                                            if ($personName === '') {
                                                                continue;
                                                            }
                                                            ?>
                                                            <div class="akten-person-item">
                                                                <span class="akten-person-main">
                                                                    <?php if ($personUrl !== ''): ?>
                                                                        <a href="<?php echo htmlspecialchars($personUrl); ?>" target="_blank" rel="noopener noreferrer" class="underline decoration-1 underline-offset-2 decoration-gray-500"><?php echo htmlspecialchars($personName); ?></a>
                                                                    <?php else: ?>
                                                                        <?php echo htmlspecialchars($personName); ?>
                                                                    <?php endif; ?>
                                                                </span>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <div class="akten-stages">
                                                <?php foreach ($stageOrder as $stageKey): ?>
                                                    <?php
                                                    $stage = isset($stageMap[$stageKey]) && is_array($stageMap[$stageKey]) ? $stageMap[$stageKey] : [
                                                        'label' => $stageKey,
                                                        'completed' => false,
                                                        'date' => ''
                                                    ];
                                                    $isCompleted = !empty($stage['completed']);
                                                    $stageLabel = isset($stage['label']) ? (string) $stage['label'] : $stageKey;
                                                    $stageDate = isset($stage['date']) ? trim((string) $stage['date']) : '';
                                                    ?>
                                                    <div class="akten-stage-item <?php echo $isCompleted ? 'is-done' : 'is-open'; ?>">
                                                        <span class="akten-stage-dot" aria-hidden="true"></span>
                                                        <span class="akten-stage-label"><?php echo htmlspecialchars($stageLabel); ?></span>
                                                        <?php if ($stageDate !== ''): ?>
                                                            <span class="akten-stage-date"><?php echo htmlspecialchars($stageDate); ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>

                                            <?php if (!empty($topics)): ?>
                                                <div class="akten-chip-row">
                                                    <span class="akten-chip-label">Themen</span>
                                                    <div class="akten-chip-wrap">
                                                        <?php foreach (array_slice($topics, 0, 8) as $topic): ?>
                                                            <span class="akten-chip"><?php echo htmlspecialchars($topic); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($headwords)): ?>
                                                <div class="akten-chip-row">
                                                    <span class="akten-chip-label">Schlagwörter</span>
                                                    <div class="akten-chip-wrap">
                                                        <?php foreach (array_slice($headwords, 0, 8) as $headword): ?>
                                                            <span class="akten-chip"><?php echo htmlspecialchars($headword); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($eurovoc)): ?>
                                                <div class="akten-chip-row">
                                                    <span class="akten-chip-label">EUROVOC</span>
                                                    <div class="akten-chip-wrap">
                                                        <?php foreach (array_slice($eurovoc, 0, 8) as $eurovocTerm): ?>
                                                            <span class="akten-chip"><?php echo htmlspecialchars($eurovocTerm); ?></span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="md:col-span-2 flex justify-end md:block md:text-right mt-2 md:mt-0">
                                <?php if ($result['answered']): ?>
                                    <?php 
                                    preg_match('/\/gegenstand\/([^\/]+)\//', $result['link'], $gpMatch);
                                    $gpCode = $gpMatch[1] ?? 'XXVIII';
                                    $answerLink = "https://www.parlament.gv.at/gegenstand/{$gpCode}/AB/{$result['answer_number']}";
                                    ?>
                                    <a href="<?php echo htmlspecialchars($answerLink); ?>" target="_blank" class="inline-block border border-green-900 text-green-500 px-2 py-1 text-xs font-mono uppercase hover:bg-green-900 hover:text-white transition-colors">
                                        Beantwortet
                                    </a>
                                <?php else: ?>
                                    <span class="inline-block bg-red-900/20 text-red-500 px-2 py-1 text-xs font-mono uppercase">
                                        Offen
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($totalPages > 1): ?>
                <?php
                $paginationParams = ['range' => $timeRange];
                if ($customFrom !== '') {
                    $paginationParams['from'] = $customFrom;
                }
                if ($customTo !== '') {
                    $paginationParams['to'] = $customTo;
                }
                ?>
                <div class="flex flex-wrap justify-center gap-2 md:gap-4 mt-12 md:mt-16">
                    <?php if ($page > 1): ?>
                        <?php $prevQuery = http_build_query(array_merge($paginationParams, ['page' => $page - 1])); ?>
                        <a href="?<?php echo htmlspecialchars($prevQuery); ?>" class="pag-btn">&larr; Zurück</a>
                    <?php endif; ?>

                    <?php 
                    $start = max(1, $page - 2);
                    $end = min($totalPages, $page + 2);
                    for ($i = $start; $i <= $end; $i++):
                    ?>
                        <?php $pageQuery = http_build_query(array_merge($paginationParams, ['page' => $i])); ?>
                        <a href="?<?php echo htmlspecialchars($pageQuery); ?>" class="pag-btn <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <?php $nextQuery = http_build_query(array_merge($paginationParams, ['page' => $page + 1])); ?>
                        <a href="?<?php echo htmlspecialchars($nextQuery); ?>" class="pag-btn">Weiter &rarr;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

        <section class="mb-24 pt-12 border-t border-gray-800" itemscope itemtype="https://schema.org/FAQPage">
            <h2 class="text-2xl md:text-3xl text-white mb-12 font-head text-center">Hintergrund</h2>

            <div class="max-w-4xl mx-auto space-y-8 px-2 md:px-0">
                <div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="text-lg md:text-xl font-bold text-white mb-4 border-l-2 border-white pl-4" itemprop="name">
                        Was sind parlamentarische Anfragen?
                    </h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p class="text-gray-400 leading-relaxed font-sans" itemprop="text">
                            Parlamentarische Anfragen sind ein offizielles Kontrollinstrument im österreichischen Nationalrat. 
                            Abgeordnete können damit <a href="https://www.parlament.gv.at/recherchieren/gegenstaende/anfragen-und-beantwortungen/" class="text-white hover:text-gray-300 underline">schriftliche Fragen an Ministerien richten</a>, die verpflichtend beantwortet werden müssen. 
                            Sie dienen grundsätzlich der demokratischen Kontrolle der Regierung.
                        </p>
                    </div>
                </div>

                <div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="text-lg md:text-xl font-bold text-white mb-4 border-l-2 border-white pl-4" itemprop="name">
                        Was könnte die Strategie dahinter sein?
                    </h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p class="text-gray-400 leading-relaxed font-sans" itemprop="text">
                            Parlamentarische Anfragen werden häufig auch strategisch eingesetzt, um Themen und Botschaften in den öffentlichen Diskurs zu bringen.
                            Wiederholte, ähnlich formulierte Anfragen können Wahrnehmungen verstärken und politische Narrative prägen.
                            Das Dashboard hilft dabei, solche Muster datenbasiert und über Zeit sichtbar zu machen.
                        </p>
                    </div>
                </div>

                <div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="text-lg md:text-xl font-bold text-white mb-4 border-l-2 border-white pl-4" itemprop="name">
                        Wieso ist das relevant?
                    </h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p class="text-gray-400 leading-relaxed font-sans" itemprop="text">
                            Parlamentarische Anfragen erzeugen öffentliche Dokumente, Schlagzeilen und Suchtreffer. 
                            Werden sie strategisch geflutet, entsteht der Eindruck eines systemischen Problems, 
                            selbst wenn keine Rechtswidrigkeit vorliegt. 
                            So kann Vertrauen in Zivilgesellschaft, Wissenschaft und soziale Arbeit gezielt untergraben werden.
                        </p>
                    </div>
                </div>

                <div itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="text-lg md:text-xl font-bold text-white mb-4 border-l-2 border-white pl-4" itemprop="name">
                        Was kannst du tun?
                    </h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p class="text-gray-400 leading-relaxed font-sans" itemprop="text">
                            Red darüber. Teile die Daten. Hinterfrage Schlagworte. 
                            Je sichtbarer mögliche Muster werden, desto schwerer wird es, 
                            parlamentarische Instrumente für politische Stimmungsmache zu missbrauchen.
                        </p>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-8" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
                    <h3 class="text-lg md:text-xl font-bold text-white mb-4 border-l-2 border-white pl-4" itemprop="name">
                        Was ist <a href="https://mediamanipulation.org/definitions/keyword-squatting/" class="text-white hover:text-gray-300 underline">Keyword-Squatting</a>?
                    </h3>
                    <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
                        <p class="text-gray-300 leading-relaxed" itemprop="text">
                            Keyword-Squatting beschreibt die gezielte Besetzung eines Begriffs durch massenhafte Wiederholung, 
                            um dessen Bedeutung langfristig zu prägen. 
                            Der Begriff wird so häufig verwendet, dass er in Suchmaschinen, 
                            Medienberichten und öffentlichen Dokumenten automatisch mit einem bestimmten Narrativ verknüpft wird.
                        </p>
                        
                        <p class="text-gray-300 leading-relaxed mt-4" itemprop="text">
                            In parlamentarischen Anfragen können Begriffe durch häufige Wiederholung dauerhaft mit bestimmten Deutungen verknüpft werden,
                            unabhängig davon, ob die zugrunde liegenden Sachverhalte im gleichen Ausmaß bestehen.
                        </p>

                        <p class="text-gray-300 leading-relaxed mt-4" itemprop="text">
                            Parlamentsseiten eignen sich dafür besonders gut. 
                            Sie gelten als staatliche Primärquelle, besitzen hohe Glaubwürdigkeit 
                            und werden von Suchmaschinen stark priorisiert. 
                            Jeder dort verwendete Begriff erhält dadurch Sichtbarkeit, Autorität und Dauerhaftigkeit.
                        </p>

                        <p class="text-gray-300 leading-relaxed mt-4" itemprop="text">
                            Wird ein Schlagwort systematisch über parlamentarische Dokumente verbreitet, 
                            entsteht ein digitales Archiv politischer Narrative, 
                            das weit über tagespolitische Debatten hinaus wirkt.
                        </p>
                    </div>
                </div>
            </div>
        </section>

    </main>

    <div id="modal-timeline" class="modal-overlay" onclick="closeModalOnOverlay(event, 'timeline')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal('timeline')" aria-label="Schließen">&times;</button>
            <h3 class="modal-title">Zeitlicher Verlauf</h3>
            <div class="modal-body">
                <p><strong>Was zeigt diese Grafik?</strong></p>
                <p>Diese Grafik zeigt, wie viele parlamentarische Anfragen im gewählten Zeitraum gestellt wurden.</p>
                <p><strong>Wie wird sie berechnet?</strong></p>
                <p>Für jeden Tag oder Monat (je nach gewähltem Zeitraum) werden alle Anfragen gezählt. Die Linie zeigt die Entwicklung über die Zeit.</p>
                <p><strong>Was bedeutet das?</strong></p>
                <p>Spitzen in der Kurve zeigen Phasen besonders intensiver Anfrage-Aktivität.</p>
            </div>
        </div>
    </div>

    <div id="modal-kampfbegriffe" class="modal-overlay" onclick="closeModalOnOverlay(event, 'kampfbegriffe')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal('kampfbegriffe')" aria-label="Schließen">&times;</button>
            <h3 class="modal-title">Kampfbegriffe</h3>
            <div class="modal-body">
                <p><strong>Was zeigt diese Grafik?</strong></p>
                <p>Diese Liste zeigt die häufigsten politisch aufgeladenen Begriffe aus den Anfragen und welche Partei diese am meisten verwendet.</p>
                <p><strong>Wie wird sie berechnet?</strong></p>
                <p>Alle Wörter aus den Anfragen werden analysiert. Neutrale Begriffe wie "Österreich", "Verein" oder "Förderung" werden herausgefiltert. Übrig bleiben aussagekräftige Schlagwörter. Für jedes Wort wird gezählt, welche Partei es wie oft verwendet hat.</p>
                <p><strong>Was bedeutet das?</strong></p>
                <p>Die Liste zeigt, welche Begriffe in Anfragen dominieren. Die "Dominanz" zeigt, welche Partei ein Wort besonders häufig nutzt.</p>
            </div>
        </div>
    </div>

    <div id="modal-floodwall" class="modal-overlay" onclick="closeModalOnOverlay(event, 'floodwall')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal('floodwall')" aria-label="Schließen">&times;</button>
            <h3 class="modal-title">The Flood Wall</h3>
            <div class="modal-body">
                <p><strong>Was zeigt diese Grafik?</strong></p>
                <p>Diese Grafik zeigt die kumulative (aufaddierte) Anzahl der Anfragen jeder Partei über die Zeit.</p>
                <p><strong>Wie wird sie berechnet?</strong></p>
                <p>Für jede Partei wird täglich gezählt: Wie viele Anfragen hat diese Partei insgesamt bis zu diesem Tag gestellt? Die Linien steigen also nur an, nie ab. Je steiler die Linie, desto mehr Anfragen wurden in diesem Zeitraum gestellt.</p>
                <p><strong>Was bedeutet das?</strong></p>
                <p>Die "Flood Wall" macht sichtbar, wie systematisch und massiv einzelne Parteien das Parlament mit Anfragen zu einem Thema "überfluten". Eine steil ansteigende Linie bedeutet: Hier wird intensiv und kontinuierlich Druck aufgebaut.</p>
            </div>
        </div>
    </div>

    <div id="modal-calendar" class="modal-overlay" onclick="closeModalOnOverlay(event, 'calendar')">
        <div class="modal-content" onclick="event.stopPropagation()">
            <button class="modal-close" onclick="closeModal('calendar')" aria-label="Schließen">&times;</button>
            <h3 class="modal-title">Der Kalender</h3>
            <div class="modal-body">
                <p><strong>Was zeigt diese Grafik?</strong></p>
                <p>Diese Heatmap zeigt für jeden Tag, wie viele Anfragen jede Partei gestellt hat. Je intensiver die Farbe, desto mehr Anfragen wurden an diesem Tag eingereicht.</p>
                <p><strong>Wie wird sie berechnet?</strong></p>
                <p>Jeder Tag wird als Punkt dargestellt. Die Farbe entspricht der jeweiligen Partei. Die Farbintensität (Helligkeit) zeigt die Anzahl der Anfragen: Dunkel = wenige Anfragen, Hell/Leuchtend = viele Anfragen an diesem Tag.</p>
                <p><strong>Was bedeutet das?</strong></p>
                <p>Der Kalender macht "Bulk-Tage" sichtbar - Tage, an denen besonders viele Anfragen auf einmal eingereicht wurden.</p>
            </div>
        </div>
    </div>

    <footer class="site-footer bg-black border-t border-white py-8 md:py-12 mt-auto">
        <div class="container-custom">
            <div class="flex flex-col md:flex-row justify-between items-start gap-8">
                <div class="max-w-md">
                    <h3 class="text-sm font-bold text-white mb-4 uppercase tracking-wider">Über das Projekt</h3>
                    <p class="text-xs text-gray-500 leading-relaxed font-sans mb-4">
                        Das Parlaments-Anfragen Dashboard analysiert parlamentarische Anfragen aus Nationalrat und Bundesrat.
                        <br><br>
                        Es macht sichtbar, wie oft, von wem und in welchen Mustern Anfragen eingebracht werden.
                    </p>
                    <div class="text-xs text-yellow-600 leading-relaxed font-sans mb-4 italic">
                        Hinweis: Diese Plattform ist experimentell. Fehler können vorkommen.
                    </div>
                    <div class="text-xs font-mono text-gray-600">
                          © <?php echo date('Y'); ?> PARLAMENTS-ANFRAGEN DASHBOARD
                    </div>
                    <div class="mt-2 space-x-4">
                        <a href="impressum.php" class="text-xs font-mono text-gray-500 hover:text-white transition-colors underline">Impressum</a>
                        <a href="kontakt.php" class="text-xs font-mono text-gray-500 hover:text-white transition-colors underline">Kontakt</a>
                        <a href="mailingliste.php" class="text-xs font-mono text-blue-400 hover:text-blue-300 transition-colors underline">📧 Newsletter</a>
                    </div>
                </div>

                <div class="text-left md:text-right w-full md:w-auto">
                    <div class="text-xs font-mono text-gray-500 mb-2">QUELLE: PARLAMENT.GV.AT</div>
                    <div class="text-xs font-mono text-gray-500 mb-2">LAST UPDATE: <?php echo date('d.m.Y H:i'); ?></div>
                    <div class="flex items-center justify-start md:justify-end gap-2 mt-4">
                        <div class="w-2 h-2 bg-green-600 rounded-full"></div>
                        <span class="text-xs font-mono text-green-600">SYSTEM OPERATIONAL</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <script>
        const loaderMeta = {
            total: <?php echo (int) $totalCount; ?>,
            details: <?php echo (int) count($displayResults); ?>
        };
        let loaderProgress = 0;

        function setLoaderProgress(value) {
            const safeTotal = Math.max(1, loaderMeta.details);
            const bounded = Math.max(0, Math.min(safeTotal, Math.floor(value)));
            loaderProgress = bounded;

            const counter = document.getElementById('loader-count-current');
            const bar = document.getElementById('loader-bar-fill');
            if (counter) {
                counter.textContent = bounded.toLocaleString('de-AT');
            }
            if (bar) {
                bar.style.width = ((bounded / safeTotal) * 100).toFixed(2) + '%';
            }
        }

        function animateLoaderProgress(target, durationMs) {
            const startValue = loaderProgress;
            const delta = Math.max(0, target - startValue);
            if (delta === 0) {
                return;
            }

            const duration = Math.max(250, durationMs || 800);
            const startedAt = performance.now();

            function tick(now) {
                const ratio = Math.min(1, (now - startedAt) / duration);
                const eased = 1 - Math.pow(1 - ratio, 3);
                setLoaderProgress(startValue + (delta * eased));
                if (ratio < 1) {
                    requestAnimationFrame(tick);
                }
            }

            requestAnimationFrame(tick);
        }

        function hidePageLoader() {
            const loader = document.getElementById('page-loader');
            if (!loader || loader.classList.contains('is-hidden')) {
                return;
            }

            loader.classList.add('is-hidden');
            setTimeout(function() {
                loader.style.display = 'none';
            }, 420);
        }

        function initPageLoader() {
            setLoaderProgress(0);
            const firstTarget = Math.min(loaderMeta.details, Math.max(1, Math.ceil(loaderMeta.details * 0.25)));
            animateLoaderProgress(firstTarget, 650);
        }

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function buildPeopleHtml(people) {
            if (!Array.isArray(people) || people.length === 0) {
                return '';
            }

            const rows = people.slice(0, 6).map(function(person) {
                const name = escapeHtml(person.name || '');
                const url = escapeHtml(person.url || '');
                if (!name) {
                    return '';
                }

                const nameHtml = url ? `<a href="${url}" target="_blank" rel="noopener noreferrer" class="underline decoration-1 underline-offset-2 decoration-gray-500">${name}</a>` : name;

                return `
                    <div class="akten-person-item">
                        <span class="akten-person-main">
                            ${nameHtml}
                        </span>
                    </div>
                `;
            }).join('');

            return rows ? `<div class="akten-person-list">${rows}</div>` : '';
        }

        function buildStageHtml(akten) {
            const stageOrder = Array.isArray(akten.stage_order) ? akten.stage_order : ['einlangen', 'uebermittlung', 'mitteilung', 'beantwortung'];
            const stageMap = (akten.stages && typeof akten.stages === 'object') ? akten.stages : {};
            return stageOrder.map(function(stageKey) {
                const stage = stageMap[stageKey] || { label: stageKey, completed: false, date: '' };
                const doneClass = stage.completed ? 'is-done' : 'is-open';
                const label = escapeHtml(stage.label || stageKey);
                const date = escapeHtml(stage.date || '');
                return `
                    <div class="akten-stage-item ${doneClass}">
                        <span class="akten-stage-dot" aria-hidden="true"></span>
                        <span class="akten-stage-label">${label}</span>
                        ${date ? `<span class="akten-stage-date">${date}</span>` : ''}
                    </div>
                `;
            }).join('');
        }

        function buildChipRow(label, values) {
            if (!Array.isArray(values) || values.length === 0) {
                return '';
            }
            const chips = values.slice(0, 8).map(function(value) {
                return `<span class="akten-chip">${escapeHtml(value)}</span>`;
            }).join('');
            return `
                <div class="akten-chip-row">
                    <span class="akten-chip-label">${escapeHtml(label)}</span>
                    <div class="akten-chip-wrap">${chips}</div>
                </div>
            `;
        }

        function splitPeopleByGovernment(people) {
            const by = [];
            const to = [];

            (Array.isArray(people) ? people : []).forEach(function(person) {
                if (!person || typeof person !== 'object') {
                    return;
                }

                const hasGovFlag = Object.prototype.hasOwnProperty.call(person, 'is_government');
                const govValue = person.is_government;
                const isGovernment = govValue === true || govValue === 1 || govValue === '1' || String(govValue).toLowerCase() === 'true';
                const role = String(person.role || '').trim().toLowerCase();

                if (role === 'recipient' || (hasGovFlag && isGovernment)) {
                    to.push(person);
                    return;
                }

                if (role === 'initiator') {
                    by.push(person);
                    return;
                }

                by.push(person);
            });

            return { by: by, to: to };
        }

        function renderAktenMetaBlock(akten) {
            const people = Array.isArray(akten.people) ? akten.people : [];
            const split = splitPeopleByGovernment(people);
            const submittedByPeople = split.by;
            const submittedToPeople = split.to;
            const currentStageLabel = akten.current_stage_label || 'Einlangen im Nationalrat';

            const submittedByList = submittedByPeople.length ? `
                <div class="akten-chip-row">
                    <span class="akten-chip-label">Eingebracht von</span>
                    ${buildPeopleHtml(submittedByPeople)}
                </div>
            ` : '';

            const submittedToList = submittedToPeople.length ? `
                <div class="akten-chip-row">
                    <span class="akten-chip-label">Eingebracht an</span>
                    ${buildPeopleHtml(submittedToPeople)}
                </div>
            ` : '';

            return `
                <div class="akten-meta-line">
                    <span class="akten-meta-label">Aktueller Stand im Verfahren</span>
                    <span class="akten-meta-value akten-status-pill">${escapeHtml(currentStageLabel)}</span>
                </div>
                ${submittedByList}
                ${submittedToList}
                <div class="akten-stages">${buildStageHtml(akten)}</div>
                ${buildChipRow('Themen', akten.topics || [])}
                ${buildChipRow('Schlagwörter', akten.headwords || [])}
                ${buildChipRow('EUROVOC', akten.eurovoc || [])}
            `;
        }

        function refreshOpenAktenDetailsHeight(root) {
            const scope = root && root.querySelectorAll ? root : document;
            scope.querySelectorAll('.akten-details-shell.is-open [data-details-content]').forEach(function(content) {
                if (!content || content.hidden) {
                    return;
                }
                content.style.maxHeight = content.scrollHeight + 'px';
            });
        }

        function setAktenDetailsExpanded(shell, shouldExpand) {
            if (!shell) {
                return;
            }

            const toggle = shell.querySelector('[data-details-toggle]');
            const content = shell.querySelector('[data-details-content]');
            const label = shell.querySelector('.akten-details-toggle-text');
            if (!toggle || !content || !label) {
                return;
            }

            if (content._closeTimer) {
                clearTimeout(content._closeTimer);
                content._closeTimer = null;
            }

            if (shouldExpand) {
                content.hidden = false;
                content.style.maxHeight = '0px';
                shell.classList.add('is-open');
                toggle.setAttribute('aria-expanded', 'true');
                label.textContent = 'Details ausblenden';

                requestAnimationFrame(function() {
                    content.style.maxHeight = content.scrollHeight + 'px';
                });
                return;
            }

            content.style.maxHeight = content.scrollHeight + 'px';
            toggle.setAttribute('aria-expanded', 'false');
            label.textContent = 'Details anzeigen';

            requestAnimationFrame(function() {
                shell.classList.remove('is-open');
                content.style.maxHeight = '0px';
            });

            content._closeTimer = setTimeout(function() {
                if (!shell.classList.contains('is-open')) {
                    content.hidden = true;
                }
            }, 460);
        }

        function initAktenDetailToggles() {
            document.querySelectorAll('[data-details-shell]').forEach(function(shell) {
                const toggle = shell.querySelector('[data-details-toggle]');
                const content = shell.querySelector('[data-details-content]');
                const label = shell.querySelector('.akten-details-toggle-text');

                if (!toggle || !content || !label) {
                    return;
                }

                if (toggle.dataset.bound === '1') {
                    return;
                }
                toggle.dataset.bound = '1';

                shell.classList.remove('is-open');
                content.hidden = true;
                content.style.maxHeight = '0px';
                toggle.setAttribute('aria-expanded', 'false');
                label.textContent = 'Details anzeigen';

                toggle.addEventListener('click', function() {
                    const isOpen = shell.classList.contains('is-open');
                    setAktenDetailsExpanded(shell, !isOpen);
                });
            });
        }

        function updateAktenMetaBlocks(items) {
            const keys = Object.keys(items || {});
            if (!keys.length) {
                return;
            }

            let loaded = 0;
            keys.forEach(function(key) {
                const row = document.querySelector('.result-item[data-akten-key="' + key + '"]');
                if (!row) {
                    return;
                }

                const block = row.querySelector('.akten-meta-block');
                if (!block) {
                    return;
                }

                block.innerHTML = renderAktenMetaBlock(items[key] || {});
                refreshOpenAktenDetailsHeight(row);
                loaded++;
                setLoaderProgress(loaded);
            });
        }

        async function loadAktenDetails() {
            const params = new URLSearchParams(window.location.search);
            if (!params.has('range')) {
                params.set('range', '<?php echo htmlspecialchars($timeRange, ENT_QUOTES); ?>');
            }
            if (!params.has('page')) {
                params.set('page', '<?php echo (int) $page; ?>');
            }

            const endpointUrl = 'akten-details.php?' + params.toString();
            const waitCap = Math.max(1, Math.floor(loaderMeta.details * 0.8));
            const waitTicker = setInterval(function() {
                if (loaderProgress < waitCap) {
                    setLoaderProgress(loaderProgress + 1);
                }
            }, 240);

            try {
                const response = await fetch(endpointUrl, {
                    method: 'GET',
                    headers: { 'Accept': 'application/json' }
                });
                if (!response.ok) {
                    return;
                }

                const payload = await response.json();
                if (!payload || payload.ok !== true || !payload.items) {
                    return;
                }

                updateAktenMetaBlocks(payload.items);
            } catch (error) {
                console.error('Failed to load Akten details:', error);
            } finally {
                clearInterval(waitTicker);
            }
        }

        console.log('=== PARLIAMENT INQUIRY TRACKER DEBUG START ===');

        // Initialize charts function - called after Chart.js loads
        function initializeCharts() {
            // Wait for Chart.js to be available
            if (typeof Chart === 'undefined') {
                console.error('❌ Chart.js not loaded yet, waiting...');
                return;
            }

            console.log('✅ Chart.js loaded successfully, version:', Chart.version);
            console.log('🎨 Initializing charts...');

            // Chart Config - Cleaner, less "techy" more editorial
            Chart.defaults.color = '#4f544a';
            Chart.defaults.borderColor = 'rgba(32,35,31,0.12)';
            Chart.defaults.font.family = "'Inter', sans-serif";

            // Disable animations completely to prevent callback errors
            Chart.defaults.animation = false;
            Chart.defaults.responsive = true;
            Chart.defaults.maintainAspectRatio = false;

            // Data Prep
            const monthlyData = <?php echo json_encode($monthlyData); ?>;
            const floodData = <?php echo json_encode($floodWallData); ?>;
            const spamData = <?php echo json_encode($spamCalendarData); ?>;
            const dates = <?php echo json_encode(array_values(array_map(fn($d) => $d->format('d.m.Y'), $allDates))); ?>;
            const allDateKeys = <?php echo json_encode(array_keys($allDates)); ?>;

            const partyColors = {
                'S': '#ef4444', 'V': '#22d3ee', 'F': '#3b82f6',
                'G': '#22c55e', 'N': '#e879f9', 'OTHER': '#9ca3af'
            };
            const partyNames = {
                'S': 'SPÖ', 'V': 'ÖVP', 'F': 'FPÖ',
                'G': 'GRÜNE', 'N': 'NEOS', 'OTHER': 'ANDERE'
            };

            // 1. TIMELINE
            const ctx1 = document.getElementById('timelineChart');
            if (ctx1) {
                const labels = Object.values(monthlyData).map(m => m.label);
                const counts = Object.values(monthlyData).map(m => m.count);

                new Chart(ctx1, {
                    type: 'line',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Anfragen',
                            data: counts,
                            borderColor: '#174d37',
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 3,
                            pointHoverRadius: 8,
                            pointHoverBackgroundColor: '#174d37',
                            pointHoverBorderColor: '#f6f4ea',
                            pointHoverBorderWidth: 3,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: true,
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#fff',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    title: (tooltipItems) => {
                                        return 'Datum: ' + tooltipItems[0].label;
                                    },
                                    label: (context) => {
                                        return 'Anfragen: ' + context.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { display: true, drawBorder: false } },
                            x: {
                                grid: { display: false },
                                display: true,
                                ticks: {
                                    color: '#667060',
                                    font: { family: 'JetBrains Mono', size: 10 },
                                    autoSkip: true,
                                    maxRotation: 0
                                }
                            }
                        }
                    }
                });
                console.log('✅ Timeline Chart initialized with tooltips');

                // Test canvas interactivity
                ctx1.addEventListener('mousemove', function() {
                    console.log('👆 Mouse moved over Timeline Chart canvas');
                }, { once: true });
            }

            // 2. FLOOD WALL
            const ctx2 = document.getElementById('floodWallChart');
            if (ctx2) {
                new Chart(ctx2, {
                    type: 'line',
                    data: {
                        labels: dates,
                        datasets: Object.keys(floodData).map(party => ({
                            label: partyNames[party],
                            data: floodData[party].map(d => d.cumulative),
                            borderColor: partyColors[party],
                            backgroundColor: 'transparent',
                            borderWidth: 2,
                            pointRadius: 2,
                            pointHoverRadius: 6,
                            pointHoverBackgroundColor: partyColors[party],
                            pointHoverBorderColor: '#fff',
                            pointHoverBorderWidth: 2,
                            stepped: true
                        }))
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false
                        },
                        plugins: {
                            legend: {
                                labels: { color: '#55604f', font: { family: 'Inter' } }
                            },
                            tooltip: {
                                enabled: true,
                                mode: 'index',
                                intersect: false,
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#fff',
                                borderWidth: 1,
                                padding: 12,
                                callbacks: {
                                    title: (tooltipItems) => {
                                        return 'Datum: ' + tooltipItems[0].label;
                                    },
                                    label: (context) => {
                                        return context.dataset.label + ': ' + context.parsed.y + ' (kumulativ)';
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: { display: false },
                                ticks: {
                                    color: '#667060',
                                    font: { family: 'JetBrains Mono', size: 10 },
                                    autoSkip: true,
                                    maxTicksLimit: 10,
                                    maxRotation: 0,
                                    minRotation: 0
                                }
                            },
                            y: { grid: { color: '#d8d8ce' } }
                        }
                    }
                });
                console.log('✅ Flood Wall Chart initialized with tooltips');

                // Test canvas interactivity
                ctx2.addEventListener('mousemove', function() {
                    console.log('👆 Mouse moved over Flood Wall Chart canvas');
                }, { once: true });
            }

            // 3. SPAM CALENDAR
            const ctx3 = document.getElementById('spamCalendarChart');
            if (ctx3) {
                const matrixData = [];
                const pOrder = ['S', 'V', 'F', 'G', 'N', 'OTHER'];

                pOrder.forEach((party, pIdx) => {
                    const pData = spamData[party] || [];
                    const dMap = {};
                    pData.forEach(i => dMap[i.date] = i.count);

                    allDateKeys.forEach((d, dIdx) => {
                        if (dMap[d]) {
                            matrixData.push({
                                x: dIdx, y: pIdx, v: dMap[d], party: party, date: d
                            });
                        }
                    });
                });

                const maxVal = Math.max(...matrixData.map(d => d.v), 1);

                new Chart(ctx3, {
                    type: 'scatter',
                    data: {
                        datasets: [{
                            data: matrixData.map(d => ({ x: d.x, y: d.y, v: d.v, p: d.party, date: d.date })),
                            backgroundColor: ctx => {
                                const v = ctx.raw;
                                if (!v) return '#333';
                                const c = partyColors[v.p];
                                // Improved color intensity: wider range from 0.2 to 1.0 for better contrast
                                // Using exponential curve to make differences more pronounced
                                const normalizedValue = v.v / maxVal;
                                const alpha = 0.2 + Math.pow(normalizedValue, 0.7) * 0.8;
                                return c + Math.floor(alpha * 255).toString(16).padStart(2,'0');
                            },
                            pointRadius: 8,
                            pointHoverRadius: 12,
                            pointStyle: 'rect',
                            pointHoverBorderWidth: 2,
                            pointHoverBorderColor: '#fff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'point',
                            intersect: true
                        },
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                enabled: true,
                                mode: 'point',
                                intersect: true,
                                backgroundColor: 'rgba(0, 0, 0, 0.9)',
                                titleColor: '#fff',
                                bodyColor: '#fff',
                                borderColor: '#fff',
                                borderWidth: 1,
                                padding: 12,
                                displayColors: false,
                                callbacks: {
                                    title: (tooltipItems) => {
                                        const dateKey = tooltipItems[0].raw.date;
                                        const parts = dateKey.split('-');
                                        return 'Datum: ' + parts[2] + '.' + parts[1] + '.' + parts[0];
                                    },
                                    label: (context) => {
                                        const partyName = partyNames[context.raw.p];
                                        const count = context.raw.v;
                                        return partyName + ': ' + count + ' Anfrage' + (count > 1 ? 'n' : '');
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                display: true,
                                grid: { display: false },
                                ticks: {
                                    callback: (value, index) => {
                                        // Ensure we have a date for this index
                                        const dateKey = allDateKeys[value];
                                        if(dateKey) {
                                            const parts = dateKey.split('-');
                                            return `${parts[2]}.${parts[1]}.`;
                                        }
                                        return '';
                                    },
                                    color: '#667060',
                                    font: { family: 'JetBrains Mono', size: 10 },
                                    autoSkip: true,
                                    maxRotation: 0
                                }
                            },
                            y: {
                                min: -0.5, max: 5.5,
                                ticks: { callback: v => partyNames[pOrder[v]] },
                                grid: { display: false }
                            }
                        }
                    }
                });
                console.log('✅ Spam Calendar Chart initialized with tooltips');

                // Test canvas interactivity
                ctx3.addEventListener('mousemove', function() {
                    console.log('👆 Mouse moved over Spam Calendar Chart canvas');
                }, { once: true });
            }

            console.log('✅ All charts initialized successfully!');
            console.log('=== PARLIAMENT INQUIRY TRACKER DEBUG END ===');

            // Modal Functions
            window.openModal = function(modalId) {
                const modal = document.getElementById('modal-' + modalId);
                if (modal) {
                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                }
            };

            window.closeModal = function(modalId) {
                const modal = document.getElementById('modal-' + modalId);
                if (modal) {
                    modal.classList.remove('active');
                    document.body.style.overflow = '';
                }
            };

            window.closeModalOnOverlay = function(event, modalId) {
                if (event.target.classList.contains('modal-overlay')) {
                    closeModal(modalId);
                }
            };

            // Close modal on ESC key
            document.addEventListener('keydown', function(event) {
                if (event.key === 'Escape') {
                    const activeModal = document.querySelector('.modal-overlay.active');
                    if (activeModal) {
                        const modalId = activeModal.id.replace('modal-', '');
                        closeModal(modalId);
                    }
                }
            });
        }

        // Initialize charts when Chart.js is ready and DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            initPageLoader();
            initAktenDetailToggles();

            let aktenResizeTimer = null;
            window.addEventListener('resize', function() {
                if (aktenResizeTimer !== null) {
                    clearTimeout(aktenResizeTimer);
                }
                aktenResizeTimer = setTimeout(function() {
                    refreshOpenAktenDetailsHeight(document);
                }, 120);
            });

            const chartPromise = loadChartJS().then(() => {
                const midTarget = Math.min(loaderMeta.details, Math.max(1, Math.ceil(loaderMeta.details * 0.55)));
                animateLoaderProgress(midTarget, 650);

                return new Promise(function(resolve) {
                    requestAnimationFrame(() => {
                        requestAnimationFrame(() => {
                            initializeCharts();
                            resolve();
                        });
                    });
                });
            }).catch(err => {
                console.error('Failed to load Chart.js:', err);
            });

            const aktenPromise = loadAktenDetails();

            Promise.allSettled([chartPromise, aktenPromise]).then(function() {
                setLoaderProgress(loaderMeta.details);
                setTimeout(hidePageLoader, 260);
            });

            // Safety timeout so the overlay cannot get stuck
            setTimeout(hidePageLoader, 15000);
        });
    </script>
</body>
</html>
