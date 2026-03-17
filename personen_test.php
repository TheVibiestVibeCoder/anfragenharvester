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

$rangeData = app_resolve_time_range('1week');
$cutoffDate = $rangeData['cutoffDate'];
$now = $rangeData['now'];
$gpCodes = isset($rangeData['gpCodes']) && is_array($rangeData['gpCodes']) ? $rangeData['gpCodes'] : ['XXVIII', 'BR'];

$rows = app_fetch_parliament_rows($gpCodes, ['J', 'JPR'], 30);
$rows = is_array($rows) ? $rows : [];

$inquiries = [];
foreach ($rows as $row) {
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
        'date' => $dateString,
        'date_obj' => $dateObject,
        'title' => trim((string) app_get_row_value($row, 6, 'TITEL')),
        'number' => trim((string) app_get_row_value($row, 7, 'NPARL')),
        'link' => app_build_inquiry_link(app_get_row_value($row, 14, 'LINK')),
        'party' => trim((string) app_get_row_value($row, 21, 'PARTIE')),
        'pad_ids' => app_parse_jsonish_list(app_get_row_value($row, 20, 'PAD_INTERN')),
        'frak_codes' => app_parse_jsonish_list(app_get_row_value($row, 21, 'FRAK_CODE'))
    ];
}

usort($inquiries, function ($a, $b) {
    return $b['date_obj'] <=> $a['date_obj'];
});

$profileCache = [];
$personPayloadCache = [];
$totalPersons = 0;
$historyAvailableCount = 0;

?><!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Personen Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 1rem; background: #f4f6f8; color: #18212b; }
        h1, h2, h3, h4 { margin: 0 0 0.5rem; }
        .meta { margin-bottom: 1rem; padding: 0.8rem; background: #fff; border: 1px solid #d9e1ea; border-radius: 10px; }
        .box { margin-bottom: 1rem; border: 1px solid #d9e1ea; border-radius: 10px; background: #fff; padding: 0.8rem; }
        .muted { color: #556272; font-size: 0.9rem; }
        .na { color: #b13a3a; font-weight: 600; }
        table { width: 100%; border-collapse: collapse; margin-top: 0.5rem; }
        th, td { border: 1px solid #e0e6ee; padding: 0.45rem; text-align: left; vertical-align: top; font-size: 0.9rem; }
        th { width: 260px; background: #f8fafc; font-weight: 600; }
        pre { margin: 0; white-space: pre-wrap; word-break: break-word; font-size: 0.8rem; line-height: 1.35; }
        .person { margin-top: 0.7rem; padding: 0.7rem; border: 1px solid #e0e6ee; border-radius: 8px; background: #fcfdff; }
        .person h4 { margin-bottom: 0.35rem; }
        .pill { display: inline-block; margin-right: 0.35rem; margin-top: 0.2rem; padding: 0.15rem 0.45rem; border-radius: 999px; border: 1px solid #b8c7d9; background: #eef4fa; font-size: 0.8rem; }
        a { color: #0d4a8f; text-decoration: underline; }
        details { margin-bottom: 0.9rem; }
        summary { cursor: pointer; font-weight: 700; }
    </style>
</head>
<body>
    <h1>personen_test.php</h1>
    <div class="meta">
        <div><strong>Zeitraum:</strong> 1 Woche</div>
        <div><strong>Cutoff:</strong> <?php echo pt_h($cutoffDate->format('d.m.Y H:i:s')); ?></div>
        <div><strong>Jetzt:</strong> <?php echo pt_h($now->format('d.m.Y H:i:s')); ?></div>
        <div><strong>GP_CODE Anfrage:</strong> <?php echo pt_h(implode(', ', $gpCodes)); ?></div>
        <div><strong>Rows gesamt von Listen-API:</strong> <?php echo pt_h((string) count($rows)); ?></div>
        <div><strong>Rows im 1-Wochen-Fenster:</strong> <?php echo pt_h((string) count($inquiries)); ?></div>
    </div>

<?php if (empty($inquiries)): ?>
    <div class="box">
        <strong>Keine Anfragen im 1-Wochen-Fenster gefunden.</strong>
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

            $record = [
                'source' => 'geschichtsseite.names',
                'index_in_names' => $entryIndex,
                'names.funktext' => $funktext,
                'names.name' => $name,
                'names.frak_code' => $frakCode,
                'names.ltext' => $ltext,
                'names.url_raw' => $urlRaw,
                'names.url_absolute' => $urlAbsolute,
                'names.portrait' => $portrait,
                'names.extra_fields' => $extraFields,
                'names.raw_entry' => $entry,
                'derived.pad_from_url' => $padFromUrl,
                'derived.role_from_funktext' => pt_role_from_funktext((string) $funktext),
                'list.pad_ids_from_row' => $inquiry['pad_ids'],
                'list.frak_codes_from_row' => $inquiry['frak_codes'],
                'profile.name' => is_array($profile) && isset($profile['name']) ? $profile['name'] : null,
                'profile.party_code' => is_array($profile) && isset($profile['party_code']) ? $profile['party_code'] : null,
                'profile.is_government' => is_array($profile) && array_key_exists('is_government', $profile) ? (bool) $profile['is_government'] : null,
                'profile.is_parliamentarian' => is_array($profile) && array_key_exists('is_parliamentarian', $profile) ? (bool) $profile['is_parliamentarian'] : null,
                'profile.raw_payload_source_url' => is_array($profilePayload) && isset($profilePayload['source_url']) ? $profilePayload['source_url'] : null,
                'profile.raw_payload' => is_array($profilePayload) && isset($profilePayload['payload']) ? $profilePayload['payload'] : null
            ];

            $personRecords[] = $record;
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
                'names.funktext' => null,
                'names.name' => null,
                'names.frak_code' => null,
                'names.ltext' => null,
                'names.url_raw' => null,
                'names.url_absolute' => app_parliament_make_absolute_url('/person/' . $padFromRow),
                'names.portrait' => null,
                'names.extra_fields' => [],
                'names.raw_entry' => null,
                'derived.pad_from_url' => $padFromRow,
                'derived.role_from_funktext' => 'unbekannt',
                'list.pad_ids_from_row' => $inquiry['pad_ids'],
                'list.frak_codes_from_row' => $inquiry['frak_codes'],
                'profile.name' => is_array($profile) && isset($profile['name']) ? $profile['name'] : null,
                'profile.party_code' => is_array($profile) && isset($profile['party_code']) ? $profile['party_code'] : null,
                'profile.is_government' => is_array($profile) && array_key_exists('is_government', $profile) ? (bool) $profile['is_government'] : null,
                'profile.is_parliamentarian' => is_array($profile) && array_key_exists('is_parliamentarian', $profile) ? (bool) $profile['is_parliamentarian'] : null,
                'profile.raw_payload_source_url' => is_array($profilePayload) && isset($profilePayload['source_url']) ? $profilePayload['source_url'] : null,
                'profile.raw_payload' => is_array($profilePayload) && isset($profilePayload['payload']) ? $profilePayload['payload'] : null
            ];
        }

        $totalPersons += count($personRecords);
        $summaryText = ($inquiry['number'] !== '' ? $inquiry['number'] : 'ohne Nummer') . ' | ' . $inquiry['date'] . ' | Personen: ' . count($personRecords);
        ?>

        <details class="box" <?php echo $index < 3 ? 'open' : ''; ?>>
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
            <div>
                <span class="pill">History vorhanden: <?php echo $hasHistory ? 'true' : 'false'; ?></span>
                <span class="pill">content.names Anzahl: <?php echo pt_h((string) count($names)); ?></span>
                <span class="pill">PAD_INTERN Anzahl: <?php echo pt_h((string) count($inquiry['pad_ids'])); ?></span>
            </div>

            <?php if (empty($personRecords)): ?>
                <div class="person">
                    <strong>Keine Personendaten aus API verfuegbar.</strong>
                </div>
            <?php else: ?>
                <?php foreach ($personRecords as $personIndex => $record): ?>
                    <div class="person">
                        <h4>Person <?php echo pt_h((string) ($personIndex + 1)); ?> | Source: <?php echo pt_h((string) $record['source']); ?></h4>
                        <table>
                            <tr><th>source</th><td><?php echo pt_value_html($record['source']); ?></td></tr>
                            <tr><th>index_in_names</th><td><?php echo pt_value_html($record['index_in_names']); ?></td></tr>
                            <tr><th>names.funktext</th><td><?php echo pt_value_html($record['names.funktext']); ?></td></tr>
                            <tr><th>names.name</th><td><?php echo pt_value_html($record['names.name']); ?></td></tr>
                            <tr><th>names.frak_code</th><td><?php echo pt_value_html($record['names.frak_code']); ?></td></tr>
                            <tr><th>names.ltext</th><td><?php echo pt_value_html($record['names.ltext']); ?></td></tr>
                            <tr><th>names.url_raw</th><td><?php echo pt_value_html($record['names.url_raw']); ?></td></tr>
                            <tr><th>names.url_absolute</th><td><?php echo pt_value_html($record['names.url_absolute']); ?></td></tr>
                            <tr><th>names.portrait</th><td><?php echo pt_value_html($record['names.portrait']); ?></td></tr>
                            <tr><th>names.extra_fields</th><td><?php echo pt_value_html($record['names.extra_fields']); ?></td></tr>
                            <tr><th>names.raw_entry</th><td><?php echo pt_value_html($record['names.raw_entry']); ?></td></tr>
                            <tr><th>derived.pad_from_url</th><td><?php echo pt_value_html($record['derived.pad_from_url']); ?></td></tr>
                            <tr><th>derived.role_from_funktext</th><td><?php echo pt_value_html($record['derived.role_from_funktext']); ?></td></tr>
                            <tr><th>list.pad_ids_from_row</th><td><?php echo pt_value_html($record['list.pad_ids_from_row']); ?></td></tr>
                            <tr><th>list.frak_codes_from_row</th><td><?php echo pt_value_html($record['list.frak_codes_from_row']); ?></td></tr>
                            <tr><th>profile.name</th><td><?php echo pt_value_html($record['profile.name']); ?></td></tr>
                            <tr><th>profile.party_code</th><td><?php echo pt_value_html($record['profile.party_code']); ?></td></tr>
                            <tr><th>profile.is_government</th><td><?php echo pt_value_html($record['profile.is_government']); ?></td></tr>
                            <tr><th>profile.is_parliamentarian</th><td><?php echo pt_value_html($record['profile.is_parliamentarian']); ?></td></tr>
                            <tr><th>profile.raw_payload_source_url</th><td><?php echo pt_value_html($record['profile.raw_payload_source_url']); ?></td></tr>
                            <tr><th>profile.raw_payload</th><td><?php echo pt_value_html($record['profile.raw_payload']); ?></td></tr>
                        </table>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </details>
    <?php endforeach; ?>

    <div class="meta">
        <div><strong>History vorhanden bei Anfragen:</strong> <?php echo pt_h((string) $historyAvailableCount); ?> / <?php echo pt_h((string) count($inquiries)); ?></div>
        <div><strong>Gesamtzahl Personenrecords:</strong> <?php echo pt_h((string) $totalPersons); ?></div>
        <div class="muted">Alle leeren Felder werden als N/A angezeigt.</div>
    </div>
<?php endif; ?>
</body>
</html>
