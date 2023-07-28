<?php
function debug($value) {
    print "<xmp style=\"display:block;font:9pt 'Bitstream Vera Sans Mono, Courier New';background:#202020;color:#D2FFD2;padding:10px;margin:5px;overflow:auto;\">";
    switch (gettype($value)) {
        case 'string' :
            echo $value;
            break;
        case 'array' :
        case 'object' :
        default :
            print_r($value);
            break;
    }
    print "</xmp>";
}

function dumpSqlFileSet ($fileName, $arrayDataQuery) {
//	$fileName = '../../' . $fileName . '.sql';
//	$fileName = $fileName . '.sql';

    $writeMode = 'w';

    if (file_exists($fileName)) {
        $writeMode = 'a+';
    }
    $dumpSqlFP = fopen($fileName, $writeMode);

    foreach ($arrayDataQuery as $dataQuery) {
        fwrite($dumpSqlFP, $dataQuery);
        if (substr($dataQuery, -1) != ';') {
            fwrite($dumpSqlFP, ';');
        }
        fwrite($dumpSqlFP, chr(13) . chr(10));
    }

    fclose($dumpSqlFP);
}
?>