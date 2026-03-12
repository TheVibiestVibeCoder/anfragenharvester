<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/app/parliament_api.php';
require_once __DIR__ . '/app/inquiry_helpers.php';

$gpCodes = ['XXVIII', 'XXVII', 'XXVI', 'XXV', 'BR'];

echo "Calling API...\n";
$response = app_fetch_parliament_response($gpCodes, ['J', 'JPR'], 30);

if (!is_array($response)) {
    echo "API request failed or response was not valid JSON.\n";
    exit(1);
}

$rows = isset($response['rows']) && is_array($response['rows']) ? $response['rows'] : [];

echo "JSON decoded successfully\n";
echo "Type: " . gettype($response) . "\n";
echo "Keys: " . implode(', ', array_keys($response)) . "\n\n";
echo "rows: array with " . count($rows) . " elements\n";

if (!empty($rows)) {
    $firstRow = $rows[0];
    echo "  First row type: " . gettype($firstRow) . "\n";
    if (is_array($firstRow)) {
        echo "  First row has " . count($firstRow) . " elements\n";
        echo "  First row [4] (date): " . app_get_row_value($firstRow, 4, 'DATUM') . "\n";
        echo "  First row [6] (title): " . substr((string) app_get_row_value($firstRow, 6, 'TITEL'), 0, 100) . "\n";
    }
}

$today = date('d.m.Y');
echo "\n  Looking for entries from today ($today)...\n";

$todayEntries = 0;
foreach ($rows as $row) {
    $dateStr = app_get_row_value($row, 4, 'DATUM');
    if ($dateStr === $today) {
        $todayEntries++;
        echo "    Found: $dateStr - " . substr((string) app_get_row_value($row, 6, 'TITEL'), 0, 80) . "...\n";
    }
}

echo "\n  Total entries today: $todayEntries\n";
echo "  Total entries (all time): " . count($rows) . "\n";
