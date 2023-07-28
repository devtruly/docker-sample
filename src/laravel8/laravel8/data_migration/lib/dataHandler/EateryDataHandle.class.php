<?php
$file_server_path = realpath(__FILE__);
$server_path = str_replace(basename(__FILE__), "", $file_server_path);

require_once ($server_path . 'DataHandler.php');
require_once ($server_path . 'DataHandlerInterface.php');

use dataHandler\DataHandler;

/*
    {query code} pattern
    ISRT - Insert Select into Real Table
    UPRT - Update Real Table
    ISTT - Insert Select into Temporary Table
    UPTT - Update Temporary Table

    {table name} pattern : mb_member => Member, pb_product_option => ProductOption

    array key pattern : {query code}_{table name}
*/
/*
    공통 변수, 공통 메소드 사용 및 부모클래스 자동 실행 메소드를 활용하기 위한 abstract class 인 dataHandler extends 상속
    DataHandleController 클래스 내 인스턴스 변수 타입 사용 제한을 위한 DataHandlerInterface implements 상속
 */
class CsInquiryDataHandle extends DataHandler implements DataHandlerInterface {
    // 생성자 선언
    public function CsInquiryDataHandle($shopNo = 0) {
        parent::DataHandler();// 부모 생성자 기본 호출
        if ($shopNo) {  // 부모클래스에서 선언된 protected $shopNo 변수 셋팅
            $this::setShopNo($shopNo);
        }
    }

    public function setRoopInfo($arrayRoopTableInfo = array()) // 동시 처리를 진행 하는 릴레이션 쿼리 코드 설정
    {
        if (!empty($arrayRoopTableInfo)) {
            $this->arrayRoopTableInfo = $arrayRoopTableInfo;
        }
        else {
            $this->arrayRoopTableInfo = array(
                0 => array('ISRT_EateryInfo'),

            );
        }
    }

    public function setQuery()  // 기본 처리 쿼리 설정
    {
        $this->arrayQuery['ISRT_EateryInfo'] = array(
            "query" => "Insert eatery_info (
                    sno, service_name, service_id, autonomous_body_code,
                    manage_no, confirm_date, confirm_cancel_date, status_cd,
                    status_name, detail_status_cd, detail_status_name,
                    shut_down_date, cose_temporarily_start_date,
                    cose_temporarily_end_date, reopening_date, phone,
                    extent, zipcode, address, road_name, roadcode, name,
                    modify_type, modify_date, uptae, location_x, location_y,
                    sanitation_uptae, man_staff_cnt, woman_staff_cnt,
                    surrounding_type, level, waterworks_type, total_staff_cnt,
                    head_office_staff_cnt, factory_office_staff_cnt,
                    factory_sales_staff_cnt, factory_production_staff_cnt,
                    building_have_type, deposit, pay_monthly_rent, multiple_usage_yn,
                    facilities_size, tradition_number, tradition_main_food, homepage
                    ) 
                Select 
                    sno, service_name, service_id, autonomous_body_code,
                    manage_no, confirm_date, confirm_cancel_date, status_cd,
                    status_name, detail_status_cd, detail_status_name,
                    shut_down_date, cose_temporarily_start_date,
                    cose_temporarily_end_date, reopening_date, phone,
                    extent, zipcode, address, road_name, roadcode, name,
                    modify_type, modify_date, uptae, location_x, location_y,
                    sanitation_uptae, man_staff_cnt, woman_staff_cnt,
                    surrounding_type, level, waterworks_type, total_staff_cnt,
                    head_office_staff_cnt, factory_office_staff_cnt,
                    factory_sales_staff_cnt, factory_production_staff_cnt,
                    building_have_type, deposit, pay_monthly_rent, multiple_usage_yn,
                    facilities_size, tradition_number, tradition_main_food, homepage 
                From tmp_eatery_info 
                    Where {between\};",
            "between_column" => "seq",
        );
    }
}


?>