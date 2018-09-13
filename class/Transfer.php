<?php

class Transfer {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     *  Ask user to choose whether they want to transfer to own account other centruy account
     */

    function transferStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $subminimenu = $this->functions->parseMenuXml($ussd_body, $sessdetails['menulanguagefile'], "subminimenu", $sessdetails['mainmenuselected'], $sessdetails['submenuselected']);
            $responseArr['USSD_BODY'] = $subminimenu['menulist'];

            $data['ft_options_list'] = implode("|", $subminimenu['subminicode']);
            $data['menu'] = 'Menu';
            $data['status'] = 'chooseFTdestination|transferStepTwo|Transfer';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check if user wants to transfer funds between own accounts or other century accounts
     * &&
     * Ask user to enter account from which funds should be transferred from
     */

    function transferStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $user_selection = intval(trim($ussd_body));
            $available_options = explode("|", $sessdetails['ft_options_list']);
            $number_of_options = count($available_options);

            $responseArr['END_OF_SESSION'] = "false";

            //Means the person wants to go home or back
            if ($user_selection == 99 || $user_selection == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($user_selection == 0 || $user_selection > $number_of_options) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {
                $responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails);
                $responseArr['USSD_BODY'] = str_replace("Select Account:", $sessdetails['lang']['enter_account_from'], $responseArr['USSD_BODY']);
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];

                $data['status'] = 'select ac from|transferStepThree|Transfer';
                $data['option_selected'] = $available_options[$user_selection - 1];
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check account(from) number entered
     * &&
     * Ask user to enter account number to transfer funds to
     */

    function transferStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $account_selected = intval(trim($ussd_body));
            $available_accounts = explode("|", $sessdetails['accounts']);
            $number_of_accounts = count($available_accounts);

            $responseArr['END_OF_SESSION'] = "False";

            if (intval($account_selected) == 99 || intval($account_selected) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($account_selected == 0 || $account_selected > $number_of_accounts) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {
                $responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails);
                $responseArr['USSD_BODY'] = str_replace("Select Account:", "Select Account to transfer to:", $responseArr['USSD_BODY']);
                        //$sessdetails['lang']['account_to'] . $responseArr['USSD_BODY'];

                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'acTo|transferStepFour|Transfer';
                if ($sessdetails['option_selected'] == 'third_party_account') {
                    $data['status'] = 'acTo|transferStepFive|Transfer'; //Means user wants to transfer to other century account therefore we need to capture the account number
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['account_to'];
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

    /* Checks account(to) selected (Only applies for accounts owned by the user)
     * &&
     * Asks user to enter amount to transfer
     */

    function transferStepFour($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $account_selected = trim($ussd_body);
            $available_accounts = explode("|", $sessdetails['accounts']);
            $number_of_accounts = count($available_accounts);

            $responseArr['END_OF_SESSION'] = "False";

            if (intval($account_selected) == 99 || intval($account_selected) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if (!ctype_digit($account_selected) || intval($account_selected) > $number_of_accounts) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_amount'];
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];


                $data['accto'] = $available_accounts[$account_selected - 1];
                if ($sessdetails['accfrom'] == $data['accto']) {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['account_similar'] . $sessdetails['previous_response'];
                    $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | User tried to transfer funds to same account ");
                    return $responseArr;
                }
                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'acTo|transferStepSix|Transfer';
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check account(to) manually typed in by user when user selects 'other century accounts'
     * &&
     * Ask user to enter amount to transfer
     */

    function transferStepFive($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $account_to = trim($ussd_body);
            $responseArr['END_OF_SESSION'] = "False";

            if (intval($account_to) == 99 || intval($account_to) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }
			//print_r($ussd_body);
            if (!ctype_digit($account_to) || strlen($account_to) < 9) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['account_invalid'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Account number entered is either wrong or not a valid account number");
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_amount'];
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];

                if (strlen($account_to) < 12) {
                    $number_valid = $this->functions->validatePhoneNumber($account_to);
                    $account_to = $number_valid['number'];
                }

                $data['accto'] = $account_to;
                if ($sessdetails['accfrom'] == $data['accto']) {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['account_similar'] . $sessdetails['previous_response'];
                    $this->functions->flog('INFO', __METHOD__ . "() | User ~" . $mobile_number . "~ tried to transfer funds to same account ");
                    return $responseArr;
                }
				
                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'acTo|transferStepSix|Transfer';
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check amount entered
     * &&
     * Ask user to confirm or cancel transaction
     */

    function transferStepSix($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $amount_entered = intval(trim($ussd_body));
            $responseArr['END_OF_SESSION'] = "False";

            if (intval($amount_entered) == 99 || intval($amount_entered) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($amount_entered < 100 || strlen($amount_entered) == 0) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['amount_invalid'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() | Account number entered is either wrong or not a valid account number");
            } else {
                $responseArr['USSD_BODY'] = str_replace("[ACCOUNT_TO]", $sessdetails['accto'], str_replace("[ACCOUNT_FROM]", $sessdetails['accfrom'], str_replace("[AMOUNT]", $amount_entered, $sessdetails['lang']['confirm_transfer'])));
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];

                $data['amount'] = $amount_entered;

                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['status'] = 'acTo|transferStepSeven|Transfer';
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check if user wants to confrim or cancel the transaction
     * &&
     * Ask if user wants another transaction or exit instead
     */

    function transferStepSeven($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = "false";
            $sessionStatus = "final|endTxn|Functions";

            if (intval($ussd_body) == 1) {//post transaction
                $spdetails = $this->functions->getServiceProvider("FT");

                $postdetails['f0'] = '0200';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = $sessdetails['amount'];
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['fld24'] = $spdetails['walletToWallet'];
                if (strlen($sessdetails['accfrom']) < 13 && strlen($sessdetails['accto']) > 12 ) {
                    $postdetails['fld24'] = $spdetails['walletToCore'];
                }
				if (strlen($sessdetails['accfrom']) > 12 && strlen($sessdetails['accto']) < 13 ) {
                    $postdetails['fld24'] = $spdetails['coreToWallet'];
                }
				if (strlen($sessdetails['accfrom']) > 12 && strlen($sessdetails['accto']) > 12 ) {
                    $postdetails['fld24'] = $spdetails['coreToCore'];
                }
                $postdetails['fld102'] = $sessdetails['accfrom'];
                $postdetails['fld103'] = $sessdetails['accto'];
                $postdetails['logFileName'] = $spdetails['logFileName'];
				//print_r($postdetails);
                $result = $this->functions->postEconnect($postdetails);

                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '000') {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    if ($result['data']['field39'] == '51'){
						$responseArr['USSD_BODY'] = $sessdetails['lang']['insufficient_funds'];
					}
					//$this->functions->send_sms('+254726128484','FT imechapa!');
                }

                $data['menu'] = 'Menu';
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
