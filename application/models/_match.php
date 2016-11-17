<?php
/**
 * This class matches transactions to their respective stores. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 06/06/2015
 */

class _match extends CI_Model
{
	
	# match transactions based on instruction
	function match_transactions_to_stores($parameters=array())
	{
		log_message('debug', '_match/match_transactions_to_stores:: [1] ');
		log_message('debug', '_match/match_transactions_to_stores:: [2] parameters: '.json_encode($parameters));
		$this->load->model('_mongo');
		
		# 1. pull all the transactions to process based on the set criteria
		$filter = (!empty($parameters['user_id'])? " AND _user_id='".$parameters['user_id']."' ": '').
					(!empty($parameters['ignore_new_users']) && $parameters['ignore_new_users'] == 'yes'? " AND new_user='N' ": '').
					(!empty($parameters['read_limit'])? " LIMIT ".$parameters['read_limit']: '');
		
		$rawTransactions = $this->_query_reader->get_list('get_transactions_to_process', array('filter_condition'=>$filter));
		$lastTransaction = $this->_query_reader->get_row_as_array('get_last_inserted_transaction');
		
		# unique name-address-city patterns
		$matchingData = $finalTransactions = $transactionsWithCats = $rawIds = $userIds = $storeIds = array();
		$lastId = !empty($lastTransaction['transaction_id'])? $lastTransaction['transaction_id']: 0;
		log_message('debug', '_match/match_transactions_to_stores:: [3] lastId='.$lastId.' rawTransactions(count): '.count($rawTransactions).' last_transaction: '.json_encode($lastTransaction));
		
		# for each new transaction, perform a match while learning from the previous match
		foreach($rawTransactions AS $row) {
			$lastId++;
			$status = $matchStoreId = $matchChainId = '';
			# keep track of your matching to run each only once
			$key = $row['descriptor'].'-'.$row['address_string'];
			#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] key='.$key);
			if(!array_key_exists($key, $matchingData)){
				$matchingData[$key] = array();
					
				# 2.1 reject unqualified transactions
				$conditions = array('descriptor'=>$row['descriptor'], 'place_type'=>$row['place_type'], 'address'=>$row['address'], 'city'=>$row['city']);
				#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) reject-- conditions='.json_encode($conditions));
				$reject = $this->_query_reader->get_row_as_array('run_matching_rule', array_merge(array('command'=>'reject'),$conditions));
				#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) reject-- result='.json_encode($reject));
				if(!empty($reject)) {
					$status = 'unqualified';
					$matchStoreId = '';
					$matchChainId = '';
				}
				else {
					# 2.2 check apply matching rules for store matches
					#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) match-- in match');
					$match = $this->_query_reader->get_row_as_array('run_matching_rule', array_merge(array('command'=>'match'),$conditions));
					#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) match-- result='.json_encode($match));
					if(!empty($match)){
						$status = 'auto-matched-rule';
						$matchStoreId = $match['store_id'];
						$matchChainId = $match['chain_id'];
					}
					else {
						# 2.3 perfom search on un-matched record
						/*log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) mongo search -- conditions: '.json_encode(array('table'=>'bname', 'array'=>array(
								'conditions'=>array('name'=>$row['descriptor'], 'address'=>array('$regex'=>'^'.strstr_($row['address'],' ',TRUE,3).'.*'), 'city'=>$row['city']), 
								'values'=>array('store_id'=>1,'chain_id'=>1) 
							))));*/
						if(!empty($row['descriptor']) && !empty($row['address']) && !empty($row['city'])){
							$search = $this->_mongo->get_row_as_array(array('table'=>'bname', 'array'=>array(
									'conditions'=>array('name'=>$row['descriptor'], 'address'=>array('$regex'=>'^'.strstr_($row['address'],' ',TRUE,3).'.*'), 'city'=>$row['city']), 
									'values'=>array('store_id'=>1,'chain_id'=>1) 
								)), TRUE);
						}
						#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) mongo search-- result='.json_encode($search));
						if(!empty($search)){
							$status = 'auto-matched-search';
							$matchStoreId = $search['store_id'];
							$matchChainId = $search['chain_id'];
						}
						
						# 2.4 insert new stores which could not be matched
						else {
							/*log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: ALL FAILED! -- insert: '.json_encode(array(
									'name'=>$row['descriptor'], 'address_line_1'=>$row['address'], 'city'=>$row['city'], 'state'=>$row['state'], 
									'zipcode'=>$row['zipcode'], 'country'=>'usa', 'is_live'=>'N'
							)));*/
							
							$matchChainId = $this->_query_reader->add_data('add_chain_record_from_transaction', array(
									'name'=>$row['descriptor'], 'address_line_1'=>$row['address'], 'city'=>$row['city'], 'state'=>$row['state'], 
									'zipcode'=>$row['zipcode'], 'country'=>'usa', 'is_live'=>'N'
							));
							# if no new chain is inserted may be the chain already exists..
							if(empty($matchChainId)) {
								$chain = $this->_query_reader->get_row_as_array('get_chain_by_name', array('chain_name'=>$row['descriptor']));
								$matchChainId = !empty($chain['chain_id'])? $chain['chain_id']: '';
							}
							#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) matchChainId='.$matchChainId);
							if(!empty($matchChainId)) {
								/*log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: -- insert:'.json_encode(array(
										'chain_id'=>$matchChainId, 'name'=>$row['descriptor'], 'online_only'=>($row['place_type'] == 'digital'? 'Y': 'N'),
										'status'=>'pending', 'address_line_1'=>$row['address'], 'city'=>$row['city'], 'state'=>$row['state'], 'zipcode'=>$row['zipcode'], 
										'country'=>'usa'
								)));*/
								$matchStoreId = $this->_query_reader->add_data('add_store_record_from_transaction', array(
										'chain_id'=>$matchChainId, 'name'=>$row['descriptor'], 'online_only'=>($row['place_type'] == 'digital'? 'Y': 'N'),
										'status'=>'pending', 'address_line_1'=>$row['address'], 'city'=>$row['city'], 'state'=>$row['state'], 'zipcode'=>$row['zipcode'], 
										'country'=>'usa'
								));
								
								# if no new store is inserted may be the store already exists..
								if(empty($matchStoreId)) {
									$store = $this->_query_reader->get_row_as_array('get_store_by_details', array('store_name'=>$row['descriptor'], 'address'=>$row['address'],
												'city'=>$row['city']
											));
									$matchStoreId = !empty($store['store_id'])? $store['store_id']: '';
								}
								
								#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) matchStoreId='.$matchStoreId);
							}
							
							if(!empty($matchStoreId)) $status = 'auto-matched-insert';
						}
					}
				}
				
				# if everything else fails or there is an error, mark status as not-found to be ran again later
				if(empty($status)) $status = 'not-found';
				
				$matchingData[$key]['status'] = $status; 
				$matchingData[$key]['match_store_id'] = $matchStoreId;
				$matchingData[$key]['match_chain_id'] = $matchChainId;
				#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) matchingData final array: '.json_encode($matchingData[$key]));
			}
			# already matched, reuse
			else {
				#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) matchingData repeated key: '.$key);
				$status = $matchingData[$key]['status'];
				$matchStoreId = $matchingData[$key]['match_store_id'];
				$matchChainId = $matchingData[$key]['match_chain_id'];
				#log_message('debug', '>>>>>>>>>_match/match_transactions_to_stores:: [*] NEW KEY: 1) matchingData repeated array: '.json_encode($matchingData[$key]));
			}
			
			# 3. Apply matching instructions while inserting the transaction record 
			$finalTransactions[] = " (SELECT '".$lastId."' AS id, '".($row['amount'] < 0? 'deposit': 'buy')."' AS transaction_type, ".
					"'".$row['user_id']."' AS _user_id, '".$row['descriptor']."' AS raw_store_name, '".($row['pending'] == 'true'? 'pending': 'complete')."' AS `status`, ".
					"'".$row['amount']."' AS amount, '".$row['raw_id']."' AS _raw_id, '".$row['sub_category_id']."' AS item_category, ".
					"'".date('Y-m-d',strtotime($row['transaction_date']))."' AS start_date, '".date('Y-m-d',strtotime($row['end_date']))."' AS end_date, ". 
					"'".$row['bank_id']."' AS _bank_id, '".$row['zipcode']."' AS zipcode, '".$row['state']."' AS state, '".$row['city']."' AS city, '".$row['address']."' AS address, ".
					"'".$status."' AS match_status, '".$matchStoreId."' AS _store_id, '".$matchChainId."' AS _chain_id, '".$row['place_type']."' AS place_type ) ";
			
			# 4. collect the raw transaction IDs for marking as complete when done
			array_push($rawIds, $row['raw_id']);
			# 5. collect all users from the transaction records
			$userIds[$row['user_id']] = $lastId; #the value does not matter. Interest is the key
			$storeIds[$row['user_id']][] = $matchStoreId;
			
			# 6. filter out transactions with categories to be matched to system categories
			if(!empty($row['sub_category_id'])) $transactionsWithCats[] = array('user_id'=>$row['user_id'], 'transaction_id'=>$lastId, 'sub_category_id'=>$row['sub_category_id']);
		}
		
		# batch-insert the transactions with their respective matches and match-status
		$result = !empty($finalTransactions)? $this->_query_reader->run('batch_insert_transactions', array('insert_string'=>implode('UNION',$finalTransactions) )): FALSE;
		log_message('debug', '_match/match_transactions_to_stores:: [4] result: '.($result? 'SUCCESS': 'FAIL').' transactionsWithCats count:'.count($transactionsWithCats));
		
		# update any previously matched stores as unreported now that they have new transactions
		if($result){
			$results = array();
			foreach($storeIds AS $userId=>$ids){
				$stores = array_diff(array_unique($ids), array(''));
				if(!empty($stores)) $results[] = $this->_query_reader->run('mark_stores_as_un_reported', array('user_id'=>$userId, 'store_ids'=>implode("','",$stores) ));
			}
			$result = get_decision($results, TRUE);
		}
		
		return array('boolean'=>$result, 'transactions'=>$transactionsWithCats, 'raw_ids'=>$rawIds, 'user_ids'=>array_keys($userIds));
	}
	
	
	
	
	
	
	
	
	
	
	
	# match all given transactions to the transaction category
	function match_transactions_to_categories($transactions)
	{
		log_message('debug', '_match/match_transactions_to_categories:: [1] ');
		log_message('debug', '_match/match_transactions_to_categories:: [2] transactions count: '.count($transactions));
		
		# 1. get all the category matches for the passed transactions 
		$categories = $this->_query_reader->get_list('get_plaid_category_matches', 
						array('sub_category_ids'=>implode("','", get_column_from_multi_array($transactions, 'sub_category_id'))));
		
		$categoryMatches = bundle_by_column_multi_array($categories, 'system_sub_category_id', 'plaid_sub_category_id', TRUE);
		log_message('debug', '_match/match_transactions_to_categories:: [3] categories(count): '.count($categories).' categoryMatches(count):'.count($categoryMatches));
		
		# 2. generate query string to insert categories
		$matchStrings = array();
		foreach($transactions AS $transaction){
			foreach($categoryMatches AS $match) {
				if($match['plaid_sub_category_id'] == $transaction['sub_category_id']) {
					$matchStrings[] = " (SELECT '".$transaction['user_id']."' AS _user_id, '".$transaction['transaction_id']."' AS _transaction_id, '".
							$match['system_category_id']."' AS _category_id, '".
							$match['system_sub_category_id']."' AS _sub_category_id, 'N' AS is_processed) ";
				}
			}
		}
		
		# batch insert the transaction categories
		$result['boolean'] = !empty($matchStrings)? $this->_query_reader->run('batch_insert_transaction_categories', array('insert_string'=>implode('UNION',$matchStrings) )): FALSE;
		return $result;
	}
	
	
	
	
	
}


?>