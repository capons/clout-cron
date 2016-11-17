<?php
include_once "common_functions.php";

# 1. pick required user tokens
if(!empty($_GET['limit']) || !empty($_GET['user'])) {
	# ignore php timeouts
	ignore_user_abort(true);
	set_time_limit(0);
	
	$userTokens = array();
	# use limits
	if(!empty($_GET['limit'])){
		if ($result = $db->query("SELECT _user_id AS user_id, access_token AS token FROM plaid_access_token LIMIT ".(!empty($_GET['offset'])? $_GET['offset']: '0').",".$_GET['limit'])) {
			while($row = $result->fetch_assoc()) $userTokens[] = $row;
		}
	}
	# just one user
	else {
		if ($result = $db->query("SELECT '".$_GET['user']."' AS user_id, access_token AS token FROM plaid_access_token WHERE _user_id = '".$_GET['user']."' LIMIT 1")) {
			$userTokens[] = $result->fetch_assoc();
		}
	}
	
	# clean-up
	if(!empty($result)) $result->free();
	log_message('info', 'update_web_hooks:: user tokens = '.json_encode($userTokens));
}

$db->close();





# now update the webhooks on the plaid API
if(!empty($userTokens)){
	log_message('info', 'update_web_hooks:: RUNNING - HOOK UPDATE.. ');
	foreach($userTokens AS $tokenRow){
		if(!empty($tokenRow['user_id']) && !empty($tokenRow['token'])){
			$data = array(
				'client_id'=>'53598e0b18fed60710851327',
				'secret'=>'AhNfb_cdk--WQDpkkz8JTo',
				'access_token'=>$tokenRow['token'],
				'options'=>array('webhook'=>'http://pro-dw-crn1.clout.com/h/'.encrypt_value($tokenRow['user_id']))
			);
			$response = run_on_api('https://api.plaid.com/connect', $data, 'PATCH');
			log_message('info', 'update_web_hooks:: response = '.json_encode($response));
		}
	}
}
else  {
	log_message('info', 'update_web_hooks:: FAIL');
	exit;
}









?>