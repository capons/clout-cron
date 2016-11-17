<?php
$db = mysqli_connect("localhost","extlocaluser","3xtCl0ut","clout_v1_3cron");
if (mysqli_connect_errno()){
	log_message('error', "Failed to connect to DB: " . mysqli_connect_error());
	exit;
}





# FUNCTIONS START HERE

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
		curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
		curl_setopt($ch, CURLOPT_POSTFIELDS,  $string);
		curl_setopt($ch, CURLOPT_POST, 1);
	}
	else if($runType == 'GET')
	{
		curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($data)); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, '100000');
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
		curl_setopt($ch, CURLOPT_HTTPGET, TRUE);
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
	#log_message('debug', 'run_on_api:: raw result '.json_encode($result));

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



# checks all values to see if they are all true and returns the value TRUE or FALSE
function get_decision($values_array, $defaultTo=FALSE)
{
	$decision = empty($values_array)? $defaultTo: TRUE;

	if(!empty($values_array))
	{
		foreach($values_array AS $value)
		{
			if(!$value)
			{
				$decision = FALSE;
				break;
			}
		}
	}

	return $decision;
}


# log stuff as string
function log_message($level, $message, $fileName='')
{
	$fileName = !empty($fileName)? $fileName: 'log-'.date('Y-m-d').'.log';
	$fileString = PHP_EOL.strtoupper($level)." -> ".(!empty($message) && is_string($message)? $message: (is_bool($message)? ($message? 'TRUE': 'FALSE'): json_encode($message))).PHP_EOL;
	
	if(!file_exists($fileName)) file_put_contents($fileName, $fileString);
	else file_put_contents($fileName, $fileString, FILE_APPEND);
}









#*****************************************************
# DB functions
#*****************************************************

# run query on database
function run($code, $values=array()){
	$db = mysqli_connect("localhost","extlocaluser","3xtCl0ut","clout_v1_3cron");
	if (mysqli_connect_errno()){
		log_message('error', "Failed to connect to DB: " . mysqli_connect_error());
		exit;
	}
	log_message('info', 'query code: '.$code);
	
	if(!empty($values['__query'])){
		$queryString = $values['__query'];
	}
	else if($result = $db->query("SELECT details FROM queries WHERE code = '".$code."' LIMIT 1")) {
		$query = $result->fetch_assoc();
		log_message('debug', 'query results: '.json_encode($query));
		
		# the query template exists prefill with details
		if(!empty($query['details'])) {
			$keys = array_keys($values);
			usort($keys, function($a, $b) {
				return strlen($b) - strlen($a);
			});
					
			#e.g., $queryData['_LIMIT_'] = "10";
			$queryString = $query['details'];
		}
	}
	
	if(!empty($queryString)){
		log_message('debug', 'query template: '.$queryString);
		foreach($keys AS $key) $queryString = str_replace('_'.strtoupper($key).'_', $values[$key], $queryString);
		
		log_message('debug', 'final query: '.$queryString);
		# run the real query on the database
		if($result = $db->query($queryString)) return $result;
		else return FALSE;
	}
	
	return FALSE;
}


# get list results
function get_list($code, $values=array()){
	$result = run($code, $values);
	$data =array();
	if($result !== FALSE) while($row = $result->fetch_assoc()) $data[] = $row;
	
	return $data;
}


# return row as array
function get_row_as_array($code, $values=array()){
	$result = run($code, $values);
	return $result !== FALSE? $result->fetch_assoc(): array();
}





?>