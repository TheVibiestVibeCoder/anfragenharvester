<?php

function app_extract_answer_info($rowTitle) {
    if (preg_match('/beantwortet durch (\d+)\/AB/i', (string) $rowTitle, $matches)) {
        return [
            'answered' => true,
            'answer_number' => $matches[1]
        ];
    }

    return [
        'answered' => false,
        'answer_number' => null
    ];
}

function app_parse_row_date($rowDateStr) {
    $rowDateStr = trim((string) $rowDateStr);
    if ($rowDateStr === '') {
        return null;
    }

    $rowDate = DateTime::createFromFormat('d.m.Y', $rowDateStr);
    if ($rowDate instanceof DateTime) {
        return $rowDate;
    }

    try {
        return new DateTime($rowDateStr);
    } catch (Exception $e) {
        return null;
    }
}

function app_build_answer_link($inquiryLink, $answerNumber, $fallbackGpCode = 'XXVIII') {
    $answerNumber = preg_replace('/[^0-9]/', '', (string) $answerNumber);
    if ($answerNumber === '') {
        return '';
    }

    $gpCode = trim((string) $fallbackGpCode);
    if (preg_match('/\/gegenstand\/([^\/]+)\//', (string) $inquiryLink, $match)) {
        $gpCode = trim((string) $match[1]);
    }
    if ($gpCode === '') {
        $gpCode = 'XXVIII';
    }

    return 'https://www.parlament.gv.at/gegenstand/' . rawurlencode($gpCode) . '/AB/' . rawurlencode($answerNumber);
}

function app_get_row_value($row, $index, $key = null) {
    if (!is_array($row)) {
        return '';
    }

    if (array_key_exists($index, $row)) {
        return $row[$index];
    }

    if ($key !== null && array_key_exists($key, $row)) {
        return $row[$key];
    }

    return '';
}

function app_build_inquiry_link($rowLink) {
    if (empty($rowLink)) {
        return '';
    }

    if (strpos($rowLink, 'http://') === 0 || strpos($rowLink, 'https://') === 0) {
        return $rowLink;
    }

    return 'https://www.parlament.gv.at' . $rowLink;
}

function app_extract_keywords($title, $stopwords) {
    $keywords = [];
    $words = preg_split('/\s+/', mb_strtolower((string) $title));

    foreach ($words as $word) {
        $word = preg_replace('/[^\p{L}\p{N}\-]/u', '', $word);
        $word = trim($word, '-');

        if ($word === '') {
            continue;
        }

        if (mb_strlen($word) < 5 || in_array($word, $stopwords, true) || is_numeric($word)) {
            continue;
        }

        $keywords[] = $word;
    }

    return $keywords;
}

function app_parse_jsonish_list($value) {
    if (is_array($value)) {
        $items = $value;
    } else {
        $raw = trim((string) $value);
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $items = $decoded;
        } else {
            $parts = preg_split('/\s*,\s*/', $raw);
            $items = is_array($parts) ? $parts : [];
        }
    }

    $normalized = [];
    foreach ($items as $item) {
        $text = trim((string) $item);
        if ($text === '') {
            continue;
        }
        if (!in_array($text, $normalized, true)) {
            $normalized[] = $text;
        }
    }

    return $normalized;
}

function app_html_to_plain_text($value) {
    $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $text = preg_replace('/\s+/', ' ', $text);
    return trim((string) $text);
}

function app_extract_pad_from_person_url($url) {
    $url = (string) $url;
    if (preg_match('/\/person\/(\d+)/', $url, $matches)) {
        return $matches[1];
    }

    return '';
}

function app_match_stage_key($text) {
    $normalized = mb_strtolower((string) $text, 'UTF-8');
    $normalized = strtr($normalized, [
        'ä' => 'ae',
        'ö' => 'oe',
        'ü' => 'ue',
        'ß' => 'ss'
    ]);

    if (strpos($normalized, 'einlangen im nationalrat') !== false) {
        return 'einlangen';
    }

    if (strpos($normalized, 'uebermittlung') !== false) {
        return 'uebermittlung';
    }

    if (strpos($normalized, 'mitteilung des einlangens') !== false) {
        return 'mitteilung';
    }

    if (strpos($normalized, 'schriftliche beantwortung') !== false || strpos($normalized, 'beantwortung') !== false) {
        return 'beantwortung';
    }

    return null;
}
