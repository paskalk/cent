<?php

//Start new or resume existing session
//session_start();
//Set which PHP errors are reported
error_reporting(E_ALL);
//ini_set('display_errors', TRUE);
//ini_set('display_startup_errors', TRUE);

//Include and evaluate the necessary files for program execution.
require_once 'core/init.php';

//print_r($_POST, true);

////Get the variables from the URL using the [GET METHOD]
$session_id = filter_input(INPUT_GET, 'SESSION_ID', FILTER_SANITIZE_STRING);
$service_code = filter_input(INPUT_GET, 'SERVICE_CODE', FILTER_SANITIZE_STRING);
$msisdn = filter_input(INPUT_GET, 'MOBILE_NUMBER', FILTER_SANITIZE_STRING);
$ussd_body = filter_input(INPUT_GET, 'USSD_BODY', FILTER_SANITIZE_STRING);
$imsi = filter_input(INPUT_GET, 'IMSI', FILTER_SANITIZE_STRING);

//$session_id = '0873989';
//$service_code = '*981#';
//$msisdn = '254728452458';
//$ussd_body = '9000';
//$imsi = '';
//1:21 PM 16-Feb-16|| Put the variables received in an associative array for clean handling
$variablesArray = array(
    'SESSION_ID' => $session_id,
    'MOBILE_NUMBER' => $msisdn,
    'SERVICE_CODE' => $service_code,
    'IMSI' => $imsi,
    'USSD_BODY' => $ussd_body
);

///print_r($variablesArray);// $variablesArray;
//echo "CON ".json_encode($variablesArray);
//die;

$func = new Functions();
$statusArray = $func->getSession($msisdn, $session_id);



if (!empty($statusArray)) {
    //Customer DATA FOUND in sessions table. Convert the array keys to UPPER CASE
    $status = array_change_key_case($statusArray, CASE_LOWER);
} else {
    //Means customer DATA NOT FOUND in the sessions table. Set the status variable to null
    $status = NULL;
}

//Send the variables to the classes through the SERVER CLASS for processing

$serverClass = new ServerSafaricom($func, $status);
$responseArr = $serverClass->serverSafaricom($variablesArray, $func, $status);

///* Deals with direct processing of the ussd code by safaricom directly
// * 
// */
// $connectionState = "CON";
// if (strtolower($responseArr['END_OF_SESSION']) == "true") {
//     $connectionState = "END";
// }
// $refinedArray = $connectionState . ' ' . $responseArr['USSD_BODY'];


/* Compose an array composed only of the required items
 * This works when forwading to infobip or onfon    
 */
$refinedArray = array(
    'USSD_BODY' => ' ' . $responseArr['USSD_BODY'],
    'END_OF_SESSION' => ucwords(strtolower($responseArr['END_OF_SESSION']))
);

/*
 * Print_r the response so that the routing application can pick 
 * the output, read and send it to MNO for display on customer's phone
 */
print_r($refinedArray);


