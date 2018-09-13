<?php

class StopCheque {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /* Check which account whose cheques the user wants to stop
     * &&
     * Ask user to select page number of the cheque
     */

    function stopChequeStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
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
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_cheque_number'] . $sessdetails['lang']['home_or_back'];

                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'getpagenumber|stopChequeStepTwo|StopCheque';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the number of the cheque that user wnats to stop
     * &&
     * Ask user to enter amount that had been written on the cheque
     */

    function stopChequeStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";
            
            if (intval($ussd_body) == 99 || intval($ussd_body) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }
            
            $cheque_valid = $this->functions->validateStopCheque($ussd_body);

            if ($cheque_valid['status'] == true) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_cheque_amt'] . '- i.e the amount you had written on the cheque (e.g 1000)';
                $responseArr['USSD_BODY'] .=  $sessdetails['lang']['home_or_back'];

                $data['page_to_stop'] = $ussd_body;
                $data['status'] = 'amount|stopChequeStepThree|StopCheque';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['cheque_invalid'] . '(' . $cheque_valid['message'] . ') ' . $sessdetails['previous_response'];
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check the worth of the cheque to be stopped as entered by user
     * &&
     * Ask user to confirm or cancel stopping the cheque
     */

    function stopChequeStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $amount_entered = intval(trim($ussd_body));
            $responseArr['END_OF_SESSION'] = "False";
            
            if ($amount_entered == 99 || $amount_entered == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }
            
            if ($amount_entered < 100 || strlen($amount_entered) == 0) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['amount_invalid'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Account number entered is either wrong or not a valid account number");
            } else {
                $responseArr['USSD_BODY'] = str_replace("[CHEQUE_NUMBER]", $sessdetails['page_to_stop'], str_replace("[ACCOUNT_FROM]", $sessdetails['accfrom'], str_replace("[AMOUNT]", $amount_entered, $sessdetails['lang']['confirm_stop_cheque'])));
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];

                $data['amount'] = $amount_entered;
                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'confirmcancel|stopChequeStepFour|StopCheque';
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

    function stopChequeStepFour($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";
            $sessionStatus = "final|endTxn|Functions";

            if (intval($ussd_body) == 1) {
                $spdetails = $this->functions->getServiceProvider("STOPCHEQUE");

                $postdetails['f0'] = "0200";
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = $sessdetails['amount'];
                $postdetails['fld46'] = 'N';
                $postdetails['fld47'] = $sessdetails['page_to_stop'];
                $postdetails['fld48'] = $sessdetails['page_to_stop'];
                $postdetails['fld68'] = 'STOP CHEQUE';
                $postdetails['fld102'] = $sessdetails['accfrom'];
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $result = $this->functions->postEconnect($postdetails);

                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '53') {// '53' because the neptune cbs guys said '53' is also ok
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
					if ($result['data']['field39'] == '51'){
						$responseArr['USSD_BODY'] = $sessdetails['lang']['insufficient_funds'];
					}
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

// end class
