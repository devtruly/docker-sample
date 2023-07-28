<?php
	/*
		insertSet 클래스
	*/
class insertSet {
	
	private $multiQueryFl;					// 멀티쿼리 기능 생성
	private $multiMaxCount;					// 한번에 생성할 멀티 쿼리 수
	private $tableName;						// 테이블명
	private $roopCount;						// 멀티 쿼리 최대 등록 갯수;

	private $arrayString;
	private $arrayMultiValue;
	private $arrayInsertQuery;
	private $arrayFieldDefaultValue;

	
	public function __construct($tableName, $tableInfo, $multiQueryFl, $maxCount = 500) {
		$this->multiQueryFl				= $multiQueryFl;
		$this->multiMaxCount			= $maxCount;

		$this->tableName				= $tableName;
		$this->arrayMultiValue			= array();
		$this->arrayInsertQuery			= array();
		$this->arrayFieldDefaultValue 	= $tableInfo;
		$this->roopCount				= 0;
	}

	public function querySet($arrayDataValue) {
		$this->arrayString = array();

		foreach ($this->arrayFieldDefaultValue as $fieldName => $fieldValue) {
			$this->arrayString[$fieldName] = ((string)$arrayDataValue[$fieldName] !== '') ? $arrayDataValue[$fieldName] : $this->arrayFieldDefaultValue[$fieldName];
		}

		if ($this->multiQueryFl == 'n') {
			$queryText = implode(', ', array_map(function ($fieldName, $fieldValue) {
				preg_match('/^([1-9]{1}[0-9]{0,19}|null|now\(\))$/i', $fieldValue, $result);
				preg_match('/^(sha2)\(\'/i', $fieldValue, $result2);

				if ((($fieldName == 'password') && !empty($result2)) || !empty($result)) {
					return $fieldName . ' = ' . $fieldValue;
				}
				else {
					return $fieldName . " = '" . addslashes($fieldValue) .  "'";
				}
			},
				array_keys($this->arrayString),
				$this->arrayString
			));

			$this->arrayInsertQuery[] = "Insert Into " . $this->tableName . " Set " . $queryText . ';';
		}
		else {
			//debug($this->tableName);
			foreach ($this->arrayString as $fieldName => $fieldValue) {
				preg_match('/^([1-9]{1}[0-9]{0,19}|null|now\(\))$/i', $fieldValue, $result);
				preg_match('/^(sha2)\(\'/i', $fieldValue, $result2);

				if ((($fieldName == 'password') && !empty($result2)) || !empty($result)) {
					$this->arrayString[$fieldName] = $fieldValue;
				}
				else {
					$this->arrayString[$fieldName] = "'" . addslashes($fieldValue) . "'";
				}
			}

			$this->arrayMultiValue[] = '(' . implode(',', $this->arrayString) . ')';

			$this->roopCount++;
			if (($this->roopCount % $this->multiMaxCount) == 0) {
				$this->multiQuerySet();
				$this->roopCount = 0;
			}
		}
	}

	private function multiQuerySet() {
		$this->arrayInsertQuery[] = "Insert Into " . $this->tableName . " (" . implode(', ', array_keys($this->arrayFieldDefaultValue)) . ") VALUES " . implode(',', $this->arrayMultiValue) . ';';

		unset($this->arrayMultiValue);
		$this->arrayMultiValue = array();
	}

	public function getQuery ($arrayDataQuery) {
		$arrayReturnQuery = array();
	
		if ($this->multiQueryFl == 'y' && !empty($this->arrayMultiValue)) {
			$this->multiQuerySet();
		}
		
		if (!empty($arrayDataQuery)) {
			$arrayReturnQuery = array_merge($arrayDataQuery, $this->arrayInsertQuery);
		}
		else {
			$arrayReturnQuery = $this->arrayInsertQuery;
		}
		unset($this->arrayInsertQuery);
		$this->arrayInsertQuery = array();

		return $arrayReturnQuery;
	}
}

?>
