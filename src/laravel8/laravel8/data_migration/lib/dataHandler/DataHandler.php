<?php
namespace dataHandler;

// 참조하는 자식 객체의 공통 메소드 지정 및 기본값 셋팅을 위한 추상화 클래스
// 변수 선언 가능, 자체 처리 메소드 가능(ex-> 자식 클래스내 강제 사용 메소드 선언 후 자식 클래스 내 메소드 오버라이드 된 경우 부모 클래스에서 강제 실행 가능)
// 추상화 클래스 종류 abstract class, interface

abstract class DataHandler {
    // 접근 지시자 private : 본인클래스만 사용, protected : 본인 및 자식클래스 사용, public : 접근 제한 없음

    protected $arrayQuery          = array();       // 참조하는 자식 클래스에서 활용 할 수 있는 쿼리 배열 변수
    protected $arrayRoopTableInfo  = array();       // 참조하는 자식 클래스에서 활용 할 수 있는 릴레이션 쿼리 코드 정보
    protected $shopNo              = 0;             // 참조하는 자식 클래스에서 활용 할 수 있는 상점 일련번호
    protected $afterDomain         = '';

    // 생성자
    protected function DataHandler() {
           $this::setRoopInfo();                    // 참조하는 자식 클래스에서 오버라이드 된 setRoopInfo 메소드 실행
           $this::setQuery();                       // 참조하는 자식 클래스에서 오버라이드 된 setQuery 메소드 실행
    }

    protected function roopDataSearch($searchValue) { // 루프 데이터를 조회할거를 생각해서 만들었는데 .. 음 안사용할듯...;;
        foreach ($this->arrayRoopTableInfo as $key => $arrayTableInfo) {
            if (in_array($searchValue, $arrayTableInfo)) {
                return $key;
            }
        }
        return false;
    }

    public function addQuery($arrayQuery = array()) { // 객체 호출시 생성된 쿼리외 별도 쿼리 추가
        if (!empty($arrayQuery)) {
            $this->arrayQuery = array_merge($this->arrayQuery, $arrayQuery);
        }
    }

    /*
    본 추상화 클래스를 참조하는 자식 클래스에 강제적으로 추가해야 하는 메소드 설정
    interface 추상화 클래스와 다르게  abstract 키워드 셋팅
    */
    public function setShopNo($shopNo)  // 부모 클래스 선언 변수 shopNo 설정
    {
        $this->shopNo = $shopNo;
    }

    public function getShopNo() // 부모 클래스 선언 변수 shopNo 반납
    {
        return $this->shopNo;
    }

    public function getRoopInfo() // 설정한 릴레이션 쿼리 코드 반납
    {
        return $this->arrayRoopTableInfo;
    }

    public function getQuery() { // 설정한 쿼리코드 반납
        return $this->arrayQuery;
    }

    abstract public function setRoopInfo();

    abstract public function setQuery();
}