<?php

//Start new or resume existing session
//session_start();
//Set which PHP errors are reported
error_reporting(E_ERROR);

//Include and evaluate the necessary files for program execution.
require_once 'core/init.php';

$session_id = $_POST['SESSION_ID'];
$service_code = $_POST['SERVICE_CODE'];
$msisdn = $_POST['MOBILE_NUMBER'];
$ussd_body = $_POST['USSD_BODY'];
$imsi = $_POST['IMSI'];


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
$connectionState = "CON";
if (strtolower($responseArr['END_OF_SESSION']) == "true") {
    $connectionState = "END";
}
$refinedArray = $connectionState . ' ' . $responseArr['USSD_BODY'];


/* Compose an array composed only of the required items
 * This works when forwading to infobip or onfon    
 */
// $refinedArray = array(
//     'USSD_BODY' => ' ' . $responseArr['USSD_BODY'],
//     'END_OF_SESSION' => ucwords(strtolower($responseArr['END_OF_SESSION']))
// );

/*
 * Print_r the response so that the routing application can pick 
 * the output, read and send it to MNO for display on customer's phone
 */
print_r($refinedArray);


