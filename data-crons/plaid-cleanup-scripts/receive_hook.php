<?php
include_once "common_functions.php";

# if the webhook is specified
$fileString = (!empty($_GET['h'])? PHP_EOL.PHP_EOL."USER ID: ".decrypt_value($_GET['h']).PHP_EOL.PHP_EOL: '');

# get API response
$apiResponse = !empty($HTTP_RAW_POST_DATA)? $HTTP_RAW_POST_DATA : @file_get_contents("php://input");

# record un-attended response from the API
log_message('info',$fileString.'RESPONSE: '.json_encode($apiResponse), getcwd().'/api-response-'.date('Y-m-d').'.log');

?>