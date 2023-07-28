<?php
ini_set('memory_limit', -1);
ini_set('max_execution_time', 0);

include_once "./lib/db.class.php";
include_once "./lib/insertSet.class.php";
include_once "./lib/lib.func.php";

$dumpFileName = './test.sql';
unlink($dumpFileName);
$arrayQueryPostData = array();

$arrayIntType = array(
    'tinyint',
    'smallint',
    'mediumint',
    'int',
    'bigint',
    'decimal',
    'float',
    'double',
    'bit',
    'real',
    'bool',
    'boolean',
    'serial',
);
$intType = implode('|', $arrayIntType);



$targetTable = 'eatery_info';
$targetTempTable = "tmp_{$targetTable}";

$dropTempTableQuery = "Drop Temporary Table If Exists {$targetTempTable};";
//$arrayQueryPostData[] = $dropTempTableQuery;
$db = new db(arrayDBInfo: array(
    'host' => 'mysql8',
    'name' => 'test',
    'user' => 'root',
    'pass' => 'love1004'
    )
);
$result = $db->query("show create table {$targetTable};");

$createTableRow = $result->fetch_assoc();
$createTableQuery = $createTableRow['Create Table'];
$createTableQuery = preg_replace("/^(create)[[:space:]](table)[[:space:]](`)($targetTable`)[[:space:]](\(\\n)([^`]+)/i", "$1 TEMPORARY $2 IF NOT EXISTS $3tmp_$4 $5$6`seq` int NOT NULL COMMENT '임시 생성 테이블 일련번호'," . chr(13) . '$6', $createTableQuery);
$createTableQuery = preg_replace("/primary[[:space:]]key[[:space:]]\(`[a-zA-Z0-9_-]{1,}\`\)/i", "PRIMARY KEY (`mig_seq`)", $createTableQuery);

$result = $db->query("
    SELECT *
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = SCHEMA()
          AND TABLE_NAME = 'eatery_info'
        ORDER BY ORDINAL_POSITION;
");

$arrayDataDefaultValue = array();
//$arrayDataDefaultValue['mig_seq'] = '';
while ($tableFieldInfoRow = $result->fetch_assoc()) {
    $defaultValue = '';
    if ($tableFieldInfoRow['IS_NULLABLE'] == 'NO') {
        preg_match("/($intType)/i", $tableFieldInfoRow['COLUMN_TYPE'], $matchResult);
        if (!empty($matchResult)) {
            $defaultValue = 1;
        }
        else {
            $defaultValue = $tableFieldInfoRow['COLUMN_DEFAULT'];
        }
    }
    else {
        $defaultValue = 'NULL';
    }
    $arrayDataDefaultValue[$tableFieldInfoRow['COLUMN_NAME']] = $defaultValue;
}


$insertSet				= new insertSet('eatery_info', $arrayDataDefaultValue, 'y');

$arrayNewData       = array();
$dataRow           = array();

$csvFilePath = './ori_data/';

$dataCnt = 1;

$fp = fopen($csvFilePath . 'fulldata.csv', 'r' );
$tt = fgetcsv($fp, 1500000, ',');
while($dataRow = fgetcsv($fp, 1500000, ',')) {
    foreach ($dataRow as $key => $value) {
        if (mb_detect_encoding($dataRow[$key], 'EUC-KR')) {
            $dataRow[$key] = iconv('euc-kr', 'utf-8', $dataRow[$key]);
        }
    }

    $arrayNewData = $arrayDataDefaultValue;
    $fieldCnt = 0;
    foreach ($arrayNewData as $key => $value) {
        $arrayNewData[$key] = $dataRow[$fieldCnt];
        $fieldCnt++;
    }

    $insertSet->querySet($arrayNewData);
    if ((($dataCnt) % 1000) == 0) {
        debug(1);
        ob_flush();
        flush();
        $arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);

        dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);

        unset($arrayQueryPostData);
        $arrayQueryPostData = array();
    }
    $dataCnt++;
}

$arrayQueryPostData = $insertSet->getQuery($arrayQueryPostData);

dumpSqlFileSet ($dumpFileName, $arrayQueryPostData);

?>
