<?php

class Pin {

    private $func, $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    function pinProcess($ussd_body, $mobile_number, $session_id, $sessdetails) {
		$time_monitor_start = microtime(true);
		
        $method_name = $sessdetails['status']; // eg pinStepTwo  
        $responseArr = $this->$method_name($ussd_body, $mobile_number, $session_id, $sessdetails);
		
		$time_taken = microtime(true) - $time_monitor_start;
            if (floatval($time_taken) > 1.00) {
                $this->functions->log("TIME_MONITOR", "PIN", $method_name . ': ' . $time_taken);
            }
        return $responseArr;
    }

    /* Hit servlet to get name of the individual
     * &&
     * Ask user to enter PIN
     */

    function askForPin($session_id, $mobile_number, $service_code, $extcall = null, $respons = null) {
        try {
            $this->functions->clearSession($mobile_number, $del = true);

            $responseArr['SESSION_ID'] = $session_id;
            $responseArr['SEQUENCE'] = 1;
            $responseArr['REQUEST_TYPE'] = "RESPONSE";

            $spdetails = $this->functions->getServiceProvider("LOGINREQUEST");

            $postdetails['f0'] = '0100';
            $postdetails['fld3'] = $spdetails['procode'];
            $postdetails['fld2'] = $mobile_number;
            $postdetails['fld68'] = $spdetails['fld68'];
            $postdetails['fld98'] = $spdetails['fld98'];
            $postdetails['fld13'] = date('md');
            $postdetails['fld24'] = $spdetails['fncode'];
            $postdetails['fld123'] = $spdetails['fld123'];
            $postdetails['fld126'] = $spdetails['fld68'];
            $postdetails['logFileName'] = $spdetails['logFileName'];

            $responseReturned = $this->functions->postEconnect($postdetails);
            
//            //Generate PIN
//            print_r($this->functions->encryptpin('254726128484','1235'));
//            echo '--';
                    //Fake response
            //$responseReturned = $this->functions->getfakeresponse("fake_response");
            $sessdetails['lang'] = $this->functions->getLangAll($this->functions->defaultLang, true); //Load the language to be used in the application
            if (isset($responseReturned['data']['eErr'])) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['service_unavailable'] . "\n" . $sessdetails['lang']['exit_menu'];
                $responseArr['END_OF_SESSION'] = "true";

                $this->functions->send_sms_servlet_down();
                return $responseArr;
            }

            /*
             *  Check if user is registered or not
             */

            if (trim($responseReturned['data']['field48']) !== "This account has been disabled.") {

                /*
                 *  Check if account is blocked
                 */
                if (strtolower(substr($accountdetails, 0, 8)) == 'too many') {
                    $responseArr = $this->checkErrorMessage($ussd_body, $mobile_number, $sessdetails, $responseReturned['data']['field48']);
                    return $responseArr;
                }
                /*
                 * Customer is registered for mwallet
                 */


                $customer_name = (trim($responseReturned['data']['field61']) == "" ? "Customer" : trim($responseReturned['data']['field61']));
                
                $toSessCols['count'] = 1;
                $toSessCols['sessionid'] = $session_id;
                $toSessCols['phonenumber'] = $mobile_number;
                $toSessCols['sessdate'] = date("Y-m-d H:i:s");
                $toSessCols['customername'] = $customer_name;
                $toSessCols['status'] = "validatePin";
                $toSessCols['menu'] = "Pin";
                $toSessCols['tempdata'] = $service_code;
                $this->functions->createSession($mobile_number, $toSessCols);

                $responseArr['END_OF_SESSION'] = "False";

                $responseArr['USSD_BODY'] = $sessdetails['lang']['welcome'] . $customer_name;
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['enter_pin_proceed'];
            } else {
                /*
                 * Not registered for mwallet
                 * Invoke self registration 
                 * English (en) is the default language
                 */
                $responseArr = $this->beginSelfRegistration($mobile_number, $session_id, $sessdetails['lang']);
            }

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Only called if the servlet returns customers information
     * &&
     * Store the information in local session file(xml)
     */

    function storeSessionDetails($ussd_body, $session_id, $mobile_number, $sessdetails, $accountdetails) {
        try {

            $response = array_change_key_case($this->functions->stdObjectToArray(json_decode($accountdetails)), CASE_LOWER);

            $lang_choice = (trim($response['lang']) == "") ? "en" : trim($response['lang']);

            $active = $response['active'];
            $customer_name = trim($response['firstname']) . ' ' . trim($response['secondname']);

            //Check if account is blocked
            if (intval($response['blockinginstitution']) == 1) {
                //Account blocked
                $responseArr['USSD_BODY'] = $sessdetails['lang']['ac_blocked'];
                $responseArr['END_OF_SESSION'] = "true";
                $this->functions->log("INFO", __CLASS__, __FUNCTION__ . ': ' . $mobile_number . ' Account is blocked') . date('His');
                return $responseArr;
            }

            //Check if account is closed
            if (intval($response['closeaccount']) == 1) {
                //Force self registration
                $responseArr = $this->beginSelfRegistration($ussd_body, $mobile_number, $session_id, $sessdetails['lang']);
                $this->functions->log("INFO", __CLASS__, __FUNCTION__ . ': ' . $mobile_number . ' Account had been closed and is trying to access channel|RE-Registration') . date('His');
                return $responseArr;
            }
			
            $toSessCols['count'] = 1;
            $toSessCols['sessionid'] = $session_id;
            $toSessCols['phonenumber'] = $mobile_number;
            $toSessCols['active'] = $active;
            $toSessCols['sessdate'] = date("Y-m-d H:i:s");
            $toSessCols['customer_language'] = (trim($lang_choice) == "es") ? "en" : trim($lang_choice); //'es' was to enforce the safcom *266*1# menu --left it in this version so as to use it in case such a thing happens in another site. es is a dummy lang --george
            $toSessCols['customername'] = (trim($customer_name == "")) ? $response['accountname'] : $customer_name;
            $toSessCols['status'] = 'menuStepOne';
            $toSessCols['menu'] = 'Menu';
            $toSessCols['identificationid'] = $response['identificationid'];
            $toSessCols['dateofbirth'] = $response['dateofbirth'];
            $toSessCols['customerno'] = $response['customerno'];
            $toSessCols['firstlogin'] = $response['firstlogin'];
            $toSessCols['accounts'] = $response['accounts'];
            $toSessCols['loan_accounts'] = $response['loanaccounts'];//'LN-SL1-2547261284840'; //Change to accomodate loan account
            $toSessCols['pin'] = $response['pin'];
            $toSessCols['accountnumber'] = $response['accounts'];
            $toSessCols['partial_registration'] = ($response['partial_registration'] === '1') ? '1' : '0';
            $toSessCols['menulanguagefile'] = $toSessCols['customer_language'];
            if (intval($response['partial_registration']) == 1) {
                //add pr for partial registration
                $toSessCols['menulanguagefile'] = $toSessCols['customer_language'] . "_pr";
            }
            $this->functions->updateSession($mobile_number, $toSessCols);

            //if this is the first login then prompt pin change
            if ($response['firstlogin'] == '1') {
                $task = new PinChange($this->functions);
                $responseArr = $task->pinChangeStepOne($ussd_body, $mobile_number, $session_id, $toSessCols);
                return $responseArr;
            } else {
				
                $task = new Menu($this->functions);
                $responseArr = $task->menuProcess($ussd_body, $mobile_number, $session_id, $toSessCols);
				
            }

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Send the PIN entered by the user to servlet where it'll be validated. Security Feature.
     * &&
     * Redirect to Main menu if login is successful. Then store session details
     */
    function validatePin($ussd_body, $mobile_number, $session_id, $status, $pinok = null) {
        try {
            $format_passed = $this->checkPinFormat($ussd_body, $mobile_number, $session_id, $status);
            if (isset($format_passed['USSD_BODY'])) {
                $responseArr['USSD_BODY'] = $format_passed['USSD_BODY'] . ' Enter PIN again.';
                return $responseArr;
            }

            $spdetails = $this->functions->getServiceProvider("LOGINREQUEST");

            $postdetails['f0'] = '0100';
            $postdetails['fld3'] = $spdetails['procode'];
            $postdetails['fld2'] = $mobile_number;
            $postdetails['fld64'] = $ussd_body;
            $postdetails['fld68'] = $spdetails['fld68'];
            $postdetails['fld98'] = $spdetails['fld98'];
            $postdetails['fld13'] = date('md');
            $postdetails['fld24'] = $spdetails['fncode'];
            $postdetails['fld123'] = $spdetails['fld123'];
            $postdetails['fld126'] = $spdetails['fld68'];
            $postdetails['logFileName'] = "LOGINVALIDATION";

            $responseReturned = $this->functions->postEconnect($postdetails);
            //Fake response
            //$responseReturned = $this->functions->getfakeresponse("fake_response");
			//print_r($responseReturned);
            if (isset($responseReturned['data']['eErr'])) {
                $responseArr['USSD_BODY'] = $status['lang']['service_unavailable'] . "\n" . $status['lang']['exit_menu'];
                $responseArr['END_OF_SESSION'] = "true";

                $this->functions->send_sms('+254725764230', "Servlet iko down tena, si uifufue.  ");
                return $responseArr;
            }
            $accountdetails = $this->functions->json_fix_quotes($responseReturned['data']['field48']);

            if (trim($responseReturned['data']['field39']) == "00") { //Succesful Login
                $this->functions->log("INFO", "USSDLOGIN", $mobile_number . "|" . $this->functions->time_stamp() . "|pin|passed");
                $responseArr = $this->storeSessionDetails($ussd_body, $session_id, $mobile_number, $status, $accountdetails);
            } else {
			//echo 'down';
                $responseArr = $this->checkErrorMessage($ussd_body, $mobile_number, $status, $accountdetails);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Counts the number of times a user tries to enter their password (3 trials max)
     * &&
     * Sends a block request to servlet if there are 3 incorrect trials
     */

    function checkPinTrials($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $x = (intval($sessdetails['trials']) > 0) ? intval($sessdetails['trials']) : 0;

            // 3 pin tries
            if ($x < 2) {
                $responseArr['USSD_BODY'] = 2 - $x . " trial(s) Remaining\n";
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['wrong_pin'] . " " . $sessdetails['lang']['enter_old_pin'];
                $responseArr['SEQUENCE'] = $sessdetails['SEQUENCE'];
                $responseArr['END_OF_SESSION'] = "False";
                if ($sessdetails['other_origin']) {
                    $responseArr = false;
                } else {
                    // add to the trials column

                    $k = $x + 1;
                    $data['trials'] = $k;
                    $this->functions->updateSession($mobile_number, $data);
                }
                return $responseArr;
            } else {

                $this->functions->log("INFO", "USSDLOGIN", $mobile_number . "|" . $this->functions->time_stamp() . "|pinChange|failed");
                // add to the trials column
                //$k = $x + 1;

                $spdetails = $this->functions->getServiceProvider("BLOCKREQUEST");

                $postdetails['f0'] = '0100';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld24'] = $spdetails['fncode'];
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['fld102'] = $mobile_number;
                $postdetails['logFileName'] = $spdetails['logFileName'];
                $this->functions->postEconnect($postdetails);

                $responseArr['USSD_BODY'] = $sessdetails['lang']['ac_blocked'];
                $responseArr['END_OF_SESSION'] = "True";

                $this->functions->clearSession($mobile_number, $del = true);
                return $responseArr;
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check format the pin has been entered (i.e make sure it's the right length-4 idigits, no letters etc)
     * &&
     * Returns false if not valid and updates count.  If more than 3 block
     */

    function checkPinFormat($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

            if (!ctype_digit($ussd_body)) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['pin_invalid_numbers_only'];
                return $responseArr;
            }

            if (strlen($ussd_body) < 4) {
                $this->functions->log("INFO", "USSDLOGIN", $mobile_number . "|" . $this->functions->time_stamp() . "|pin|shortpin");
                $responseArr['USSD_BODY'] = $sessdetails['lang']['pin_too_short'];
                return $responseArr;
            }

            if (strlen($ussd_body) > 5) {
                $this->functions->log("INFO", "USSDLOGIN", $mobile_number . "|" . $this->functions->time_stamp() . "|pin|longpin");
                $responseArr['USSD_BODY'] = $sessdetails['lang']['pin_too_long'];
                return $responseArr;
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check pin strength
     * &&
     * Returns false if not strong
     */

    function checkPinStrength($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $response['PIN_NOT_VALID'] = false;

            $invalid = str_split($ussd_body);

            $numbers = "1,2,3,4,5,6,7,8,9,0";
            $numeric_array = explode(",", $numbers);
            for ($i = 0; $i < count($invalid); $i++) { //Ensures all digits are numbers
                if (!in_array($invalid[$i], $numeric_array, true)) {
                    $response['USSD_BODY'] = $sessdetails['lang']['pin_invalid_numbers_only'];
                    $response['PIN_NOT_VALID'] = true;
                    return $response;
                }
            }

            if (count(array_unique($invalid)) == 1) {//Checks for the same consecutive numbers
                $response['USSD_BODY'] = $sessdetails['lang']['pin_strength_invalid'];
                $response['PIN_NOT_VALID'] = true;
                return $response;
            }

            $invalid_pin = array();
            $invalid_pin[0] = '1234';
            $invalid_pin[1] = '4321';
            if (in_array($ussd_body, $invalid_pin, true)) {//Checks if the new pin is in the array containing what we consider as easy passwords
                $response['USSD_BODY'] = $sessdetails['lang']['pin_strength_invalid'] . "\n";
                $response['PIN_NOT_VALID'] = true;
                return $response;
            }

            if (trim($this->functions->encryptPin($mobile_number, $ussd_body)) == trim($sessdetails['pin'])) {//Same pin as old pin
                $response['USSD_BODY'] = $sessdetails['lang']['pin_similar'] . "\n";
                $response['PIN_NOT_VALID'] = true;
                return $response;
            }

            return $response;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Called either if a user doesnt exist on the system or if account was previously closed but now user wants to access again
     * &&
     * Redirects to Register class to continue the rest of the self registration
     */

    function beginSelfRegistration($mobile_number, $session_id, $lang) {
        try {

            $all_available_languages = $this->functions->getLangAll("*"); //get a list of all languages for mwallet registration
            //$responseArr['USSD_BODY'] = $lang['register_option'];
            $responseArr['USSD_BODY'] = $lang['not_registered'] . $lang['enter_surname'];
            $responseArr['END_OF_SESSION'] = "false";

            $toSessCols['count'] = 1;
            $toSessCols['sessionid'] = $session_id;
            $toSessCols['phonenumber'] = $mobile_number;
            $toSessCols['sessdate'] = date("Y-m-d H:i:s");
            $toSessCols['status'] = "REG|registerStepFive|Register";
//            if (count($all_available_languages['menuStr']) != 1) {
//                $toSessCols['status'] = "REG|registerStepOne|Register";
//                //$responseArr['USSD_BODY'] .= $lang['select_language']. $all_available_languages;
//            }
            $toSessCols['available_languages'] = implode("|", $all_available_languages['menuCode']);
            $toSessCols['menu'] = "Menu";
            $this->functions->createSession($mobile_number, $toSessCols);
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the error message displayed
     * && 
     * Format it before relaying back to the user
     */

    function checkErrorMessage($ussd_body, $mobile_number, $sessdetails, $accountdetails) {
        try {
            $prefix = strtolower(substr($accountdetails, 0, 10));
            switch ($prefix) {
                case 'wrong auth':
                    $trials_passed = $this->checkPinTrials($ussd_body, $mobile_number, $session_id, $sessdetails);
                    if (isset($trials_passed['USSD_BODY'])) {
                        $responseArr['USSD_BODY'] = $trials_passed['USSD_BODY'];
                        return $responseArr;
                    }
                    break;
                case 'too many t':
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['ac_blocked'];
                    $responseArr['END_OF_SESSION'] = "True";
                    break;

                default:
                    $responseArr['USSD_BODY'] = 'There is a problem with your account. Kindly contact Century support.';
                    break;
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}
