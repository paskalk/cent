<?php

class Enquiry {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     *  Ask user to choose whether they want to do balance enquiry or mini statement 
     */

    function enquiryStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $responseArr['END_OF_SESSION'] = 'False';

            $subminimenu = $this->functions->parseMenuXml($ussd_body, $sessdetails['menulanguagefile'], "subminimenu", $sessdetails['mainmenuselected'], $sessdetails['submenuselected']);
            $responseArr['USSD_BODY'] = $subminimenu['menulist'];

            $data['enquiry_code_list'] = implode("|", $subminimenu['subminicode']);
            $data['menu'] = 'Menu';
            $data['status'] = 'ENQ|enquiryStepTwo|Enquiry';
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check if user wants to check balance or get a ministatement
     * &&
     * Ask for user to select account
     */

    function enquiryStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $user_selection = intval(trim($ussd_body));
            $available_options = explode("|", $sessdetails['enquiry_code_list']);
            $number_of_options = count($available_options);

            //Means the person wants to go home or back
            if ($user_selection == 99 || $user_selection == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($user_selection == 0 || $user_selection > $number_of_options) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {
                $responseArr['END_OF_SESSION'] = "false";
                $responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails);
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];

                $selection = ucfirst($available_options[$user_selection - 1]); //Determines the class to redirect to
                $menu_class = lcfirst((string) $selection . "StepOne"); //Determines first method to be called

                $data['enquiry_selected'] = $selection;
                $data['pending_status'] = '|' . $menu_class . '|' . $selection;
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $data['status'] = 'getaccount|enquiryStepThree|Enquiry';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check Account number entered
     * &&
     * Ask user to confirm or cancel
     */

    function enquiryStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $account_selected = trim($ussd_body);
            $available_accounts = explode("|", $sessdetails['accounts']);
            $number_of_accounts = count($available_accounts);

            $responseArr['END_OF_SESSION'] = "False";

            if (intval($account_selected) == 99 || intval($account_selected) == 77) {//Check if user wants to cancel and go home
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if (!ctype_digit($account_selected) || intval($account_selected) > $number_of_accounts) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {
                $responseArr['USSD_BODY'] = str_replace("[ENQUIRY_TYPE]", $sessdetails['enquiry_selected'], str_replace("[ACCOUNT]", $available_accounts[$account_selected - 1], $sessdetails['lang']['confirm_enquiry']));
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['confirm_cancel'];

                $data['accfrom'] = $available_accounts[$account_selected - 1];
                $data['menu'] = 'Menu';
                $data['status'] = $sessdetails['pending_status'];
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}

// end class
