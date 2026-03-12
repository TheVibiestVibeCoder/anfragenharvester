<?php

require_once __DIR__ . '/config.php';

function app_resolve_time_range($requestedTimeRange) {
    $definitions = app_time_range_definitions();
    $timeRange = isset($definitions[$requestedTimeRange]) ? $requestedTimeRange : '12months';
    $definition = $definitions[$timeRange];

    $now = new DateTime();
    $cutoffDate = clone $now;
    $cutoffDate->modify($definition['modify']);

    return [
        'timeRange' => $timeRange,
        'now' => $now,
        'cutoffDate' => $cutoffDate,
        'rangeLabel' => $definition['label'],
        'gpCodes' => $definition['gpCodes']
    ];
}
