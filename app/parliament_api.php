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
