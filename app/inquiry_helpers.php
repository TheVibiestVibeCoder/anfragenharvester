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
