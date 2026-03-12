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
