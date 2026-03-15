<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/party.php';
require_once __DIR__ . '/inquiry_helpers.php';
require_once __DIR__ . '/parliament_api.php';
require_once __DIR__ . '/time_range.php';

function app_build_dashboard_view_model($queryParams, $cache) {
    $requestedRange = isset($queryParams['range']) ? $queryParams['range'] : '12months';
    $rangeData = app_resolve_time_range($requestedRange);

    $timeRange = $rangeData['timeRange'];
    $now = $rangeData['now'];
    $cutoffDate = $rangeData['cutoffDate'];
    $rangeLabel = $rangeData['rangeLabel'];
    $gpCodes = $rangeData['gpCodes'];
    $stopwords = app_stopwords();
    $partyCodes = array_keys(app_default_party_stats());

    $cacheKey = 'inquiry_data_v4_' . md5(serialize($gpCodes) . $cutoffDate->format('Y-m-d'));
    $cachedData = $cache->get($cacheKey);

    if (is_array($cachedData)) {
        $allResults = isset($cachedData['allResults']) ? $cachedData['allResults'] : [];
        $wordFrequency = isset($cachedData['wordFrequency']) ? $cachedData['wordFrequency'] : [];
        $monthlyData = isset($cachedData['monthlyData']) ? $cachedData['monthlyData'] : [];
        $partyStats = isset($cachedData['partyStats']) ? $cachedData['partyStats'] : app_default_party_stats();
        $answeredCount = isset($cachedData['answeredCount']) ? $cachedData['answeredCount'] : 0;
        $pendingCount = isset($cachedData['pendingCount']) ? $cachedData['pendingCount'] : 0;
        error_log('Dashboard cache HIT: ' . $cacheKey);
    } else {
        error_log('Dashboard cache MISS: ' . $cacheKey . ' - fetching fresh data');

        $rows = app_fetch_parliament_rows($gpCodes, ['J', 'JPR'], 15);
        $allResults = [];
        $wordFrequency = [];
        $monthlyData = [];
        $partyStats = app_default_party_stats();
        $answeredCount = 0;
        $pendingCount = 0;

        foreach ($rows as $row) {
            $rowDateStr = trim((string) app_get_row_value($row, 4, 'DATUM'));
            if ($rowDateStr === '') {
                continue;
            }

            $rowDate = app_parse_row_date($rowDateStr);
            if (!$rowDate instanceof DateTime) {
                continue;
            }
            if ($rowDate < $cutoffDate) {
                continue;
            }

            $rowTitle = trim((string) app_get_row_value($row, 6, 'TITEL'));
            if ($rowTitle === '') {
                $rowTitle = 'Anfrage ohne Titel';
            }

            $rowPartyCode = app_get_party_code(app_get_row_value($row, 21, 'PARTIE'));
            if (!isset($partyStats[$rowPartyCode])) {
                $rowPartyCode = 'OTHER';
            }

            $rowLink = app_build_inquiry_link(app_get_row_value($row, 14, 'LINK'));
            $rowNumber = trim((string) app_get_row_value($row, 7, 'NPARL'));
            $rowGpCode = trim((string) app_get_row_value($row, 0, 'GP_CODE'));
            $rowPadIds = app_parse_jsonish_list(app_get_row_value($row, 20, 'PAD_INTERN'));
            $rowTopics = app_parse_jsonish_list(app_get_row_value($row, 22, 'THEMEN'));
            $rowHeadwords = app_parse_jsonish_list(app_get_row_value($row, 23, 'SW'));
            $rowEurovoc = app_parse_jsonish_list(app_get_row_value($row, 24, 'EUROVOC'));

            $answerInfo = app_extract_answer_info($rowTitle);

            $partyStats[$rowPartyCode]++;
            if ($answerInfo['answered']) {
                $answeredCount++;
            } else {
                $pendingCount++;
            }

            $useDays = in_array($timeRange, ['1week', '1month'], true);
            $timeKey = $useDays ? $rowDate->format('Y-m-d') : $rowDate->format('Y-m');
            if (!isset($monthlyData[$timeKey])) {
                $monthlyData[$timeKey] = [
                    'count' => 0,
                    'label' => $useDays ? $rowDate->format('d.m.') : $rowDate->format('M Y'),
                    'timestamp' => $rowDate->getTimestamp()
                ];
            }
            $monthlyData[$timeKey]['count']++;

            foreach (app_extract_keywords($rowTitle, $stopwords) as $word) {
                if (!isset($wordFrequency[$word])) {
                    $wordFrequency[$word] = 0;
                }
                $wordFrequency[$word]++;
            }

            $allResults[] = [
                'date' => $rowDate->format('d.m.Y'),
                'date_obj' => $rowDate,
                'title' => $rowTitle,
                'party' => $rowPartyCode,
                'answered' => $answerInfo['answered'],
                'answer_number' => $answerInfo['answer_number'],
                'link' => $rowLink,
                'number' => $rowNumber,
                'gp_code' => $rowGpCode,
                'pad_ids' => $rowPadIds,
                'topics' => $rowTopics,
                'headwords' => $rowHeadwords,
                'eurovoc' => $rowEurovoc
            ];
        }

        usort($allResults, function($a, $b) {
            return $b['date_obj'] <=> $a['date_obj'];
        });

        ksort($monthlyData);
        arsort($wordFrequency);

        $cache->set($cacheKey, [
            'allResults' => $allResults,
            'wordFrequency' => $wordFrequency,
            'monthlyData' => $monthlyData,
            'partyStats' => $partyStats,
            'answeredCount' => $answeredCount,
            'pendingCount' => $pendingCount
        ]);
    }

    $partyDailyCounts = [];
    foreach ($partyCodes as $partyCode) {
        $partyDailyCounts[$partyCode] = [];
    }

    foreach ($allResults as $result) {
        $dateKey = $result['date_obj']->format('Y-m-d');
        $partyCode = $result['party'];
        if (!isset($partyDailyCounts[$partyCode][$dateKey])) {
            $partyDailyCounts[$partyCode][$dateKey] = 0;
        }
        $partyDailyCounts[$partyCode][$dateKey]++;
    }

    $allDates = [];
    foreach ($allResults as $result) {
        $dateKey = $result['date_obj']->format('Y-m-d');
        if (!isset($allDates[$dateKey])) {
            $allDates[$dateKey] = $result['date_obj'];
        }
    }
    ksort($allDates);

    $floodWallData = [];
    foreach ($partyCodes as $partyCode) {
        $cumulative = 0;
        $floodWallData[$partyCode] = [];

        foreach ($allDates as $dateObj) {
            $dateKey = $dateObj->format('Y-m-d');
            $count = isset($partyDailyCounts[$partyCode][$dateKey]) ? $partyDailyCounts[$partyCode][$dateKey] : 0;
            $cumulative += $count;

            $floodWallData[$partyCode][] = [
                'date' => $dateObj->format('d.m.Y'),
                'cumulative' => $cumulative
            ];
        }
    }

    $keywordPartyUsage = [];
    foreach ($allResults as $result) {
        foreach (app_extract_keywords($result['title'], $stopwords) as $word) {
            if (!isset($keywordPartyUsage[$word])) {
                $keywordPartyUsage[$word] = app_default_party_stats();
            }
            $keywordPartyUsage[$word][$result['party']]++;
        }
    }

    $kampfbegriffeData = [];
    foreach ($wordFrequency as $word => $count) {
        if (!isset($keywordPartyUsage[$word])) {
            continue;
        }

        $partyUsage = $keywordPartyUsage[$word];
        $maxParty = array_keys($partyUsage, max($partyUsage))[0];
        $kampfbegriffeData[] = [
            'word' => $word,
            'count' => $count,
            'party' => $maxParty,
            'partyBreakdown' => $partyUsage
        ];
    }
    $topKampfbegriffe = array_slice($kampfbegriffeData, 0, 20, true);

    $spamCalendarData = [];
    foreach ($partyCodes as $partyCode) {
        $spamCalendarData[$partyCode] = [];

        foreach ($allDates as $dateKey => $dateObj) {
            $count = isset($partyDailyCounts[$partyCode][$dateKey]) ? $partyDailyCounts[$partyCode][$dateKey] : 0;
            if ($count <= 0) {
                continue;
            }

            $spamCalendarData[$partyCode][] = [
                'date' => $dateKey,
                'displayDate' => $dateObj->format('d.m.Y'),
                'count' => $count
            ];
        }
    }

    $page = isset($queryParams['page']) ? max(1, intval($queryParams['page'])) : 1;
    $perPage = 25;
    $totalResults = count($allResults);
    $totalPages = max(1, (int) ceil($totalResults / $perPage));
    $offset = ($page - 1) * $perPage;
    $displayResults = array_slice($allResults, $offset, $perPage);
    $displayResults = app_enrich_results_for_akten($displayResults, $cache);
    $totalCount = $totalResults;

    $earliestDate = null;
    $earliestDateFormatted = '';
    if (!empty($allResults)) {
        $earliestInquiry = end($allResults);
        if (isset($earliestInquiry['date_obj'])) {
            $earliestDate = $earliestInquiry['date_obj'];
            $earliestDateFormatted = $earliestDate->format('d.m.Y');
        }
    }

    $partyMap = app_party_map();

    return [
        'timeRange' => $timeRange,
        'now' => $now,
        'cutoffDate' => $cutoffDate,
        'rangeLabel' => $rangeLabel,
        'gpCodes' => $gpCodes,
        'allNGOResults' => $allResults,
        'allResults' => $allResults,
        'wordFrequency' => $wordFrequency,
        'monthlyData' => $monthlyData,
        'partyStats' => $partyStats,
        'answeredCount' => $answeredCount,
        'pendingCount' => $pendingCount,
        'floodWallData' => $floodWallData,
        'spamCalendarData' => $spamCalendarData,
        'topKampfbegriffe' => $topKampfbegriffe,
        'allDates' => $allDates,
        'page' => $page,
        'perPage' => $perPage,
        'totalResults' => $totalResults,
        'totalPages' => $totalPages,
        'offset' => $offset,
        'displayResults' => $displayResults,
        'totalCount' => $totalCount,
        'earliestDate' => $earliestDate,
        'earliestDateFormatted' => $earliestDateFormatted,
        'partyMap' => $partyMap
    ];
}

function app_enrich_results_for_akten(array $results, $cache) {
    $enriched = [];

    foreach ($results as $result) {
        if (!is_array($result)) {
            continue;
        }

        $historyCacheKey = 'geschichtsseite_v1_' . md5((string) ($result['link'] ?? ''));
        $aktenData = $cache->get($historyCacheKey);

        if (!is_array($aktenData)) {
            $historyResponse = app_fetch_geschichtsseite_response(isset($result['link']) ? $result['link'] : '', 5);
            if (is_array($historyResponse)) {
                $aktenData = app_build_akten_from_geschichtsseite($historyResponse, $result, $cache);
            }

            if (!is_array($aktenData)) {
                $aktenData = app_build_akten_fallback($result, $cache);
                $cache->set($historyCacheKey, $aktenData, 1800);
            } else {
                $cache->set($historyCacheKey, $aktenData, 43200);
            }
        }

        $result['akten'] = $aktenData;
        $enriched[] = $result;
    }

    return $enriched;
}

function app_build_akten_from_geschichtsseite(array $historyResponse, array $result, $cache = null) {
    $content = isset($historyResponse['content']) && is_array($historyResponse['content']) ? $historyResponse['content'] : [];
    if (empty($content)) {
        return app_build_akten_fallback($result, $cache);
    }

    $people = [];
    $initiators = [];
    $recipients = [];
    $names = isset($content['names']) && is_array($content['names']) ? $content['names'] : [];

    foreach ($names as $entry) {
        if (!is_array($entry)) {
            continue;
        }

        $functionLabel = trim((string) (isset($entry['funktext']) ? $entry['funktext'] : ''));
        $name = trim((string) (isset($entry['name']) ? $entry['name'] : ''));
        if ($name === '') {
            continue;
        }

        $partyCode = trim((string) (isset($entry['frak_code']) ? $entry['frak_code'] : ''));
        $personUrl = app_parliament_make_absolute_url(isset($entry['url']) ? $entry['url'] : '');
        $pad = app_extract_pad_from_person_url($personUrl);

        $person = [
            'function' => $functionLabel,
            'name' => $name,
            'party_code' => $partyCode,
            'pad' => $pad,
            'url' => $personUrl
        ];
        $people[] = $person;

        $functionMatch = mb_strtolower($functionLabel, 'UTF-8');
        if (strpos($functionMatch, 'eingebracht von') !== false) {
            $initiators[] = $person;
        }
        if (strpos($functionMatch, 'eingebracht an') !== false) {
            $recipients[] = $person;
        }
    }

    if (empty($initiators) && !empty($people)) {
        $initiators[] = $people[0];
    }

    $topics = app_collect_bubble_labels(isset($content['topics']) ? $content['topics'] : null);
    $headwords = app_collect_bubble_labels(isset($content['headwords']) ? $content['headwords'] : null);
    $eurovoc = app_collect_bubble_labels(isset($content['eurovoc']) ? $content['eurovoc'] : null);

    if (empty($topics)) {
        $topics = app_parse_jsonish_list(isset($result['topics']) ? $result['topics'] : []);
    }
    if (empty($headwords)) {
        $headwords = app_parse_jsonish_list(isset($result['headwords']) ? $result['headwords'] : []);
    }
    if (empty($eurovoc)) {
        $eurovoc = app_parse_jsonish_list(isset($result['eurovoc']) ? $result['eurovoc'] : []);
    }

    $stages = app_default_akten_stages();
    $rawStages = isset($content['stages']) && is_array($content['stages']) ? $content['stages'] : [];
    $stagesRaw = [];

    foreach ($rawStages as $stage) {
        if (!is_array($stage)) {
            continue;
        }

        $date = trim((string) (isset($stage['date']) ? $stage['date'] : ''));
        $plainText = app_html_to_plain_text(isset($stage['text']) ? $stage['text'] : '');
        $stageKey = app_match_stage_key($plainText);

        $stagesRaw[] = [
            'date' => $date,
            'text' => $plainText,
            'key' => $stageKey
        ];

        if ($stageKey === null || !isset($stages[$stageKey])) {
            continue;
        }

        $stages[$stageKey]['completed'] = true;
        if ($stages[$stageKey]['date'] === '' && $date !== '') {
            $stages[$stageKey]['date'] = $date;
        }
        if ($stages[$stageKey]['text'] === '' && $plainText !== '') {
            $stages[$stageKey]['text'] = $plainText;
        }
    }

    $currentStageLabel = app_resolve_current_stage_label($content, $stages, $result);

    return [
        'source' => 'geschichtsseite',
        'current_stage_label' => $currentStageLabel,
        'people' => $people,
        'initiators' => $initiators,
        'recipients' => $recipients,
        'topics' => $topics,
        'headwords' => $headwords,
        'eurovoc' => $eurovoc,
        'stages' => $stages,
        'stage_order' => ['einlangen', 'uebermittlung', 'mitteilung', 'beantwortung'],
        'stages_raw' => $stagesRaw
    ];
}

function app_build_akten_fallback(array $result, $cache = null) {
    $stages = app_default_akten_stages();
    $stages['einlangen']['completed'] = true;
    $stages['einlangen']['date'] = isset($result['date']) ? (string) $result['date'] : '';

    $isAnswered = !empty($result['answered']);
    if ($isAnswered) {
        $stages['beantwortung']['completed'] = true;
    }

    $padIds = app_parse_jsonish_list(isset($result['pad_ids']) ? $result['pad_ids'] : []);
    $initiators = [];
    foreach ($padIds as $pad) {
        $pad = trim((string) $pad);
        if ($pad === '') {
            continue;
        }

        $displayName = app_resolve_person_name_by_pad($pad, $cache);
        if ($displayName === '') {
            $displayName = 'PAD ' . $pad;
        }

        $initiators[] = [
            'function' => 'Eingebracht von',
            'name' => $displayName,
            'party_code' => '',
            'pad' => $pad,
            'url' => app_parliament_make_absolute_url('/person/' . $pad)
        ];
    }

    return [
        'source' => 'fallback',
        'current_stage_label' => $isAnswered ? 'Schriftliche Beantwortung' : 'Einlangen im Nationalrat',
        'people' => $initiators,
        'initiators' => $initiators,
        'recipients' => [],
        'topics' => app_parse_jsonish_list(isset($result['topics']) ? $result['topics'] : []),
        'headwords' => app_parse_jsonish_list(isset($result['headwords']) ? $result['headwords'] : []),
        'eurovoc' => app_parse_jsonish_list(isset($result['eurovoc']) ? $result['eurovoc'] : []),
        'stages' => $stages,
        'stage_order' => ['einlangen', 'uebermittlung', 'mitteilung', 'beantwortung'],
        'stages_raw' => []
    ];
}

function app_collect_bubble_labels($node) {
    if (!is_array($node)) {
        return [];
    }

    $data = isset($node['data']) && is_array($node['data']) ? $node['data'] : [];
    $bubbles = isset($data['bubbles']) && is_array($data['bubbles']) ? $data['bubbles'] : [];
    $labels = [];

    foreach ($bubbles as $bubble) {
        if (!is_array($bubble)) {
            continue;
        }
        $label = trim((string) (isset($bubble['label']) ? $bubble['label'] : ''));
        if ($label === '') {
            continue;
        }
        if (!in_array($label, $labels, true)) {
            $labels[] = $label;
        }
    }

    return $labels;
}

function app_default_akten_stages() {
    return [
        'einlangen' => [
            'label' => 'Einlangen im Nationalrat',
            'completed' => false,
            'date' => '',
            'text' => ''
        ],
        'uebermittlung' => [
            'label' => 'Übermittlung',
            'completed' => false,
            'date' => '',
            'text' => ''
        ],
        'mitteilung' => [
            'label' => 'Mitteilung des Einlangens in einer Plenarsitzung',
            'completed' => false,
            'date' => '',
            'text' => ''
        ],
        'beantwortung' => [
            'label' => 'Schriftliche Beantwortung',
            'completed' => false,
            'date' => '',
            'text' => ''
        ]
    ];
}

function app_resolve_current_stage_label(array $content, array $stages, array $result) {
    if (isset($content['status'])) {
        if (is_string($content['status'])) {
            $status = trim($content['status']);
            if ($status !== '') {
                return $status;
            }
        } elseif (is_array($content['status'])) {
            foreach (['label', 'text', 'name'] as $candidateKey) {
                if (isset($content['status'][$candidateKey])) {
                    $status = trim((string) $content['status'][$candidateKey]);
                    if ($status !== '') {
                        return $status;
                    }
                }
            }
        }
    }

    $priority = ['beantwortung', 'mitteilung', 'uebermittlung', 'einlangen'];
    foreach ($priority as $stageKey) {
        if (isset($stages[$stageKey]) && !empty($stages[$stageKey]['completed'])) {
            return $stages[$stageKey]['label'];
        }
    }

    if (!empty($result['answered'])) {
        return 'Schriftliche Beantwortung';
    }

    return 'Einlangen im Nationalrat';
}

function app_resolve_person_name_by_pad($pad, $cache = null) {
    $pad = preg_replace('/[^0-9]/', '', (string) $pad);
    if ($pad === '') {
        return '';
    }

    $cacheKey = 'person_name_v1_' . $pad;
    if ($cache && method_exists($cache, 'get')) {
        $cachedName = $cache->get($cacheKey);
        if ($cachedName !== null) {
            return is_string($cachedName) ? trim($cachedName) : '';
        }
    }

    $name = app_fetch_person_name_by_pad($pad, 8);
    $name = trim((string) $name);

    if ($cache && method_exists($cache, 'set')) {
        if ($name !== '') {
            $cache->set($cacheKey, $name, 2592000);
        } else {
            $cache->set($cacheKey, '', 1800);
        }
    }

    return $name;
}
