<?php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log');
ini_set('memory_limit', '512M');
set_time_limit(0);

require_once __DIR__ . '/app/export_service.php';

try {
    $payload = app_build_export_payload($_GET);
    $requestedFormat = isset($_GET['format']) ? (string) $_GET['format'] : 'xlsx';
    app_stream_export($payload, $requestedFormat);
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Export failed: ' . $e->getMessage();
}
