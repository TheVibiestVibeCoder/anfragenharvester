<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

require_once __DIR__ . '/app/config.php';
require_once __DIR__ . '/app/time_range.php';
require_once __DIR__ . '/app/parliament_api.php';
require_once __DIR__ . '/app/inquiry_helpers.php';

function pt_h($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function pt_is_blank($value) {
    if ($value === null) {
        return true;
    }
    if (is_string($value)) {
        return trim($value) === '';
    }
    if (is_array($value)) {
        return count($value) === 0;
    }
    return false;
}

function pt_to_string_or_na($value) {
    if (pt_is_blank($value)) {
        return 'N/A';
    }
    if (is_bool($value)) {
        return $value ? 'true' : 'false';
    }
    if (is_scalar($value)) {
        return (string) $value;
    }

    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if (!is_string($json) || trim($json) === '') {
        return 'N/A';
    }

    return $json;
}

function pt_value_html($value) {
    $stringValue = pt_to_string_or_na($value);
    if ($stringValue === 'N/A') {
        return '<span class="na">N/A</span>';
    }

    $isStructured = is_array($value) || is_object($value) || strpos($stringValue, "\n") !== false || strlen($stringValue) > 130;
    if ($isStructured) {
        return '<pre>' . pt_h($stringValue) . '</pre>';
    }

    return '<span>' . pt_h($stringValue) . '</span>';
}

function pt_role_from_funktext($funktext) {
    $text = mb_strtolower((string) $funktext, 'UTF-8');
    $text = strtr($text, [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    ]);

    if (strpos($text, 'eingebracht von') !== false) {
        return 'eingebracht_von';
    }
    if (strpos($text, 'eingebracht an') !== false) {
        return 'eingebracht_an';
    }

    return 'unbekannt';
}

function pt_fetch_person_profile_cached($pad, array &$cache) {
    $cleanPad = preg_replace('/[^0-9]/', '', (string) $pad);
    if ($cleanPad === '') {
        return null;
    }
    if (array_key_exists($cleanPad, $cache)) {
        return $cache[$cleanPad];
    }

    $profile = app_fetch_person_profile_by_pad($cleanPad, 10);
    $cache[$cleanPad] = is_array($profile) ? $profile : null;
    return $cache[$cleanPad];
}

function pt_fetch_person_payload_cached($pad, array &$cache) {
    $cleanPad = preg_replace('/[^0-9]/', '', (string) $pad);
    if ($cleanPad === '') {
        return null;
    }
    if (array_key_exists($cleanPad, $cache)) {
        return $cache[$cleanPad];
    }

    $baseUrl = 'https://www.parlament.gv.at/person/' . rawurlencode($cleanPad);
    $candidateUrls = [
        app_append_query_param($baseUrl, 'outputMode', 'jsontemplate'),
        app_append_query_param($baseUrl, 'outputMode', 'json'),
        $baseUrl
    ];

    foreach ($candidateUrls as $candidateUrl) {
        $payload = app_fetch_json_get($candidateUrl, 10);
        if (is_array($payload)) {
            $cache[$cleanPad] = [
                'source_url' => $candidateUrl,
                'payload' => $payload
            ];
            return $cache[$cleanPad];
        }
    }

    $cache[$cleanPad] = null;
    return null;
}

function pt_flatten_parameters($prefix, $value, array &$rows) {
    if (is_object($value)) {
        $value = get_object_vars($value);
    }

    if (is_array($value)) {
        if (count($value) === 0) {
            $rows[] = [
                'parameter' => $prefix,
                'value' => []
            ];
            return;
        }

        foreach ($value as $key => $childValue) {
            $keyString = (string) $key;
            if ($prefix === '') {
                $childPrefix = $keyString;
            } else {
                $childPrefix = is_int($key)
                    ? $prefix . '[' . $keyString . ']'
                    : $prefix . '.' . $keyString;
            }

            pt_flatten_parameters($childPrefix, $childValue, $rows);
        }
        return;
    }

    $rows[] = [
        'parameter' => $prefix,
        'value' => $value
    ];
}

function pt_parameter_description($parameter) {
    $parameter = (string) $parameter;
    $normalized = preg_replace('/\[\d+\]/', '[]', $parameter);

    $exact = [
        'meta.number' => 'Nummer der Anfrage (NPARL).',
        'meta.date' => 'Datum der Anfrage aus der Listen-API.',
        'meta.title' => 'Titel/Betreff der Anfrage.',
        'meta.link' => 'Vollständiger Link zur Anfrage auf parlament.gv.at.',
        'meta.party' => 'Parteicode aus der Listen-API.',
        'meta.gp_code' => 'Gesetzgebungsperiode/GP-Code der Anfrage.',
        'meta.row_index' => 'Interner Index der Anfrage in dieser Testausgabe.',
        'meta.time_window.cutoff' => 'Start des betrachteten Zeitfensters (inklusive).',
        'meta.time_window.now' => 'Zeitpunkt der Skriptausführung.',
        'meta.time_window.range_label' => 'Lesbarer Name des Zeitfensters.',
        'list.pad_ids' => 'PAD-IDs aus der Listen-API-Zeile.',
        'list.frak_codes' => 'Fraktionscodes aus der Listen-API-Zeile.',
        'history.available' => 'Ob zur Anfrage eine Geschichtsseiten-Antwort vorliegt.',
        'history.names_count' => 'Anzahl `content.names` aus der Geschichtsseite.',
        'derived.person_records_count' => 'Anzahl der erzeugten Personen-Datensätze.',
        'derived.history_stage_matches' => 'Erkannte Verfahrensstufen aus `history.content.stages`.'
    ];

    if (isset($exact[$normalized])) {
        return $exact[$normalized];
    }

    $prefixes = [
        'list.row_raw.' => 'Rohfeld direkt aus der Listen-API-Zeile (numerisch oder benannt).',
        'history.response_raw.' => 'Rohfeld aus der vollständigen Geschichtsseiten-Antwort.',
        'history.content.' => 'Feld innerhalb von `content` aus der Geschichtsseite.',
        'people.records[].source' => 'Quelle des Personenrecords (`geschichtsseite.names` oder `list.pad_intern_only`).',
        'people.records[].names.' => 'Rohfeld aus `content.names` der Geschichtsseite.',
        'people.records[].derived.' => 'Abgeleitete Information (z. B. Rolle oder PAD aus URL).',
        'people.records[].list.' => 'Referenzwerte aus der Listen-API-Zeile der Anfrage.',
        'people.records[].profile.' => 'Aufgelöstes Profil zur PAD-ID (Personen-Endpunkt).',
        'people.records[].profile.raw_payload' => 'Komplette Rohantwort des Personen-Endpunkts.'
    ];

    foreach ($prefixes as $prefix => $description) {
        if (strpos($normalized, $prefix) === 0) {
            return $description;
        }
    }

    return 'Rohparameter aus API/Verarbeitung (keine spezifische Beschreibung hinterlegt).';
}

function pt_collect_person_records(array $names, array $inquiry, array &$profileCache, array &$personPayloadCache) {
    $personRecords = [];
    $padsFromNames = [];
    $knownNameKeys = ['funktext', 'name', 'frak_code', 'ltext', 'url', 'portrait'];

    foreach ($names as $entryIndex => $entry) {
        $entry = is_array($entry) ? $entry : [];
        $funktext = isset($entry['funktext']) ? $entry['funktext'] : null;
        $name = isset($entry['name']) ? $entry['name'] : null;
        $frakCode = isset($entry['frak_code']) ? $entry['frak_code'] : null;
        $ltext = isset($entry['ltext']) ? $entry['ltext'] : null;
        $urlRaw = isset($entry['url']) ? $entry['url'] : null;
        $urlAbsolute = app_parliament_make_absolute_url((string) $urlRaw);
        $portrait = isset($entry['portrait']) ? $entry['portrait'] : null;
        $padFromUrl = app_extract_pad_from_person_url($urlAbsolute);
        if ($padFromUrl !== '') {
            $padsFromNames[] = $padFromUrl;
        }

        $profile = pt_fetch_person_profile_cached($padFromUrl, $profileCache);
        $profilePayload = pt_fetch_person_payload_cached($padFromUrl, $personPayloadCache);

        $extraFields = [];
        foreach ($entry as $field => $value) {
            if (!in_array((string) $field, $knownNameKeys, true)) {
                $extraFields[(string) $field] = $value;
            }
        }

        $personRecords[] = [
            'source' => 'geschichtsseite.names',
            'index_in_names' => $entryIndex,
            'names' => [
                'funktext' => $funktext,
                'name' => $name,
                'frak_code' => $frakCode,
                'ltext' => $ltext,
                'url_raw' => $urlRaw,
                'url_absolute' => $urlAbsolute,
                'portrait' => $portrait,
                'extra_fields' => $extraFields,
                'raw_entry' => $entry
            ],
            'derived' => [
                'pad_from_url' => $padFromUrl,
                'role_from_funktext' => pt_role_from_funktext((string) $funktext)
            ],
            'list' => [
                'pad_ids_from_row' => $inquiry['pad_ids'],
                'frak_codes_from_row' => $inquiry['frak_codes']
            ],
            'profile' => [
                'name' => is_array($profile) && isset($profile['name']) ? $profile['name'] : null,
                'party_code' => is_array($profile) && isset($profile['party_code']) ? $profile['party_code'] : null,
                'is_government' => is_array($profile) && array_key_exists('is_government', $profile) ? (bool) $profile['is_government'] : null,
                'is_parliamentarian' => is_array($profile) && array_key_exists('is_parliamentarian', $profile) ? (bool) $profile['is_parliamentarian'] : null,
                'raw_payload_source_url' => is_array($profilePayload) && isset($profilePayload['source_url']) ? $profilePayload['source_url'] : null,
                'raw_payload' => is_array($profilePayload) && isset($profilePayload['payload']) ? $profilePayload['payload'] : null
            ]
        ];
    }

    foreach ($inquiry['pad_ids'] as $padFromRow) {
        $padFromRow = preg_replace('/[^0-9]/', '', (string) $padFromRow);
        if ($padFromRow === '' || in_array($padFromRow, $padsFromNames, true)) {
            continue;
        }

        $profile = pt_fetch_person_profile_cached($padFromRow, $profileCache);
        $profilePayload = pt_fetch_person_payload_cached($padFromRow, $personPayloadCache);

        $personRecords[] = [
            'source' => 'list.pad_intern_only',
            'index_in_names' => null,
            'names' => [
                'funktext' => null,
                'name' => null,
                'frak_code' => null,
                'ltext' => null,
                'url_raw' => null,
                'url_absolute' => app_parliament_make_absolute_url('/person/' . $padFromRow),
                'portrait' => null,
                'extra_fields' => [],
                'raw_entry' => null
            ],
            'derived' => [
                'pad_from_url' => $padFromRow,
                'role_from_funktext' => 'unbekannt'
            ],
            'list' => [
                'pad_ids_from_row' => $inquiry['pad_ids'],
                'frak_codes_from_row' => $inquiry['frak_codes']
            ],
            'profile' => [
                'name' => is_array($profile) && isset($profile['name']) ? $profile['name'] : null,
                'party_code' => is_array($profile) && isset($profile['party_code']) ? $profile['party_code'] : null,
                'is_government' => is_array($profile) && array_key_exists('is_government', $profile) ? (bool) $profile['is_government'] : null,
                'is_parliamentarian' => is_array($profile) && array_key_exists('is_parliamentarian', $profile) ? (bool) $profile['is_parliamentarian'] : null,
                'raw_payload_source_url' => is_array($profilePayload) && isset($profilePayload['source_url']) ? $profilePayload['source_url'] : null,
                'raw_payload' => is_array($profilePayload) && isset($profilePayload['payload']) ? $profilePayload['payload'] : null
            ]
        ];
    }

    return $personRecords;
}

$rangeData = app_resolve_time_range('1week');
$cutoffDate = $rangeData['cutoffDate'];
$now = $rangeData['now'];
$rangeLabel = isset($rangeData['rangeLabel']) ? (string) $rangeData['rangeLabel'] : '1 Woche';
$gpCodes = isset($rangeData['gpCodes']) && is_array($rangeData['gpCodes']) ? $rangeData['gpCodes'] : ['XXVIII', 'BR'];

$rows = app_fetch_parliament_rows($gpCodes, ['J', 'JPR'], 30);
$rows = is_array($rows) ? $rows : [];

$inquiries = [];
foreach ($rows as $rowIndex => $row) {
    $dateString = trim((string) app_get_row_value($row, 4, 'DATUM'));
    $dateObject = app_parse_row_date($dateString);
    if (!$dateObject instanceof DateTime) {
        continue;
    }
    if ($dateObject < $cutoffDate) {
        continue;
    }

    $inquiries[] = [
        'row' => $row,
        'row_index' => $rowIndex,
        'date' => $dateString,
        'date_obj' => $dateObject,
        'title' => trim((string) app_get_row_value($row, 6, 'TITEL')),
        'number' => trim((string) app_get_row_value($row, 7, 'NPARL')),
        'link' => app_build_inquiry_link(app_get_row_value($row, 14, 'LINK')),
        'party' => trim((string) app_get_row_value($row, 21, 'PARTIE')),
        'gp_code' => trim((string) app_get_row_value($row, 0, 'GP_CODE')),
        'pad_ids' => app_parse_jsonish_list(app_get_row_value($row, 20, 'PAD_INTERN')),
        'frak_codes' => app_parse_jsonish_list(app_get_row_value($row, 21, 'FRAK_CODE'))
    ];
}

usort($inquiries, function ($a, $b) {
    return $b['date_obj'] <=> $a['date_obj'];
});

$profileCache = [];
$personPayloadCache = [];
$totalFlatParameters = 0;
$historyAvailableCount = 0;

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>test.php - API Volltest</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 1rem; background: #f4f6f8; color: #18212b; }
        h1, h2, h3, h4 { margin: 0 0 0.5rem; }
        .meta { margin-bottom: 1rem; padding: 0.8rem; background: #fff; border: 1px solid #d9e1ea; border-radius: 10px; }
        .box { margin-bottom: 1rem; border: 1px solid #d9e1ea; border-radius: 10px; background: #fff; padding: 0.8rem; }
        .muted { color: #556272; font-size: 0.9rem; }
        .na { color: #b13a3a; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th, td { border: 1px solid #e0e6ee; padding: 0.45rem; text-align: left; vertical-align: top; font-size: 0.86rem; }
        th { background: #f8fafc; font-weight: 600; }
        pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 0.78rem; line-height: 1.35; }
        .pill { display: inline-block; margin-right: 0.35rem; margin-top: 0.2rem; padding: 0.15rem 0.45rem; border-radius: 999px; border: 1px solid #b8c7d9; background: #eef4fa; font-size: 0.8rem; }
        a { color: #0d4a8f; text-decoration: underline; }
        details { margin-bottom: 0.9rem; }
        summary { cursor: pointer; font-weight: 700; }
        .param-col { width: 26%; }
        .desc-col { width: 28%; }
        .value-col { width: 46%; }
    </style>
</head>
<body>
    <h1>test.php</h1>
    <div class="meta">
        <div><strong>Zeitraum:</strong> <?php echo pt_h($rangeLabel); ?></div>
        <div><strong>Cutoff:</strong> <?php echo pt_h($cutoffDate->format('d.m.Y H:i:s')); ?></div>
        <div><strong>Jetzt:</strong> <?php echo pt_h($now->format('d.m.Y H:i:s')); ?></div>
        <div><strong>GP_CODE Anfrage:</strong> <?php echo pt_h(implode(', ', $gpCodes)); ?></div>
        <div><strong>Rows gesamt von Listen-API:</strong> <?php echo pt_h((string) count($rows)); ?></div>
        <div><strong>Rows im Zeitfenster:</strong> <?php echo pt_h((string) count($inquiries)); ?></div>
    </div>

<?php if (empty($inquiries)): ?>
    <div class="box">
        <strong>Keine Anfragen im Zeitfenster gefunden.</strong>
    </div>
<?php else: ?>
    <?php foreach ($inquiries as $index => $inquiry): ?>
        <?php
        $historyResponse = app_fetch_geschichtsseite_response($inquiry['link'], 12);
        $content = is_array($historyResponse) && isset($historyResponse['content']) && is_array($historyResponse['content'])
            ? $historyResponse['content']
            : [];
        $names = isset($content['names']) && is_array($content['names']) ? $content['names'] : [];
        $hasHistory = !empty($content);
        if ($hasHistory) {
            $historyAvailableCount++;
        }

        $personRecords = pt_collect_person_records($names, $inquiry, $profileCache, $personPayloadCache);

        $stageMatches = [];
        $historyStages = isset($content['stages']) && is_array($content['stages']) ? $content['stages'] : [];
        foreach ($historyStages as $stageNode) {
            if (!is_array($stageNode)) {
                continue;
            }
            $stageText = app_html_to_plain_text(isset($stageNode['text']) ? $stageNode['text'] : '');
            $matched = app_match_stage_key($stageText);
            if ($matched !== null) {
                $stageMatches[] = $matched;
            }
        }

        $payload = [
            'meta' => [
                'number' => $inquiry['number'],
                'date' => $inquiry['date'],
                'title' => $inquiry['title'],
                'link' => $inquiry['link'],
                'party' => $inquiry['party'],
                'gp_code' => $inquiry['gp_code'],
                'row_index' => $inquiry['row_index'],
                'time_window' => [
                    'cutoff' => $cutoffDate->format('c'),
                    'now' => $now->format('c'),
                    'range_label' => $rangeLabel
                ]
            ],
            'list' => [
                'pad_ids' => $inquiry['pad_ids'],
                'frak_codes' => $inquiry['frak_codes'],
                'row_raw' => $inquiry['row']
            ],
            'history' => [
                'available' => $hasHistory,
                'names_count' => count($names),
                'response_raw' => $historyResponse,
                'content' => $content
            ],
            'people' => [
                'records' => $personRecords
            ],
            'derived' => [
                'person_records_count' => count($personRecords),
                'history_stage_matches' => $stageMatches
            ]
        ];

        $flatParams = [];
        pt_flatten_parameters('', $payload, $flatParams);
        usort($flatParams, function ($a, $b) {
            return strcmp((string) $a['parameter'], (string) $b['parameter']);
        });
        $totalFlatParameters += count($flatParams);

        $summaryText = ($inquiry['number'] !== '' ? $inquiry['number'] : 'ohne Nummer')
            . ' | ' . $inquiry['date']
            . ' | Parameter: ' . count($flatParams);
        ?>

        <details class="box" <?php echo $index < 2 ? 'open' : ''; ?>>
            <summary><?php echo pt_h($summaryText); ?></summary>
            <h2 style="margin-top:0.7rem;"><?php echo pt_h($inquiry['title'] !== '' ? $inquiry['title'] : 'Anfrage ohne Titel'); ?></h2>
            <p class="muted">
                Link:
                <?php if (trim((string) $inquiry['link']) !== ''): ?>
                    <a href="<?php echo pt_h($inquiry['link']); ?>" target="_blank" rel="noopener noreferrer"><?php echo pt_h($inquiry['link']); ?></a>
                <?php else: ?>
                    <span class="na">N/A</span>
                <?php endif; ?>
            </p>

            <table>
                <tr>
                    <th class="param-col">Parameter</th>
                    <th class="desc-col">Bedeutung</th>
                    <th class="value-col">Wert</th>
                </tr>
                <?php foreach ($flatParams as $paramRow): ?>
                    <?php
                    $parameterName = isset($paramRow['parameter']) ? (string) $paramRow['parameter'] : '';
                    $parameterValue = isset($paramRow['value']) ? $paramRow['value'] : null;
                    $parameterDescription = pt_parameter_description($parameterName);
                    ?>
                    <tr>
                        <td><code><?php echo pt_h($parameterName); ?></code></td>
                        <td><?php echo pt_h($parameterDescription); ?></td>
                        <td><?php echo pt_value_html($parameterValue); ?></td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </details>
    <?php endforeach; ?>

    <div class="meta">
        <div><strong>History vorhanden bei Anfragen:</strong> <?php echo pt_h((string) $historyAvailableCount); ?> / <?php echo pt_h((string) count($inquiries)); ?></div>
        <div><strong>Gesamtzahl Parameterzeilen:</strong> <?php echo pt_h((string) $totalFlatParameters); ?></div>
        <div class="muted">Leere Felder werden als N/A angezeigt. Unbekannte Felder bleiben als Rohparameter sichtbar.</div>
    </div>
<?php endif; ?>
</body>
</html>

