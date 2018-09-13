<?php

class ServerSafaricom {

    public $functions;
    public $status;

    function __construct($func, $status) {
        $this->functions = $func;
        $this->status = $status;
    }

    /*
     * USSD_MESSAGE is the main function which is similar for all MNOs
     */

    function serverSafaricom($variablesArray, $func, $status) {
        try {
            //Assign meaningful variables to the elements in the associative array [$variablesArray]
            $mobile_number = $variablesArray['MOBILE_NUMBER'];
            $service_code = $variablesArray['SERVICE_CODE'];
            $session_id = $variablesArray['SESSION_ID'];
            $imsi = $variablesArray['IMSI'];
            //The USSD_BODY is delimited by asteriks[*] e.g. 3*2345*20*5 ... so get the last most digit
            $ussdbody = explode('*', $variablesArray['USSD_BODY']);
            $countarray = count($ussdbody) - 1;
            $ussd_body = trim($ussdbody[$countarray]);

            //Check whether it is the first session or not
            if (!$status || trim($ussd_body) == '') {
                //Means that this is the first request
                $sequence = 1;
            } else {
                //Check whether the session has timed out 
                $sessduration = $func->date_difference(strtotime(date("Y-m-d H:i:s")), strtotime($status['sessdate']));
                if ($sessduration['mins'] > 1000) {
                    //Means session has timed out. Set the $sequence variable to 1 for PIN prompt
                    $sequence = 1;
                }
            }


            if (trim($sequence == "1")) {
                //Pass the IMSI and the USSD_BODY to the PIN prompt class in an array
                $status['imsi'] = $imsi;
                $status['text'] = $ussd_body;

                //Ask the customer to enter their PIN
                $task = new Pin($func);
                $responseArr = $task->askForPin($session_id, $mobile_number, $service_code, null, $status);

                unset($func); //unset functions object. we dont need it after this level
                return $responseArr;
            }
            //This level onwards the session exists and we have the values.
            //Check if we have language code in the session. 
            //Most probably we do. its held in the WS return or the customer selected
            if ($status['customer_language'] == "") {
                $langCodes = explode("|", $status['info']);
                $en = $langCodes[intval($ussd_body) - 1];
            } else {
                $en = $status['customer_language'];
            }
            //Get Lang array from the lang xml file and overwrite session[lang] with the langa array
            $lang = $func->getLangAll(($en == null) ? "en" : $en, true); //have it default to english
            $status['en'] = ($en == null) ? "en" : $en; //setr en as default. We may want to have this in the params later -george
            $status['imsi'] = $imsi; //add imsi to the session
            $status['lang'] = $lang; //Store language array in 'lang'

            if ($status['menu']) {//Must be a valid menu
                $menu_class = trim($status['menu']); //Create classname dynamically//will always be menu
                $mc = new $menu_class($func); //Instanciate Classname dynamically. res is the dynamic class object
                //1st chr lowercase e.g from BalMini to balMini(naming conventions)
                //Dynamically load the Process fn. most times this is the Menu class
                $menu_method = ucfirst((string) $menu_class . "Process");
                //dynamically return the response from respective Class
                $responseArr = $mc->$menu_method($ussd_body, $mobile_number, $session_id, $status);
                unset($func); //unset functions object. we dont need it after this level
                return $responseArr;
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function date_difference($date1timestamp, $date2timestamp) {
        try {
            $all = round(($date1timestamp - $date2timestamp) / 60);
            $d = floor($all / 1440);
            $h = floor(($all - $d * 1440) / 60);
            $m = $all - ($d * 1440) - ($h * 60);
            //Since you need just hours and mins
            return array('hours' => $h, 'mins' => $m);
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}
