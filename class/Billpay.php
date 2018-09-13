<?php

class Billpay {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     *  Ask user to choose biller
     */

    function billpayStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $subminimenu = $this->functions->parseMenuXml($ussd_body, $sessdetails['menulanguagefile'], "subminimenu", $sessdetails['mainmenuselected'], $sessdetails['submenuselected']);
            $responseArr['USSD_BODY'] = $subminimenu['menulist'];
            $responseArr['END_OF_SESSION'] = 'False';

            $data['billers_list'] = implode("|", $subminimenu['subminicode']);

            $data['submenuselected'] = $sessdetails['submenuselected']; //Because we hadn't saved it before we passed it from menuStepThree
            $data['menu'] = 'Menu';
            $data['prevstatus'] = $sessdetails['status'];
            $data['status'] = 'billpay|billpayStepTwo|Billpay';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check which biller the user wants to pay
     * &&
     * Ask user to select account from which to pay
     */

    function billpayStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $biller_selected = intval(trim($ussd_body));
            $available_billers = explode("|", $sessdetails['billers_list']);
            $number_of_billers = count($available_billers);

            $responseArr['END_OF_SESSION'] = "False";
            //Check if user wants to proceed or go home/back
            if ($biller_selected == 99 || $biller_selected == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($biller_selected == 0 || $biller_selected > $number_of_billers) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the selected value was not an integer or the biller no. selected was not on list of biller no.s on the screen ");
            } else {
                $responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails);
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];

                $data['biller'] = $available_billers[$biller_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'billpay|billpayStepThree|Billpay';
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check which account user wants to use to pay from
     * &&
     * Ask user to enter account/bill/meter number to pay
     */

    function billpayStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
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
                $responseArr['USSD_BODY'] = str_replace("[BILLER]", $sessdetails['biller'], $sessdetails['lang']['enter_bill_no']) . $sessdetails['lang']['home_or_back'];

                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'billpay|billpayStepFour|Billpay';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check biller account number entered
     * &&
     * Ask user to enter amount
     */

    function billpayStepFour($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";

            if (intval($ussd_body) == 99 || intval($ussd_body) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            $account_valid = $this->functions->validateBillerAccount($ussd_body, $sessdetails['biller']);

            if ($account_valid['status'] == true) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_amount'] . $sessdetails['lang']['home_or_back'];
                ;

                $data['accto'] = $ussd_body;
                $data['status'] = 'billpay|billpayStepFive|Billpay';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['account_invalid_biller'] . '(' . $account_valid['message'] . ') ' . $sessdetails['previous_response'];
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check amount entered
     * &&
     * Ask user to confirm or cancel the transaction
     */

    function billpayStepFive($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $amount_entered = intval(trim($ussd_body));
            $responseArr['END_OF_SESSION'] = "False";

            if (intval($amount_entered) == 99 || intval($amount_entered) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($amount_entered < 200 || strlen($amount_entered) == 0) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['amount_invalid'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Account number entered is either wrong or not a valid account number");
            } else {
                $responseArr['USSD_BODY'] = str_replace("[ACCOUNT_TO]", $sessdetails['accto'], str_replace("[ACCOUNT_FROM]", $sessdetails['accfrom'], str_replace("[AMOUNT]", $amount_entered, str_replace("[BILLER]", $sessdetails['biller'], $sessdetails['lang']['confirm_billpay']))));
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];

                $data['amount'] = $amount_entered;

                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'billpay|billpayStepSix|Billpay';
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

    function billpayStepSix($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";
            $sessionStatus = "final|endTxn|Functions";

            if (intval($ussd_body) == 1) {
                $spdetails = $this->functions->getServiceProvider(strtoupper($sessdetails['biller']));

                $postdetails['f0'] = '0200';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = $sessdetails['amount'];
                $postdetails['fld24'] = $spdetails['cbsFncode'];
                if (strlen($sessdetails['accfrom']) < 13) {
                    $postdetails['fld24'] = $spdetails['fncode'];
                }
                $postdetails['fld65'] = $sessdetails['accto'];
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld100'] = $spdetails['fld100'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld102'] = $sessdetails['accfrom'];
                $postdetails['fld126'] = $spdetails['fld126'];
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $responseArr['END_OF_SESSION'] = "false";
                $result = $this->functions->postEconnect($postdetails);

                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '00') {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    if ($result['data']['field39'] == '51'){
						$responseArr['USSD_BODY'] = $sessdetails['lang']['insufficient_funds'];
					}
					//$this->functions->send_sms('+254726128484','Bill Pay imechapa!');
                }

                $data['previous_response'] = $responseArr['USSD_BODY'];
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
