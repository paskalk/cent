<?php

class FarmingRequests {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     * coming soon
     */

    function farmingRequestsStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $lang = $sessdetails['lang'];
            $responseArr['USSD_BODY'] = $lang['service_unavailable_temp'];
            $responseArr['END_OF_SESSION'] = 'False';

            $data['submenuselected'] = $sessdetails['submenuselected']; //Because we hadn't saved it before we passed it from menuStepThree
            $data['menu'] = 'Menu';
            $data['prevstatus'] = $sessdetails['status'];
            $data['status'] = 'get famr stuff|farmingRequestsStepTwo|FarmingRequests';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function farmingRequestsStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $user_selection = intval($ussd_body);
            $responseArr['END_OF_SESSION'] = 'False';

            //Means the person wants to go home or back
            if ($user_selection == 99 || $user_selection == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            } else {
                $lang = $sessdetails['lang'];

                $responseArr['USSD_BODY'] = $lang['invalid_option'];

                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}

// end class
