<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/app/bootstrap.php';
require_once __DIR__ . '/app/dashboard_service.php';

try {
    $cache = app_bootstrap(__DIR__);
    $viewModel = app_build_dashboard_view_model($_GET, $cache, ['includeAktenDetails' => true]);
    $displayResults = isset($viewModel['displayResults']) && is_array($viewModel['displayResults']) ? $viewModel['displayResults'] : [];

    $items = [];
    foreach ($displayResults as $result) {
        if (!is_array($result)) {
            continue;
        }

        $aktenKey = isset($result['akten_key']) ? trim((string) $result['akten_key']) : '';
        if ($aktenKey === '') {
            $aktenKey = app_build_result_akten_key(
                isset($result['link']) ? $result['link'] : '',
                isset($result['number']) ? $result['number'] : '',
                isset($result['date_obj']) && $result['date_obj'] instanceof DateTime ? $result['date_obj']->format('Y-m-d') : ''
            );
        }

        $items[$aktenKey] = isset($result['akten']) && is_array($result['akten']) ? $result['akten'] : [];
    }

    echo json_encode([
        'ok' => true,
        'count' => count($items),
        'items' => $items
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
