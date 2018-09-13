<?php

/* To re-enable ability of user to select which Loan Account he/she wants to service, redirect to loanStepFive from loanStepTwo
 * 
 * &&
 * 
 */

class Loan {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /* Display submenus
     * &&
     * Ask user to select one of the loan submenus
     */

    function loanStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $subminimenu = $this->functions->parseMenuXml($ussd_body, $sessdetails['customer_language'], "subminimenu", $sessdetails['mainmenuselected'], $sessdetails['submenuselected']);
            $responseArr['USSD_BODY'] = $subminimenu['menulist'];
            $responseArr['END_OF_SESSION'] = 'False';

            $data['loan_items_list'] = implode("|", $subminimenu['subminicode']);

            $data['menu'] = 'Menu';
            $data['prevstatus'] = $sessdetails['status'];
            $data['status'] = 'getloan|loanStepTwo|Loan';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check loan submenu selected 
     * &&
     * Ask user to either select account
     */

    function loanStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $user_selection = intval(trim($ussd_body));
            $available_options = explode("|", $sessdetails['loan_items_list']);
            $number_of_loan_items = count($available_options);

            $responseArr['END_OF_SESSION'] = 'False';

            //Means the person wants to go home or back
            if ($user_selection == 99 || $user_selection == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($number_of_loan_items == 0 || $user_selection > $number_of_loan_items) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the selected value was not an integer or the  no. selected was not on list of  no.s on the screen ");
            } else {
                $loan_menu_selected = $available_options[$user_selection - 1];

                if ($loan_menu_selected == 'loan_statement' || $loan_menu_selected == 'loan_enquiry') {//Load loan accounts
                    //$responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails, true);
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['confirm_loan_enquiry'];
                    $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];
                    $data['status'] = 'loanstuff|loanEnquiryStepTwo|LoanEnquiry';
//                    $task = new LoanEnquiry($func);
//                    $responseArr = $task->loanEnquiryStepOne($ussd_body, $mobile_number, $session_id, $sessdetails);
                } else {//Load general accounts
                    if ($loan_menu_selected == 'loan_repayment' && strtolower(trim($sessdetails['loanaccounts'])) == 'loan account'){
                        $responseArr['USSD_BODY'] = $sessdetails['lang']['no_loan_account'].$sessdetails['lang']['home_or_back'];
                        return $responseArr;
                    }
                    
                    
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_loan_amount'];
                    if ($loan_menu_selected == 'loan_repayment')
                    {
                        $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_loan_amount_repay'];
                    }
                    $data['status'] = 'loanstuff|loanStepSix|Loan';
                }
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];

                $data['loan_menu_selected'] = $loan_menu_selected;
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check account selected
     * &&
     * Ask use to select product(Loan application)
     * or
     * Ask user to select loan account (Loan repayment)
     */

    function loanStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
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
                if ($sessdetails['loan_menu_selected'] == 'loan_application') {
                    $data['status'] = 'loan|loanStepFour|Loan';
//                    $responseArr['USSD_BODY'] = $sessdetails['lang']['select_product'];
//                    $spdetails = $this->functions->getServiceProvider("LOAN_PRODUCTS");
//                    $products = explode("|", $spdetails['items']);
//                    $responseArr['USSD_BODY'] .= $products;
                    $responseArr = $this->functions->getLoanProducts($mobile_number, $sessdetails);
                } else {
                    $data['status'] = 'loan|loanStepSix|Loan';
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_loan_amount'];
                }

                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    
     
    
    /* Check product selected
     * &&
     * Ask user to enter amount - step 6
     */

    function loanStepFour($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $product_selected = intval(trim($ussd_body));
            $available_products = explode("|", $sessdetails['loan_products']);
            $number_of_products = count($available_products);

            $responseArr['END_OF_SESSION'] = "False";
            //Check if user wants to proceed or go home/back
            if ($product_selected == 99 || $product_selected == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($product_selected == 0 || $product_selected > $number_of_products) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the selected value was not an integer or the product no. selected was not on list of product no.s on the screen ");
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_amount']; //.$sessdetails['lang']['home_or_back'];

                $data['loan_product_selected'] = $available_products[$product_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'amount|loanStepSix|Loan';
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

   

    /* Check loan account selected
     * &&
     * Ask user to enter amount - step 6
     */

    function loanStepFive($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $account_selected = intval(trim($ussd_body));
            $available_accounts = explode("|", $sessdetails['loan_accounts']);
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

                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_loan_amount'];

                $data['status'] = 'loan|loanStepSix|Loan';
                $data['accto'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check amount entered
     * &&
     * And ask user to confirm or cancel
     */

    function loanStepSix($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $amount_entered = intval(trim($ussd_body));
            $responseArr['END_OF_SESSION'] = "False";

            if (intval($amount_entered) == 99 || intval($amount_entered) == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($sessdetails['loan_menu_selected'] == 'loan_repayment') {
                $responseArr['USSD_BODY'] = str_replace("[AMOUNT]", $amount_entered, $sessdetails['lang']['confirm_loan_repayment']);//str_replace("[ACCOUNT_TO]", $sessdetails['accto'], str_replace("[ACCOUNT_FROM]", $sessdetails['accfrom'], str_replace("[AMOUNT]", $amount_entered, $sessdetails['lang']['confirm_loan_repayment'])));
            } else {
                if ($amount_entered < 1000 || $amount_entered > 10000) {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['loan_amount_invalid'];// . $sessdetails['previous_response'];
                    $this->functions->flog('INFO', __METHOD__ . $mobile_number . "() |Loan amount entered is not valid");
                    return $responseArr;
                }
                $responseArr['USSD_BODY'] = str_replace("[LOAN_TYPE]", $sessdetails['loan_product_selected'], str_replace("[AMOUNT]", $amount_entered, $sessdetails['lang']['confirm_loan_application']));
            }

            $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];

            $data['amount'] = $amount_entered;
            $data['menu'] = 'Menu';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $data['status'] = 'final|loanStepSeven|Loan';
            $this->functions->updateSession($mobile_number, $data);
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Checks if user wants to confirm or cancel the request
     * 
     * End Transaction or ask user if they want to transact again
     */

    function loanStepSeven($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $sessionStatus = "final|endTxn|Functions";
            $responseArr['END_OF_SESSION'] = "false";

            if (intval($ussd_body) == 1) {
                $spdetails = $this->functions->getServiceProvider(strtoupper($sessdetails['loan_menu_selected']));

                $postdetails['f0'] = '0200';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = (intval($sessdetails['amount']) == 0) ? 0 : $sessdetails['amount'];

                $postdetails['fld22'] = 'false';
                $postdetails['fld32'] = '00000063802';
                $postdetails['fld69'] = '001';

                $postdetails['fld24'] = $spdetails['fncode'];
                $postdetails['fld66'] = $sessdetails['loan_product_selected'];
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld102'] = $mobile_number;//$sessdetails['accfrom'];
                $postdetails['fld103'] = $sessdetails['accto'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $result = $this->functions->postEconnect($postdetails);
                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '000') {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    $this->functions->send_sms('+254725764230', 'Buda, hatutashindia hii story ya loans zimebeat!');
                }

                $data['status'] = $sessionStatus;
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['trn_cancel'];

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
