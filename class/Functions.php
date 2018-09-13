<?php

class Functions {

    var $logDir;
    var $countfile;
    var $sessionDir;
    var $menuDir;
    var $keydes;
    var $langFile;
    var $langDir;
    var $simBanking;
    var $genConfigDir;
    var $configFile = "app_config.ini";
    var $pageOneMenuCount;
    var $accPrefix;
    var $defaultLang;
    var $ATMMultiples;
    var $safaricomPrefixes;
    var $phoneIdentifierLength;
    var $defaultPrefix;
    var $countryCode;
    var $httpserver;
    var $logFiles;
    var $callingfileName;

    function Functions() {

        $config = new Parse($this->configFile);
        $this->logDir = $config->logDir;
        $this->countFile = $config->countFile;
        $this->sessionDir = $config->sessionDir;
        $this->menuDir = $config->menuDir;
        $this->keydes = $config->keydes;
        $this->langFile = $config->langFile;
        $this->langDir = $config->langDir;
        $this->simBanking = $config->simBanking;
        $this->genConfigDir = $config->genConfigDir;
        $this->pageOneMenuCount = $config->pageOneMenuCount;
        $this->accPrefix = $config->accPrefix;
        $this->defaultLang = $config->defaultLang;
        $this->atmMultiples = $config->atmMultiples;
        $this->safaricomPrefixes = $config->safaricomPrefixes;
        $this->phoneIdentifierLength = $config->phoneIdentiierLength;
        $this->defaultPrefix = $config->defaultPrefix;
        $this->countryCode = $config->countryCode;
        $this->httpserver = $config->httpserver;
        //$this->callingfileName = $callingFileName;
        $this->logsFile = $config->logsFile; //[$this->callingfileName];


        return true;
    }

    function clean($string) {
        $string = str_replace(' ', '-', $string);
        return preg_replace('/[#]/', '', $string);
    }

    function Oldlog($dirname, $file, $info) {
        $todayslogs = $this->logDir . strtoupper(date("d-M-Y"));
        if (!file_exists($todayslogs)) {
            //mkdir($todayslogs, NULL, TRUE);
            mkdir($todayslogs, 0777, TRUE);
        }
        $foldername = $todayslogs . '/' . $dirname;
        if (!file_exists($foldername)) {
            //mkdir($foldername, NULL, TRUE);
            mkdir($foldername, 0777, TRUE);
        }
        //2016051310 <- YY-DD-MM-HH
        $filename = $foldername . '/' . $file . '_' . substr($this->time_stamp(), 0, strlen($this->time_stamp()) - 4) . ".log";
        file_put_contents($filename, $info . " " . $this->now() . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    function log($dirname, $file, $string, $type = "INFO") {
        $date = date("Y-m-d H:i:s");
        $todayslogs = $this->logDir . strtoupper(date("d-M-Y"));
        if (!file_exists($todayslogs)) {
            //mkdir($todayslogs, NULL, TRUE);
            mkdir($todayslogs, 0777, TRUE);
        }
        $foldername = $todayslogs . '/' . $dirname;
        if (!file_exists($foldername)) {
            mkdir($foldername, 0777, TRUE);
        }

        $filename = $foldername . '/' . $file . '_' . substr($this->time_stamp(), 0, strlen($this->time_stamp()) - 4) . ".log";

        if ($fo = fopen($filename, 'ab')) {
            fwrite($fo, "$date - [ $type ] " . $_SERVER['PHP_SELF'] . " | $string\n");
            fclose($fo);
        } else {
            trigger_error("log Cannot log '$string' to file '$file' ", E_USER_WARNING);
        }
    }

    /**
     * Trying to store session in files
     * That will eliminate db connections between hops
     * Make it perform even better
     * */
    function getSession($mobile_number, $session) {
        try {
            $sessdir = $this->sessionDir . "/" . date("d-M-Y");
            $sessionFile = $sessdir . "/SESS_" . $mobile_number . ".xml";
            if (!file_exists($sessionFile)) {
                return null;
            } else {
                $config = file_get_contents($sessionFile);
                $arr = $this->stdObjectToArray(simplexml_load_string($config));

                $sessDetails = array();
                foreach ($arr as $key => $value) {
                    $newValue = $this->getValueXML($value, $mobile_number);
                    if ($newValue == $key . $key) {
                        $sessDetails[$key] = $key;
                    } else {
                        $sessDetails[$key] = str_replace($key, "", $newValue);
                    }
                }
                if (trim($sessDetails['sessionid']) !== trim($session)) {
                    unlink($sessionFile);
                    return null;
                }

                return $sessDetails;
            }
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /*     * *
     * unlink session
     * *** */

    function clearSession($mobile_number, $del = null) {
        try {
            $sessdir = $this->sessionDir . "/" . date("d-M-Y");
            $sessionFile = $sessdir . "/SESS_" . $mobile_number . ".xml";
            if ($del) {
                return unlink($sessionFile);
            } else {
                //A lot more work will be needed here if we want to retain the sessions
                return unlink($sessionFile); //When we will need to retain sessions
            }
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function getNarrations($procode, $account_number, $account_number_to) {

        $narrations['01'] = "MW:CashWithdraw by [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['21'] = "MW:Cash Deposit by [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['22'] = "MW:Sub-Wallet Upload to [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['23'] = "MW:Agent-Wallet Upload to [Agent number] Ref  [mWallet Sys Trans Ref]";
        $narrations['31'] = "MW:Bal Inquiry on [mobile number] Ref [mWallet Sys Trans Ref]";
        $narrations['38'] = "MW:Mini Statm on [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['40'] = "FUNDS TRANSFER";
        $narrations['42'] = "MW:Airtime Topup by [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['43'] = "MW:Fund Trans B2C from [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['45'] = "MW:Fund Trans C2B from [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['50'] = "MW:Bill Pay by [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['53'] = "MW:School fees Paid by [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['51'] = "MW:Items Purchase by [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['62'] = "MW:Cardless Origination on [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['63'] = "MW:Cardless Fulfilment on [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['90'] = "MW:Full Statement on [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['64'] = "MW:Loan Request on [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['67'] = "MW:Loan Mini Request on [account number] Ref [mWallet Sys Trans Ref]";
        $narrations['66'] = "MW:Loan Bal Request on [account number] Ref [mWallet Sys Trans Ref]";

        $key = substr($procode, 0, 2);
        $raw_narration = str_replace("[account number to]", $account_number_to, str_replace("[account number]", $account_number, $narrations[$key]));

        return $raw_narration;
    }

    function getJSONResponse($FromEC, $status = null, $txnType = null) {
        if ($txnType == null) {
            $txnType = 'IN';
        }
        $response = $this->stdObjectToArray(json_decode(str_replace("]", "", str_replace("[", "", $FromEC))));

        if (isset($response['mwalletcstms'])) {
            $retArray['mwallet'] = array_change_key_case($this->stdObjectToArray(json_decode($response['mwalletcstms'])), CASE_LOWER);
        }
        if (isset($response['merchantdetails'])) {
            $retArray['merchantdetails'] = $response['merchantdetails'];
        }
        if (isset($response['loandetails'])) {
            $retArray['loandetails'] = $response['loandetails'];
        }
        if (isset($response['simbanking'])) {
            $retArray['simbanking'] = array_change_key_case($this->stdObjectToArray(json_decode($response['simbanking'])), CASE_LOWER);
        }

        if (isset($response['group_details'])) {
            $retArray['group_details'] = $response['group_details'];
        }
        if (isset($response['loan_accounts'])) {
            $retArray['loan_accounts'] = $response['loan_accounts'];
        }

        if (isset($response['enquiry'])) {
            $retArray['enquiry'] = array_change_key_case($this->stdObjectToArray(json_decode($response['enquiry'])), CASE_LOWER);
        }
        return $retArray;
    }

    /*
     * Create session file
     */

    function createSession($mobile_number, $sessionCols) {
        try {
            $sessdir = $this->sessionDir . "/" . date("d-M-Y");
            if (!file_exists($sessdir)) {
                mkdir($sessdir);
            }
            $sessionFilexml = $sessdir . "/SESS_" . $mobile_number . ".xml";
            $iniDetailsdd = array();
            foreach ($sessionCols as $key => $value) {
                if (trim($value) !== "") {
                    $iniDetailsdd[$key] = $this->bindValueXML($value . $key, $mobile_number);
                }
            }
            $newSessCols = array_flip($iniDetailsdd);
            $xml = new SimpleXMLElement('<sessionDetails/>');
            array_walk_recursive($newSessCols, array($xml, 'addChild'));
            if (!file_put_contents($sessionFilexml, $xml->asXML() . PHP_EOL, FILE_APPEND | LOCK_EX)) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /*
     * update session file
     */

    function updateSession($mobile_number, $setVars) {
        try {
            $sessdir = $this->sessionDir . "/" . date("d-M-Y");
            $sessionFile = $sessdir . "/SESS_" . $mobile_number . ".xml";
            //get the xml
            $xmlDetails = file_get_contents($sessionFile);
            $iniDetailsExisting = $this->stdObjectToArray(simplexml_load_string($xmlDetails));

            $ArrayTosetVars = array();
            foreach ($setVars as $key => $value) {
                $ArrayTosetVars[$key] = $this->bindValueXML($value . $key, $mobile_number);
            }
            $arraytomerge['iniDetailsExisting'] = $iniDetailsExisting;
            $arraytomerge['setVars'] = $ArrayTosetVars;
            $finalArray = array_flip(array_merge($arraytomerge['iniDetailsExisting'], $arraytomerge['setVars']));
            unlink($sessionFile);
            $xml = new SimpleXMLElement('<sessionDetails/>');
            array_walk_recursive($finalArray, array($xml, 'addChild'));
            if (!file_put_contents($sessionFile, $xml->asXML() . PHP_EOL, FILE_APPEND | LOCK_EX)) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function now() {
        //return date('Y-m-d H:i:s');
        return date('H:i:s');
    }

    function date_difference($date1timestamp, $date2timestamp) {
        $all = round(($date1timestamp - $date2timestamp) / 60);
        $d = floor($all / 1440);
        $h = floor(($all - $d * 1440) / 60);
        $m = $all - ($d * 1440) - ($h * 60);
//Since you need just hours and mins
        return array('hours' => $h, 'mins' => $m);
    }

    //Functional code
    function getFnCode($myid = null, $getwhere = null, $accountnumber = null, $getlength = null) {

        $accountsxml = $this->genConfigDir . 'linked_accounts.xml';
        $file = fopen($accountsxml, "r") or exit("Unable to open file!");
        $xmlstr = "";
        while (!feof($file)) {
            $xmlstr .= fgets($file);
        }
        $xmlObject = simplexml_load_string($xmlstr);

        foreach ($xmlObject->isomsg->field as $field) {
            $id = $field->attributes()->id;
            $code = $field->attributes()->code;
            $length = $field->attributes()->length;
            $value = $field->attributes()->value;
            if ($getwhere) {
                for ($i = 1; $i < 5; $i++) {
                    $retval .=$id . $i . "='" . $accountnumber . "' or ";
                }
            } elseif ($getlength) {
                if (trim($myid) === trim($code)) {
                    $retval = trim($length);
                    break;
                }
            } else {
                if (trim($myid) === trim($id)) {
                    $retval = trim($value);
                    break;
                }
            }
        }
        return $retval;
    }

    //validate local accounts
    function AccountValidation($account, $return = null) {
        $retval = array();
        str_replace('+', '', $account);
        if ($return == false) {
            $accountlength = strlen(trim($account));
            switch ($accountlength) {
                case 12://mobile
//                    //customer side memo code. NB GLs are not considered in this case... -george 20150626
//                    if (in_array(substr($account, 0, 3), explode(".", $this->mwalletPrefix))) {
//                        $retval['account'] = trim($account);
//                        $retval['ac_class'] = "MWALLET_AC";
//                        $retval['ac_type'] = "MWallet Account";
//                    } else {
//                        $retval['ac_class'] = "SACCO_AC";
//                        $retval['ac_type'] = "Sacco Account";
//                        $retval['account'] = $account;
//                    }
                    break;
                case 13:
                    //Account Validity
                    $actype_allowed = explode(".", $this->accPrefix);
                    //print_r($actype_allowed);
                    //die(substr(trim($account), 0, 3));
                    if (in_array(substr(trim($account), 0, 3), $actype_allowed)) {
                        $retval['account'] = $account;
                        $retval['ac_type'] = "Century Account";
                        $retval['ac_class'] = "CBKL_AC";
                    } else {
                        $retval['account'] = $account;
                        $retval['ac_type'] = "Sacco Account";
                        $retval['ac_class'] = "SACCO_AC";
                    }
                    break;
                case 10://mobile

                    $retval['account'] = $this->defaultPrefix . substr($account, (intval($this->phoneIdentifierLength) * -1), intval($this->phoneIdentifierLength));
                    $retval['ac_type'] = "MWallet Account";
                    $retval['ac_class'] = "MWALLET_AC";
                    break;
                case 16://card whichever
                    switch (substr($account, 0, 8)) {

                        case '42993321': //Co-op Virtual: 42993321XXXXXXXX
                            $retval['account'] = $account;
                            $retval['ac_type'] = "CRDB Pay Card";
                            $retval['ac_class'] = "CRDB_PAY_AC";
                            break;
                        case '42993421':// Co-op Sacco: 42993421XXXXXXXX
                            $retval['account'] = $account;
                            $retval['ac_type'] = "Sacco Pay Card";
                            $retval['ac_class'] = "SACCO_PAY_AC";
                            break;
                        default:
                            switch (substr($account, 0, 6)) {
                                case '440782'://Co-op credit Local: 440782XXXXXXXXX
                                    $retval['account'] = $account;
                                    $retval['ac_type'] = "CRDB credit Card Local";
                                    $retval['ac_class'] = "CREDIT_CARD_NO";
                                    break;
                                case '479740'://Co-op credit Classic: 479740XXXXXXXXX
                                    $retval['account'] = $account;
                                    $retval['ac_type'] = "Credit card Classic";
                                    $retval['ac_class'] = "CREDIT_CARD_NO";
                                    break;
                                case '440781':// Co-op credit Gold: 440781XXXXXXXXXX* */
                                    $retval['account'] = $account;
                                    $retval['ac_type'] = "CRDB credit Card Gold";
                                    $retval['ac_class'] = "CREDIT_CARD_NO";
                                    break;
                                default ://must be a sacco link
                                    $retval['ac_class'] = "SACCO_VIRTUAL_AC";
                                    $retval['ac_type'] = "Sacco Link Card";
                                    $retval['account'] = $account;
                                    break;
                            }
                            break;
                    }
                    break;
                default :
//cds and sacco to be worked on when we get account structure
                    $retval['ac_class'] = "SACCO_VIRTUAL_AC";
                    $retval['ac_type'] = "Sacco Account";
                    $retval['account'] = $account;
                    break;
            }
        }
        return $retval;
    }

    function bindValueXML($data, $phone) {
        //Generate a key from a hash
        $key = md5(utf8_encode(base64_encode($this->keydes) . md5($phone)), true);
        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);
        //Pad for PKCS7
        $blockSize = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = $blockSize - ($len % $blockSize);
        $data .= str_repeat(chr($pad), $pad);
        //Encrypt data
        $encData = mcrypt_encrypt('tripledes', $key, $data, 'ecb');
        return base64_encode($encData);
    }

    function getValueXML($data, $phone) {

        //Generate a key from a hash
        $key = md5(utf8_encode(base64_encode($this->keydes) . md5($phone)), true);
        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);
        $data = base64_decode($data);
        $data = mcrypt_decrypt('tripledes', $key, $data, 'ecb');
        $block = mcrypt_get_block_size('tripledes', 'ecb');
        $len = strlen($data);
        $pad = ord($data[$len - 1]);
        return substr($data, 0, strlen($data) - $pad);
    }

    function Decrypt($data) {

        //Generate a key from a hash
        $secret = "@123Century321#";
        $key = md5(utf8_encode($secret), true);

        // $key = hash('sha1', utf8_encode($secret));
        //Take first 8 bytes of $key and append them to the end of $key.
        $key .= substr($key, 0, 8);

        $data = base64_decode($data);

        $data2 = mcrypt_decrypt('tripledes', $key, $data, 'ecb');

        $block = mcrypt_get_block_size('tripledes', 'ecb');
//        $len = strlen($data);
//        $pad = ord($data[$len - 1]);

        return substr($data2, 0, strlen($data2) - $block);
    }

    function validateID($ussd_body, $registered_id) {

        if (trim(strtoupper($ussd_body)) != trim(strtoupper($registered_id))) {
            return false;
        }

        return true;
    }

    function maskCards($encodedCardNumber) {
        $card = $encodedCardNumber; //base64_decode($encodedCardNumber);
        //return substr($card, 0, 8) . str_repeat("*", 4) . substr($card, -4);
        return substr($card, 0, 6) . str_repeat("*", 6) . substr($card, -4);
    }

    function encryptPin($mobile_number, $pin) {
        try {
            $salt = "$2a$12$8nf7v5yaB/MpJQp7Jo7eOu";
            $hashedPayload = base64_encode($mobile_number . $pin);
            $encryptedPin = crypt($hashedPayload, $salt);
            return $encryptedPin;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    public function sendToServlet($params) {
        try {
            $post_body = $params;
            $response = "";
            $curl = curl_init();
            curl_setopt($curl, CURLOPT_URL, $this->httpserver);
            curl_setopt($curl, CURLOPT_POST, true); // Use POST
            curl_setopt($curl, CURLOPT_POSTFIELDS, $post_body); // Setup post body
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true); // Receive server response
            curl_setopt($curl, CURLOPT_HTTPHEADER, array(
                "text/xml;charset=UTF-8"
            ));
            $response = curl_exec($curl);
            if ($response) {
                return $response;
            } else {
                return false;
            }
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    //post to servlet
    function postEconnect($postdetails) {
        try {
            $strXML = $this->ecMessage($postdetails);
            //print_r($strXML);

            $servletResponse = $this->sendToServlet(base64_encode($strXML));

            $Response = base64_decode($servletResponse);
            $connerror = 'Connection refused';
            $licenseErr = "Invalid License";

            if (strpos($Response, $connerror) !== false || strpos($Response, $licenseErr) !== false) {
                $ResponseFromEC['success'] = false;
                $ResponseFromEC['data'] = array("eErr" => "System Unavailable");
                return $ResponseFromEC;
            }

            if ($Response == null) {
                $ResponseFromEC['success'] = false;
                $ResponseFromEC['data'] = array("eErr" => "System Unavailable");

                $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . 'Servlet is Unavailable') . date('His');
            } else {

                $ResponseFromEC['success'] = true;
                $ResponseFromEC['data'] = $this->getValXML($Response);
            }
            //print_r($Response);
            return $ResponseFromEC;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function ecMessage($postdetails) {
        try {
            $stan = $this->createStan();
            do {
                $unique = rand(1, 100000);
            } while ($unique >= 100000);
            $unique = str_pad($unique, 6, "0", STR_PAD_LEFT);

            $cols['f0'] = $postdetails['f0'];
            $cols['f2'] = $postdetails['fld2'];
            $cols['f3'] = $postdetails['fld3'];
            $cols['f4'] = $postdetails['fld4'];
            $cols['f7'] = date('mdHis'); //2016031412
            $cols['f11'] = $stan; //1
            $cols['f12'] = date('His'); // n6, hhmmss
            $cols['f13'] = date('Ymd');
//            $cols['f15'] = $cols['f13'];
//            $cols['f17'] = $cols['f13'];
            $cols['f24'] = $postdetails['fld24'];

            $cols['f22'] = $postdetails['fld22'];
            $cols['f32'] = $postdetails['fld32'];
            $cols['f69'] = $postdetails['fld69'];



            $cols['f33'] = $this->keydes;
            //$cols['f35'] = $postdetails['fld35'];
            $cols['f37'] = $unique . $stan;
            $cols['f40'] = '000';
            $cols['f41'] = 'FID00001';
            $cols['f42'] = '606465ATM000001';
            $cols['f43'] = 'CENTURY MFI';
            $cols['f46'] = $postdetails['fld46'];
            $cols['f47'] = $postdetails['fld47'];
            $cols['f48'] = $postdetails['fld48'];
            $cols['f49'] = 'KES';
            $cols['f56'] = 'bcac5bdc-32fb-42df-8ddb-ce8f705ba8c5';
            //$cols['f66'] = $postdetails['fld66'];
            $cols['f60'] = 'KE';
            $cols['f64'] = $postdetails['fld64'];
            $cols['f65'] = $postdetails['fld65'];
            $cols['f67'] = $postdetails['fld67'];
            //$narr = $this->getNarrations($cols['f3'], $cols['f2'], $cols['f37']);
            $cols['f68'] = $postdetails['fld68'];
            //$cols['f68'] = (strlen(trim($postdetails['fld68'])) <= 0) ? $this->getNarrations($cols['f3'], $postdetails['fld102'], $postdetails['fld103']) : trim($postdetails['fld68']);
            //$cols['f68'] = (strlen($narr) <= 0) ? $postdetails['fld68'] : $narr;
            $cols['f96'] = $postdetails['fld96'];
            //$cols['f97'] = $postdetails['fld97'];
            $cols['f98'] = $postdetails['fld98'];
            $cols['f100'] = $postdetails['fld100'];
            //$cols['f101'] = $postdetails['fld101'];
            $cols['f102'] = $postdetails['fld102'];
            $cols['f103'] = $postdetails['fld103'];
            $cols['f123'] = $postdetails['fld123'];
            $cols['f126'] = $postdetails['fld126'];

            $strXML = $this->genXML($cols);

            //$cdigit = hash_hmac('SHA512', (strlen($strXML) * intval($stan)) . $cols['f32'], $cols['f33']); //generate a check digit for channels

            $this->log("OUT_XML", $postdetails['logFileName'], $cols['f2'] . ": " . $strXML);

            return $strXML;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function getValXML($xmlFromEC) {
        $xmlObject = simplexml_load_string($xmlFromEC);

        foreach ($xmlObject->isomsg->field as $field) {
            $id = trim($field->attributes()->id);
            $value = trim($field->attributes()->value);
            $cols[$id] = $value;
        }
        $cols = $this->stdObjectToArray($xmlObject);
        $name = trim($cols['field126']) == "" ? $cols['field68'] : $cols['field126'];
        $docname = str_replace(" ", "", strtoupper($name));
        $this->log("IN_XML", $docname, $cols['field2'] . ": " . $xmlFromEC);
        return $cols;
    }

    function checkPin($pin, $salt, $encpin) {
        $pword = $this->encryptPin($salt, $pin);

        if ($pword != $encpin) {
            return false;
        }
        return true;
    }

    function getMenu($en = null) {
        $myXML = $this->menuDir . "menu_" . $en . ".xml";
        //Overide the above and used below
        //$myXML = $this->menuDir . "menu_en" . ".xml";
        $file = fopen($myXML, "r") or exit("Unable to open file!menu");
        $myXMLData = "";
        while (!feof($file)) {
            $myXMLData .= fgets($file);
        }
        return $myXMLData;
    }

    function getGroupAcount() {
        $myXML = $this->genConfigDir . "group_account_type.xml";
        $file = fopen($myXML, "r") or exit("Unable to open file!provider");
        $myXMLData = "";
        while (!feof($file)) {
            $myXMLData .= fgets($file);
        }

        $xml = simplexml_load_string($myXMLData) or die("Error: Cannot create object");
        $x = 1;
        $acodes = array();
        $retval = array();
        foreach ($xml->field as $provider) {
            $acodes[$x] = $provider->attributes()->accode;
            $acdescription[$x] = $provider->attributes()->acdesc;
            $menu .= "\n" . $x . ". " . $provider->attributes()->acdesc;

            $x++;
        }
        $retval['acodes'] = $acodes;
        $retval['acdesc'] = $menu;
        $retval['acdescription'] = $acdescription;


        fclose($file);

        return $retval;
    }

    function parseMenuXml($ussd_body = null, $en = null, $menutype = null, $base_code = null, $submenu_code = null, $exemption = null) {
        try {
            if (strpos($en, "pr") !== false) {
                $en = str_replace(" ", "", str_replace("pr", "", $en)) . "pr";
            }
            $menuxml = $this->getMenu(trim($en));
            $xml = new SimpleXMLElement($menuxml);
            $i = 1;
            $menu = "";
            if (is_array($menutype)) {
                $menupage = $menutype['page'];
                $menutype = $menutype['title'];
            }
            switch ($menutype) {
                case 'mainmenu':
                    foreach ($xml->children() as $mainmenu) {
                        //print_r($mainmenu);
                        //exit();
                        if ($mainmenu->children()) {
                            if ($mainmenu['active'] == '1' && !in_array(trim($mainmenu['code']), $exemption['deny'], true)) {
                                if ($i <= intval($this->pageOneMenuCount) && $menupage == 2) {
                                    $i++;
                                    continue;
                                }
                                $menu['menulist'].="\n" . $i . ". " . $mainmenu["value"];
                                $mainmenucodes[$i] = $mainmenu['code'];
                                $menu['enabled'] = $mainmenu['active'];
                                $i++;
                                if ($i > intval($this->pageOneMenuCount) && $menupage == 1) {
                                    $menu['lastmenu'] = intval($this->pageOneMenuCount);
                                    break;
                                }
                            }
                        }
                    }
                    if ($mainmenucodes) {
                        $menu['mainmenucodes'] = $mainmenucodes;
                    }
                    break;
                case 'submenu':
                    $menucount = 0;
                    foreach ($xml->children() as $mainmenu) {

                        if ($mainmenu->children()) {

                            foreach ($mainmenu->children() as $submenu) {

                                $menucount++;
//                            if ($i < 7 && $menupage == 2) {
//                                $i++;
//                                continue;
//                            }

                                if (trim($mainmenu['code']) == trim($base_code)) {//third gen is the main menus
                                    $enabled = $submenu['active']; //exemption allow and deny allow for override of the active menu flag --george 20151004
                                    if ((intval($enabled) == 1 && !in_array($submenu['code'], $exemption['deny'])) || in_array($submenu['code'], $exemption['allow'])) {
                                        $menu['code'][$i] = $submenu['code'];
                                        $menu['class'] = $mainmenu['code'];
                                        $menu['menulist'].="\n" . $i . ". " . $submenu['value'];
                                        $i++;
                                    }
                                }
//                            $i++;
//                            if ($i > 6 && $menupage == 1) {
//                                $menu['lastmenu'] = 6;
//                                break;
//                            }
                            }
                        }
                        if (isset($menu['menulist'])) {
                            $menu['menucount'] = $menucount;
                            break;
                        }
                    }

                    break;
                case 'subminimenu':

                    if (!is_array($exemption)) {
                        $exemption = array();
                    }
                    foreach ($xml->children() as $mainmenu) {
                        if (trim($mainmenu['code']) == trim($base_code)) {

                            foreach ($mainmenu->children() as $submenu) {

                                //echo trim($submenu['code']) . "-" . trim($submenu_code);
                                if (trim($submenu['code']) == trim($submenu_code)) {
                                    //die($ussd_string . "," . $en . "," . $menutype . "," . $base_code . "," . $submenu_code . "," . $exemption);
                                    foreach ($submenu->children()as $subminimenu) {

                                        $validmenu = (string) $subminimenu['code'];
                                        $activemenu = (string) $subminimenu['active'];

                                        if (!in_array(trim($validmenu), $exemption['deny'], true) && intval(trim($activemenu)) === 1) {
                                            $menu['menulist'] .="\n" . $i . ". " . $subminimenu['value'];
                                            $subminicode[$i] = $subminimenu['code'];
                                            $i++;
                                        }
                                    }
                                }
                                if (intval($submenu['id']) == $ussd_body && !isset($menu['code'])) {//third gen is the main menus
                                    $menu['code'] = $submenu['code'];
                                    $menu['enabled'] = 1;
                                }
                            }
                        }
                    }
                    if ($subminicode) {
                        $menu['subminicode'] = $subminicode;
                    }
                    break;
                default:
                    foreach ($xml->children() as $second_gen) {
                        if ($second_gen->children()) {
                            foreach ($second_gen->children() as $mainmenu) {
                                if ($mainmenu->children()) {
                                    foreach ($mainmenu->children() as $submenu) {
                                        $menu .="\n" . $i . ". " . $mainmenu['value'];
                                    }
                                }
                            }
                        }
                    }
                    break;
            }
            return $menu;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function countMenu($menutype, $en, $base = null, $subbase = null, $exemption = null) {
        if (strpos($en, "pr") !== false) {
            $en = str_replace(" ", "", str_replace("pr", "", $en)) . "pr";
        }
        $menuxml = $this->getMenu(trim($en));
        $xml = new SimpleXMLElement($menuxml);
        $i = 0;

        switch ($menutype) {
            case 'mainmenu':
                foreach ($xml->children() as $mainmenu) {
                    if ($mainmenu->children()) {
                        if ($mainmenu['active'] == '1') {
                            $i++;
                        }
                    }
                }
                return $i;
            case 'submenu':
                foreach ($xml->children() as $mainmenu) {
                    if ($mainmenu->children()) {

                        foreach ($mainmenu->children() as $submenu) {
                            if (trim($mainmenu['code']) == trim($base)) {//third gen is the main menus
                                $enabled = $submenu['active'];
                                if ((intval($enabled) == 1 && !in_array($submenu['code'], $exemption['deny'])) || in_array($submenu['code'], $exemption['allow'])) {
                                    $i++;
                                }
                                break;
                            }
                        }
                    }
                }
                return $i;
            case 'subminimenu':
                foreach ($xml->children() as $mainmenu) {
                    if (trim($mainmenu['code']) == trim($base)) {
                        foreach ($mainmenu->children() as $submenu) {
                            if (trim($submenu['code']) == trim($subbase)) {
                                foreach ($submenu->children()as $subminimenu) {
                                    $validmenu = (string) $subminimenu['code'];
                                    $activemenu = (string) $subminimenu['active'];
                                    if (!in_array($validmenu, $exemption, true) && intval(trim($activemenu)) === 1) {
                                        $i++;
                                    }
                                }
                            }
                        }
                    }
                }

                return $i;
            default:
                return 0;
        }
        return $i;
    }

    function time_stamp() {
        return date('YmdHis');
    }

    function createStan() {
        return str_pad(mt_rand(1, 999999), 6, "0", STR_PAD_LEFT);
    }

    function createStan_ken() {
        $filename = $this->countfile;
        $handle = fopen($filename, "r+");
        $count1 = "99998";
        if (flock($handle, LOCK_EX)) {
            $count1 = fread($handle, filesize($filename));    //Get Current Hit Count
            $count2 = intval($count1);
            $count3 = $count2 + 1;    //Increment Hit Count by 1
            $count4 = ($count3 > 999999) ? 100000 : $count3; // reset to 100000
            $count5 = ($count4 < 100000) ? 100000 : $count4; // reset to 100000

            $count6 = str_pad($count5, 6, "0", STR_PAD_LEFT);
            rewind($handle);
            fwrite($handle, $count6);    //Write the new Hit Count
            flock($handle, LOCK_UN);    //Unlock File

            fclose($handle);

            return $count6;
        }
    }

    /*
     * Basically converts xml to array
     */

    function stdObjectToArray($d) {
        try {
            if (is_object($d)) {
                // Gets the properties of the given object
                // with get_object_vars function
                $d = get_object_vars($d);
            } else {
                if (is_array($d)) {
                    /*
                     * Return array converted to object
                     * Using __FUNCTION__ (Magic constant)
                     * for recursive call
                     */
                    return array_map(__FUNCTION__, $d);
                }
            }
            return $d;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function getServiceProvider($spname) {
        $myXML = $this->genConfigDir . "service_providers.xml";
        $file = fopen($myXML, "r") or exit("Unable to open file!provider");
        $myXMLData = "";
        while (!feof($file)) {
            $myXMLData .= fgets($file);
        }

        $xml = simplexml_load_string($myXMLData) or die("Error: Cannot create object");

        foreach ($xml->field as $provider) {

            $fldname = $provider->attributes()->spname;
            if (trim($fldname) == trim($spname)) {
                $spdetails = array_values($this->stdObjectToArray($provider->attributes()));
                return $spdetails[0];
            }
        }

        fclose($file);

        return $menu;
    }

    function checkAccountTypeValidity($linkedacs, $acCode) {
        $countLinkedAccounts = count($linkedacs);
        for ($i = 0; $i < $countLinkedAccounts; $i++) {
            if (trim($linkedacs[$i]) == "") {
                unset($linkedacs[$i]);
            }
            $specfrom = $this->AccountValidation($linkedacs[$i]);
            $fncode = $this->getFnCode($specfrom['ac_class']);
            if (trim($fncode) !== $acCode) {
                unset($linkedacs[$i]);
            }
        }

        $finalLinkedAccounts = array_values($linkedacs);
        if (count($finalLinkedAccounts) > 0) {
            $include = true;
        } else {
            $include = false;
        }
        return $include;
    }

    function getValidAccountTypes($accounttype = null, $includes = null) {
        $myXML = $this->genConfigDir . "account_type.xml";
        $file = fopen($myXML, "r") or exit("Unable to open file!");
        $myXMLData = "";
        while (!feof($file)) {
            $myXMLData .= fgets($file);
        }

        $xml = simplexml_load_string($myXMLData) or die("Error: Cannot create object");
        $i = 0;
        $mainmenucodes = array();
        foreach ($xml->field as $provider) {
            if (strlen(trim($accounttype)) > 0) {
                if ($provider->attributes()->name == trim($accounttype)) {
                    $menu = $provider->attributes()->name;
                    break;
                }
            } else {
                $active = intval($provider->attributes()->active);
                $accode = $provider->attributes()->accode;
                if ($active == 1 || in_array(trim($accode), $includes, true)) {
                    $i++;
                    $menu['menulist'].="\n" . $i . ". " . $provider->attributes()->name;
                    $mainmenucodes[$i] = $accode;
                }
            }
        }
        if (is_array($menu)) {
            $menu['menulist'].="\n";
            $menu['mainmenucodes'] = array_values($mainmenucodes);
        }

        fclose($file);
        return $menu;
    }

    function getSpecialServices() {
        //we are meant to get this data from the db we may implement a processing code for it later --george
        $myXML = $this->genConfigDir . "special_services.xml";
        $file = fopen($myXML, "r") or exit("Unable to open file!");
        $myXMLData = "";
        while (!feof($file)) {
            $myXMLData .= fgets($file);
        }

        $xml = simplexml_load_string($myXMLData) or die("Error: Cannot create object");
        $i = 0;
        $mainmenucodes = array();
        foreach ($xml->field as $provider) {
            $active = intval($provider->attributes()->active);
            if ($active == 1) {
                $i++;
                $menu['menulist'].="\n" . $i . ". " . $provider->attributes()->name;
                $mainmenucodes[$i] = $provider->attributes()->code;
            }
        }
        if (is_array($menu)) {
            $menu['menulist'].="\n";
            $menu['mainmenucodes'] = array_values($mainmenucodes);
        }
        fclose($file);

        return $menu;
    }

    function genXML($cols) {
        $strAML = "";
        if (is_array($cols)) {
            $strAML .= "<?xml version= \"1.0\"  encoding= \"utf-8\"?> ";
            $strAML .= "<message> ";
            for ($i = 0; $i <= 128; $i++) {
                $key = "f$i";
                if (trim($cols[$key]) !== "") {
                    $strAML .= "<field$i>" . $cols[$key] . "</field$i> ";
                }
            }
            //12:18 PM 25-Feb-16 || Was advised to add the [branch102] tag below to sort out Mini Statement transactions
            //$strAML .= "<branch102>000</branch102>";
            $strAML .= "</message> ";
        } else {
            $strAML = "ERROR. None array.";
        }

        return $strAML;
    }

    function genXML_($cols) {
        $strAML = "";
        if (is_array($cols)) {
            $strAML .= "<?xml version= \"1.0\"  encoding= \"utf-8\"?> ";
            $strAML .= "<message> ";
            if (trim($cols['f0']) !== "") {
                $strAML .= "<field0>" . $cols['f0'] . "</field0> ";
            }
            if (trim($cols['f2']) !== "") {
                $strAML .= "<field2>" . $cols['f2'] . "</field2> ";
            }
            if (trim($cols['f3']) !== "") {
                $strAML .= "<field3>" . $cols['f3'] . "</field3> ";
            }
            if (trim($cols['f4']) !== "") {
                $strAML .= "<field4>" . $cols['f4'] . "</field4> ";
            }
            if (trim($cols['f7']) !== "") {
                $strAML .= "<field7>" . $cols['f7'] . "</field7> ";
            }
            if (trim($cols['f11']) !== "") {
                $strAML .= "<field11>" . $cols['f11'] . "</field11> ";
            }
            if (trim($cols['f12']) !== "") {
                $strAML .= "<field12>" . $cols['f12'] . "</field12> ";
            }
            if (trim($cols['f24']) !== "") {
                $strAML .= "<field24>" . $cols['f24'] . "</field24> ";
            }
            if (trim($cols['f32']) !== "") {
                $strAML .= "<field32>" . $cols['f32'] . "</field32> ";
            }
            if (trim($cols['f37']) !== "") {
                $strAML .= "<field37>" . $cols['f37'] . "</field37> ";
            }
            if (trim($cols['f41']) !== "") {
                $strAML .= "<field41>" . $cols['f41'] . "</field41> ";
            }
            if (trim($cols['f48']) !== "") {
                $strAML .= "<field48>" . $cols['f48'] . "</field48> ";
            }
            if (trim($cols['f49']) !== "") {
                $strAML .= "<field49>" . $cols['f49'] . "</field49> ";
            }
            if (trim($cols['f56']) !== "") {
                $strAML .= "<field56>" . $cols['f56'] . "</field56> ";
            }
            if (trim($cols['f65']) !== "") {
                $strAML .= "<field65>" . $cols['f65'] . "</field65> ";
            }
            if (trim($cols['f66']) !== "") {
                $strAML .= "<field66>" . $cols['f66'] . "</field66> ";
            }

            $generated_narration = $this->getNarrations($cols['f3'], $cols['f2'], $cols['f37']);
            $strAML .= "<field68>" . ((trim($generated_narration) == "") ? $cols['f68'] : $generated_narration) . "</field68> ";

            if (trim($cols['f69']) !== "") {
                $strAML .= "<field69>" . $cols['f69'] . "</field69> ";
            }
            if (trim($cols['f97']) !== "") {
                $strAML .= "<field97>" . $cols['f97'] . "</field97> ";
            }
            if (trim($cols['f98']) !== "") {
                $strAML .= "<field98>" . $cols['f98'] . "</field98> ";
            }
            if (trim($cols['f100']) !== "") {
                $strAML .= "<field100>" . $cols['f100'] . "</field100> ";
            }
            if (trim($cols['f101']) !== "") {
                $strAML .= "<field101>" . $cols['f101'] . "</field101> ";
            }
            if (trim($cols['f102']) !== "") {
                $strAML .= "<field102>" . $cols['f102'] . "</field102> ";
            }
            if (trim($cols['f103']) !== "") {
                $strAML .= "<field103>" . $cols['f103'] . "</field103> ";
            }
            if (trim($cols['f126']) !== "") {
                $strAML .= "<field126>" . $cols['f126'] . "</field126> ";
            }
            $strAML .= "</message> ";
        } else {
            $strAML = "ERROR. None array.";
        }
        //die($strAML);
        return $strAML;
    }

    //Seperate the cents from the amount
    function formatBalance($amount) {
        $amount1 = floatval($amount); // convert into float
        $cents = substr($amount1, -2); // get the cents part
        $amount2 = substr_replace($amount1, '', -2) . "." . $cents; // replace last two chars with blank then append cents
        $amount3 = number_format($amount2, 2);
        return $amount3;
    }

    function getProvider($code, $en = null) {
        $providerxml = $this->genConfigDir . 'provider.xml';
        $file = fopen($providerxml, "r") or exit("Unable to open file!" . $providerxml);
        $xmlstr = "";
        while (!feof($file)) {
            $xmlstr .= fgets($file);
        }

        $xml = simplexml_load_string($xmlstr);

        foreach ($xml->vendor->provider as $provider) {
            $id = $provider->attributes()->code;
            if (strtoupper($id) == strtoupper($code)) {
                $value = $provider->attributes()->value;
                $codename = $provider->attributes()->codename;
                $biller = $provider->attributes()->biller;
                $encodename = $provider->attributes()->encodename;
                break;
            }
        }
        $retval['value'] = $value;
        $retval['biller'] = $biller;
        $retval['codename'] = (trim($en) !== 'en') ? $encodename : $codename;
        fclose($file);
        return $retval;
    }

    /**
     * 
     * @param type $en
     * @param type $loadfile
     * @param type $simload
     * @return boolean
     * Display lang choices, Load language details in case the app directs --george
     */
    function getLangAll($en = null, $loadfile = null, $simload = null) {
        $en = trim(str_replace("pr", "", $en)); //even when doing partially registered customers, we can use the same lang file
        $langxml = $this->langFile;
        $file = fopen($langxml, "r") or exit("Unable to open file!la");
        $xmlstr = "";

        while (!feof($file)) {
            $xmlstr .= fgets($file);
        }

        $xml = simplexml_load_string($xmlstr);
        $menu = null;
        $langRetVal = array();
        $i = 1;

        foreach ($xml->langfield as $lang) {
            if ($en !== "*") {

                $langcode = $lang->attributes()->langcode;
                if (strtoupper(trim($en)) == strtoupper($langcode)) {
                    $langfile = $lang->attributes()->langfile;
                    break;
                }
                //echo(strtoupper($en))."|".(strtoupper($langcode))."\n";
            } else {
                $active = $lang->attributes()->active;
                if ($active == 1 || $langRetVal[$i] = $lang->attributes()->langcode == $simload) {
                    $menu .= "\n" . $i . ". " . $lang->attributes()->langname;
                    $langRetVal[$i] = $lang->attributes()->langcode;
                    $i++;
                }
            }
        }
        fclose($file);

        if ($loadfile) {
            $langxml = $this->langDir . $langfile;
            //die($langxml);
            $file = fopen($langxml, "r") or exit("Unable to open file!lang");
            $xmlstr = "";
            while (!feof($file)) {
                $xmlstr .= fgets($file);
            }
            $menu = $this->stdObjectToArray(simplexml_load_string($xmlstr));
            return $menu;
        }
        if ($i >= 1) {
            $retval['menuStr'] = $menu;
            $retval['menuCode'] = $langRetVal;

            return $retval;
        } else {
            return false;
        }
    }

    function arrayToXML($data) {
        $keys = array_keys($data);
        $countArray = count($keys);
        $xml = "<?xml version= '1.0'   encoding= 'utf-8'?>";
        $xml .= "<message>";
        for ($count = 0; $count < $countArray; $count++) {
            $key = $keys[$count];
            $xml .="<$key>$data[$key]</$key>";
        }
        $xml .= "</message>";

        return $xml;
    }

    public function determineResponse($transactionResponse, $sessdetails) {
        //Determine what is contained in field 39 and field 67
        $field39 = $transactionResponse['data']['field39'];
        $responseMechanism = trim(strtoupper($transactionResponse['data']['field67']));

        if ($field39 == '00' && $responseMechanism == 'SYNC') {
            //Means Transaction SUCCESS || Examine the processing code and determine what feedback to return to the user
            $responseArr = $this->examineProcCodeReturnFeedback($transactionResponse, $sessdetails);
        } elseif ($field39 != '000') {
            //For the rest of the other response codes, process them and return feedback
            $responseArr = $this->examineRespCodeReturnFeedback($field39, $sessdetails);
        } else {
            
        }
        return $responseArr;
    }

    public function examineProcCodeReturnFeedback($transactionResponse, $sessionDetails) {
        //Get the processing code and process the transaction
        switch (trim($transactionResponse['data']['field3'])) {
            case '010000': //CASH WITHDRAWAL
                $sessionDetails['TXN_TYPE'] = 'cash withdrawal';
                $responseArr = $this->returnCashWithdrawalResponse($transactionResponse, $sessionDetails);
                break;
            case '310000': //BI
                $responseArr = $this->returnCustomerBalances($transactionResponse, $sessionDetails);
                break;
            case '380000': //MINI
                $responseArr = $this->generateMiniStatement($transactionResponse, $sessionDetails);
                break;
            case '360000': //FOREX INQUIRY
                /*
                 * FOREX RATES ARE NOT BEING OFFERED IN ATLANTIS MFI
                 */
                break;
            case '370000': //FULL STATEMENT
                /*
                 * FLASH MESSAGE COMPOSED ELSEWHERE
                 */
                break;
            case '400000': //FT
                $sessionDetails['TXN_TYPE'] = 'funds transfer';
                $responseArr = $this->returnFTResponse($transactionResponse, $sessionDetails);
                break;
            case '420000': //TOPUP
                $sessionDetails['TXN_TYPE'] = 'top up';
                $responseArr = $this->returnTopupResponse($transactionResponse, $sessionDetails);
                break;
            case '500000': //BILL PAYMENTS
                $sessionDetails['TXN_TYPE'] = 'bill payment';
                $responseArr = $this->returnBillPayResponse($transactionResponse, $sessionDetails);
                break;
            case '620000': //MONEY SEND (REMITTANCE)
                $sessionDetails['TXN_TYPE'] = 'send money';
                $responseArr = $this->returnRemittanceResponse($transactionResponse, $sessionDetails);
                break;
            default:
                //TRANSACTION TYPE UNKNOWN
                $sessionDetails['TXN_TYPE'] = 'send money';
                $responseArr = $this->returnRemittanceResponse($transactionResponse, $sessionDetails);
                break;
        }
        return $responseArr;
    }

    public function examineRespCodeReturnFeedback($field39, $sessionDetails) {
        //Examine the responsecode in FIELD39 and determine what to tell the customer.
        //001|Failed|ORA-06550: line 1, column 7: PLS-00905: object EBANK.SP_POST_MINI_TRANSACTIONS is invalid ORA-06550: line 1, column 7: PL/SQL: Statement ignored
        switch ($field39) {
            case '-10':
                //ERROR OCCURED
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            case '12':
                //CR ACCOUNT MISSING
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            case '51':
                //INSUFFICIENT FUNDS
                $responseArr = $this->returnInsufficientFundsMsg($sessionDetails);
                break;
            case '52':
                //AGENT DEPOSIT REQUIRED
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            case '53':
                //DEBIT ACCOUNT MISSING
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            case '57':
                //DONOT HONOR
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            case '58':
                //NO MATCHING RECORD
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            case '59':
                //EXPIRED CODE
                $responseArr = $this->returnExpiredTokenMsg($sessionDetails);
                break;
            case '60':
                //WRONG AMOUNT
                $responseArr = $this->returnWrongAmountMsg($sessionDetails);
                break;
            case '61':
                //LIMIT EXCEEDED
                $responseArr = $this->returnLimitExceededMsg($sessionDetails);
                break;
            case '999':
                //HOST DOWN
                $responseArr = $this->generalErrorMsg($sessionDetails);
                break;
            default :
                break;
        }
        return $responseArr;
    }

    public function returnCustomerBalances($transactionResponse, $sessionDetails) {
        //Get the AVAILABLE and CURRENT balances of the account.
        $custBalances = explode('|', $transactionResponse['data']['field54']);

        //Compose feedback for the customer
        $responseArr['USSD_BODY'] = $sessionDetails['lang']['avail_bal'] .
                $custBalances[0] . ' ' .
                $sessionDetails['lang']['actual_bal'] .
                $custBalances[1] . ' ';

        //Send feedback to the customer after processing
        return $responseArr;
    }

    public function generalErrorMsg($sessionDetails) {
        $responseArr['USSD_BODY'] = $sessionDetails['lang']['null_response'];
        return $responseArr;
    }

    public function generateMiniStatement($transactionResponse, $sessionDetails) {
        //Check whether we are dealing with a WALLET or a CORE BANKING SYSTEM account
        if ($this->isWalletAccount($transactionResponse['field102']) == TRUE) {
            //Means it is a WALLET ACCOUNT || Check for the PRESENCE OF transactions. At times the Mini statement might not have any transactions to display
            if (trim(strtoupper($transactionResponse['data']['field127'])) == 'NO TRANSACTIONS') {
                //Means there is NO DATA / NO TRANSCTIONS on the Mini statement. Advise customer.
                $responseArr['USSD_BODY'] = $sessionDetails['lang']['no_transaction_history'];
            } else {
                //Means DATA EXISTS on the Mini statement
                $miniStatementObject = $transactionResponse['data']['field127'];

                $miniStatementArray = $this->convertObject2Array($miniStatementObject);

                print_r($miniStatementArray['MiniStatement']);

                //Count how many elements are in the array
                echo count($miniStatementArray);

                die('Working on the MINI STATEMENT');
            }
            return $responseArr;
        }
        //Means it is a CORE BANKING SYSTEM account []
        if (!empty($transactionResponse['data']['field48'])) {
            //Means there is data returned
            $miniStatArray = explode('~', str_replace(' ', '', $transactionResponse['data']['field48']));
            //To get the most recent transactions to appear first, re-sort the array keys from the LARGEST INDEX[n] to the ZEROETH INDEX[0]
            krsort($miniStatArray);
            //Reset the INDEXING of the array to start from the ZEROETH[0] index to the largest
            $miniStatArrayOrdered = array_values(array_filter($miniStatArray));

            $arrayCount = count($miniStatArrayOrdered);

            //Check if the Mini Statemnt exceeds 5 transactions
            if ($arrayCount > 4) {
                //Means there are more than 5 transactions on the mini statement history. Remove the extras
                for ($arrayIndex = 5; $arrayIndex <= $arrayCount; $arrayIndex++) {
                    //Unset all the array elements from the 5th index up to the final index
                    unset($miniStatArrayOrdered[$arrayIndex]);
                }
            } else {
                /*
                 * Mini statement has the correct number of transactions.
                 * I am avoiding too much [IF-ELSE] nesting so the code continues hapo chini ouside the "ELSE"
                 */
            }

            /*
             * The ministatement is an array, IMPLODE the array with a PIPE[|] so as to make it a string.
             * Replace the PIPE[|] with a LINE FEED[\n] character for formatting
             */
            $miniStatString = str_replace('|', "\n", implode('|', $miniStatArrayOrdered));

            $responseArr['USSD_BODY'] = $sessionDetails['lang']['check_mini'] .
                    $transactionResponse['data']['field102'] . "\n" .
                    $miniStatString;
            return $responseArr;
        }
        //Return feedback back to the calling function
        return $responseArr;
    }

    public function convertObject2Array($object2Convert) {
        if (is_object($object2Convert)) {
            //Means the item passed under the variable [$object2Convert] IS AN OBJECT. Cast the OBJECT into an ARRAY
            $object2Convert = (array) $object2Convert;
        }
        //Check if the variable passed is an array
        if (is_array($object2Convert)) {
            //Declare a new array to hold the results of conversion
            $resultantArray = array();
            foreach ($object2Convert as $key => $val) {
                //Recursively convert NESTED-OBJECTS into arrays
                $resultantArray[$key] = $this->convertObject2Array($val);
            }
        } else {
            $resultantArray = $object2Convert;
        }
        return $resultantArray;
    }

    public function determineResponseMode($syncFlag) {
        //Sanitize the [$syncFlag] variable and convert it to an integer for evaluation            
        switch ((int) trim($syncFlag)) {
            case 1:
                //Means response mode is [SYNCHRONOUS / SYNC]
                $responseMode = 'SYNC';
                break;
            case 0:
                //Means the response mode is [ASYNCHRONOUS / ASYNC]
                $responseMode = 'ASYNC';
                break;
            default:
                //Means the response mode is [NOT KNOWN]. We shall default to [ASYNCHRONOUS / ASYNC]
                $responseMode = 'ASYNC';
                break;
        }
        //Return back the resultant response mode to the calling function
        return $responseMode;
    }

    public function generateCustomerResponse($esbResponse, $sessdetails) {
        //Check the response that came back from the ESB
        if ($esbResponse['success'] == TRUE) {
            //Means the transaction was SUCCESSFUL
            $responseArr = $this->determineResponse($esbResponse, $sessdetails);
        } elseif ($esbResponse['success'] == FALSE) {
            //Means the transaction FAILED. Advise the customer.
            $responseArr['USSD_BODY'] = $sessdetails['lang']['null_response'];
        } else {
            //Means the transaction status is UNKNOWN - We shall DEFAULT to [FAILED] to be on the safe side
            $responseArr['USSD_BODY'] = $sessdetails['lang']['null_response'];
        }

        //Return the feedback that is to go to the customer
        return $responseArr;
    }

    public function isWalletAccount($accountNo) {
        /*
         * We shall sanitize phone numbers by removing the + SIGN from the number and any WHITESPACES
         * We shall also return the phone number with the country code prefix e.g. 254700999888
         */
        $accountNumber = str_replace('+', '', str_replace(' ', '', $accountNo));

        switch (strlen(trim($accountNumber))) {
            case 12:
                //Means it is a phone number starting with 254 e.g. 254700999888
                if (substr($accountNumber, 0, 3) == $this->countryCode) {
                    $isWalletAccount = TRUE;
                }
                break;
            case 10:
                $isWalletAccount = TRUE;
                break;
            default:
                $isWalletAccount = FALSE;
                break;
        }
        return $isWalletAccount;
    }

    /* Takes the user either back one step or to the home menu
     * 
     */

    public function checkNextStep($ussd_body, $mobile_number, $session_id, $sessdetails, $menulevel = null) {
        try {
            $task = new Menu($this);
            if (intval($ussd_body) == 99) {//Home
                $responseArr = $task->menuStepOne($ussd_body, $mobile_number, $session_id, $sessdetails);
            } else if (intval($ussd_body) == 77) {//Back to previous menu
                $status = explode("|", $sessdetails['previous_status_details']);

                $className = ucfirst($status[2]);
                $methodName = lcfirst($status[1]);
                $ussd_body = ($status[0]);
                $task = new $className($this);
                $responseArr = $task->$methodName($ussd_body, $mobile_number, $session_id, $sessdetails);

                if ($status[1] == 'MenuStepThree') {//To redirect the user back to submenus in case 'back' is selected
                    $status[1] = 'MenuStepTwo';
                    $status[0] = $sessdetails['mainmenucodeselected'];

                    $data['previous_status_details'] = implode('|', $status);
                    $this->updateSession($mobile_number, $data);
                }
            } else {
                $responseArr['USSD_BODY'] = $sessdetails['invalid_menu_input'];
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->flog('ERROR', __METHOD__ . $e->getMessage() . "() | Error ");
        }
    }

    /* Check if phone number entered by user is valid
     * &&
     * Returns it in the format 254 722 333 444
     */

    function validatePhoneNumber($number) {
        try {
            $mobile_number = trim($number);

            $response['status'] = false;
            if (!ctype_digit($mobile_number)) {
                $response['message'] = 'Enter numbers only';
                return $response;
            }

            if (strlen($mobile_number) >= 9 && strlen($mobile_number) <= 12) {
                $response['message'] = 'Check format of number you have entered.';

                switch (strlen($mobile_number)) {
                    case 9:
                        $prefix = substr($mobile_number, 0, 1);
                        if ($prefix == 7) {
                            $response['status'] = true;
                            $response['number'] = $this->defaultPrefix . $mobile_number;
                        }
                        break;
                    case 10:
                        $prefix = substr($mobile_number, 0, 2);
                        if ($prefix == 07) {
                            $pattern = "/^0/";
                            $replacement = '254';
                            $new_mobile_number = preg_replace($pattern, $replacement, $mobile_number);

                            $response['status'] = true;
                            $response['number'] = $new_mobile_number;
                        }
                        break;
                    case 11://No mobile number is 11 digits long
                        $response['status'] = false;
                        $response['message'] = 'Number entered has 11 digits';
                        break;
                    case 12:
                        $prefix = substr($mobile_number, 0, 3);
                        if ($prefix == $this->defaultPrefix) {
                            $response['status'] = true;
                            $response['number'] = $mobile_number;
                        }
                        break;
                    default:
                        $response['status'] = false;
                        $response['message'] = 'Number entered has incorrect number of digits';
                        break;
                }

                if (!in_array(substr($response['number'], 3, 3), explode(".", $this->safaricomPrefixes), true)) {
                    $response['message'] = 'Not a valid Safaricom number';
                    $response['status'] = false;
                    return $response;
                }
            } else {
                $response['status'] = false;
            }
            return $response;
        } catch (Exception $e) {
            $this->flog('ERROR', __METHOD__ . $e->getMessage() . "() | Error ");
        }
    }

    /* Check if biller account details are valid
     * &&
     * Return true + error message if any
     * 404
     */

    function validateBillerAccount($account, $biller) {
        try {
            $biller_account_number = trim($account);

            $response['status'] = false;

            if (strlen($biller_account_number) >= 7 && strlen($biller_account_number) <= 12) {
                //$response['message'] = 'Check format of number you have entered.';
                $account_length = strlen($biller_account_number);
                switch ($biller) {
                    case 'GOTV':
                        if ($account_length >= 9) {
                            $response['status'] = true;
                        }
                        break;

                    case 'DSTV':
                        if ($account_length >= 8 || $account_length <= 11) {
                            $response['status'] = true;
                        }
                        break;

                    case 'KPLC_POSTPAID':
					case 'KPLC':
                        if ($account_length >= 7) {
                            $response['status'] = true;
                        }
                        break;

                    case 'NAIROBI_WATER':
					case 'NWSC':
                        if ($account_length >= 7) {
                            $response['status'] = true;
                        }
                        break;

                    default:
                        $response['status'] = false;
                        $response['message'] = 'Check account number';
						$this->log("ERROR", __CLASS__, __FUNCTION__ . ': Biller - ' .$biller.' cant be validated, add its CASE to the SWITCH statement' . date('His'));
                        break;
                }
            } else {
                $response['status'] = false;
            }
            return $response;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check if biller account details are valid
     * &&
     * Return true + error message if any
     * 404
     */

    function validateStopCheque($chq_number) {
        try {
            $cheque_number = trim($chq_number);

            $response['status'] = false;

            if (!ctype_digit($cheque_number) || strlen($cheque_number) > 5) {//Set a maximum of 99999 cheques issued 
                $response['message'] = 'Enter page number only e.g 21';
            } else {
                $response['status'] = true;
            }

            return $response;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* This gets all accounts owned by customer and stores them in an array for display during transactions
     * If $loan_account is set to true, we get the loan accounts else we just get the normal linked accounts
     */

    function loadAccounts($ussd_body, $mobile_number, $session_id, $sessdetails, $loan_account = null) {
        try {
            if ($loan_account == true) {
                $linked_accounts[0] = $sessdetails['loan_accounts'];
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_loan_account'];
                $data['loan_accounts'] = implode("|", $linked_accounts);
            } else {
                $linked_accounts = explode("|", $sessdetails['accounts']);
                $responseArr['USSD_BODY'] = $sessdetails['lang']['enter_account'];
                $data['accounts'] = implode("|", $linked_accounts);
            }
            $k = 1;
            $count_account = count($linked_accounts);
            for ($i = 0; $i < $count_account; $i++) {//Prepare accounts for display
                if (trim($linked_accounts[$i]) != "") {
                    $responseArr['USSD_BODY'] .="\n" . $k . ". " . $linked_accounts[$i];
                    $k++;
                } else {
                    unlink($linked_accounts[$i]);
                }
            }

            $this->updateSession($mobile_number, $data);
            return $responseArr;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Final step for all transactions except self registration
     * &&
     * Checks if user wants to perform another transaction or if they want to exit
     */

    function endTxn($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            if (intval($ussd_body) == 1) {
                //Take the user home
                $responseArr = $this->checkNextStep(99, $mobile_number, $session_id, $sessdetails);
            } else {
                //User doesn't want another transaction
                $responseArr['END_OF_SESSION'] = "true";
                $responseArr['USSD_BODY'] .= $sessdetails['lang']['exit_menu'];
                $this->clearSession($mobile_number, true);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Log general info and errors
     * 
     * 404
     */

    public function flog($type, $string) {
        $date = date("Y-m-d H:i:s");
        if ($fo = fopen('C:\Logs\Century\logs.txt', 'ab')) {
            //fwrite($fo, "$date - [ $type ] " . $_SERVER['PHP_SELF'] . " | $string \n");
            fwrite($fo, "$date - [ $type ] " . $_SERVER['PHP_SELF'] . " | $string \n");
            fclose($fo);
        } else {
            trigger_error("flog Cannot log '$string' to file '$file' ", E_USER_WARNING);
        }
    }

    /* Fixes those json strings that are not standard 
     * Especially when getting customer details from field 48 from esbservlet
     * e.g -- '[{ac=,name=dude}]' to {'ac':'','name':'dude'}
     */

    function json_fix_quotes($string) {
        try {
            $string = trim(str_replace("null", "", $string));
            $string = trim(str_replace(":", '~', $string));
            $string = trim(str_replace("=", ':', $string));
            $string = trim(str_replace("{", '{"', $string));
            $string = trim(str_replace(":", '":"', $string));
            $string = trim(str_replace(",", '","', $string));
            $string = trim(str_replace("}", '"}', $string));
            $string = trim(str_replace(':"{', ':{', $string));
            $string = trim(str_replace('}",', '},', $string));
            $string = trim(str_replace('," ', ',"', $string));
            $string = trim(str_replace('{""}', '{}', $string));
            $string = trim(str_replace("]", "", $string));
            $string = trim(str_replace("[", "", $string));


            $string = trim(str_replace('},"{', ",", $string)); //Because Francis O.O. sent weird stuff


            return $string;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Using this to temporarily fake login request responses from esbservlet
     * (or any other responses you want.. just change content  of fake response file)
     * 
     */

    /* Loads all loan products currently available from the service_providers.xml 'loan_products'
     * &&
     * Formats them for display
     */

    function getLoanProducts($mobile_number, $sessdetails) {
        try {
            $responseArr['USSD_BODY'] = $sessdetails['lang']['select_product'];
            $spdetails = $this->getServiceProvider("LOAN_PRODUCTS");
            $products = explode("|", $spdetails['items']);
            $k = 1;
            $count_products = count($products);
            for ($i = 0; $i < $count_products; $i++) {//Prepare loan products for display
                if (trim($products[$i]) != "") {
                    $responseArr['USSD_BODY'] .="\n" . $k . ". " . $products[$i];
                    $k++;
                } else {
                    unlink($products[$i]);
                }
            }
            $data['loan_products'] = $spdetails['items'];
            $this->updateSession($mobile_number, $data);

            return $responseArr;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function getfakeresponse($fakefilename) {
        try {
            $myXML = $this->genConfigDir . $fakefilename . ".xml";
            $file = fopen($myXML, "r") or exit("Unable to open file!menu");
            $myXMLData = "";
            while (!feof($file)) {
                $myXMLData .= fgets($file);
            }
            $response['data'] = $this->getValXML($myXMLData);

            return $response;
        } catch (Exception $e) {
            $this->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    function send_sms($mobile_number, $msg) {
        //$mobile_number = '+254725764230';//bonoko
        //$msg = 'Buda servlet iko down!';
//        $data = 'SMSText=' . $msg . '&password=1234eclec&GSM=' . $mobile_number . '&user=ECLECTICS&sender=ECLECTICS';
//        $ch = curl_init();
//        curl_setopt($ch, CURLOPT_URL, "http://193.105.74.159/api/sendsms/plain?");
//        curl_setopt($ch, CURLOPT_POST, 1); // Use POST
//        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
//        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //
//        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
//        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
//        $output = curl_exec($ch);

        return $output;
    }

    //Notify Us if servlet is down.. by us i mean stanley kamau
    function send_sms_servlet_down() {
        //$mobile_number = '254724133418';//'+254725764230'; //bonoko
		$output = "";
        $msg = 'Hello Sir. Century Servlet is down.';

		$numbers = explode("|", "254724133418|254725764230");
        $k = 1;
        $count_numbers = count($numbers);
        for ($i = 0; $i < $count_numbers; $i++) {
            if (trim($numbers[$i]) != "") {
                $mobile_number = $numbers[$i];
				if ($mobile_number == "254725764230")
				{
					$msg = 'Mr. Manager Sir, Servlet is sleeping a fat sleep, please wake it up.';
				}
				$data = 'SMSText=' . $msg . '&password=1234eclec&GSM=' . $mobile_number . '&user=ECLECTICS&sender=ECLECTICS';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, "http://193.105.74.159/api/sendsms/plain?");
				curl_setopt($ch, CURLOPT_POST, 1); 
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
				curl_setopt($ch, CURLOPT_TIMEOUT, 5);
				$output = curl_exec($ch);
				
                $k++;
            }
        }
        

        return $output;
    }

}
