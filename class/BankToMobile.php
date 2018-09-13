<?php

class BankToMobile { //AKA Send to Mpesa ---- AKA B2C

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     *  Ask user to choose whether they want to do balance enquiry or mini statement 
     */

    function bankToMobileStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails);
            $responseArr['END_OF_SESSION'] = 'False';

            $data['submenuselected'] = $sessdetails['submenuselected']; //Because we hadn't saved it before we passed it from menuStepThree
            $data['menu'] = 'Menu';
            $data['prevstatus'] = $sessdetails['status'];
            $data['status'] = 'B2C2B|bankToMobileStepTwo|BankToMobile';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check account(from) number entered
     * &&
     * Ask user to select number to transfer funds to
     */

    function bankToMobileStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {

            $account_selected = intval(trim($ussd_body));
            $available_accounts = explode("|", $sessdetails['accounts']);
            $number_of_accounts = count($available_accounts);

            $responseArr['END_OF_SESSION'] = "False";
            //Check if user wants to proceed or go home/back
            if ($account_selected == 99 || $account_selected == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($account_selected == 0 || $account_selected > $number_of_accounts) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['number_to_topup_menu'] . $sessdetails['lang']['home_or_back'];

                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'banktomobile|bankToMobileStepThree|BankToMobile';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check whether user wants to send to own number or other
     * &&
     * Ask number to send to or select own number 
     */

    function bankToMobileStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $lang = $sessdetails['lang'];
            $user_selection = intval($ussd_body);
            $responseArr['END_OF_SESSION'] = "False";

            //Check if user wants to proceed or go home/back
            if ($user_selection == 99 || $user_selection == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
            } else {
                switch ($user_selection) {
                    case 1: //Own number ~~  Ask for amount
                        $data['accto'] = $mobile_number;
                        $data['status'] = 'banktomobile|bankToMobileStepFive|BankToMobile';
                        $responseArr['USSD_BODY'] = $lang['enter_b2c_amount'];
                        break;
                    case 2: //Other number ~~ Ask user to enter number
                        $data['status'] = 'banktomobile|bankToMobileStepFour|BankToMobile';
                        $responseArr['USSD_BODY'] = $lang['topup_no'];
                        break;
                    default:
                        $responseArr['USSD_BODY'] = $lang['invalid_option'] . $sessdetails['previous_response'];
                        return $responseArr;
                }
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];

                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the (other) number user entered
     * &&
     * Ask user to enter amount
     */

    function bankToMobileStepFour($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

            if (intval($ussd_body) == 99 || intval($ussd_body) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            $number_valid = $this->functions->validatePhoneNumber($ussd_body);

            if ($number_valid['status'] == true) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_b2c_amount'] . $sessdetails['lang']['home_or_back'];

                $data['accto'] = $number_valid['number'];
                $data['status'] = 'banktomobile|bankToMobileStepFive|BankToMobile';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['account_invalid_phone'] . '(' . $number_valid['message'] . ') ' . $sessdetails['previous_response'];
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the amount entered by user
     * &&
     * Ask user to confirm or cancel transaction
     */

    function bankToMobileStepFive($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $amount_entered = intval(trim($ussd_body));
            $responseArr['END_OF_SESSION'] = "False";

            if (intval($amount_entered) == 99 || intval($amount_entered) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($amount_entered < 100 || $amount_entered > 70000 || strlen($amount_entered) == 0) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['amount_invalid'] . $sessdetails['previous_response'];
				if ($amount_entered > 70000){
				$responseArr['USSD_BODY'] = str_replace("Min. 200","Min. 200, Max. 70000",$responseArr['USSD_BODY'] );
				}
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Account number entered is either wrong or not a valid account number");
            } else {
                $responseArr['USSD_BODY'] = str_replace("[PHONE]", $sessdetails['accto'], str_replace("[ACCOUNT_FROM]", $sessdetails['accfrom'], str_replace("[AMOUNT]", $amount_entered, $sessdetails['lang']['confirm_bank_to_mobile'])));
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];

                $data['amount'] = $amount_entered;

                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'banktomobile|bankToMobileStepSix|BankToMobile';
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check if user wants to cancel or post the transaction
     * &&
     * Ask if user wants to end session or transact again
     */

    function bankToMobileStepSix($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";
            $sessionStatus = "final|endTxn|Functions";

            if (intval($ussd_body) == 1) {//post the transaction
                $spdetails = $this->functions->getServiceProvider("B2C");

                $postdetails['f0'] = "0200";
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = $sessdetails['amount'];
                $postdetails['fld24'] = $spdetails['coreToBank'];
				if (strlen($sessdetails['accfrom']) < 13 ) {
                    $postdetails['fld24'] = $spdetails['walletToBank'];
                }
                $postdetails['fld65'] = $sessdetails['accto'];
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld102'] = $sessdetails['accfrom'];
                $postdetails['fld103'] = " ";//$sessdetails['accto'];
                $postdetails['fld121'] = str_replace("ki", "sw", str_replace(" ", "", str_replace("pr", "", $sessdetails['en'])));
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $result = $this->functions->postEconnect($postdetails);

                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '00') {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                    $data['previous_response'] = $responseArr['USSD_BODY'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    if ($result['data']['field39'] == '51'){
						$responseArr['USSD_BODY'] = $sessdetails['lang']['insufficient_funds'];
					}
					//$this->functions->send_sms('+254727830769','Madam! Bank to mobile haiwork!');
                }

                $data['status'] = $sessionStatus;
                $this->functions->updateSession($mobile_number, $data);
            } else {
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['trn_cancel'];

                $data['status'] = $sessionStatus;
                $this->functions->updateSession($mobile_number, $data);
            }
            $responseArr['USSD_BODY'] .= $sessdetails['lang']['back_menu'];
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}
