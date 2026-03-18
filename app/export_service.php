<?php

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/party.php';
require_once __DIR__ . '/inquiry_helpers.php';
require_once __DIR__ . '/parliament_api.php';
require_once __DIR__ . '/time_range.php';

function app_build_export_payload(array $queryParams) {
    $requestedRange = isset($queryParams['range']) ? (string) $queryParams['range'] : '12months';
    $rangeData = app_resolve_time_range($requestedRange, $queryParams);

    $timeRange = $rangeData['timeRange'];
    $rangeLabel = $rangeData['rangeLabel'];
    $gpCodes = $rangeData['gpCodes'];
    $cutoffDate = $rangeData['cutoffDate'];
    $endDate = isset($rangeData['endDate']) && $rangeData['endDate'] instanceof DateTime ? $rangeData['endDate'] : $rangeData['now'];
    $now = $rangeData['now'];
    $isCustomRange = !empty($rangeData['isCustomRange']);
    $customFrom = isset($rangeData['customFrom']) ? trim((string) $rangeData['customFrom']) : '';
    $customTo = isset($rangeData['customTo']) ? trim((string) $rangeData['customTo']) : '';
    $docTypes = ['J', 'JPR'];

    $response = app_fetch_parliament_response($gpCodes, $docTypes, 45);
    if (!is_array($response)) {
        throw new RuntimeException('Unable to fetch data from Parliament API.');
    }

    $rows = isset($response['rows']) && is_array($response['rows']) ? $response['rows'] : [];
    $header = isset($response['header']) && is_array($response['header']) ? array_values($response['header']) : [];
    $apiCount = isset($response['count']) ? (int) $response['count'] : count($rows);
    $apiPages = isset($response['pages']) ? (int) $response['pages'] : 1;

    if (empty($header) && !empty($rows)) {
        $discoveredHeader = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            foreach (array_keys($row) as $rowKey) {
                if (!array_key_exists((string) $rowKey, $discoveredHeader)) {
                    $discoveredHeader[(string) $rowKey] = true;
                }
            }
        }
        $header = array_keys($discoveredHeader);
    }

    $maxColumns = count($header);
    foreach ($rows as $row) {
        if (is_array($row)) {
            $maxColumns = max($maxColumns, count($row));
        }
    }

    $columnSpecs = app_build_export_column_specs($header, $maxColumns);
    $columnOrder = array_merge(app_export_base_columns(), array_map(function($spec) {
        return $spec['column'];
    }, $columnSpecs));

    $partyNames = app_party_names();
    $records = [];

    foreach ($rows as $row) {
        $rowDate = app_parse_row_date(app_get_row_value($row, 4, 'DATUM'));
        if (!$rowDate instanceof DateTime) {
            continue;
        }
        if ($rowDate < $cutoffDate) {
            continue;
        }
        if ($rowDate > $endDate) {
            continue;
        }

        $title = trim((string) app_get_row_value($row, 6, 'TITEL'));
        if ($title === '') {
            $title = 'Anfrage ohne Titel';
        }

        $partyCode = app_get_party_code(app_get_row_value($row, 21, 'PARTIE'));
        if (!isset($partyNames[$partyCode])) {
            $partyCode = 'OTHER';
        }

        $inquiryLink = app_build_inquiry_link(app_get_row_value($row, 14, 'LINK'));
        $answerInfo = app_extract_answer_info($title);
        $answerLink = app_build_answer_link($inquiryLink, $answerInfo['answer_number'], app_get_row_value($row, 0, 'GP_CODE'));

        $record = [
            'DATE' => $rowDate->format('d.m.Y'),
            'DATE_ISO' => $rowDate->format('Y-m-d'),
            'INQUIRY_NUMBER' => trim((string) app_get_row_value($row, 7, 'NPARL')),
            'TITLE' => $title,
            'PARTY_CODE' => $partyCode,
            'PARTY_NAME' => isset($partyNames[$partyCode]) ? $partyNames[$partyCode] : $partyCode,
            'ANSWERED' => $answerInfo['answered'] ? 'yes' : 'no',
            'ANSWER_NUMBER' => $answerInfo['answer_number'] !== null ? (string) $answerInfo['answer_number'] : '',
            'INQUIRY_LINK' => $inquiryLink,
            'ANSWER_LINK' => $answerLink
        ];

        foreach ($columnSpecs as $spec) {
            $cellValue = app_export_get_row_cell_value($row, $spec['index'], $spec['source_key']);
            $record[$spec['column']] = app_export_stringify_value($cellValue);
        }

        $records[] = $record;
    }

    usort($records, function($a, $b) {
        $dateCompare = strcmp((string) $b['DATE_ISO'], (string) $a['DATE_ISO']);
        if ($dateCompare !== 0) {
            return $dateCompare;
        }

        return strcmp((string) $a['INQUIRY_NUMBER'], (string) $b['INQUIRY_NUMBER']);
    });

    return [
        'generated_at_utc' => gmdate('Y-m-d H:i:s'),
        'time_range' => $timeRange,
        'range_label' => $rangeLabel,
        'gp_codes' => $gpCodes,
        'doc_types' => $docTypes,
        'cutoff_date' => $cutoffDate->format('Y-m-d'),
        'end_date' => $endDate->format('Y-m-d'),
        'is_custom_range' => $isCustomRange ? 'yes' : 'no',
        'custom_from' => $customFrom,
        'custom_to' => $customTo,
        'current_timestamp' => $now->format('Y-m-d H:i:s'),
        'api_count' => $apiCount,
        'api_pages' => $apiPages,
        'api_row_count' => count($rows),
        'column_order' => $columnOrder,
        'api_column_count' => count($columnSpecs),
        'records' => $records,
        'query_params' => app_export_normalize_query_params($queryParams)
    ];
}

function app_export_base_columns() {
    return [
        'DATE',
        'DATE_ISO',
        'INQUIRY_NUMBER',
        'TITLE',
        'PARTY_CODE',
        'PARTY_NAME',
        'ANSWERED',
        'ANSWER_NUMBER',
        'INQUIRY_LINK',
        'ANSWER_LINK'
    ];
}

function app_build_export_column_specs(array $header, $maxColumns) {
    $columnSpecs = [];
    $seenColumns = [];
    $maxColumns = max(0, (int) $maxColumns);

    for ($i = 0; $i < $maxColumns; $i++) {
        $sourceKey = isset($header[$i]) ? trim((string) $header[$i]) : '';
        if ($sourceKey !== '' && ctype_digit($sourceKey) && (int) $sourceKey === $i) {
            $sourceKey = '';
        }
        $normalized = app_normalize_export_column_name($sourceKey, $i);
        $candidate = 'API_' . $normalized;
        $suffix = 2;
        while (isset($seenColumns[$candidate])) {
            $candidate = 'API_' . $normalized . '_' . $suffix;
            $suffix++;
        }
        $seenColumns[$candidate] = true;

        $columnSpecs[] = [
            'index' => $i,
            'source_key' => $sourceKey,
            'column' => $candidate
        ];
    }

    return $columnSpecs;
}

function app_normalize_export_column_name($name, $index) {
    $name = strtoupper(trim((string) $name));
    $name = preg_replace('/[^A-Z0-9]+/', '_', $name);
    $name = trim((string) $name, '_');

    if ($name === '') {
        $name = 'COL_' . str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT);
    }

    if (preg_match('/^[0-9]/', $name)) {
        $name = 'C_' . $name;
    }

    return $name;
}

function app_export_get_row_cell_value($row, $index, $sourceKey = '') {
    if (!is_array($row)) {
        return '';
    }

    if (array_key_exists($index, $row)) {
        return $row[$index];
    }

    if ($sourceKey !== '' && array_key_exists($sourceKey, $row)) {
        return $row[$sourceKey];
    }

    return '';
}

function app_export_normalize_query_params(array $queryParams) {
    $normalized = [];
    foreach ($queryParams as $key => $value) {
        $normalized[(string) $key] = app_export_stringify_value($value);
    }

    ksort($normalized);
    return $normalized;
}

function app_export_stringify_value($value) {
    if (is_array($value) || is_object($value)) {
        $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : '';
    }

    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }

    if ($value === null) {
        return '';
    }

    return (string) $value;
}

function app_stream_export(array $payload, $requestedFormat = 'xlsx') {
    $format = strtolower((string) $requestedFormat);
    if (!in_array($format, ['xlsx', 'csv'], true)) {
        $format = 'xlsx';
    }

    $filenameBase = 'parlaments_anfragen_' . $payload['time_range'] . '_' . gmdate('Ymd_His');

    if ($format === 'xlsx') {
        if (class_exists('ZipArchive') && app_stream_xlsx_export($payload, $filenameBase . '.xlsx')) {
            return;
        }

        app_stream_excel_xml_export($payload, $filenameBase . '.xml');
        return;
    }

    app_stream_csv_export($payload, $filenameBase . '.csv');
}

function app_stream_xlsx_export(array $payload, $filename) {
    $settingsRows = app_build_export_settings_rows($payload);
    $resultRows = app_build_export_result_rows($payload);

    $tmpDir = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'anfragen_export_' . str_replace('.', '_', uniqid('', true));
    if (!@mkdir($tmpDir, 0700, true) && !is_dir($tmpDir)) {
        return false;
    }

    $sheet1Path = $tmpDir . DIRECTORY_SEPARATOR . 'sheet1.xml';
    $sheet2Path = $tmpDir . DIRECTORY_SEPARATOR . 'sheet2.xml';
    $zipPath = $tmpDir . DIRECTORY_SEPARATOR . 'export.xlsx';

    try {
        if (!app_write_xlsx_worksheet_xml($sheet1Path, $settingsRows)) {
            return false;
        }
        if (!app_write_xlsx_worksheet_xml($sheet2Path, $resultRows)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return false;
        }

        $sheet1Name = app_excel_safe_sheet_name('Settings');
        $sheet2Name = app_excel_safe_sheet_name('Results');

        $zip->addFromString('[Content_Types].xml', app_build_xlsx_content_types_xml());
        $zip->addFromString('_rels/.rels', app_build_xlsx_root_rels_xml());
        $zip->addFromString('xl/workbook.xml', app_build_xlsx_workbook_xml($sheet1Name, $sheet2Name));
        $zip->addFromString('xl/_rels/workbook.xml.rels', app_build_xlsx_workbook_rels_xml());
        $zip->addFile($sheet1Path, 'xl/worksheets/sheet1.xml');
        $zip->addFile($sheet2Path, 'xl/worksheets/sheet2.xml');
        $zip->close();

        if (!is_file($zipPath)) {
            return false;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . filesize($zipPath));

        readfile($zipPath);
        return true;
    } finally {
        app_remove_export_temp_path($sheet1Path);
        app_remove_export_temp_path($sheet2Path);
        app_remove_export_temp_path($zipPath);
        app_remove_export_temp_path($tmpDir);
    }
}

function app_stream_csv_export(array $payload, $filename) {
    $settingsRows = app_build_export_settings_rows($payload);
    $resultRows = app_build_export_result_rows($payload);

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    $output = fopen('php://output', 'wb');
    if ($output === false) {
        return;
    }

    fwrite($output, "\xEF\xBB\xBF");
    fputcsv($output, ['SETTINGS'], ';');
    foreach ($settingsRows as $row) {
        fputcsv($output, $row, ';');
    }

    fputcsv($output, [], ';');
    fputcsv($output, ['RESULTS'], ';');
    foreach ($resultRows as $row) {
        fputcsv($output, $row, ';');
    }

    fclose($output);
}

function app_stream_excel_xml_export(array $payload, $filename) {
    $settingsRows = app_build_export_settings_rows($payload);
    $resultRows = app_build_export_result_rows($payload);

    header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');

    echo '<?xml version="1.0" encoding="UTF-8"?>';
    echo '<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet"';
    echo ' xmlns:o="urn:schemas-microsoft-com:office:office"';
    echo ' xmlns:x="urn:schemas-microsoft-com:office:excel"';
    echo ' xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet">';

    app_write_excel_xml_worksheet('Settings', $settingsRows);
    app_write_excel_xml_worksheet('Results', $resultRows);

    echo '</Workbook>';
}

function app_write_excel_xml_worksheet($sheetName, array $rows) {
    echo '<Worksheet ss:Name="' . app_export_xml_escape(app_excel_safe_sheet_name($sheetName)) . '">';
    echo '<Table>';

    foreach ($rows as $row) {
        if (!is_array($row)) {
            $row = [app_export_stringify_value($row)];
        }

        echo '<Row>';
        foreach ($row as $cellValue) {
            echo '<Cell><Data ss:Type="String">' . app_export_xml_escape($cellValue) . '</Data></Cell>';
        }
        echo '</Row>';
    }

    echo '</Table>';
    echo '</Worksheet>';
}

function app_build_export_settings_rows(array $payload) {
    $rows = [
        ['SETTING', 'VALUE'],
        ['Export generated at (UTC)', (string) $payload['generated_at_utc']],
        ['Selected range key', (string) $payload['time_range']],
        ['Selected range label', (string) $payload['range_label']],
        ['Start date (inclusive)', (string) $payload['cutoff_date']],
        ['End date (inclusive)', (string) $payload['end_date']],
        ['Custom range active', (string) $payload['is_custom_range']],
        ['Custom range from', (string) $payload['custom_from']],
        ['Custom range to', (string) $payload['custom_to']],
        ['Current timestamp (server)', (string) $payload['current_timestamp']],
        ['API endpoint', APP_PARL_API_URL],
        ['Document types', implode(', ', (array) $payload['doc_types'])],
        ['GP codes', implode(', ', (array) $payload['gp_codes'])],
        ['API count field', (string) $payload['api_count']],
        ['API pages field', (string) $payload['api_pages']],
        ['Raw API rows returned', (string) $payload['api_row_count']],
        ['Rows exported after date filtering', (string) count($payload['records'])],
        ['API columns exported', (string) $payload['api_column_count']]
    ];

    if (!empty($payload['query_params'])) {
        $rows[] = [];
        $rows[] = ['QUERY_PARAM', 'VALUE'];
        foreach ($payload['query_params'] as $key => $value) {
            $rows[] = [(string) $key, (string) $value];
        }
    }

    return $rows;
}

function app_build_export_result_rows(array $payload) {
    $rows = [];
    $columnOrder = isset($payload['column_order']) && is_array($payload['column_order']) ? $payload['column_order'] : [];

    if (empty($columnOrder)) {
        $columnOrder = app_export_base_columns();
    }

    $rows[] = $columnOrder;

    foreach ($payload['records'] as $record) {
        $row = [];
        foreach ($columnOrder as $column) {
            $row[] = isset($record[$column]) ? app_export_stringify_value($record[$column]) : '';
        }
        $rows[] = $row;
    }

    return $rows;
}

function app_write_xlsx_worksheet_xml($filePath, array $rows) {
    $handle = fopen($filePath, 'wb');
    if ($handle === false) {
        return false;
    }

    $ok = true;
    $ok = $ok && fwrite($handle, '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>') !== false;
    $ok = $ok && fwrite($handle, '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>') !== false;

    $rowNumber = 1;
    foreach ($rows as $row) {
        if (!is_array($row)) {
            $row = [app_export_stringify_value($row)];
        }

        $ok = $ok && fwrite($handle, '<row r="' . $rowNumber . '">') !== false;

        $columnNumber = 1;
        foreach ($row as $cellValue) {
            $cellRef = app_excel_column_name($columnNumber) . $rowNumber;
            $cellText = app_export_xml_escape(app_export_stringify_value($cellValue));
            $cellXml = '<c r="' . $cellRef . '" t="inlineStr"><is><t xml:space="preserve">' . $cellText . '</t></is></c>';
            $ok = $ok && fwrite($handle, $cellXml) !== false;
            $columnNumber++;
        }

        $ok = $ok && fwrite($handle, '</row>') !== false;
        $rowNumber++;
    }

    $ok = $ok && fwrite($handle, '</sheetData></worksheet>') !== false;
    fclose($handle);

    return $ok;
}

function app_build_xlsx_content_types_xml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
        . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
        . '<Default Extension="xml" ContentType="application/xml"/>'
        . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
        . '</Types>';
}

function app_build_xlsx_root_rels_xml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
        . '</Relationships>';
}

function app_build_xlsx_workbook_xml($sheet1Name, $sheet2Name) {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
        . '<sheets>'
        . '<sheet name="' . app_export_xml_escape($sheet1Name) . '" sheetId="1" r:id="rId1"/>'
        . '<sheet name="' . app_export_xml_escape($sheet2Name) . '" sheetId="2" r:id="rId2"/>'
        . '</sheets>'
        . '</workbook>';
}

function app_build_xlsx_workbook_rels_xml() {
    return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
        . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
        . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
        . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
        . '</Relationships>';
}

function app_excel_column_name($number) {
    $number = (int) $number;
    if ($number < 1) {
        return 'A';
    }

    $column = '';
    while ($number > 0) {
        $remainder = ($number - 1) % 26;
        $column = chr(65 + $remainder) . $column;
        $number = (int) floor(($number - 1) / 26);
    }

    return $column;
}

function app_excel_safe_sheet_name($name) {
    $name = preg_replace('/[\\\\\\/?*\\[\\]:]/', ' ', (string) $name);
    $name = trim((string) $name);
    if ($name === '') {
        $name = 'Sheet';
    }

    if (strlen($name) > 31) {
        $name = substr($name, 0, 31);
    }

    return $name;
}

function app_export_xml_escape($value) {
    $value = app_export_stringify_value($value);

    if (function_exists('mb_check_encoding') && !mb_check_encoding($value, 'UTF-8') && function_exists('mb_convert_encoding')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }

    $value = preg_replace('/[^\x09\x0A\x0D\x20-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $value);
    if ($value === null) {
        $value = '';
    }

    return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
}

function app_remove_export_temp_path($path) {
    if (!file_exists($path)) {
        return;
    }

    if (is_dir($path)) {
        @rmdir($path);
        return;
    }

    @unlink($path);
}
