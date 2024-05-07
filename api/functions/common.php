<?php
function jsonify($response) {
    $json     = json_encode($response);
    $tabcount = 0;
    $result   = '';
    $inquote  = false;
    $tab      = "   ";
    $newline  = "\n";

    for ($i = 0; $i < strlen($json); $i++) {
        $char = $json[$i];

        if ($char == '"' && $json[$i - 1] != '\\')
            $inquote = !$inquote;

        if ($inquote) {
            $result.=$char;
            continue;
        }
        switch ($char) {
            case '{':
                if ($i)
                    $result.=$newline;
                    $result.=str_repeat($tab, $tabcount) . $char . $newline . str_repeat($tab, ++$tabcount);
                break;
            case '}':
                $result.=$newline . str_repeat($tab, --$tabcount) . $char;
                break;
            case ',':
                $result.=$char;
                if ($json[$i + 1] != '{')
                    $result.=$newline . str_repeat($tab, $tabcount);
                break;
            default:
                $result.=$char;
        }
    }
    return $result;
}

function xguid() {
    mt_srand((double) microtime() * 10000);
    $charid = strtoupper(md5(uniqid(rand(), true)));
    $hyphen = chr(45);
    $uuid   = substr($charid, 0, 8) . $hyphen . substr($charid, 8, 4) . $hyphen . substr($charid, 12, 4) . $hyphen . substr($charid, 16, 4) . $hyphen . substr($charid, 20, 12);
    return $uuid;
}

function salt() {
	return "39574f3d336b77693a5e7d282e47292c";
}