<?php

class PinChange {

    private $functions, $Pinn;

    function __construct($func) {
        $this->functions = $func;
        $this->Pin = $Pinn;
    }

    /* Check old pin entered by the user
     * &&
     * Ask user to enter new pin
     */

    function pinChangeStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {

            if (intval($ussd_body) == 99 || intval($ussd_body) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            $chkpin = new Pin($this->functions);
            $format_passed = $chkpin->checkPinFormat($ussd_body, $mobile_number, $session_id, $sessdetails);
            if (isset($format_passed['USSD_BODY'])) {
                $responseArr['USSD_BODY'] = $format_passed['USSD_BODY'];
                return $responseArr;
            }
            //var_dump($sessdetails);

            $old_pin =  $this->functions->encryptPin($mobile_number, $ussd_body);
//            if (!$this->functions->checkPin($ussd_body, strtoupper($mobile_number), $sessdetails['pin'])) {
            if (trim($old_pin) != trim($sessdetails['pin'])) {
                $trials_status = $chkpin->checkPinTrials($ussd_body, $mobile_number, $session_id, $sessdetails);
                $responseArr['USSD_BODY'] = $trials_status['USSD_BODY'];
                return $responseArr;
            } else {
                $responseArr['END_OF_SESSION'] = "false";
                $responseArr['USSD_BODY'] = 'Enter New PIN.';// $sessdetails['lang']['enter_new_pin'];

                $data['status'] = "|pinChangeStepTwo|PinChange";
                $data['old_pin'] = $old_pin;
                $data['old_pin_plain'] = $ussd_body;
                $data['menu'] = 'Menu';
                $this->functions->updateSession($mobile_number, $data);
            }


            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the new pin entered by user and make sure it's not the same as the last pin
     * &&
     * Ask user to confirm new pin
     */

    function pinChangeStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $chkpin = new Pin($this->functions);

            $format_passed = $chkpin->checkPinFormat($ussd_body, $mobile_number, $session_id, $sessdetails);
            if (isset($format_passed['USSD_BODY'])) {
                $responseArr['USSD_BODY'] = $format_passed['USSD_BODY'];
                return $responseArr;
            }

            $strength_passed = $chkpin->checkPinStrength($ussd_body, $mobile_number, $session_id, $sessdetails);
            if (isset($strength_passed['USSD_BODY']) && $strength_passed['PIN_NOT_VALID'] == true) {
                $responseArr['USSD_BODY'] = $strength_passed['USSD_BODY'];
                return $responseArr;
            }

            $responseArr['END_OF_SESSION'] = "false";
            $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_new_pin'];

            $data['status'] = "PC|pinChangeStepThree|PinChange";
            $data['menu'] = 'Menu';
            $data['new_pin'] = $this->functions->encryptPin($mobile_number, $ussd_body);
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Confirm new pin entry is the same by checking second entry of new pin
     * &&
     * Send the details to servlet
     */

    function pinChangeStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {

            $method_details = $sessdetails['status'];
            $status = explode("|", $method_details);
            $x = (intval($sessdetails['trials'])) ? intval($sessdetails['trials']) : 0;
            if (trim($this->functions->encryptPin($mobile_number, $ussd_body)) !== trim($sessdetails['new_pin'])) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['pin_not_match']; // $sessdetails['lang']['back_menu'];
            } else {
                $password = $this->functions->encryptPin($mobile_number, $ussd_body);
                $x = 0;
                $spdetails = $this->functions->getServiceProvider(strtoupper("PINCHANGE"));
                //send change pin request to econnect
                $postdetails = array();
                $postdetails['f0'] = '0100';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld64'] = $sessdetails['old_pin_plain']; //Old pin
                $postdetails['fld65'] = $ussd_body; //New Pin
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $ECresponse = $this->functions->postEconnect($postdetails);

                $responseArr['END_OF_SESSION'] = "false";
                $sessionstatus = $status[0] . "|endTxn|Functions";

                if ($ECresponse['data']['field39'] == '00') {
                    $this->functions->log("INFO", __CLASS__, $mobile_number . ": Pin Changed Successfully");

                    $sessdetails['pin'] = $ussd_body;
                    $data['pin'] = $password;
                    $data['menu'] = $sessdetails['menu'] = 'Menu';
                    $this->functions->updateSession($mobile_number, $data);
                    $task = new Pin($this->functions);
                    $responseArr = $task->validatePin($ussd_body, $mobile_number, $session_id, $sessdetails, true);
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['pin_change_success'];//.$responseArr['USSD_BODY'];
                    $responseArr['END_OF_SESSION'] = "true";
                } else {
                    $this->functions->log("INFO", __CLASS__, $mobile_number . ": Pin Change Failed");

                    $responseArr['USSD_BODY'] .= $sessdetails['lang']['pin_fail'] ;//. $sessdetails['lang']['back_menu'];
                    $responseArr['END_OF_SESSION'] = "true";

                    $this->functions->send_sms('+254725764230','Manager Bonoko, pinchange inafail');
                    $data['status'] = $sessionstatus;
                    $data['menu'] = 'Menu';
                    $this->functions->updateSession($mobile_number, $data);
                }
            }

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}
