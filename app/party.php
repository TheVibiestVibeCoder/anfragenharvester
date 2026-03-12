<?php

require_once __DIR__ . '/config.php';

function app_get_party_code($rowPartyJson) {
    $rowParties = json_decode($rowPartyJson ?? '[]', true);
    if (!is_array($rowParties)) {
        return 'OTHER';
    }

    $partyString = mb_strtoupper(implode(' ', $rowParties));

    if (strpos($partyString, 'SPÖ') !== false || strpos($partyString, 'SPÃ–') !== false || strpos($partyString, 'SOZIALDEMOKRATEN') !== false) {
        return 'S';
    }
    if (strpos($partyString, 'ÖVP') !== false || strpos($partyString, 'Ã–VP') !== false || strpos($partyString, 'VOLKSPARTEI') !== false) {
        return 'V';
    }
    if (strpos($partyString, 'FPÖ') !== false || strpos($partyString, 'FPÃ–') !== false || strpos($partyString, 'FREIHEITLICHE') !== false) {
        return 'F';
    }
    if (strpos($partyString, 'GRÜNE') !== false || strpos($partyString, 'GRÃœNE') !== false) {
        return 'G';
    }
    if (strpos($partyString, 'NEOS') !== false) {
        return 'N';
    }

    return 'OTHER';
}
