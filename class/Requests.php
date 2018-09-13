<?php

class Requests {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /*
     * Check request type
     */

    function requestsStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $subminimenu = $this->functions->parseMenuXml($ussd_body, $sessdetails['menulanguagefile'], "subminimenu", $sessdetails['mainmenuselected'], $sessdetails['submenuselected']);

            $responseArr['USSD_BODY'] = $subminimenu['menulist'];
            $responseArr['END_OF_SESSION'] = 'False';

            $data['requests_list'] = implode("|", $subminimenu['subminicode']);
            $data['submenuselected'] = $sessdetails['submenuselected']; //Because we hadn't saved it before we passed it from menuStepThree
            $data['menu'] = 'Menu';
            $data['prevstatus'] = $sessdetails['status'];
            $data['status'] = 'otherrequests|requestsStepTwo|Requests';
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $this->functions->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check which option the user wants
     * &&
     * Redirect user to appropriate class (pinchange or stop cheque) and ask them for next step
     */

    function requestsStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $requested_option = intval(trim($ussd_body));
            $available_options = explode("|", $sessdetails['requests_list']);
            $number_of_options = count($available_options);

            $responseArr['END_OF_SESSION'] = "False";
            //Check if user wants to proceed or go home/back
            if ($requested_option == 99 || $requested_option == 77) {
                $responseArr = $this->functions->checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($requested_option == 0 || $requested_option > $number_of_options) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_selection'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the selected value was not an integer or the option selected was not on list of available requests displayed ");
            } else {

                $selection = $available_options[$requested_option - 1]; //Determines the class to redirect to
                $menu_class = lcfirst((string) $selection . "StepOne"); //Determines first method to be called
                //Gets responses for requests that don't involve aasking user to select acount
                $responseArr['USSD_BODY'] = str_replace("[SPECIFIC_RESPONSE]", $sessdetails['lang'][$selection . '_menu'], $sessdetails['lang']['requests_menu']);
                if (trim($sessdetails['lang'][$selection . '_menu']) == 'accounts') {//Means we're supposed to load accounts for user to pick instead
                    $responseArr = $this->functions->loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails);
                }

                $data['request_selected'] = $selection;
                $data['status'] = '|' . $menu_class . '|' . $selection;
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);

                $responseArr['USSD_BODY'] .= $sessdetails['lang']['home_or_back'];
            }

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}

// end class
