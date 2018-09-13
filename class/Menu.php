<?php

/*
 * menu.pdo.php
 * *** Note this class should be made into an extensible super class to be reused later for all similar cases 03 Jun 2013
 */

class Menu {

    private $functions;

    function __construct($func) {
        $this->functions = $func;
    }

    /* Redirects to other classes if status field has been set to contain target class and target method in the specified class
     * 
     */

    function menuProcess($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
			$time_monitor_start = microtime(true);
            $method_details = $sessdetails['status'];
            $status = explode("|", $method_details);
            if (count($status) > 1) {
                $className = ucfirst($status[2]);
                $methodName = lcfirst($status[1]);
                $task = new $className($this->functions);
				
                $responseArr = $task->$methodName($ussd_body, $mobile_number, $session_id, $sessdetails);
            } else {
				$methodName = trim(lcfirst($status[0]));
                $responseArr = $this->$methodName($ussd_body, $mobile_number, $session_id, $sessdetails);
            }
			$time_taken = microtime(true) - $time_monitor_start;
            if (floatval($time_taken) > 1.00) {
                $this->functions->log("TIME_MONITOR", $className, $methodName . ': ' . $time_taken);
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /**
     * @purpose This will get the main menu from the menu xml 
     * There is no partial registration hence no need for partial registration menu
     * 
     */
    function menuStepOne($ussd_body, $mobile_number, $session_id, $sessdetails) {
	
        try {
		
            $en = $sessdetails['customer_language'];
//            //'es' was to enforce the safcom *266*1# menu --left it in this version so as to use it in case such a thing happens in another site. es is a dummy lang --george
//            if ($sessdetails['en'] == "es") {
//                $en = "en";
//            }
            $lang = $this->functions->getLangAll($en, true);
            $countmainMenu = $this->functions->countMenu("mainmenu", $sessdetails['menulanguagefile']);

            $mainmenu['title'] = 'mainmenu';
            $mainmenu['page'] = 1;
            $menu = $this->functions->parseMenuXml($ussd_body, $sessdetails['menulanguagefile'], $mainmenu);

            $responseArr['USSD_BODY'] = $lang['reply_with_mwallet'] . $menu['menulist'];

            if (count($menu['mainmenucodes']) < $countmainMenu) {//Adds '98.more' if menus are more than 7
                $more = "\n98. " . $lang['more'];
                $responseArr['USSD_BODY'] .= $more;
            }

            $info = implode("|", $menu['mainmenucodes']);

            $data['status'] = 'menuStepTwo';
            $data['mainmenucodes'] = $info;
            $data['previous_response'] = $responseArr['USSD_BODY'];
            $data['menu'] = 'Menu';
            $data['sessionid'] = $session_id;
            $this->functions->updateSession($mobile_number, $data);

            $responseArr['END_OF_SESSION'] = "False";

            return $responseArr;
        } catch (Exception $e) {
		
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check mainmenu selected and stores in 'mainmenuselected'
     * &&
     * Load the submenus from the menuxml
     */

    function menuStepTwo($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
		
            $user_selection = trim($ussd_body);
            $available_menus = explode("|", $sessdetails['mainmenucodes']);
            $number_of_menus = count($available_menus);

            if (!ctype_digit($user_selection) || intval($user_selection) > $number_of_menus) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_menu_input'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the input value was not an integer or the value was more than number on the screen ");
            } else {

                $lang = $this->functions->getLangAll($sessdetails['customer_language'], true);

                $submenu['title'] = 'submenu';
                $submenu['page'] = (intval($sessdetails['menupage']) == "") ? 1 : intval($sessdetails['menupage']);

                $base_code = explode("|", $sessdetails['mainmenucodes']);

                $exemptCodes['deny'] = array_values($exemptCode);
                $submenu = $this->functions->parseMenuXml($ussd_body, $sessdetails['menulanguagefile'], 'submenu', $base_code[$ussd_body - 1], "", $exemptCodes);

                $responseArr['USSD_BODY'] = $submenu['menulist'] . $lang['go_home'];

                $subcodeslist = implode("|", $submenu['code']);

                $data['mainmenuselected'] = $base_code[$ussd_body - 1];
                $data['mainmenucodeselected'] = $ussd_body;
                $data['submenucodes'] = $subcodeslist;
                $data['menu'] = 'Menu';
                $data['current_status_details'] = $ussd_body . '|' . __FUNCTION__ . '|' . __CLASS__;
                $data['status'] = 'menuStepThree';
                $data['previous_response'] = $responseArr['USSD_BODY'];
                $this->functions->updateSession($mobile_number, $data);

                $responseArr['END_OF_SESSION'] = "False";
            }
            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

    /* Check submenu selected and store in subminimenuselected
     * &&
     * This loads the subsequent menus (whether subminimenus or other types). It redirects to the specific class for further processing
     * 
     */

    function MenuStepThree($ussd_body, $mobile_number, $session_id, $sessdetails) {
        try {
            $user_selection = intval(trim($ussd_body));
            $available_submenus = explode("|", $sessdetails['submenucodes']);
            $number_of_submenus = count($available_submenus);

            $lang = $sessdetails['lang'];

            if ($user_selection == 99 || $user_selection == 77) {//Home or back
                $responseArr = $this->functions->checkNextStep(99, $mobile_number, $session_id, $sessdetails);
                return $responseArr;
            }

            if ($user_selection == 0 || $user_selection > $number_of_submenus) {
                $responseArr['USSD_BODY'] = $sessdetails['lang']['invalid_menu_input'] . $sessdetails['previous_response'];
                $this->functions->flog('INFO', __METHOD__ . "() | Either the selected value was not an integer or the submenu selected was not on list of submenus on the screen ");
            } else {
                $submenuselected = $available_submenus[$user_selection - 1];
                $sessdetails['submenuselected'] = $submenuselected;

                $data['submenuselected'] = $sessdetails['submenuselected'];
                $data['previous_status_details'] = $sessdetails['current_status_details'];
                $data['current_status_details'] = $ussd_body . '|' . __FUNCTION__ . '|' . __CLASS__;
                $this->functions->updateSession($mobile_number, $data);

                $class = ucfirst($submenuselected);
                $method = lcfirst($submenuselected) . 'StepOne';
                $task = new $class($this->functions);

                $responseArr = $task->$method($ussd_body, $mobile_number, $session_id, $sessdetails);
                $responseArr['USSD_BODY'] .= $lang['home_or_back'];
                $responseArr['END_OF_SESSION'] = 'False';
            }

            return $responseArr;
        } catch (Exception $e) {
            $this->functions->log("ERROR", __CLASS__, __FUNCTION__ . ': ' . $e->getMessage() . date('His'));
        }
    }

}

// end class




    