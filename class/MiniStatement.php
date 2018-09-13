<?php

class MiniStatement {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /* Checks if user wants to confirm or cancel the request
     * 
     * End Transaction or ask user if they want to tranact again
     */

    function miniStatementStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $sessionStatus = "final|endTxn|Functions";
            $responseArr['END_OF_SESSION'] = "false";
            
            if (intval($ussd_body) == 1) {

                $spdetails = $this->functions->getServiceProvider("MINISTATEMENT"); //read mini statements

                $postdetails['f0'] = '0200';
                $postdetails['fld2'] = $mobile_number;
                $postdetails['fld3'] = $spdetails['procode'];
                $postdetails['fld4'] = "0";
                $postdetails['fld68'] = $spdetails['fld68'];
                $postdetails['fld98'] = $spdetails['fld98'];
                $postdetails['fld123'] = $spdetails['fld123'];
                $postdetails['fld126'] = $spdetails['fld68'];
                $postdetails['fld24'] = $spdetails['cbsFncode'];
                if (strlen($sessdetails['accfrom']) < 13) {
                    $postdetails['fld24'] = $spdetails['fncode'];
                }
                $postdetails['fld102'] = $sessdetails['accfrom'];
                $postdetails['logFileName'] = $spdetails['logFileName'];

                $result = $this->functions->postEconnect($postdetails);
                if ($result['data']['field39'] == '00' || $result['data']['field39'] == '000'){
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['customer_resp'];
                } else {
                    $responseArr['USSD_BODY'] = $sessdetails['lang']['trnfail_no_98'];
                    if ($result['data']['field39'] == '51'){
						$responseArr['USSD_BODY'] = $sessdetails['lang']['insufficient_funds'];
					}
					//$this->functions->send_sms('+254726128484','Ministatement zimebeat.. tena');
                }
                
                $data['status'] = $sessionStatus;
                $data['menu'] = 'Menu';
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
