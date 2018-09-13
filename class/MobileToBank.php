<?php

class MobileToBank {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     *  Give user instructions
     *  && 
     *  Ask User to go home
     */

    function mobileToBankStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['USSD_BODY'] = $sessdetails['lang']['mpesa_to_bank'];// .$sessdetails['lang']['go_home'] ;
            $responseArr['END_OF_SESSION'] = 'False';

            $data['submenuselected'] = $sessdetails['submenuselected']; //Because we hadn't saved it before we passed it from menuStepThree
            $data['menu'] = 'Menu';
            $data['prevstatus'] = $sessdetails['status'];
            $data['status'] = 'final|checkNextStep|Functions';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }
}
