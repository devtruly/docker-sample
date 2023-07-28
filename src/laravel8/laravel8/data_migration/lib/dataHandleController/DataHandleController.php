<?php
$file_server_path = realpath(__FILE__);
$server_path = str_replace(basename(__FILE__), "", $file_server_path);

include $server_path . '../Exception/ClassCastException.php';
include $server_path . '../Exception/BetweenNullPointerException.php';

/**
 * Class DataHandleController
 * caution :
 *  쿼리 패턴 where 절로 시작 하고 필수적으로 ";" 종결 후 줄바꿈 없이 문자열 종료 처리
 *  ex)
 *    O -> "select * from ttt where mall_no = 123;"
 *    X -> "select * from ttt where mall_no = 123"
 *    X ->
 *      "
 *          select * from ttt where mall_no = 123;
 *      "
 */
class DataHandleController {
    private $dataHandle;                            // 인스턴스시점 전달 받는 DataHandle 객체 조건 : DataHandlerInterface를 implements 상속해야함.
    private $roopInfo;                              // DataHandle내 테이블 별 루프 횟수 조정을 위한 (table code, max count, roop count 형태 배열)
    private $arrayQuery;                            // 전달 받는 DataHandle 객체 내 선언되어 있는 arrayQuery Controller객체 내에서 처리 할 수 있도록 대입하는 변수
    private $shopNoPattern = "/\{shopNo\}/";        // shopNo(mall_no) 치환을 위한 패턴
    private $likeDomainPattern = "/\{likeDomain\}/";    // like '$afterDomain%' 치환을 위한 패턴
    private $roopPattern = "/(where)([^\;]+);$/i";  // 분할 처리를 위한 between절 추가를 목적으로 생성 된 패턴
    private $betweenPattern = '/[[:space:]](between)[[:space:]](\{startNo\})[[:space:]](and)[[:space:]](\{endNo\})/'; // 추가 생성 된 between절 내 시작 값과 종료값을 셋팅하기 위한 패턴
    private $keyPattern = "/^(ISRT|UPRT|ISTT|UPTT)\_([^0-9]+)[0-9]{0,1}$/"; // 객체내 전달 받아 셋팅하는 roopInfo내 table code를 단순화 하기 위한 패턴

    /*
     * 객체 선언 DataHandle 객체를 전달받아야 함 DataHandlerInterface를 상속하지 않은 객체 전달시 Casting 익셉션 처리
     */
    public function DataHandleController($dataHandle = "") {
        if (!$dataHandle instanceof DataHandlerInterface) {
            throw new ClassCastException();
        }
        $this->dataHandle = $dataHandle;
    }

    /*
     * 전달 받은 객체 내 쿼리 정보를 이용하여 분할 처리 쿼리 생성 후 반납
     */
    public function setQuery() {
        $arrayReturnQuery   = array();  // 분할 및 상점 정보를 치환 한 쿼리를 저장할 배열 변수
        $arrayRoopFlag      = array();  // 테이블 별 불필요한 중복 루프를 방지 하기 위한 배열 변수

        $this->arrayQuery = $this->dataHandle->getQuery();      // 전달 받은 객체내 미리생성되어 있는 쿼리 정보 자체 쿼리정보 변수에 대입
        $arrayRoopTable = $this->dataHandle->getRoopInfo();                 // 전달받은 객체의 분할 작업 테이블 정보 대입

        foreach ($arrayRoopTable as $relationInfo) {          // 분할 작업 테이블 조회를 위한 루프
            $arrayNotRoopQuery  = array();
            $arrayRelationQuery = array();

            $roopInfo = $this->roopInfo[preg_replace($this->keyPattern, '$2', $relationInfo[0])];
            $maxSequence = ($roopInfo[0]) ? $roopInfo[0] : 1;            // 전달된 카운팅 시퀀스 정보 중 최대값
            $oneRoopCnt = ($roopInfo[1]) ? $roopInfo[1] : 1;             // 분할 1회당 처리할 건수

            for ($startNo = 1; $startNo <= ($maxSequence); $startNo += $oneRoopCnt) {       // 분할 쿼리 카운팅별 생성
                $endNo = $startNo + $oneRoopCnt - 1;

                foreach ($relationInfo as $relationKey) {
                    if (!$arrayRoopFlag[$relationKey] && !empty($this->arrayQuery[$relationKey])) {                        // 최초 쿼리 셋팅 실행
                        $arrayRoopFlag[$relationKey] = $roopFlag = true;        // 최초 셋팅 완료 처리
                        $this->arrayQuery[$relationKey]['query'] = $this->wherePetternSet($this->arrayQuery[$relationKey]['query']);  // 쿼리 내 {shopNo} 형태로 생성 된 문자열 전달받은 객체내 존재하는 shopNo(mall_no) 값으로 치환

                        if ($this->arrayQuery[$relationKey]['between_column'] && !empty($roopInfo)) {
                            $this->arrayQuery[$relationKey]['query'] = $this->roopQuerySet($this->arrayQuery[$relationKey]['query'], $this->arrayQuery[$relationKey]['between_column']);     // between 절 추가 삽입
                        }
                        else {
                            $arrayNotRoopQuery[] = $this->arrayQuery[$relationKey]['query'];
                            unset($this->arrayQuery[$relationKey]);
                        }
                    }

                    if (!empty($this->arrayQuery[$relationKey])) {
                        $arrayRelationQuery[] = preg_replace($this->betweenPattern, " $1 $startNo $3 $endNo", $this->arrayQuery[$relationKey]['query']); // 추가 된 between 절 시작값, 종료값 셋팅가 후 반납 배열 변수 삽입
                    }
                }
            }

            $arrayReturnQuery = array_merge($arrayReturnQuery, $arrayNotRoopQuery, $arrayRelationQuery);
        }

        $this->dataHandle = null;
        return $arrayReturnQuery;   // 생성 쿼리 반납.
    }
    /*public function setQuery() {
        $arrayReturnQuery   = array();  // 분할 및 상점 정보를 치환 한 쿼리를 저장할 배열 변수
        $arrayRoopFlag      = array();  // 테이블 별 불필요한 중복 루프를 방지 하기 위한 배열 변수

        $this->arrayQuery = $this->dataHandle->getQuery();      // 전달 받은 객체내 미리생성되어 있는 쿼리 정보 자체 쿼리정보 변수에 대입

        // 쿼리 정보 루프 실행(테이블코드 => 쿼리정보)
        foreach ($this->arrayQuery as $key => $arrayQuery) {
            $roopFlag           = false; // 분할이 불필요한 쿼리 정보를 가지고 있는 쿼리문을 판별하기 위한 flag 변수

            if ($arrayRoopFlag[$key]) { // 셋팅할 쿼리문이 이미 타 테이블과 연결 되어 셋팅 처리 된 경우 넘기기
                continue;
            }

            $arrayQuery['query'] = $this->wherePetternSet($arrayQuery['query']);  // 쿼리 내 {shopNo} 형태로 생성 된 문자열 전달받은 객체내 존재하는 shopNo(mall_no) 값으로 치환

            $roopInfo = $this->roopInfo[preg_replace($this->keyPattern, '$2', $key)]; // 입력 받은 루프 카운팅 정보를 연동하기 위한 치환
            $arrayRoopTable = $this->dataHandle->getRoopInfo();                 // 전달받은 객체의 분할 작업 테이블 정보 대입
            foreach ($arrayRoopTable as $roopTableKey => $roopTable) {          // 분할 작업 테이블 조회를 위한 루프
                if (in_array($key, $roopTable) && $arrayQuery['between_column'] && !empty($roopInfo)) {     // 분할 쿼리 여부 체크, 분할을 위한 필드 설정 여부, 카운팅 정보 조회 가 만족되는 경우 분할 시작
                    $maxSequence = $roopInfo[0];            // 전달된 카운팅 시퀀스 정보 중 최대값
                    $oneRoopCnt = $roopInfo[1];             // 분할 1회당 처리할 건수

                    if (!$arrayRoopFlag[$key]) {            // between절 삽입이 되어 있지 않은 경우 실행
                        $arrayRoopFlag[$key] = $roopFlag = true;        // between 절 삽입 된 것으로 설정
                        $this->arrayQuery[$key] = $this->roopQuerySet($arrayQuery['query'], $arrayQuery['between_column']);     // between 절 추가 삽입
                        array_splice($roopTable, array_search($key, $roopTable), 1);            // 분할 릴레이션 테이블 정보 중 최초 분할 작업이 실행 되는 테이블 정보 삭제
                    }

                    for ($startNo = 1; $startNo <= ($maxSequence); $startNo += $oneRoopCnt) {       // 분할 쿼리 카운팅별 생성
                        $endNo = $startNo + $oneRoopCnt - 1;                                        // 분할 별 종료 카운팅 계산
                        $arrayReturnQuery[] = preg_replace($this->betweenPattern, " $1 $startNo $3 $endNo", $this->arrayQuery[$key]); // 추가 된 between 절 시작값, 종료값 셋팅가 후 반납 배열 변수 삽입

                        if (!empty($roopTable)) {       // 분할 처리시 함께 분할 처리 하는 쿼리 정보 확인
                            foreach ($roopTable as $relationTableKey) {     // 분할 처리 테이블 쿼리 종류 만큼 루프
                                $this->arrayQuery[$relationTableKey]['query'] = $this->wherePetternSet($this->arrayQuery[$relationTableKey]['query']); // 릴레이션 쿼리 shopNo 생성
                                try {
                                    if ($this->arrayQuery[$relationTableKey]['between_column']) {   // 분할 조정을 위한 컬럼 확인, 미존재시 BetweenNullPointerException 발생
                                        if (!$arrayRoopFlag[$relationTableKey]) {              // 릴레이션 쿼리 내 between 쿼리 생성 여부 체크
                                            $arrayRoopFlag[$relationTableKey] = true;           // between 쿼리 생성 설정
                                            $this->arrayQuery[$relationTableKey]['query'] = $this->roopQuerySet($this->arrayQuery[$relationTableKey]['query'], $this->arrayQuery[$relationTableKey]['between_column']); // between 절 추가 삽입
                                        }

                                        $arrayReturnQuery[] = preg_replace($this->betweenPattern, " $1 $startNo $3 $endNo", $this->arrayQuery[$relationTableKey]['query']); // 추가 된 between 절 시작값, 종료값 셋팅가 후 반납 배열 변수 삽입
                                    }
                                    else {
                                        throw new BetweenNullPointerException();
                                    }
                                }
                                catch (BetweenNullPointerException $e) {
                                    $e->printStackTrace();
                                }

                            }
                        }
                    }
                    unset($arrayRoopTable[$roopTableKey]);      // 중복 방지를 위해 처리된 쿼리 코드 중복 체크 변수 내 삭제
                    break;
                }
            }

            if (!$roopFlag) {
                $arrayReturnQuery[] = $arrayQuery['query'];     // 분할 쿼리가 아닌 경우 반납 배열 변수 쿼리 추가(즉, 분할 쿼리에서 삽입 되지 않은 나머지 쿼리 삽입)
            }
        }

        $this->dataHandle = null;
        return $arrayReturnQuery;   // 생성 쿼리 반납.
    }*/

    // 객체 인스턴스 페이지내에서 전달 받을 수 있는 분할 카운팅 정보 셋팅 해당 셋팅 후 setQuery 실행 필요
    public function setRoopInfo($key, $maxCount, $roopCount) {
        $this->roopInfo[$key] = array($maxCount, $roopCount);
    }

    // setQuery실행 시 between절 추가 삽입 메소드
    public function roopQuerySet($query, $betweenColumn) {
        $targetPettern = '/\{between\}/i';
        if (preg_match($targetPettern, $query, $result)) {
            return preg_replace($targetPettern, $betweenColumn . ' between {startNo} and {endNo}', $query);
        }
        else {
            return preg_replace($this->roopPattern, '$1$2' . ' and ' . $betweenColumn . ' between {startNo} and {endNo};', $query);
        }
    }

    // setQuery 실행 시 shopNo 셋팅 메소드
    public function wherePetternSet($query) {
        $query = preg_replace($this->shopNoPattern, $this->dataHandle->getShopNo(), $query);
        if (method_exists($this->dataHandle, 'getAfterDomain')) {
            $query = preg_replace($this->likeDomainPattern, " like '" . $this->dataHandle->getAfterDomain() . "%'", $query);
        }
        return $query;
    }
}
/*
try {
    $dataHandleController = new DataHandleController(new MemberDataHandle(2969));

    $dataHandleController->setRoopInfo('Member', 50000, 10000);
    $dataHandleController->setRoopInfo('Address', 50000, 10000);
    $dataHandleController->setRoopInfo('Accumulation', 50000, 10000);
    $dataHandleController->setRoopInfo('MemberProvider', 50000, 10000);

    $arrayTest = $dataHandleController->setQuery();
    echo '<pre>';
    print_r($arrayTest);
    echo '</pre>';

}
catch (ClassCastException $e) {
    $e->printStackTrace();
}
*/
?>