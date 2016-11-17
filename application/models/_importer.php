<?php
/**
 * Imports data into the database.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 09/09/2015
 */
class _importer extends CI_Model
{
	
	
	#Get new user score stores
	public function get_new_store_scores($userId, $return = 'string', $limit=1000)
	{
		log_message('debug', '_importer/get_new_store_scores');
		log_message('debug', '_importer/get_new_store_scores:: [1] userId='.$userId.' limit='.$limit);
		
		$list = $this->_query_reader->get_list('get_new_store_scores', array('user_id'=>$userId, 'limit_text'=>' LIMIT '.$limit.' ')); 
		$emailString = $smsString = "";
		if(!empty($list)) {
			foreach($list AS $i=>$row) {
				$emailString .= $row['store_name'].'='.format_number($row['store_score'],100,0).', ';
				if($i < 5) $smsString .= $row['store_name'].'='.format_number($row['store_score'],100,0).', ';
			}
		}
		
		log_message('debug', '_importer/get_new_store_scores:: [2] emailString='.$emailString.' SMS string='.$smsString);
		
		return array(
				'email_string'=>trim($emailString, ', '), 
				'sms_string'=>trim($smsString, ', ').($emailString != $smsString? ' ..more in email': ''), 
				'list'=>$list
		);
	}
	
	
	
	
	#Get all the transactions based on the requirements set by the caller
	public function get_user_transactions_from_api($accessToken, $restrictions)
	{
		log_message('debug', '_importer/get_user_transactions_from_api');
		log_message('debug', '_importer/get_user_transactions_from_api:: [1] accessToken='.$accessToken.' restrictions='.$restrictions);
		
		#Prepare data for running on API
		$dataArray = array(
			'client_id'=>PLAID_CLIENT_ID, 
			'secret'=>PLAID_SECRET, 
			'access_token'=>$accessToken
		);
		#Add restrictions if specified
		$dataArray['options']['pending'] = !empty($restrictions['ignore_pending'])? false: true;
		if(!empty($restrictions['plaid_account'])) $dataArray['options']['account'] = $restrictions['plaid_account'];
		if(!empty($restrictions['after_last_transaction'])) $dataArray['options']['last'] = $restrictions['after_last_transaction'];
		if(!empty($restrictions['after_date'])) $dataArray['options']['gte'] = date('m/d/Y', strtotime($restrictions['after_date']));
		if(!empty($restrictions['before_date'])) $dataArray['options']['lte'] = date('m/d/Y', strtotime($restrictions['before_date']));
		
		$url = PLAID_CONNECTION_URL.'/connect';
		log_message('debug', '_importer/get_user_transactions_from_api:: [1] dataArray='.json_encode($dataArray));
		
		return run_on_api($url, $dataArray, 'GET');
	}
	
	
	
	
	
	
	
	# save raw account data
	public function import_account_data($response, $userId, $bankId) 
	{

		log_message('debug', '_importer/import_account_data');
		log_message('debug', '_importer/import_account_data:: [1] response='.$response.' userId='.$userId.' bankId='.$bankId);
		
		$resultArray = array();
		$userDetails = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$userId));
		log_message('debug', '_importer/import_account_data:: [2] userDetails='.json_encode($userDetails));
		
		
		# go through all accounts sent and record each based on type
		$credit = $depository = $cashBalance = $creditBalance = 0;
		foreach($response AS $row)
		{
			#Does similar record already exist?
			if($row['type'] == 'depository')
			{
				$depository++;
				$variableData = array('account_id'=>$row['_id'], 'user_id'=>$userId, 'status'=>'active', 'account_number'=>$row['_item'], 'account_number_real'=>$row['meta']['number'], 'account_nickname'=>htmlentities($row['meta']['name'], ENT_QUOTES), 'display_position'=>'', 'institution_id'=>$bankId, 'description'=>'PLAID - '.htmlentities($row['meta']['name'], ENT_QUOTES), 'registered_user_name'=>(!empty($userDetails['first_name'])?htmlentities($userDetails['first_name'].' '.$userDetails['last_name'], ENT_QUOTES):''), 'balance_amount'=>$row['balance']['current'], 'balance_date'=>date('Y-m-d H:i:s'), 'balance_previous_amount'=>'0.0', 'last_transaction_date'=>'0000-00-00 00:00:00', 'aggr_success_date'=>'0000-00-00 00:00:00', 'aggr_attempt_date'=>'0000-00-00 00:00:00', 'aggr_status_code'=>'', 'currency_code'=>'USD', 'bank_id'=>'',  	'institution_login_id'=>'', 'banking_account_type'=>'', 'posted_date'=>'0000-00-00 00:00:00', 'available_balance_amount'=>(!empty($row['balance']['available'])?$row['balance']['available']:'0.0'), 'interest_type'=>'', 'origination_date'=>'0000-00-00 00:00:00', 'open_date'=>'0000-00-00 00:00:00', 'period_interest_rate'=>'0.0', 'period_deposit_amount'=>'0.0', 'period_interest_amount'=>'0.0', 'interest_amount_ytd'=>'0.0', 'interest_prior_amount_ytd'=>'0.0', 'maturity_date'=>'0000-00-00 00:00:00', 'maturity_amount'=>'0.0', 'raw_table_name'=>'bank_accounts_other_raw');
				#echo PHP_EOL.PHP_EOL."ACCOUNT DATA: "; print_r($variableData);
				$resultArray[] = $this->_query_reader->run('save_raw_bank_account', $variableData);
				$cashBalance += $row['balance']['current'];
			}
			else if($row['type'] == 'credit')
			{
				$credit++;
				$variableData = array('account_id'=>$row['_id'], 'user_id'=>$userId, 'status'=>'active', 'account_number'=>$row['_item'], 'account_number_real'=>$row['meta']['number'], 'account_nickname'=>htmlentities($row['meta']['name'], ENT_QUOTES), 'display_position'=>'', 'institution_id'=>$bankId, 'description'=>'PLAID - '.htmlentities($row['meta']['name'], ENT_QUOTES), 'registered_user_name'=>(!empty($userDetails['first_name'])?htmlentities($userDetails['first_name'].' '.$userDetails['last_name'], ENT_QUOTES):''), 'balance_amount'=>$row['balance']['current'], 'balance_date'=>date('Y-m-d H:i:s'), 'balance_previous_amount'=>'0.0', 'last_transaction_date'=>'0000-00-00 00:00:00', 'aggr_success_date'=>'0000-00-00 00:00:00', 'aggr_attempt_date'=>'0000-00-00 00:00:00', 'aggr_status_code'=>'', 'currency_code'=>'USD', 'bank_id'=>'',  	'institution_login_id'=>'', 'credit_account_type'=>'', 'detailed_description'=>'', 'interest_rate'=>'0.0', 'credit_available_amount'=>(!empty($row['balance']['available'])?$row['balance']['available']:'0.0'), 'credit_max_amount'=>(!empty($row['meta']['limit'])?$row['meta']['limit']:'0.0'), 'cash_advance_available_amount'=>'0.0', 'cash_advance_max_amount'=>'0.0', 'cash_advance_balance'=>'0.0', 'cash_advance_interest_rate'=>'0.0', 'current_balance'=>'0.0', 'payment_min_amount'=>'0.0', 'payment_due_date'=>'0000-00-00 00:00:00', 'previous_balance'=>'0.0', 'statement_end_date'=>'0000-00-00 00:00:00', 'statement_purchase_amount'=>'0.0', 'statement_finance_amount'=>'0.0', 'past_due_amount'=>'0.0', 'last_payment_amount'=>'0.0', 'last_payment_date'=>'0000-00-00 00:00:00', 'statement_close_balance'=>'0.0', 'statement_late_fee_amount'=>'0.0', 'raw_table_name'=>'bank_accounts_credit_raw'    );
				$resultArray[] =  $this->_query_reader->run('save_raw_credit_account', $variableData);
				$creditBalance += $row['balance']['current'];
			}
		}

		$result = get_decision($resultArray, FALSE);
		# send delayed query for execution
		if($result && ($depository > 0 || $credit > 0)){
			server_curl(CRON_SERVER_URL,  array('__action'=>'add_job_to_queue', 'return'=>'plain',
					'jobId'=>'j'.$userId.'-'.strtotime('now').'-'.rand(0,1000000),
					'jobUrl'=>'query',
					'userId'=>$userId,
					'jobCode'=>'delayed_query',
					'parameters'=>array('db_server_url'=>BASE_URL.'main/index', 'action'=>'run','query'=> 'update_user_data_fields',
							'variables'=>array('update_string'=>"total_linked_accounts=(SELECT ((SELECT COUNT(*) FROM bank_accounts_other_raw WHERE _user_id='".$userId."') + ".
										"(SELECT COUNT(*) FROM bank_accounts_credit_raw WHERE _user_id='".$userId."'))),".
									"total_linked_banks=(SELECT ((SELECT COUNT(DISTINCT _institution_id) FROM bank_accounts_other_raw WHERE _user_id='".$userId."') + ".
										"(SELECT COUNT(DISTINCT _institution_id) FROM bank_accounts_credit_raw WHERE _user_id='".$userId."'))),".
									"cash_balance_today=(SELECT SUM(balance_amount) FROM bank_accounts_other_raw WHERE _user_id='".$userId."'),".
									"credit_balance_today=(SELECT SUM(balance_amount) FROM bank_accounts_credit_raw WHERE _user_id='".$userId."')".
									($depository > 0? ", bank_verified_and_active='Y'": "").
									($credit > 0? ", credit_verified_and_active='Y'": ""), 
									'user_id'=>$userId
							))
			));
		}
		log_message('debug', '_importer/import_account_data:: [3] resultArray='.json_encode($resultArray));
		return $result;
	}
	
	
	
	
	
	# save import 
	public function import_transaction_data($response, $user)
	{
		$insertStrings = $users = array();
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
				 "'".$row['_account']."' AS payee_id, '".htmlentities(trim($row['name'],'\\'), ENT_QUOTES)."' AS payee_name, '".htmlentities(trim($row['name'],'\\'), ENT_QUOTES)."' AS extended_payee_name, ".
				 "'' AS memo, '".(!empty($row['type']['primary'])?$row['type']['primary']:'other')."' AS type, '' AS value_type, '1' AS currency_rate, '' AS original_currency, ".
				 "'".(!empty($row['date'])?date('Y-m-d H:i:s', strtotime($row['date'])):'0000-00-00 00:00:00')."' AS posted_date, '0000-00-00 00:00:00' AS user_date, '".date('Y-m-d H:i:s')."' AS available_date, ".
				 "'".(!empty($row['amount'])?$row['amount']:'0.0')."' AS amount, '' AS running_balance_amount, '".(!empty($row['pending']) && $row['pending'] == 'false'? 'false':'true')."' AS pending, ".
				 "'".htmlentities(trim($row['name'],'\\'), ENT_QUOTES)."' AS normalized_payee_name, '".$row['_account']."' AS merchant, '' AS sic, 'plaid' AS source, ".
				 	
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
				$resultArray[] = $this->_query_reader->run('batch_insert_raw_transactions', array('insert_string'=>implode('UNION', $insertStrings) ));
				# reset counter
				$limitCounter = 0;
			}
			
			$users[$user['user_id']] = 1; # we are interested in the keys - picking only the unique user IDs
			$limitCounter++;
			$counter++;
		}
		
		$result = get_decision($resultArray);
		
		# send delayed query for execution
		foreach(array_keys($users) AS $userId){
			server_curl(CRON_SERVER_URL,  array('__action'=>'add_job_to_queue', 'return'=>'plain',
				'jobId'=>'j'.$userId.'-'.strtotime('now').'-'.rand(0,1000000),
				'jobUrl'=>'query',
				'userId'=>$userId,
				'jobCode'=>'delayed_query',
				'parameters'=>array('db_server_url'=>BASE_URL.'main/index', 'action'=>'run','query'=> 'update_user_data_fields', 
									'variables'=>array('update_string'=>"last_transaction_import_date=NOW(), last_transaction_import_result='".($result? 'success': 'fail')."', member_processed_payment_last7days='Y' ", 
										'user_id'=>$userId)
									)
			));
		}
		
		log_message('debug', '::RAW transaction insert '.($result? 'SUCCESS': 'FAIL').' for user id: '.$user['user_id'].' with count of '.$counter.' transactions.');
		return $result;
	}
	
	
	
	
	
	# process raw account data for use in the system
	function process_account_data($userId)
	{
		log_message('debug', '_importer/process_account_data');
		
		# 1. get all unprocessed/updated accounts 
		$raw = $this->_query_reader->get_list('get_unprocessed_accounts', array('user_id'=>$userId));
		$lastAccount = $this->_query_reader->get_row_as_array('get_last_inserted_account_id');
		$lastId = !empty($lastAccount['max_account_id'])? $lastAccount['max_account_id']: 0;
		log_message('debug', '_importer/process_account_data:: [2] lastId='.$lastId);
		
		# 2. prepare insert string for update
		$insertString = $rawIds = $balances = $results = array();
		foreach($raw AS $rAccount){
			# the accounts to add to the final account table
			$lastId++;
			$insertString[] = " (SELECT '".$lastId."' AS id, '".$userId."' AS _user_id, '".$rAccount['account_type']."' AS account_type,'".$rAccount['account_id']."' AS account_id,".
				"'".$rAccount['account_number_real']."' AS account_number,'".$rAccount['bank_id']."' AS _bank_id,".
				"'".htmlentities($rAccount['bank_name'], ENT_QUOTES)."' AS issue_bank_name, '".htmlentities($rAccount['card_holder_full_name'], ENT_QUOTES)."' AS card_holder_full_name, ".
				"'".htmlentities($rAccount['account_nickname'], ENT_QUOTES)."' AS account_nickname, '".$rAccount['currency_code']."' AS currency_code, 'Y' AS is_verified, ".
				"'active' AS status, NOW() AS last_sync_date) ";
			
			# the raw account IDs
			$rawIds[$rAccount['account_type']][] = $rAccount['id'];
		}
		log_message('debug', '_importer/process_account_data:: [2] $rawIds='.json_encode($rawIds));
		
		# 3. move the new accounts and update the old ones
		if(!empty($insertString)) $result = $this->_query_reader->run('batch_insert_accounts', array('insert_string'=>implode('UNION',$insertString) ));
		# if all is good, mark the raw transactions as processed
		if($result) foreach($rawIds AS $type=>$ids) $result = $this->_query_reader->run('mark_raw_accounts_as_saved', array('account_type'=>$type, 'raw_ids'=>implode("','",$ids)));
		
		log_message('debug', '_importer/process_account_data:: [3] mark_raw_accounts_as_saved result='.(!empty($result) && $result? 'SUCCESS': 'FAIL'));
		
		# mark as successful even if result was fail if there are no new accounts to save
		$result = (!empty($result) && $result || empty($raw));
		# update account balances for the user
		if($result) $result = $this->update_account_balances($userId);
		
		log_message('debug', '_importer/process_account_data:: [4] result='.(!empty($result) && $result? 'SUCCESS': 'FAIL'));
		return $result;
	}
	
	
	
	
	
	
	
	
	# update account balances for the user
	function update_account_balances($userId)
	{
		log_message('debug', '_importer/update_account_balances');
		log_message('debug', '_importer/update_account_balances:: [1] result='.$userId);
		
		$results = array();
		# get account balance information for this user
		$balances = $this->_query_reader->get_list('get_processed_account_balances', array('user_id'=>$userId));
		foreach($balances AS $balance){
			$results[] = $this->_query_reader->run('mark_previous_tracking_as_not_active', array('table_name'=>'user_'.$balance['account_type'].'_tracking', 'user_id'=>$userId, 'bank_account_id'=>$balance['account_id']));
			$results[] = $this->_query_reader->run('add_user_account_tracking', array('table_name'=>'user_'.$balance['account_type'].'_tracking', 'bank_account_id'=>$balance['account_id'], 'user_id'=>$userId, 'balance_value'=>$balance['balance'], 'balance_field'=>$balance['account_type'].'_amount' ));
		}
		
		$result = get_decision($results, TRUE);
		
		# queue a job to recompute the account balances for this user
		if($result) {
			$this->load->model('_queue_publisher');
			
			$data['id'] = 'j'.$userId.'-'.strtotime('now').'-'.rand(0,1000000);
			$data['user'] = $userId;
			$data['code'] = 'recompute_account_balance_averages';
			$data['job'] = 'scoring_cron/recompute_account_balance_averages/user/'.$userId;
			$data['processor'] = RUNDECK_DEFAULT_JOB_PROCESSOR;
			$result = $this->_queue_publisher->add_job_to_queue($data);
		}
		
		log_message('debug', '_importer/update_account_balances:: [3] result='.($result? 'SUCCESS': 'FAIL'));
		return $result;
	}

	
	
	
	
	
	
	
	# Save the interaction of the user with the bank
	function record_bank_connection($userId, $bankId, $accountData)
	{
		log_message('debug', '_importer/record_bank_connection');
		log_message('debug', '_importer/record_bank_connection:: [1] userId='.$userId.' bankId='.$bankId.' restriction='.json_encode($restrictions));
		
		$response = $this->import_account_data($accountData, $userId, $bankId); 
		if($response) $response = $this->process_account_data($userId);
		log_message('debug', '_importer/record_bank_connection:: [2] response='.$response);
		
		return $response;
	}
	
	
	
	
	
	
	
	# save raw API bank data (account and transaction information)
	function save_raw_api_bank_data($userId, $token, $afterDate, $isNew='no')
	{
		log_message('debug', '_importer/save_raw_api_bank_data:: [1] ');
		$accessDetails = $this->_query_reader->get_row_as_array('get_plaid_access_token_by_user_id', array('user_id'=>$userId, 'access_token'=>$token));
		$message = "";
		
		if(!empty($accessDetails['_user_id']) && !empty($accessDetails['_bank_id'])){
			log_message('debug', '_importer/save_raw_api_bank_data:: [2] hitting the API for user_id: '.$accessDetails['_user_id']);
			$response = $this->get_user_transactions_from_api($token, array('after_date'=>$afterDate));
		
			log_message('debug', '_importer/save_raw_api_bank_data:: [3] get_user_transactions_from_api..'.
					' accounts: '. (!empty($response['accounts'])? 'received': 'NOT received').
					' transactions: '. (!empty($response['transactions'])? 'received': 'NOT received')
				);
		
			# save the account data to the database tables
			$importAccountResult = !empty($response['accounts'])? $this->import_account_data($response['accounts'], $accessDetails['_user_id'], $accessDetails['_bank_id']): false;
			log_message('debug', '_importer/save_raw_api_bank_data:: [4] import account result: '.($importAccountResult? 'SUCCESS': 'FAIL'));
			$moveAccountsResult = $importAccountResult? $this->process_account_data($accessDetails['_user_id']): false;
			
		
			# save the transaction data to the database tables
			$importTransactionResult = ($moveAccountsResult && !empty($response['transactions']))? $this->import_transaction_data($response['transactions'], 
										array('user_id'=>$accessDetails['_user_id'], 'bank_id'=>$accessDetails['_bank_id'], 'is_new'=>$isNew)): false;
			log_message('debug', '_importer/save_raw_api_bank_data:: [5] import transaction result: '.($importTransactionResult? 'SUCCESS': 'FAIL'));
			
			if(empty($response['accounts']) && empty($response['transactions'])) $message = "No new data saved.";
		}
		else {
			log_message('debug', '_importer/save_raw_api_bank_data:: [6] ERROR - no access details match the token.');
			$message = "ERROR: No access details match the token.";
		}
		
		# log activity in the database
		$result = ((!empty($response['transactions']) && $importTransactionResult) || (!empty($moveAccountsResult) && $moveAccountsResult)? 'SUCCESS': 'FAIL');
		$this->_logger->add_event(array(
				'user_id'=>(!empty($accessDetails['_user_id'])? $accessDetails['_user_id']: ''),
				'activity_code'=>'save_raw_api_bank_data',
				'result'=>strtolower($result),
				'log_details'=>"bank_id: ".(!empty($accessDetails['_bank_id'])? $accessDetails['_bank_id']: '')
								.", no_of_transactions_received: ".(!empty($response['transactions'])? count($response['transactions']): 0)
			));
		
		return array(
				'result'=>$result, 
				'import_account_result'=>$importAccountResult,
				'import_transactions_result'=>$importTransactionResult,
				'message'=>$message, 
				'user_id'=>(!empty($accessDetails['_user_id'])? $accessDetails['_user_id']:''), 
				'bank_id'=>(!empty($accessDetails['_bank_id'])? $accessDetails['_bank_id']:''),
				'account_count'=>(!empty($response['accounts'])? count($response['accounts']): 0),
				'transaction_count'=>(!empty($response['transactions'])? count($response['transactions']): 0)
		);
	}
	
	
	
	
	
	
	
	
	
	
	
	
}


?>
