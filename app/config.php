<?php

if (!defined('APP_PARL_API_URL')) {
    define('APP_PARL_API_URL', 'https://www.parlament.gv.at/Filter/api/filter/data/101?js=eval&showAll=true');
}

function app_stopwords() {
    static $stopwords = [
        'der', 'die', 'das', 'den', 'dem', 'des', 'ein', 'eine', 'einer', 'eines', 'einem', 'einen',
        'fur', 'für', 'von', 'mit', 'bei', 'aus', 'nach', 'vor', 'uber', 'über', 'unter', 'durch', 'ohne', 'gegen',
        'und', 'oder', 'aber', 'sondern', 'denn', 'sowie', 'bzw', 'bzw.',
        'ich', 'du', 'er', 'sie', 'es', 'wir', 'ihr', 'diese', 'dieser', 'dieses', 'jene', 'jener',
        'ist', 'sind', 'war', 'waren', 'wird', 'werden', 'wurde', 'wurden', 'sein', 'haben', 'hat', 'hatte',
        'beantwortet', 'beantwortung', 'anfrage', 'anfragen', 'frist', 'offen', 'erledigt',
        'januar', 'februar', 'marz', 'märz', 'april', 'mai', 'juni', 'juli', 'august', 'september', 'oktober', 'november', 'dezember',
        'euro', 'cent', 'prozent',
        'offentliche', 'öffentliche', 'offentlichen', 'öffentlichen', 'offentlicher', 'öffentlicher', 'gelder', 'geld',
        'mehr', 'weniger', 'sehr', 'auch', 'nicht', 'nur', 'noch', 'schon', 'alle', 'jede', 'jeden', 'jedes',
        'welche', 'welcher', 'welches', 'deren', 'dessen', 'wie', 'was', 'wer', 'wann', 'wo', 'warum',
        'etc', 'usw', 'dass', 'damit', 'dazu', 'davon',
        'osterreich', 'österreich', 'verein', 'vereine', 'vereinen', 'forderung', 'förderung', 'forderungen', 'förderungen', 'finanzierung',
        'ihres', 'ressort', 'ressorts', 'bundeskanzler', 'bundesminister', 'ministerin',
        'bereich', 'bereiche', 'bereichs', 'thema', 'themen',
        'manner', 'männer', 'frauen', 'personen', 'person',
        'projekt', 'projekte', 'projekts', 'massnahme', 'maßnahme', 'massnahmen', 'maßnahmen',
        'zeitraum', 'jahr', 'jahre', 'jahren', 'monat', 'monate', 'monaten'
    ];

    return $stopwords;
}

function app_default_party_stats() {
    return ['S' => 0, 'V' => 0, 'F' => 0, 'G' => 0, 'N' => 0, 'OTHER' => 0];
}

function app_party_map() {
    return [
        'S' => 'SPÖ',
        'V' => 'ÖVP',
        'F' => 'FPÖ',
        'G' => 'GRÜNE',
        'N' => 'NEOS',
        'OTHER' => 'ANDERE'
    ];
}

function app_party_colors() {
    return [
        'S' => '#EF4444',
        'V' => '#22D3EE',
        'F' => '#3B82F6',
        'G' => '#22C55E',
        'N' => '#E879F9',
        'OTHER' => '#9CA3AF'
    ];
}

function app_party_names() {
    return [
        'S' => 'SPÖ',
        'V' => 'ÖVP',
        'F' => 'FPÖ',
        'G' => 'GRÜNE',
        'N' => 'NEOS',
        'OTHER' => 'Andere'
    ];
}

function app_time_range_definitions() {
    return [
        '1week' => [
            'modify' => '-1 week',
            'label' => 'Letzte Woche',
            'gpCodes' => ['XXVIII', 'BR']
        ],
        '1month' => [
            'modify' => '-1 month',
            'label' => 'Letzter Monat',
            'gpCodes' => ['XXVIII', 'BR']
        ],
        '3months' => [
            'modify' => '-3 months',
            'label' => 'Letzte 3 Monate',
            'gpCodes' => ['XXVIII', 'BR']
        ],
        '6months' => [
            'modify' => '-6 months',
            'label' => 'Letzte 6 Monate',
            'gpCodes' => ['XXVIII', 'XXVII', 'BR']
        ],
        '12months' => [
            'modify' => '-12 months',
            'label' => 'Letzte 12 Monate',
            'gpCodes' => ['XXVIII', 'XXVII', 'BR']
        ],
        '1year' => [
            'modify' => '-1 year',
            'label' => 'Letztes Jahr',
            'gpCodes' => ['XXVIII', 'XXVII', 'BR']
        ],
        '3years' => [
            'modify' => '-3 years',
            'label' => 'Letzte 3 Jahre',
            'gpCodes' => ['XXVIII', 'XXVII', 'XXVI', 'BR']
        ],
        '5years' => [
            'modify' => '-5 years',
            'label' => 'Letzte 5 Jahre',
            'gpCodes' => ['XXVIII', 'XXVII', 'XXVI', 'XXV', 'BR']
        ]
    ];
}
