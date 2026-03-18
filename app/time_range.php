<?php

require_once __DIR__ . '/config.php';

function app_resolve_time_range($requestedTimeRange, array $queryParams = []) {
    $definitions = app_time_range_definitions();
    $timeRange = isset($definitions[$requestedTimeRange]) ? $requestedTimeRange : '12months';
    $definition = $definitions[$timeRange];

    $now = new DateTime();
    $cutoffDate = clone $now;
    $cutoffDate->modify($definition['modify']);
    $endDate = clone $now;
    $rangeLabel = $definition['label'];
    $gpCodes = $definition['gpCodes'];

    $customFrom = isset($queryParams['from']) ? trim((string) $queryParams['from']) : '';
    $customTo = isset($queryParams['to']) ? trim((string) $queryParams['to']) : '';
    $isCustomRange = false;

    if ($customFrom !== '' && $customTo !== '') {
        $customFromDate = app_parse_iso_date($customFrom);
        $customToDate = app_parse_iso_date($customTo);

        if ($customFromDate instanceof DateTime && $customToDate instanceof DateTime && $customFromDate <= $customToDate) {
            $customFromDate->setTime(0, 0, 0);
            $customToDate->setTime(23, 59, 59);

            $cutoffDate = $customFromDate;
            $endDate = $customToDate;
            $isCustomRange = true;
            $gpCodes = app_collect_all_gp_codes($definitions);
            $rangeLabel = 'Individueller Zeitraum (' . $customFromDate->format('d.m.Y') . ' bis ' . $customToDate->format('d.m.Y') . ')';
        }
    }

    return [
        'timeRange' => $timeRange,
        'now' => $now,
        'cutoffDate' => $cutoffDate,
        'endDate' => $endDate,
        'rangeLabel' => $rangeLabel,
        'gpCodes' => $gpCodes,
        'isCustomRange' => $isCustomRange,
        'customFrom' => $customFrom,
        'customTo' => $customTo
    ];
}

function app_parse_iso_date($value) {
    $value = trim((string) $value);
    if ($value === '') {
        return null;
    }

    $date = DateTime::createFromFormat('Y-m-d', $value);
    if (!$date instanceof DateTime) {
        return null;
    }

    $errors = DateTime::getLastErrors();
    if (is_array($errors) && (($errors['warning_count'] ?? 0) > 0 || ($errors['error_count'] ?? 0) > 0)) {
        return null;
    }

    return $date;
}

function app_collect_all_gp_codes(array $definitions) {
    $allCodes = [];

    foreach ($definitions as $definition) {
        $codes = isset($definition['gpCodes']) && is_array($definition['gpCodes']) ? $definition['gpCodes'] : [];
        foreach ($codes as $code) {
            $code = trim((string) $code);
            if ($code === '' || in_array($code, $allCodes, true)) {
                continue;
            }
            $allCodes[] = $code;
        }
    }

    return $allCodes;
}
