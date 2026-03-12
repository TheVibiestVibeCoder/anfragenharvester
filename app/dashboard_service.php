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

    $cacheKey = 'inquiry_data_v3_' . md5(serialize($gpCodes) . $cutoffDate->format('Y-m-d'));
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
                'number' => $rowNumber
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
