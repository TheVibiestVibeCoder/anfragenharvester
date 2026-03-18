<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/parliament_api.php';
require_once __DIR__ . '/inquiry_helpers.php';

function app_empty_nr_inquiry_ranking($reason = '') {
    return [
        'updated_at_utc' => gmdate('Y-m-d H:i:s'),
        'updated_at_iso' => gmdate('c'),
        'label' => 'Anzahl schriftlicher Anfragen (PAD-Beteiligungen)',
        'filters' => [
            'NRBR' => ['NR'],
            'VHG' => ['J_JPR_M'],
            'DOKTYP' => ['J']
        ],
        'members_source' => 'none',
        'members_total' => 0,
        'status_summary' => [
            'success' => 0,
            'failed' => 0,
            'timeout' => 0
        ],
        'reason' => trim((string) $reason),
        'rows' => []
    ];
}

function app_build_nr_inquiry_ranking($cache, array $options = []) {
    $forceRefresh = !empty($options['force_refresh']);
    $cacheTtl = isset($options['cache_ttl']) ? max(300, (int) $options['cache_ttl']) : 86400;
    $cacheKey = 'nr_inquiry_ranking_v2';

    if (!$forceRefresh && $cache && method_exists($cache, 'get')) {
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && isset($cached['rows']) && is_array($cached['rows'])) {
            return $cached;
        }
    }

    $membersData = app_load_current_nr_members($cache);
    $members = isset($membersData['members']) && is_array($membersData['members']) ? $membersData['members'] : [];
    $membersSource = isset($membersData['source']) ? (string) $membersData['source'] : 'unknown';

    if (empty($members)) {
        $empty = app_empty_nr_inquiry_ranking('Keine NR-Mitgliederdaten verfügbar.');
        $empty['members_source'] = $membersSource;
        if ($cache && method_exists($cache, 'set')) {
            $cache->set($cacheKey, $empty, 1800);
        }
        return $empty;
    }

    $countsByPad = app_fetch_written_inquiry_counts_by_members($members, [
        'timeout' => isset($options['timeout']) ? (int) $options['timeout'] : 14,
        'retries' => isset($options['retries']) ? (int) $options['retries'] : 1,
        'concurrency' => isset($options['concurrency']) ? (int) $options['concurrency'] : 8
    ]);

    $statusSummary = [
        'success' => 0,
        'failed' => 0,
        'timeout' => 0
    ];
    $rows = [];

    foreach ($members as $member) {
        $pad = isset($member['pad']) ? preg_replace('/[^0-9]/', '', (string) $member['pad']) : '';
        if ($pad === '') {
            continue;
        }

        $countResult = isset($countsByPad[$pad]) && is_array($countsByPad[$pad]) ? $countsByPad[$pad] : [
            'count' => null,
            'status' => 'failed',
            'attempts' => 0
        ];

        $status = isset($countResult['status']) ? (string) $countResult['status'] : 'failed';
        if (!isset($statusSummary[$status])) {
            $status = 'failed';
        }
        $statusSummary[$status]++;

        $rawCount = isset($countResult['count']) ? $countResult['count'] : null;
        $count = is_numeric($rawCount) ? max(0, (int) $rawCount) : null;

        $rows[] = [
            'rank' => null,
            'pad' => $pad,
            'name' => isset($member['name']) && trim((string) $member['name']) !== '' ? trim((string) $member['name']) : ('PAD ' . $pad),
            'club' => isset($member['club']) ? trim((string) $member['club']) : '',
            'count' => $count,
            'status' => $status
        ];
    }

    usort($rows, 'app_compare_nr_ranking_rows');
    $rows = app_assign_nr_ranking_positions($rows);

    $payload = [
        'updated_at_utc' => gmdate('Y-m-d H:i:s'),
        'updated_at_iso' => gmdate('c'),
        'label' => 'Anzahl schriftlicher Anfragen (PAD-Beteiligungen)',
        'filters' => [
            'NRBR' => ['NR'],
            'VHG' => ['J_JPR_M'],
            'DOKTYP' => ['J']
        ],
        'members_source' => $membersSource,
        'members_total' => count($rows),
        'status_summary' => $statusSummary,
        'reason' => '',
        'rows' => $rows
    ];

    if ($cache && method_exists($cache, 'set')) {
        $cache->set($cacheKey, $payload, $cacheTtl);
    }

    return $payload;
}

function app_compare_nr_ranking_rows(array $a, array $b) {
    $aCount = isset($a['count']) ? $a['count'] : null;
    $bCount = isset($b['count']) ? $b['count'] : null;

    $aMissing = !is_int($aCount);
    $bMissing = !is_int($bCount);

    if ($aMissing && $bMissing) {
        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    }
    if ($aMissing) {
        return 1;
    }
    if ($bMissing) {
        return -1;
    }
    if ($aCount !== $bCount) {
        return $bCount <=> $aCount;
    }

    return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
}

function app_assign_nr_ranking_positions(array $rows) {
    $currentRank = 0;
    $position = 0;
    $previousCount = null;

    foreach ($rows as $index => $row) {
        $position++;
        $count = isset($row['count']) && is_int($row['count']) ? $row['count'] : null;
        if ($count === null) {
            $rows[$index]['rank'] = null;
            continue;
        }

        if ($previousCount === null || $count !== $previousCount) {
            $currentRank = $position;
            $previousCount = $count;
        }
        $rows[$index]['rank'] = $currentRank;
    }

    return $rows;
}

function app_load_current_nr_members($cache = null) {
    $cacheKey = 'nr_member_list_v1';
    if ($cache && method_exists($cache, 'get')) {
        $cached = $cache->get($cacheKey);
        if (is_array($cached) && isset($cached['members']) && is_array($cached['members']) && !empty($cached['members'])) {
            return $cached;
        }
    }

    $localMembers = app_load_nr_members_from_local_files();
    if (!empty($localMembers)) {
        $result = [
            'source' => 'local_file',
            'members' => app_normalize_nr_member_records($localMembers)
        ];
        if ($cache && method_exists($cache, 'set')) {
            $cache->set($cacheKey, $result, 86400);
        }
        return $result;
    }

    $listingMembers = app_fetch_current_nr_members_from_listing_page();
    if (!empty($listingMembers)) {
        $normalized = app_normalize_nr_member_records($listingMembers);
        $enriched = app_enrich_nr_members_with_profiles($normalized, $cache);
        $result = [
            'source' => 'nr_listing_page',
            'members' => $enriched
        ];
        if ($cache && method_exists($cache, 'set')) {
            $cache->set($cacheKey, $result, 86400);
        }
        return $result;
    }

    $fallbackMembers = app_derive_nr_members_from_inquiry_rows($cache);
    $result = [
        'source' => 'derived_from_inquiries',
        'members' => app_normalize_nr_member_records($fallbackMembers)
    ];
    if ($cache && method_exists($cache, 'set')) {
        $cache->set($cacheKey, $result, 43200);
    }

    return $result;
}

function app_load_nr_members_from_local_files() {
    $paths = [
        __DIR__ . '/../cache/nr_members_current.json',
        __DIR__ . '/../api-data.txt'
    ];

    foreach ($paths as $path) {
        if (!is_file($path)) {
            continue;
        }

        $content = file_get_contents($path);
        if (!is_string($content) || trim($content) === '') {
            continue;
        }

        $fromJson = app_parse_nr_members_from_json($content);
        if (!empty($fromJson)) {
            return $fromJson;
        }

        $fromLines = app_parse_nr_members_from_lines($content);
        if (!empty($fromLines)) {
            return $fromLines;
        }
    }

    return [];
}

function app_parse_nr_members_from_json($content) {
    $decoded = json_decode((string) $content, true);
    if (!is_array($decoded)) {
        return [];
    }

    $records = [];
    if (isset($decoded['members']) && is_array($decoded['members'])) {
        $records = $decoded['members'];
    } elseif (isset($decoded[0])) {
        $records = $decoded;
    }

    $members = [];
    foreach ($records as $record) {
        if (!is_array($record)) {
            continue;
        }
        $pad = '';
        foreach (['pad', 'PAD', 'pad_intern', 'PAD_INTERN'] as $key) {
            if (isset($record[$key])) {
                $pad = preg_replace('/[^0-9]/', '', (string) $record[$key]);
                if ($pad !== '') {
                    break;
                }
            }
        }
        if ($pad === '') {
            continue;
        }

        $name = '';
        foreach (['name', 'NAME', 'full_name', 'person_name'] as $key) {
            if (isset($record[$key]) && trim((string) $record[$key]) !== '') {
                $name = trim((string) $record[$key]);
                break;
            }
        }

        $club = '';
        foreach (['club', 'fraktion', 'party', 'frak_code', 'club_code'] as $key) {
            if (isset($record[$key]) && trim((string) $record[$key]) !== '') {
                $club = app_ranking_normalize_frak_code((string) $record[$key]);
                break;
            }
        }

        $members[] = [
            'pad' => $pad,
            'name' => $name,
            'club' => $club
        ];
    }

    return $members;
}

function app_parse_nr_members_from_lines($content) {
    $lines = preg_split('/\r\n|\r|\n/', (string) $content);
    if (!is_array($lines) || empty($lines)) {
        return [];
    }

    $members = [];
    foreach ($lines as $line) {
        $line = trim((string) $line);
        if ($line === '') {
            continue;
        }

        if (!preg_match('/^\s*(\d{3,})(?:\s*[;|,].*)?$/', $line, $m)) {
            continue;
        }
        $pad = preg_replace('/[^0-9]/', '', (string) $m[1]);
        if ($pad === '') {
            continue;
        }

        $parts = preg_split('/\s*[;|,]\s*/', $line);
        $name = isset($parts[1]) ? trim((string) $parts[1]) : '';
        $club = isset($parts[2]) ? app_ranking_normalize_frak_code((string) $parts[2]) : '';

        $members[] = [
            'pad' => $pad,
            'name' => $name,
            'club' => $club
        ];
    }

    return $members;
}

function app_fetch_current_nr_members_from_listing_page($timeout = 20) {
    $url = 'https://www.parlament.gv.at/recherchieren/personen/nationalrat/';
    $html = app_fetch_text_get($url, $timeout);
    if (!is_string($html) || trim($html) === '') {
        return [];
    }

    $membersMap = [];

    if (preg_match_all('/<a[^>]+href=["\'][^"\']*\/person\/(\d+)[^"\']*["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $pad = isset($match[1]) ? preg_replace('/[^0-9]/', '', (string) $match[1]) : '';
            if ($pad === '') {
                continue;
            }

            $name = isset($match[2]) ? app_html_to_plain_text($match[2]) : '';
            $name = app_ranking_clean_member_name($name);
            if (!isset($membersMap[$pad])) {
                $membersMap[$pad] = [
                    'pad' => $pad,
                    'name' => $name,
                    'club' => app_ranking_detect_party_near_pad($html, $pad)
                ];
                continue;
            }

            if ($membersMap[$pad]['name'] === '' && $name !== '') {
                $membersMap[$pad]['name'] = $name;
            }
            if ($membersMap[$pad]['club'] === '') {
                $membersMap[$pad]['club'] = app_ranking_detect_party_near_pad($html, $pad);
            }
        }
    }

    return array_values($membersMap);
}

function app_ranking_clean_member_name($name) {
    $name = app_html_to_plain_text((string) $name);
    $name = preg_replace('/\s+/', ' ', (string) $name);
    $name = trim((string) $name);
    if ($name === '') {
        return '';
    }

    $lower = mb_strtolower($name, 'UTF-8');
    $blacklist = [
        'mehr',
        'profil',
        'details',
        'zur person',
        'zum profil',
        'parlament',
        'nationalrat',
        'abgeordnete'
    ];
    foreach ($blacklist as $word) {
        if ($lower === $word) {
            return '';
        }
    }

    if (preg_match('/\d/', $name)) {
        return '';
    }

    return $name;
}

function app_ranking_detect_party_near_pad($html, $pad) {
    $needle = '/person/' . $pad;
    $position = strpos((string) $html, $needle);
    if ($position === false) {
        return '';
    }

    $start = max(0, $position - 320);
    $snippet = substr((string) $html, $start, 700);
    $normalized = mb_strtoupper(app_html_to_plain_text($snippet), 'UTF-8');
    $normalized = strtr($normalized, [
        'Ö' => 'OE',
        'Ü' => 'UE',
        'Ä' => 'AE'
    ]);

    $mapping = [
        'S' => ['SPÖ', 'SPOE'],
        'V' => ['ÖVP', 'OEVP'],
        'F' => ['FPÖ', 'FPOE'],
        'G' => ['GRÜNE', 'GRUENE', 'GRUENEN'],
        'N' => ['NEOS']
    ];

    foreach ($mapping as $code => $patterns) {
        foreach ($patterns as $pattern) {
            if (strpos($normalized, $pattern) !== false) {
                return $code;
            }
        }
    }

    return '';
}

function app_enrich_nr_members_with_profiles(array $members, $cache = null) {
    foreach ($members as $index => $member) {
        if (!is_array($member)) {
            continue;
        }
        $pad = isset($member['pad']) ? preg_replace('/[^0-9]/', '', (string) $member['pad']) : '';
        if ($pad === '') {
            continue;
        }

        if (trim((string) ($member['name'] ?? '')) !== '' && trim((string) ($member['club'] ?? '')) !== '') {
            continue;
        }

        $profile = app_ranking_resolve_person_profile_by_pad($pad, $cache);
        if (!is_array($profile)) {
            continue;
        }
        if (trim((string) ($member['name'] ?? '')) === '' && trim((string) ($profile['name'] ?? '')) !== '') {
            $members[$index]['name'] = trim((string) $profile['name']);
        }
        if (trim((string) ($member['club'] ?? '')) === '' && trim((string) ($profile['party_code'] ?? '')) !== '') {
            $members[$index]['club'] = app_ranking_normalize_frak_code((string) $profile['party_code']);
        }
    }

    return $members;
}

function app_ranking_resolve_person_profile_by_pad($pad, $cache = null) {
    $pad = preg_replace('/[^0-9]/', '', (string) $pad);
    if ($pad === '') {
        return [
            'name' => '',
            'party_code' => '',
            'is_government' => false,
            'is_parliamentarian' => false
        ];
    }

    $cacheKey = 'person_profile_v2_' . $pad;
    if ($cache && method_exists($cache, 'get')) {
        $cached = $cache->get($cacheKey);
        if (is_array($cached)) {
            return [
                'name' => isset($cached['name']) ? trim((string) $cached['name']) : '',
                'party_code' => isset($cached['party_code']) ? trim((string) $cached['party_code']) : '',
                'is_government' => !empty($cached['is_government']),
                'is_parliamentarian' => !empty($cached['is_parliamentarian'])
            ];
        }
    }

    $profile = app_fetch_person_profile_by_pad($pad, 8);
    $normalized = [
        'name' => isset($profile['name']) ? trim((string) $profile['name']) : '',
        'party_code' => isset($profile['party_code']) ? trim((string) $profile['party_code']) : '',
        'is_government' => !empty($profile['is_government']),
        'is_parliamentarian' => !empty($profile['is_parliamentarian'])
    ];

    if ($cache && method_exists($cache, 'set')) {
        if ($normalized['name'] !== '' || $normalized['party_code'] !== '' || $normalized['is_government'] || $normalized['is_parliamentarian']) {
            $cache->set($cacheKey, $normalized, 2592000);
        } else {
            $cache->set($cacheKey, ['name' => '', 'party_code' => '', 'is_government' => false, 'is_parliamentarian' => false], 1800);
        }
    }

    return $normalized;
}

function app_derive_nr_members_from_inquiry_rows($cache = null) {
    $rows = app_fetch_parliament_rows(['XXVIII'], ['J'], 30);
    if (!is_array($rows) || empty($rows)) {
        return [];
    }

    $pads = [];
    foreach ($rows as $row) {
        $padIds = app_parse_jsonish_list(app_get_row_value($row, 20, 'PAD_INTERN'));
        foreach ($padIds as $pad) {
            $pad = preg_replace('/[^0-9]/', '', (string) $pad);
            if ($pad === '') {
                continue;
            }
            if (!in_array($pad, $pads, true)) {
                $pads[] = $pad;
            }
        }
    }

    $members = [];
    foreach ($pads as $pad) {
        $profile = app_ranking_resolve_person_profile_by_pad($pad, $cache);
        if (!is_array($profile) || empty($profile['is_parliamentarian']) || !empty($profile['is_government'])) {
            continue;
        }
        $members[] = [
            'pad' => $pad,
            'name' => isset($profile['name']) ? (string) $profile['name'] : '',
            'club' => isset($profile['party_code']) ? app_ranking_normalize_frak_code((string) $profile['party_code']) : ''
        ];
    }

    return $members;
}

function app_normalize_nr_member_records(array $members) {
    $normalized = [];
    $seenPads = [];

    foreach ($members as $member) {
        if (!is_array($member)) {
            continue;
        }

        $pad = isset($member['pad']) ? preg_replace('/[^0-9]/', '', (string) $member['pad']) : '';
        if ($pad === '' || isset($seenPads[$pad])) {
            continue;
        }

        $name = isset($member['name']) ? trim((string) $member['name']) : '';
        $club = isset($member['club']) ? app_ranking_normalize_frak_code((string) $member['club']) : '';
        $normalized[] = [
            'pad' => $pad,
            'name' => $name,
            'club' => $club
        ];
        $seenPads[$pad] = true;
    }

    usort($normalized, function ($a, $b) {
        return strcasecmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? ''));
    });

    return $normalized;
}

function app_ranking_normalize_frak_code($code) {
    $code = trim((string) $code);
    if ($code === '') {
        return '';
    }

    $upper = mb_strtoupper($code, 'UTF-8');
    $asciiUpper = function_exists('iconv') ? @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $upper) : false;
    if (is_string($asciiUpper) && trim($asciiUpper) !== '') {
        $upper = strtoupper($asciiUpper);
    }

    $compact = preg_replace('/[^A-Z0-9]+/', '', $upper);
    if ($compact === '') {
        return '';
    }

    if ($compact === 'S' || strpos($compact, 'SPO') !== false || strpos($upper, 'SOZIALDEMOKRAT') !== false) {
        return 'S';
    }
    if (
        $compact === 'V'
        || strpos($compact, 'OEVP') !== false
        || strpos($compact, 'OVP') !== false
        || strpos($upper, 'VOLKSPARTEI') !== false
    ) {
        return 'V';
    }
    if ($compact === 'F' || strpos($compact, 'FPO') !== false || strpos($upper, 'FREIHEIT') !== false) {
        return 'F';
    }
    if ($compact === 'G' || strpos($compact, 'GRUEN') !== false || strpos($compact, 'GRUNE') !== false) {
        return 'G';
    }
    if ($compact === 'N' || strpos($compact, 'NEOS') !== false) {
        return 'N';
    }

    return $compact;
}

function app_fetch_written_inquiry_counts_by_members(array $members, array $options = []) {
    $timeout = isset($options['timeout']) ? max(4, (int) $options['timeout']) : 14;
    $retries = isset($options['retries']) ? max(0, (int) $options['retries']) : 1;
    $concurrency = isset($options['concurrency']) ? max(1, (int) $options['concurrency']) : 8;

    $jobs = [];
    foreach ($members as $member) {
        $pad = isset($member['pad']) ? preg_replace('/[^0-9]/', '', (string) $member['pad']) : '';
        if ($pad === '') {
            continue;
        }
        $jobs[] = [
            'pad' => $pad,
            'attempt' => 0
        ];
    }

    $results = [];
    while (!empty($jobs)) {
        $batch = array_splice($jobs, 0, $concurrency);
        $multi = curl_multi_init();
        $handles = [];

        foreach ($batch as $job) {
            $ch = app_build_nr_count_curl_handle($job['pad'], $timeout);
            if (!$ch) {
                $results[$job['pad']] = [
                    'count' => null,
                    'status' => 'failed',
                    'attempts' => $job['attempt'] + 1
                ];
                continue;
            }

            $key = (int) $ch;
            $handles[$key] = [
                'handle' => $ch,
                'pad' => $job['pad'],
                'attempt' => (int) $job['attempt']
            ];
            curl_multi_add_handle($multi, $ch);
        }

        if (!empty($handles)) {
            $running = null;
            do {
                $status = curl_multi_exec($multi, $running);
                if ($running > 0) {
                    curl_multi_select($multi, 1.0);
                }
            } while ($running > 0 && $status === CURLM_OK);

            foreach ($handles as $meta) {
                $ch = $meta['handle'];
                $pad = $meta['pad'];
                $attempt = $meta['attempt'];
                $response = curl_multi_getcontent($ch);
                $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $errno = curl_errno($ch);
                $error = curl_error($ch);

                $parsed = app_parse_nr_count_response($response, $httpCode, $errno, $error);
                if (!empty($parsed['ok'])) {
                    $results[$pad] = [
                        'count' => $parsed['count'],
                        'status' => 'success',
                        'attempts' => $attempt + 1
                    ];
                } else {
                    if ($attempt < $retries) {
                        $jobs[] = [
                            'pad' => $pad,
                            'attempt' => $attempt + 1
                        ];
                    } else {
                        $results[$pad] = [
                            'count' => null,
                            'status' => isset($parsed['status']) ? (string) $parsed['status'] : 'failed',
                            'attempts' => $attempt + 1
                        ];
                    }
                }

                curl_multi_remove_handle($multi, $ch);
                curl_close($ch);
            }
        }

        curl_multi_close($multi);
    }

    return $results;
}

function app_build_nr_count_curl_handle($pad, $timeout = 14) {
    $pad = preg_replace('/[^0-9]/', '', (string) $pad);
    if ($pad === '') {
        return null;
    }

    $payload = [
        'NRBR' => ['NR'],
        'VHG' => ['J_JPR_M'],
        'DOKTYP' => ['J'],
        'PAD_INTERN' => [$pad]
    ];

    $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($encodedPayload)) {
        return null;
    }

    $ch = curl_init(APP_PARL_API_URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedPayload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json,text/plain,*/*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(4, max(1, (int) $timeout)));
    curl_setopt($ch, CURLOPT_TIMEOUT, max(4, (int) $timeout));

    return $ch;
}

function app_parse_nr_count_response($response, $httpCode, $errno, $error) {
    if ((int) $errno === 28) {
        return [
            'ok' => false,
            'status' => 'timeout',
            'count' => null,
            'error' => $error
        ];
    }

    if ($response === false || (int) $httpCode >= 400) {
        return [
            'ok' => false,
            'status' => 'failed',
            'count' => null,
            'error' => $error
        ];
    }

    $decoded = app_decode_mixed_json_response((string) $response);
    if (!is_array($decoded)) {
        return [
            'ok' => false,
            'status' => 'failed',
            'count' => null,
            'error' => 'invalid_json'
        ];
    }

    if (!array_key_exists('count', $decoded) || !is_numeric($decoded['count'])) {
        return [
            'ok' => false,
            'status' => 'failed',
            'count' => null,
            'error' => 'missing_count'
        ];
    }

    return [
        'ok' => true,
        'status' => 'success',
        'count' => max(0, (int) $decoded['count']),
        'error' => ''
    ];
}
