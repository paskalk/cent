<?php
/* To enable Loan statements, just set the "active" flag to 1 in menu.xml
 * 
 */


class LoanEnquiry {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /* Check loan account selected
     * &&
     * Ask user to confirm or cancel the transaction (Only for Loan Enquiry  & Statement Request)
     */

    function loanEnquiryStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
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
                $enquiry_type = 'STATEMENT';
                if ($sessdetails['loan_menu_selected'] == 'loan_enquiry') {
                    $enquiry_type = 'LOAN ENQUIRY';
                }

                //$responseArr['USSD_BODY'] = str_replace("[ENQUIRY_TYPE]", $enquiry_type, str_replace("[ACCOUNT]", $available_accounts[$account_selected - 1], $sessdetails['lang']['confirm_loan_enquiry']));
                $responseArr['USSD_BODY'] = $sessdetails['lang']['confirm_loan_enquiry'];
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];
                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = 'post transaction|loanEnquiryStepTwo|LoanEnquiry';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Checks if user wants to confirm or cancel the request
     * 
     * End Transaction or ask user if they want to tranact again
     */

    function loanEnquiryStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $sessionStatus = "final|endTxn|Functions";
            $responseArr['END_OF_SESSION'] = "false";

            if (intval($ussd_body) == 1) {
                $spdetails = $this->functions->getServiceProvider(strtoupper($sessdetails['loan_menu_selected']));

                $postdetails['f0'] = '0200';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = "0";
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld102'] = $sessdetails['accfrom'];
                $postdetails['fld24'] = $spdetails['cbsFncode'];
                if (strlen($sessdetails['accfrom']) < 13) {
                    $postdetails['fld24'] = $spdetails['fncode'];
                }
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $result = $this->functions->postEconnect($postdetails);
                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '000') {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    $this->functions->send_sms('+254727830769','Sasa, iko hivi.. natry ku-access hizi loan balance na mini but zimechapa');
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

// end class
