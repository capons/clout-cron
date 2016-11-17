<?php
# post a new test account to the API
$data = array(
		'client_id'=>'53598e0b18fed60710851327',
		'secret'=>'AhNfb_cdk--WQDpkkz8JTo',
		'credentials'=>array('username'=>'plaid_test','password'=>'plaid_good'),
		'type'=>'wells',
		'email'=>'al-test-clout-2@gmail.com',
		'options'=>array('pending'=>true, 'list'=>true, 'login'=>true, 'webhook'=>'http://tst.clout.com:8999/html/cron/data-crons/test-queue-scripts/receive_hook.php?h='.encrypt_value('112'))
);

echo PHP_EOL.PHP_EOL."RUNNING - REGISTRATION.. ";
$response = run_on_api('https://tartan.plaid.com/connect', $data);
echo PHP_EOL.PHP_EOL."FINAL RESPONSE - REGISTRATION: ".PHP_EOL.PHP_EOL;
print_r($response);


# post a new test account to the API
$data = array(
	'client_id'=>'53598e0b18fed60710851327',
	'secret'=>'AhNfb_cdk--WQDpkkz8JTo',
	'access_token'=>'test_wells',
	'options'=>array('webhook'=>'http://tst.clout.com:8999/html/cron/data-crons/test-queue-scripts/receive_hook.php?a=new&h='.encrypt_value('112'))
);

echo PHP_EOL.PHP_EOL."RUNNING - HOOK UPDATE.. ";
$response = run_on_api('https://tartan.plaid.com/connect', $data, 'PATCH');
echo PHP_EOL.PHP_EOL."FINAL RESPONSE - HOOK UPDATE: ".PHP_EOL.PHP_EOL;
print_r($response);





# Encrypts the entered values
function encrypt_value($value)
{
	$num = strlen($value);
	$numIndex = $num-1;
	$newValue="";

	#Reverse the order of characters
	for($x=0;$x<strlen($value);$x++){
		$newValue .= substr($value,$numIndex,1);
		$numIndex--;
	}

	#Encode the reversed value
	$newValue = base64_encode($newValue);
	return $newValue;
}





# run the data on the given API URL and return the result
function run_on_api($url, $data, $runType='POST')
{
	$string = json_encode($data);
	
	$ch = curl_init();
	if($runType=='POST'){
	    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: '. strlen($string)));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  $string);
		curl_setopt($ch, CURLOPT_POST, 1);
	}
	else {
		$string = http_build_query($data);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Content-Length: ' . strlen($string)));
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, '10000');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $runType);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  $string);
	}
	#Run the value as passed
	$result = curl_exec($ch);
	echo PHP_EOL."RAW RESULT: ".PHP_EOL; print_r($result);
	
	#Show error
	if (curl_errno($ch))
	{
		$error = curl_error($ch);
		$errorResult = array('code' => 404, 'message' => 'system error', 'resolve' => $error );
	}
	#Close the channel
	curl_close($ch);
	
	#Determine the type of data to return
	return json_decode($result, TRUE);
	
}