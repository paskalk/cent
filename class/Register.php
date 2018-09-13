<?php

/*
 * Register.self.php
 * Register
 */

class Register {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /* Load default language
     * &&
     * Ask user to enter surname
     */

    function registerStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $menuCode = explode("|", $sessdetails['available_languages']);

            if (trim($sessdetails['en']) !== "" || trim($menuCode[intval($ussd_body) - 1]) !== "") {
                $en = (trim($menuCode[intval($ussd_body) - 1]) !== "") ? trim($menuCode[intval($ussd_body) - 1]) : trim($sessdetails['en']);
                $lang = $this->functions->getLangAll($en, true);
                $sessionstatus = "REG|registerStepTwo|Register";
                $responseArr['END_OF_SESSION'] = "false";
                $responseArr['USSD_BODY'] = $lang['register_option'];
                if (strlen(trim($sessionstatus)) > 0) {
                    $data['status'] = $sessionstatus;
                }
                $data[''] = $en;
                $data['customers_chosen_language'] = $en;
                $data['lang'] = $en;
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['menu'] = 'Menu';
                $this->functions->updateSession($mobile_number, $data);
            } else {

                $responseArr['USSD_BODY'] = $sessdetails['lang']['exit_menu'];
                $responseArr['END_OF_SESSION'] = "true";
                $this->functions->clearSession($mobile_number, $del = true);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check whether user wants to view terms & conditions or start self registration process
     * &&
     * Ask user to accept or decline they read terms and conditions
     */

    function registerStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "False";

            if (intval($ussd_body) == 1) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['terms_and_conditions'];
            } elseif (intval($ussd_body) == 2) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['Accept_Terms'];

                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'REG|registerStepFour|Register';
                $data['menu'] = 'Menu';
                $this->functions->updateSession($mobile_number, $data);
            } else {
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['invalid_input'] . "\n" . $sessdetails['previous_response'];
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Checks if user accepted terms and conditions or refused
     * &&
     * End sesssion if terms not accepted, else ask user for surname
     */

    function registerStepFour($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";
            if (intval($ussd_body) == 1) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_surname'];

                $data['status'] = "MW|registerStepFive|Register";
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['menu'] = 'Menu';
                $this->functions->updateSession($mobile_number, $data);
            } elseif (intval($ussd_body) == 2) {
                $responseArr['END_OF_SESSION'] = "true";
                $responseArr['USSD_BODY'] = $sessdetails['lang']['terms_declined'];

                $this->functions->clearSession($mobile_number, true);
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_input'];
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the surname entered by the unregistered customer
     * && 
     * Ask them to enter first name seperated by a space
     */

    function registerStepFive($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

            if (strlen(trim($ussd_body)) == 0 || ctype_digit($ussd_body)) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_input'] . $sessdetails['previous_response'];
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_first_name'];

                $data['status'] = "MW|registerStepSix|Register";
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['menu'] = 'Menu';
                $data['self_reg_surname'] = trim($ussd_body);
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check first name entered
     * &&
     * Ask user to enter last name
     */

    function registerStepSix($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

            if (strlen(trim($ussd_body)) == 0 || ctype_digit($ussd_body)) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_input'] . $sessdetails['previous_response'];
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_last_name'];

                $data['status'] = "MW|registerStepSeven|Register";
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['menu'] = 'Menu';
                $data['self_reg_firstname'] = trim($ussd_body);
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check last name entered
     * &&
     * Ask user to enter id number
     */

    function registerStepSeven($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

            if (strlen(trim($ussd_body)) == 0 || ctype_digit($ussd_body)) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_input'] . $sessdetails['previous_response'];
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_identification'];

                $data['status'] = "MW|registerStepEight|Register";
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['menu'] = 'Menu';
                $data['self_reg_lastname'] = trim($ussd_body);
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check ID entered by the individual
     * &&
     * Ask user to enter account number (if they have)
     */

    function registerStepEight($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            //if (mb_strlen(trim($ussd_body),"UTF-8") == 0 || mb_strlen(trim($ussd_body),"UTF-8") < 7) {
			if (strlen(trim($ussd_body)) == 0 || strlen(trim($ussd_body)) < 7) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_id'] . $sessdetails['previous_response'];
            } else {
                $responseArr['END_OF_SESSION'] = "false";
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_ac_optional'];

                $data['status'] = "MW|registerStepNine|Register";
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['menu'] = 'Menu';
                $data['idnumber'] = trim($ussd_body);
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check if user entered their account number
     * &&
     * Post self registration request to the servlet
     */

    function registerStepNine($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

//            if ((strlen(trim($ussd_body)) < 13 && strlen(trim($ussd_body)) > 0 ) || !ctype_digit($ussd_body)) {
//                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_account'] . $sessdetails['previous_response'];
//                return $responseArr;
//            }

            $responseArr['USSD_BODY'] = $sessdetails['lang']['self_reg_confirm'] . $sessdetails['lang']['confirm_cancel'];

            $data['status'] = "MW|registerStepTen|Register";
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $data['menu'] = 'Menu';
            $data['self_reg_ac'] = trim($ussd_body);
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /*
     * Post the results to the servlet
     */

    function registerStepTen($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "true";

            if (intval($ussd_body) == 1) {//post the transaction
                $spdetails = $this->functions->getServiceProvider("SELFREG");

                $paramsArray = array("SURNAME" => strtoupper(trim($sessdetails['self_reg_surname'])),
                    "FIRSTNAME" => strtoupper(trim($sessdetails['self_reg_firstname'])),
                    "LASTNAME" => strtoupper(trim($sessdetails['self_reg_lastname'])),//second name
                    "ID_NUMBER" => trim($sessdetails['idnumber']),
                    "IMSI" => trim($sessdetails['imsi']),
                    "ACCOUNTNUMBER" => trim($sessdetails['self_reg_ac']),
                    "PHONENUMBER" => $mobile_number,
                    "LANG" => (trim($sessdetails['customers_chosen_language']) == null || trim($sessdetails['customers_chosen_language']) == "") ? "en" : trim($sessdetails['customers_chosen_language']),
                    "PROCESSED" => "0");

                //Convert the array above to an XML 
                $paramsXML = $this->functions->arrayToXML(array_change_key_case($paramsArray,CASE_UPPER),CASE_UPPER);

                //print_r($paramsXML);

                $postdetails = array();

                $postdetails['f0'] = '0100';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld24'] = $spdetails['fncode'];
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld96'] = base64_encode($paramsXML);
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['fld102'] = $mobile_number;
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $result = $this->functions->postEconnect($postdetails);
                if ($result['data']['field39'] == '00') {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'] . "\n" . $sessdetails['lang']['reg_sms'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    $user = strtoupper($sessdetails['self_reg_firstname']);
                    $this->functions->send_sms('+254725764230',"Manager Bonoko! Self reg ya $user imechapa! Cheki hio maneno mbiombio. ");
                }
                $this->functions->clearSession($mobile_number, true);
            } else {
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['trn_cancel'];

                $data['status'] = "MW|endTransaction|Register";
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Final step
     * &&
     * Checks if user wants toperform another transaction or if they want to exit
     */

    function endTransaction($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            if (intval($ussd_body) == 1) {
                //Take the user home
                $task = new Pin($this->functions);
                $responseArr = $task->askForPin($ussd_body, $mobile_number, $session_id, $sessdetails);
            } else {
                //User doesn't want another transaction
                $responseArr['END_OF_SESSION'] = "true";
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['exit_menu'];
                $this->clearSession($mobile_number, true);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}
