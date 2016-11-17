<?php
$apiResponse = !empty($HTTP_RAW_POST_DATA)? $HTTP_RAW_POST_DATA : @file_get_contents("php://input");
$fileString = json_encode($apiResponse);

if(!empty($_GET['h'])) $fileString .= PHP_EOL.PHP_EOL."USER ID: ".decrypt_value($_GET['h']).PHP_EOL.PHP_EOL;


if(!file_exists('api_response.txt')) file_put_contents('api_response.txt', $fileString);
else file_put_contents('api_response.txt', $fileString, FILE_APPEND);





# Decrypts the entered values
function decrypt_value($dvalue)
{
	#Decode value
	$dvalue = base64_decode($dvalue);

	$dnum = strlen($dvalue);
	$dnumIndex = $dnum-1;
	$newDvalue = "";

	#Reverse the order of characters
	for($x=0;$x<strlen($dvalue);$x++){
		$newDvalue .= substr($dvalue,$dnumIndex,1);
		$dnumIndex--;
	}
	return $newDvalue;
}