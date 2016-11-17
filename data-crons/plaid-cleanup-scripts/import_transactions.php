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
		if ($result = $db->query("SELECT _user_id AS user_id, _bank_id AS bank_id, access_token AS token, U.first_name, U.last_name ".
				"FROM plaid_access_token P LEFT JOIN clout_v1_3.users U ON (P._user_id=U.id) ".
				"WHERE U.email_address IS NOT NULL AND U.id NOT IN ('918') ".
				"LIMIT ".(!empty($_GET['offset'])? $_GET['offset']: '0').",".$_GET['limit'])) {
			while($row = $result->fetch_assoc()) $userTokens[] = $row;
		}
	}
	# just one user
	else {
		if ($result = $db->query("SELECT '".$_GET['user']."' AS user_id, _bank_id AS bank_id, access_token AS token, U.first_name, U.last_name ".
				"FROM plaid_access_token P LEFT JOIN clout_v1_3.users U ON (P._user_id=U.id) ".
				"WHERE P._user_id = '".$_GET['user']."' LIMIT 1")) {
			$userTokens[] = $result->fetch_assoc();
		}
	}
	
	# clean-up
	if(!empty($result)) $result->free();
}

$db->close();
log_message('info', 'count of tokens obtained: '.count($userTokens));




# now get and import the accounts and transactions into the database
if(!empty($userTokens)){
	log_message('info', 'RUNNING - IMPORTING TRANSACTIONS.. '.count($userTokens));
	foreach($userTokens AS $tokenRow){
		# check to make sure this user actually exists and not just their token
		if(!empty($tokenRow['first_name'])){
			$data = array(
				'client_id'=>'53598e0b18fed60710851327',
				'secret'=>'AhNfb_cdk--WQDpkkz8JTo',
				'access_token'=>$tokenRow['token'],
				'options'=>array('gte'=>date('m/d/Y', strtotime('-10 years')) )
			);
	
			$response = run_on_api('https://tartan.plaid.com/connect/get', $data, 'POST');
			
			if(!empty($response['accounts'])) {
				$result = import_account_data($response['accounts'], $tokenRow);
				if($result) $result = process_account_data($tokenRow['user_id']);
			}
			if(!empty($response['transactions'])) $result = import_transaction_data($response['transactions'], $tokenRow);
			
			log_message('info', 'get_user_tokens:: For user details: '.json_encode($tokenRow).PHP_EOL.
					' ACCOUNTS: '.(!empty($response['accounts'])? count($response['accounts']): 0).PHP_EOL.
					' TRANSACTIONS: '.(!empty($response['transactions'])? count($response['transactions']): 0));
		}
	}
}
else {
	log_message('info', 'get_user_tokens:: FAIL');
	exit;
}





# import account data
function import_account_data($response, $user)
{
	$credit = $depository = 0;
	$resultArray = array();
	
	foreach($response AS $row)
	{
		# depository (other) account types
		if($row['type'] == 'depository')
		{
			$depository++;
			$variableData = array('account_id'=>$row['_id'], 'user_id'=>$user['user_id'], 'status'=>'', 'account_number'=>$row['_item'], 'account_number_real'=>$row['meta']['number'], 'account_nickname'=>htmlentities($row['meta']['name'], ENT_QUOTES), 'display_position'=>'', 'institution_id'=>$user['bank_id'], 'description'=>'PLAID - ', 'registered_user_name'=>(!empty($user['first_name'])?htmlentities($user['first_name'].' '.$user['last_name'], ENT_QUOTES):''), 'balance_amount'=>$row['balance']['current'], 'balance_date'=>date('Y-m-d H:i:s'), 'balance_previous_amount'=>'0.0', 'last_transaction_date'=>'0000-00-00 00:00:00', 'aggr_success_date'=>'0000-00-00 00:00:00', 'aggr_attempt_date'=>'0000-00-00 00:00:00', 'aggr_status_code'=>'', 'currency_code'=>'USD', 'bank_id'=>'',  	'institution_login_id'=>'', 'banking_account_type'=>'', 'posted_date'=>'0000-00-00 00:00:00', 'available_balance_amount'=>(!empty($row['balance']['available'])?$row['balance']['available']:'0.0'), 'interest_type'=>'', 'origination_date'=>'0000-00-00 00:00:00', 'open_date'=>'0000-00-00 00:00:00', 'period_interest_rate'=>'0.0', 'period_deposit_amount'=>'0.0', 'period_interest_amount'=>'0.0', 'interest_amount_ytd'=>'0.0', 'interest_prior_amount_ytd'=>'0.0', 'maturity_date'=>'0000-00-00 00:00:00', 'maturity_amount'=>'0.0', 'raw_table_name'=>'bank_accounts_other_raw');
			
			$resultArray[] = run('save_raw_bank_account',$variableData);
		}
		# credit account types
		else if($row['type'] == 'credit')
		{
			$credit++;
			$variableData = array('account_id'=>$row['_id'], 'user_id'=>$user['user_id'], 'status'=>'', 'account_number'=>$row['_item'], 'account_number_real'=>$row['meta']['number'], 'account_nickname'=>htmlentities($row['meta']['name'], ENT_QUOTES), 'display_position'=>'', 'institution_id'=>$user['bank_id'], 'description'=>'PLAID - ', 'registered_user_name'=>(!empty($user['first_name'])?htmlentities($user['first_name'].' '.$user['last_name'], ENT_QUOTES):''), 'balance_amount'=>$row['balance']['current'], 'balance_date'=>date('Y-m-d H:i:s'), 'balance_previous_amount'=>'0.0', 'last_transaction_date'=>'0000-00-00 00:00:00', 'aggr_success_date'=>'0000-00-00 00:00:00', 'aggr_attempt_date'=>'0000-00-00 00:00:00', 'aggr_status_code'=>'', 'currency_code'=>'USD', 'bank_id'=>'',  	'institution_login_id'=>'', 'credit_account_type'=>'', 'detailed_description'=>'', 'interest_rate'=>'0.0', 'credit_available_amount'=>(!empty($row['balance']['available'])?$row['balance']['available']:'0.0'), 'credit_max_amount'=>(!empty($row['meta']['limit'])?$row['meta']['limit']:'0.0'), 'cash_advance_available_amount'=>'0.0', 'cash_advance_max_amount'=>'0.0', 'cash_advance_balance'=>'0.0', 'cash_advance_interest_rate'=>'0.0', 'current_balance'=>'0.0', 'payment_min_amount'=>'0.0', 'payment_due_date'=>'0000-00-00 00:00:00', 'previous_balance'=>'0.0', 'statement_end_date'=>'0000-00-00 00:00:00', 'statement_purchase_amount'=>'0.0', 'statement_finance_amount'=>'0.0', 'past_due_amount'=>'0.0', 'last_payment_amount'=>'0.0', 'last_payment_date'=>'0000-00-00 00:00:00', 'statement_close_balance'=>'0.0', 'statement_late_fee_amount'=>'0.0', 'raw_table_name'=>'bank_accounts_credit_raw'    );
			$resultArray[] =  run('save_raw_credit_account', $variableData);
		}
	}
	
	return get_decision($resultArray);
}


# process raw account data for use in the system
function process_account_data($userId)
{
	log_message('info', 'process_account_data:: start..');
	# 1. get all unprocessed/updated accounts
	$raw = get_list('get_unprocessed_accounts', array('user_id'=>$userId));
	$lastAccount = get_row_as_array('get_last_inserted_account_id');
	$lastId = !empty($lastAccount['max_account_id'])? $lastAccount['max_account_id']: 0;
	log_message('info', 'process_account_data:: lastId='.$lastId);
	
	# 2. prepare insert string for update
	$insertString = $rawIds = $balances = $results = array();
	foreach($raw AS $rAccount){
		# the accounts to add to the final account table
		$lastId++;
		$insertString[] = " (SELECT '".$lastId."' AS id, '".$userId."' AS _user_id, '".$rAccount['account_type']."' AS account_type,'".$rAccount['account_id']."' AS account_id,".
				"'".$rAccount['account_number_real']."' AS account_number,'".$rAccount['bank_id']."' AS _bank_id,".
				"'".$rAccount['bank_name']."' AS issue_bank_name, '".$rAccount['card_holder_full_name']."' AS card_holder_full_name, ".
				"'".$rAccount['account_nickname']."' AS account_nickname, '".$rAccount['currency_code']."' AS currency_code, 'Y' AS is_verified, ".
				"'active' AS status, NOW() AS last_sync_date) ";
			
		# the raw account IDs
		$rawIds[$rAccount['account_type']][] = $rAccount['id'];
		# keep track of the updated balances
		$balances[$rAccount['account_type']] = array('account_id'=>$lastId, 'balance'=>$rAccount['balance']);
	}
	log_message('info', 'process_account_data:: rawIds='.json_encode($rawIds));
	
	# 3. move the new accounts and update the old ones
	if(!empty($insertString)) $result = run('batch_insert_accounts', array('insert_string'=>implode('UNION',$insertString) ));
	if(!empty($result)) log_message('info', 'process_account_data:: batch_insert_accounts > result='.json_encode($result));
	
	# 4. update the account balances
	if(!empty($result) && $result) {
		foreach($balances AS $type=>$balance){
			$tableType = $type == 'other'? 'cash': $type;
			$results[] = run('mark_previous_tracking_as_not_active', array('table_name'=>'user_'.$tableType.'_tracking', 'user_id'=>$userId, 'bank_account_id'=>$balance['account_id']));
			$results[] = run('add_user_account_tracking', array('table_name'=>'user_'.$tableType.'_tracking', 'bank_account_id'=>$balance['account_id'], 'user_id'=>$userId, 'balance_value'=>$balance['balance'], 'balance_field'=>$tableType.'_amount' ));
		}
		$result = get_decision($results);
			
		# if all is good, mark the raw transactions as processed
		if($result) foreach($rawIds AS $type=>$ids) $result = run('mark_raw_accounts_as_saved', array('account_type'=>$type, 'raw_ids'=>implode("','",$ids)));
	}
	log_message('info', 'process_account_data:: final result='.(!empty($result) && $result? 'SUCCESS': 'FAIL'));
	
	return !empty($result) && $result;
}





# import transaction data
function import_transaction_data($response, $user)
{
	$insertStrings = array();
	$resultArray = array();
	$counter = $limitCounter = 0;
	
	$totalRawCount = count($response);
	
	#Go through all transactions sent and record each based on type
	foreach($response AS $row)
	{
		/*
		 * transaction_id, transaction_type, currency_type,  institution_transaction_id, correct_institution_transaction_id, correct_action, server_transaction_id, check_number, reference_number, confirmation_number, 
		 * payee_id, payee_name, extended_payee_name, memo, type, value_type, currency_rate, original_currency, posted_date, user_date, available_date, amount, running_balance_amount, pending, normalized_payee_name, 
		 * merchant, sic, source, category_name, context_type, schedule_c, banking_transaction_type, subaccount_fund_type, banking_401k_source_type, principal_amount, interest_amount, escrow_total_amount, 
		 * escrow_tax_amount, escrow_insurance_amount, escrow_pmi_amount, escrow_fees_amount, escrow_other_amount, last_update_date, latitude, longitude, zipcode, state, city, address, sub_category_id, 
		 * contact_telephone, website, confidence_level, place_type, _user_id, _bank_id, api_account, new_user
		 */
		$insertStrings[] = " (SELECT '".$row['_id']."' AS transaction_id, 'banking' AS transaction_type, 'USD' AS currency_type,  'PLAID-".$row['_id']."' AS institution_transaction_id, ".
		 "'' AS correct_institution_transaction_id, '' AS correct_action, '' AS server_transaction_id, '' AS check_number, '' AS reference_number, '' AS confirmation_number, ".
		 "'".$row['_account']."' AS payee_id, '".htmlentities($row['name'], ENT_QUOTES)."' AS payee_name, '".htmlentities($row['name'], ENT_QUOTES)."' AS extended_payee_name, ".
		 "'' AS memo, '".(!empty($row['type']['primary'])?$row['type']['primary']:'other')."' AS type, '' AS value_type, '1' AS currency_rate, '' AS original_currency, ".
		 "'".(!empty($row['date'])?date('Y-m-d H:i:s', strtotime($row['date'])):'0000-00-00 00:00:00')."' AS posted_date, '0000-00-00 00:00:00' AS user_date, '".date('Y-m-d H:i:s')."' AS available_date, ".
		 "'".(!empty($row['amount'])?$row['amount']:'0.0')."' AS amount, '' AS running_balance_amount, '".(!empty($row['pending']) && $row['pending'] == 'false'? 'false':'true')."' AS pending, ".
		 "'".htmlentities($row['name'], ENT_QUOTES)."' AS normalized_payee_name, '".$row['_account']."' AS merchant, '' AS sic, 'plaid' AS source, ".
		 
		 "'".htmlentities((!empty($row['category'][0])? (!empty($row['category'][1])? (!empty($row['category'][2])? $row['category'][0].':'.$row['category'][1].':'.$row['category'][2]: $row['category'][0].':'.$row['category'][1]): $row['category'][0]): ''), ENT_QUOTES)."' AS category_name, ".
		 "'' AS context_type, '' AS schedule_c, '' AS banking_transaction_type, '' AS subaccount_fund_type, '' AS banking_401k_source_type, '' AS principal_amount, '' AS interest_amount, '' AS escrow_total_amount, ".
		 "'' AS escrow_tax_amount, '' AS escrow_insurance_amount, '' AS escrow_pmi_amount, '' AS escrow_fees_amount, '' AS escrow_other_amount, NOW() AS last_update_date, ".
		 "'".(!empty($row['meta']['location']['coordinates']['lat'])? $row['meta']['location']['coordinates']['lat']: '')."' AS latitude, ".
		 "'".(!empty($row['meta']['location']['coordinates']['log'])? $row['meta']['location']['coordinates']['log']: '')."' AS longitude, ".
		 "'".(!empty($row['meta']['location']['zip'])? $row['meta']['location']['zip']: '')."' AS zipcode, ".
		 "'".(!empty($row['meta']['location']['state'])? $row['meta']['location']['state']: '')."' AS state, ".
		 "'".(!empty($row['meta']['location']['city'])? $row['meta']['location']['city']: '')."' AS city, ".
		 
		 "'".(!empty($row['meta']['location']['address'])? htmlentities($row['meta']['location']['address'], ENT_QUOTES): '')."' AS address, ".
		 "'".(!empty($row['category_id'])? $row['category_id']: '')."' AS sub_category_id, ".
		 "'".(!empty($row['meta']['contact']['telephone'])? remove_string_special_characters($row['meta']['contact']['telephone']): '')."' AS contact_telephone, ".
		 "'".(!empty($row['meta']['contact']['website'])? $row['meta']['contact']['website']: '')."' AS website, ".
		 "'".(!empty($row['score']['name'])? $row['score']['name']: '0.5')."' AS confidence_level, ".
		 "'".(!empty($row['type']['primary'])? $row['type']['primary']: '')."' AS place_type, ".
		 "'".$user['user_id']."' AS _user_id, '".$user['bank_id']."' AS _bank_id, '".$row['_account']."' AS api_account, ".
		 "'".(!empty($user['new_user']) && $user['new_user'] == 'yes'? 'Y': 'N')."' AS new_user) ";
		
		# insert at 1000 row intervals
		if($limitCounter > 1000 || ($limitCounter == ($totalRawCount - 1))){
			$resultArray[] = run('batch_insert_raw_transactions', array('insert_string'=>implode('UNION', $insertStrings) ));
			# reset counter
			$limitCounter = 0;
		}
		
		$limitCounter++;
		$counter++;
	}
	
	$result = get_decision($resultArray);
	log_message('debug', '::RAW transaction insert '.($result? 'SUCCESS': 'FAIL').' for user id: '.$user['user_id'].' with count of '.$counter.' transactions.');
	return $result;
}







?>