<?php

require_once __DIR__ . '/config.php';

function app_fetch_parliament_response($gpCodes, $docTypes = ['J', 'JPR'], $timeout = 15) {
    $payload = [
        'GP_CODE' => $gpCodes,
        'VHG' => ['J_JPR_M'],
        'DOKTYP' => $docTypes
    ];

    $ch = curl_init(APP_PARL_API_URL);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);

    if ($response === false || !empty($error) || $httpCode >= 400) {
        error_log('Parliament API request failed: HTTP ' . $httpCode . ' | Error: ' . $error);
        return null;
    }

    $decoded = json_decode($response, true);
    if (!is_array($decoded)) {
        error_log('Parliament API response decode failed.');
        return null;
    }

    return $decoded;
}

function app_fetch_parliament_rows($gpCodes, $docTypes = ['J', 'JPR'], $timeout = 15) {
    $response = app_fetch_parliament_response($gpCodes, $docTypes, $timeout);
    if (!is_array($response) || !isset($response['rows']) || !is_array($response['rows'])) {
        return [];
    }

    return $response['rows'];
}

function app_fetch_geschichtsseite_response($inquiryLink, $timeout = 15) {
    $baseUrl = app_parliament_make_absolute_url($inquiryLink);
    if ($baseUrl === '') {
        return null;
    }

    $candidateUrls = app_build_geschichtsseite_candidate_urls($baseUrl);
    foreach ($candidateUrls as $candidateUrl) {
        $payload = app_fetch_json_get($candidateUrl, $timeout);
        if (!is_array($payload)) {
            continue;
        }

        if (isset($payload['content']) && is_array($payload['content'])) {
            return $payload;
        }
    }

    return null;
}

function app_fetch_json_get($url, $timeout = 15) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json,text/plain,*/*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(3, max(1, (int) $timeout)));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($error) || $httpCode >= 400) {
        return null;
    }

    return app_decode_mixed_json_response($response);
}

function app_decode_mixed_json_response($response) {
    if (!is_string($response)) {
        return null;
    }

    $trimmed = trim($response);
    if ($trimmed === '') {
        return null;
    }

    $decoded = json_decode($trimmed, true);
    if (is_array($decoded)) {
        return $decoded;
    }

    $start = strpos($trimmed, '{');
    $end = strrpos($trimmed, '}');
    if ($start === false || $end === false || $end <= $start) {
        return null;
    }

    $snippet = substr($trimmed, $start, $end - $start + 1);
    $decoded = json_decode($snippet, true);
    return is_array($decoded) ? $decoded : null;
}

function app_parliament_make_absolute_url($url) {
    $url = trim((string) $url);
    if ($url === '') {
        return '';
    }

    if (strpos($url, 'http://') === 0 || strpos($url, 'https://') === 0) {
        return $url;
    }

    if ($url[0] === '/') {
        return 'https://www.parlament.gv.at' . $url;
    }

    return 'https://www.parlament.gv.at/' . ltrim($url, '/');
}

function app_build_geschichtsseite_candidate_urls($baseUrl) {
    $baseUrl = trim((string) $baseUrl);
    if ($baseUrl === '') {
        return [];
    }

    $candidates = [
        app_append_query_param($baseUrl, 'outputMode', 'jsontemplate'),
        app_append_query_param($baseUrl, 'outputMode', 'json'),
        app_append_query_param($baseUrl, 'js', 'eval'),
        rtrim($baseUrl, '/') . '/json',
        rtrim($baseUrl, '/') . '.json',
        $baseUrl
    ];

    $unique = [];
    foreach ($candidates as $candidate) {
        $candidate = trim((string) $candidate);
        if ($candidate === '' || isset($unique[$candidate])) {
            continue;
        }
        $unique[$candidate] = true;
    }

    return array_keys($unique);
}

function app_append_query_param($url, $name, $value) {
    $separator = strpos($url, '?') !== false ? '&' : '?';
    return $url . $separator . rawurlencode((string) $name) . '=' . rawurlencode((string) $value);
}

function app_fetch_text_get($url, $timeout = 12) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: text/html,application/xhtml+xml,application/json,text/plain,*/*',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, min(3, max(1, (int) $timeout)));
    curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false || !empty($error) || $httpCode >= 400) {
        return '';
    }

    return (string) $response;
}

function app_fetch_person_name_by_pad($pad, $timeout = 12) {
    $profile = app_fetch_person_profile_by_pad($pad, $timeout);
    return isset($profile['name']) ? (string) $profile['name'] : '';
}

function app_fetch_person_profile_by_pad($pad, $timeout = 12) {
    $pad = preg_replace('/[^0-9]/', '', (string) $pad);
    if ($pad === '') {
        return [
            'name' => '',
            'party_code' => '',
            'is_government' => false,
            'is_parliamentarian' => false
        ];
    }

    $baseUrl = 'https://www.parlament.gv.at/person/' . rawurlencode($pad);
    $candidates = [
        app_append_query_param($baseUrl, 'outputMode', 'jsontemplate'),
        app_append_query_param($baseUrl, 'outputMode', 'json'),
        $baseUrl
    ];

    $best = [
        'name' => '',
        'party_code' => '',
        'is_government' => false,
        'is_parliamentarian' => false
    ];

    foreach ($candidates as $candidateUrl) {
        $payload = app_fetch_json_get($candidateUrl, $timeout);
        if (is_array($payload)) {
            $candidate = app_extract_person_profile_from_payload($payload);
            $best = app_merge_person_profiles($best, $candidate);
        }
    }

    if ($best['name'] === '' || $best['party_code'] === '' || $best['is_government'] === false) {
        $html = app_fetch_text_get($baseUrl, $timeout);
        if ($html !== '') {
            $candidate = app_extract_person_profile_from_html($html);
            $best = app_merge_person_profiles($best, $candidate);
        }
    }

    return $best;
}

function app_merge_person_profiles(array $base, array $candidate) {
    if (!isset($base['name'])) {
        $base['name'] = '';
    }
    if (!isset($base['party_code'])) {
        $base['party_code'] = '';
    }
    if (!isset($base['is_government'])) {
        $base['is_government'] = false;
    }
    if (!isset($base['is_parliamentarian'])) {
        $base['is_parliamentarian'] = false;
    }

    if (isset($candidate['name']) && trim((string) $candidate['name']) !== '') {
        $base['name'] = trim((string) $candidate['name']);
    }
    if (isset($candidate['party_code']) && trim((string) $candidate['party_code']) !== '') {
        $base['party_code'] = trim((string) $candidate['party_code']);
    }
    if (!empty($candidate['is_government'])) {
        $base['is_government'] = true;
    }
    if (!empty($candidate['is_parliamentarian'])) {
        $base['is_parliamentarian'] = true;
    }

    return $base;
}

function app_extract_person_profile_from_payload(array $payload) {
    $name = app_extract_person_name_from_payload($payload);
    $partyCode = '';

    $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (is_string($json) && $json !== '') {
        if (preg_match('/"frak_code"\s*:\s*"([^"]+)"/i', $json, $m)) {
            $partyCode = trim((string) $m[1]);
        } elseif (preg_match('/"fraktion"\s*:\s*"([^"]+)"/i', $json, $m)) {
            $partyCode = trim((string) $m[1]);
        } elseif (preg_match('/"klub"\s*:\s*"([^"]+)"/i', $json, $m)) {
            $partyCode = trim((string) $m[1]);
        }
    }

    $roleText = '';
    if (isset($payload['meta']['description'])) {
        $roleText .= ' ' . (string) $payload['meta']['description'];
    }
    if (isset($payload['content']['description'])) {
        $roleText .= ' ' . (string) $payload['content']['description'];
    }
    if (isset($payload['content']['title'])) {
        $roleText .= ' ' . (string) $payload['content']['title'];
    }
    if (is_string($json) && $json !== '') {
        $roleText .= ' ' . $json;
    }

    return [
        'name' => $name,
        'party_code' => $partyCode,
        'is_government' => app_text_indicates_government_role($roleText),
        'is_parliamentarian' => app_text_indicates_parliamentarian_role($roleText)
    ];
}

function app_extract_person_profile_from_html($html) {
    return [
        'name' => app_extract_person_name_from_html($html),
        'party_code' => app_extract_person_party_code_from_html($html),
        'is_government' => app_text_indicates_government_role($html),
        'is_parliamentarian' => app_text_indicates_parliamentarian_role($html)
    ];
}

function app_extract_person_party_code_from_html($html) {
    $html = (string) $html;
    if (preg_match('/"frak_code"\s*:\s*"([^"]+)"/i', $html, $m)) {
        return trim((string) $m[1]);
    }
    if (preg_match('/"fraktion"\s*:\s*"([^"]+)"/i', $html, $m)) {
        return trim((string) $m[1]);
    }
    if (preg_match('/"klub"\s*:\s*"([^"]+)"/i', $html, $m)) {
        return trim((string) $m[1]);
    }

    return '';
}

function app_text_indicates_government_role($text) {
    $text = mb_strtolower((string) $text, 'UTF-8');
    $text = strtr($text, [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    ]);

    $keywords = [
        'bundesminister',
        'ministerin',
        'minister',
        'staatssekretaer',
        'bundeskanzler',
        'rechnungshof',
        'praesidentin des nationalrates',
        'praesident des nationalrates',
        'ausschussvorsitz'
    ];

    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function app_text_indicates_parliamentarian_role($text) {
    $text = mb_strtolower((string) $text, 'UTF-8');
    $text = strtr($text, [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    ]);

    $keywords = [
        'abgeordnete zum nationalrat',
        'abgeordneter zum nationalrat',
        'mitglied des bundesrates',
        'mitglied des nationalrates',
        'parlamentarier',
        'nationalrat',
        'bundesrat'
    ];

    foreach ($keywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }

    return false;
}

function app_extract_person_name_from_payload(array $payload) {
    if (isset($payload['content']['name'])) {
        $name = app_cleanup_person_title($payload['content']['name']);
        if ($name !== '') {
            return $name;
        }
    }

    if (isset($payload['meta']['title'])) {
        $name = app_cleanup_person_title($payload['meta']['title']);
        if ($name !== '') {
            return $name;
        }
    }

    if (isset($payload['meta']['openGraph']['title'])) {
        $name = app_cleanup_person_title($payload['meta']['openGraph']['title']);
        if ($name !== '') {
            return $name;
        }
    }

    return '';
}

function app_extract_person_name_from_html($html) {
    $html = (string) $html;

    if (preg_match('/<meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $matches)) {
        $name = app_cleanup_person_title($matches[1]);
        if ($name !== '') {
            return $name;
        }
    }

    if (preg_match('/<title>(.*?)<\/title>/is', $html, $matches)) {
        $name = app_cleanup_person_title($matches[1]);
        if ($name !== '') {
            return $name;
        }
    }

    return '';
}

function app_cleanup_person_title($rawTitle) {
    $title = html_entity_decode((string) $rawTitle, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $title = preg_replace('/\s+/', ' ', $title);
    $title = trim((string) $title);
    if ($title === '') {
        return '';
    }

    $title = preg_replace('/\s*\|\s*Parlament.*$/u', '', $title);
    $title = trim((string) $title);

    if ($title === '' || mb_strlen($title, 'UTF-8') < 3) {
        return '';
    }

    if (stripos($title, 'parlament') !== false && strpos($title, ' ') === false) {
        return '';
    }

    return $title;
}
