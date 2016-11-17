<?php
/**
 * This class processes scoring business logic. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 05/06/2015
 */
class _score extends CI_Model
{
	
	# generate the clout score
	function generate_clout_score($userId)
	{
		log_message('debug', '_score/generate_clout_score:: [1] ');
		$settings = $this->get_score_settings($userId, 'clout');
		$result = $this->_query_reader->run('compute_clout_score', $settings);
		if($result) $cloutScore = $this->_query_reader->get_row_as_array('get_cached_user_clout_score', array('user_id'=>$userId));
		
		log_message('debug', '_score/generate_clout_score:: [2] clout-score array: '.(!empty($cloutScore)? json_encode($cloutScore): 'NONE'));
		return array('result'=>(!empty($cloutScore['total_score'])? 'SUCCESS': 'FAIL'), 
					'score'=>(!empty($cloutScore['total_score'])? $cloutScore['total_score']: 0)
				);
	}
	
	
	
	
	# generate the store score
	function generate_store_score($userId)
	{
		log_message('debug', '_score/generate_store_score:: [1] ');
		$settings = $this->get_score_settings($userId, 'store');
		$userData = $this->_query_reader->get_row_as_array('get_user_data_record', array('user_id'=>$userId));
		log_message('debug', "_score/generate_store_score:: [2] user record: ".(!empty($userData)? $userData['user_id']: ''));
		
		# 1 a) get the matched stores whose scores need to be computed
		if(!empty($userData)) $matchedStores = $this->_query_reader->get_single_column_as_array('get_unprocessed_matched_stores', 'store_id', array('user_id'=>$userId));
		# 1 b) compute the scores for the matched stores
		if(!empty($matchedStores)) {
			$settings['matched_stores'] = implode("','",$matchedStores);
			$settings = array_merge($userData, $settings);
			log_message('debug', "_score/generate_store_score:: [3] matched stores: '".$settings['matched_stores']."'");
			foreach($matchedStores AS $store){
				# check if the store table exists
				$tableCheck = $this->_query_reader->get_row_as_array('check_if_table_exists', array('table_name'=>'datatable__store_'.$store.'_data', 'database'=>'clout_v1_3cron'));
				# since the table exists, check if the user record exists
				if(!empty($tableCheck)) {
					$recordCheck = $this->_query_reader->get_row_as_array('get_user_cache_table_record', array('user_id'=>$userId, 'cache_table'=>'datatable__store_'.$store.'_data'));
					# now compute the store score if the user record exists for that store
					if(!empty($recordCheck)) $result = $this->_query_reader->run('compute_store_score', array_merge($recordCheck, $settings, $userData, array('store_id'=>$store)));
				}
				else log_message('debug', '_score/generate_store_score:: [4] ERROR - table "datatable__store_'.$store.'_data" not found.');
			}
			if($result) $storeScores = $this->_query_reader->get_list('get_cached_store_scores', $settings);
		}
		else log_message('debug', '_score/generate_store_score:: [6] no unprocessed matched stores found');
		
		
		
		# 2 a) get the categories whose scores need to be computed
		$matchedSubCategories = $this->_query_reader->get_single_column_as_array('get_unprocessed_matched_sub_categories', 'sub_category_id', array('user_id'=>$userId));
		$settings = array_merge($userData, $settings);
		
		# 2 b) compute the scores for the matched categories
		if(!empty($matchedSubCategories)) {
			log_message('debug', "_score/generate_store_score:: [7] matched categories: '".implode("','",$matchedSubCategories)."'");
			foreach($matchedSubCategories AS $subCategory){
				# check if the category table exists
				$tableCheck = $this->_query_reader->get_row_as_array('check_if_table_exists', array('table_name'=>'datatable__subcategory_'.$subCategory.'_data', 'database'=>'clout_v1_3cron'));
				# since the table exists, check if the user record exists
				if(!empty($tableCheck)) {
					$recordCheck = $this->_query_reader->get_row_as_array('get_user_cache_table_record', array('user_id'=>$userId, 'cache_table'=>'datatable__subcategory_'.$subCategory.'_data'));
					# now compute the store score if the user record exists for that store
					if(!empty($recordCheck)) $result = $this->_query_reader->run('compute_category_score', array_merge($recordCheck, $settings, array('sub_category_id'=>$subCategory)));
				}
				else log_message('debug', '_score/generate_store_score:: [8] ERROR - table "datatable__subcategory_'.$subCategory.'_data" not found.');
			}
			if($result) $subCategoryScores = $this->_query_reader->get_list('get_cached_category_scores', array('user_id'=>$userId));
		}
		else log_message('debug', '_score/generate_store_score:: [10] no unprocessed matched stores found');
		
		# 3 compute the default store score for the user
		$result = $this->_query_reader->run('compute_default_store_score', $settings);
		if($result) $defaultStoreScore = $this->_query_reader->get_row_as_array('get_cached_default_store_score', array('user_id'=>$userId));
		
		log_message('debug', '_score/generate_store_score:: [11] store-score array: '.(!empty($storeScores)? json_encode($storeScores): ''));
		log_message('debug', '_score/generate_store_score:: [12] category-score array: '.(!empty($subCategoryScores)? json_encode($subCategoryScores): ''));
		log_message('debug', '_score/generate_store_score:: [13] default-store-score array: '.(!empty($defaultStoreScore)? json_encode($defaultStoreScore): ''));
		
		# mark the categories that have been processed
		if($result && !empty($matchedStores)) $result = $this->_query_reader->run('mark_stores_as_processed', array('user_id'=>$userId, 'store_ids'=>implode("','",$matchedStores) ));
		log_message('debug', '_score/generate_store_score:: [14] mark_stores_as_processed result: '.($result? 'SUCCESS': 'FAIL'));
		if($result && !empty($matchedSubCategories)) $result = $this->_query_reader->run('mark_sub_categories_as_processed', array('user_id'=>$userId, 'sub_category_ids'=>implode("','",$matchedSubCategories) ));
		log_message('debug', '_score/generate_store_score:: [15] mark_sub_categories_as_processed result: '.($result? 'SUCCESS': 'FAIL'));
		
		return array('result'=>(!empty($storeScores)? 'SUCCESS': 'FAIL'), 
					'store_ids'=>(!empty($storeScores)? get_column_from_multi_array($storeScores, 'store_id'): array()),
					'sub_category_ids'=>(!empty($subCategoryScores)? get_column_from_multi_array($subCategoryScores, 'sub_category_id'): array()),
					'default_store_score'=>(!empty($defaultStoreScore)? $defaultStoreScore['total_score']: 0)
				);
	}
	
	
	
	
	
	
	
	
	
	# get score settings
	function get_score_settings($userId, $type)
	{
		log_message('debug', '_score/get_score_settings:: [1] ');
		# get the total number of users 
		$systemStats = $this->_query_reader->get_row_as_array('get_system_stats', array('statistic_list'=>'number_of_users'));
		$scoreSettings = $this->_query_reader->get_list('get_scoring_criteria', array('category'=>$type));
		
		log_message('debug', '_score/get_score_settings:: [2] scoreSettings: '.json_encode($scoreSettings));
		
		# put the settings in a usable format for the computing query
		$settings = array('user_id'=>$userId);
		$settings['number_of_users'] = !empty($systemStats['code_value'])? $systemStats['code_value']: 0;
		foreach($scoreSettings AS $row) {
			if(!empty($row['code'])){
				$settings[$row['code'].'_high'] = $row['high_range'];
				$settings[$row['code'].'_low'] = $row['low_range'];
				# get the value before the cireteria string as well if data-point increments per criteria
				if(strpos($row['criteria'], '_per_') !== FALSE) $settings[$row['code'].'_per'] = strtok($row['criteria'], '_per_');
			}
		}
		
		log_message('debug', '_score/get_score_settings:: [3] settings: '.json_encode($settings));
		return $settings;
	}
	
	
	
	
	
	
	
	
	# recompute account balance averages
	function recompute_account_balance_averages($userId, $dataPoints=array('average_cash_balance_last24months','average_credit_balance_last24months'))
	{
		log_message('debug', '_score/recompute_account_balance_averages:: [1] ');
		
		# recompute balance averages for each item for the given user and also update their frequency table value
		if(in_array('average_cash_balance_last24months', $dataPoints)){
			$balances = $this->_query_reader->get_list('get_account_balances', array('account_type'=>'cash', 'user_id'=>$userId,'months_back'=>'24'));
			$data = $this->compute_average_and_oldest($balances, 'cash_amount', 'read_date');

			log_message('debug', '_score/recompute_account_balance_averages:: [2] raw compute average_cash_balance_last24months: '.json_encode($data));
			if(isset($data['average'])){
				$dataPointDetails['average_cash_balance_last24months']['data_value'] = $data['average'];
				$dataPointDetails['average_cash_balance_last24months']['is_ranked'] = 'Y';
				$dataPointDetails['average_cash_balance_last24months']['new_checkby_date'] = get_checkby_date($data['last_date'],'-24 months');
				$results[] = $this->update_user_data_cache($userId, $dataPointDetails);
			}
		}
		
		if(in_array('average_credit_balance_last24months', $dataPoints)){
			$balances = $this->_query_reader->get_list('get_account_balances', array('account_type'=>'credit', 'user_id'=>$userId,'months_back'=>'24'));
			$data = $this->compute_average_and_oldest($balances, 'credit_amount', 'read_date');
			
			log_message('debug', '_score/recompute_account_balance_averages:: [3] raw compute average_credit_balance_last24months: '.json_encode($data));
			if(isset($data['average'])){
				$dataPointDetails['average_credit_balance_last24months']['data_value'] = $data['average'];
				$dataPointDetails['average_credit_balance_last24months']['is_ranked'] = 'Y';
				$dataPointDetails['average_credit_balance_last24months']['new_checkby_date'] = get_checkby_date($data['last_date'],'-24 months');
				$results[] = $this->update_user_data_cache($userId, $dataPointDetails);
			}
		}
		
		log_message('debug', '_score/recompute_account_balance_averages:: [4] compute results: '.(!empty($results)? json_encode($results): ''));
		return (!empty($results)? get_decision($results): FALSE);
	}
	
	
	
	
	# compute average of the given list
	function compute_average_and_oldest($list, $valueField, $dateField)
	{
		log_message('debug', '_score/compute_average_and_oldest:: [1] ');
		$total = $counter = 0;
		$lastDate = '';
		$firstRow = current($list);
		if(!empty($firstRow[$valueField]) && !empty($firstRow[$dateField])){
			foreach($list AS $row){
				$total += $row[$valueField];
				$lastDate = $row[$dateField];
				$counter++;
			}
		}
		
		log_message('debug', '_score/compute_average_and_oldest:: [2] counter: '.$counter);
		
		if($counter > 0) $response['average'] = round($total/$counter);
		$response['last_date'] = $lastDate;
		
		return $response;
	}
	
	
	
	
	
	
	
	# update store survey statistics
	# updates did_my_category_survey_last90days, did_related_categories_survey_last90days
	function update_store_survey_statistics($userId, $storeId)
	{
		log_message('debug', '_score/update_store_survey_statistics:: [1] ');
		
		# 1. check if store cache and checkby tables are available, if not, create them
		$results[] = $this->_query_reader->run('add_table_instance_if_missing', array('new_table_name'=>'datatable__store_'.$storeId.'_data', 'copy_table_name'=>'datatable__store_CACHE_data'));
		$results[] = $this->_query_reader->run('add_table_instance_if_missing', array('new_table_name'=>'datatable__store_'.$storeId.'_data__age', 'copy_table_name'=>'datatable__store_CACHE_data__age'));
		log_message('debug', '_score/update_store_survey_statistics:: [3] all cache tables created if missing: '.json_encode($results));
		
		# 2. update the store cache value and related categories value
		$results[] = $this->_query_reader->run('set_did_category_survey', array('store_id'=>$storeId, 'user_id'=>$userId));
		
		# 3. update this stores checkby dates. reset to N a day earlier to avoid same-day conflicts.
		if(get_decision($results)){
			$dataPoints = array();
			$dataPoints['did_my_category_survey_last90days']['data_value'] ='';
			$dataPoints['did_my_category_survey_last90days']['new_checkby_date'] = get_checkby_date(date('Y-m-d H:i:s', strtotime('-1 days')),'-90 days');
			$results[] = $this->update_store_data_cache($userId, $storeId, $dataPoints);
			
			$dataPoints = array();
			$dataPoints['did_related_categories_survey_last90days']['data_value'] = '';
			$dataPoints['did_related_categories_survey_last90days']['new_checkby_date'] = get_checkby_date(date('Y-m-d H:i:s', strtotime('-1 days')),'-90 days');
			$results[] = $this->update_store_data_cache($userId, $storeId, $dataPoints);

			$dataPoints = array();
			$dataPoints['number_of_surveys_answered_in_last90days']['data_value'] = '';
			$dataPoints['number_of_surveys_answered_in_last90days']['new_checkby_date'] = get_checkby_date(date('Y-m-d H:i:s', strtotime('-1 days')),'-90 days');
			$results[] = $this->update_user_data_cache($userId, $dataPoints);

			$dataPoints = array();
			$dataPoints['has_answered_survey_in_last90days']['data_value'] = '';
			$dataPoints['has_answered_survey_in_last90days']['new_checkby_date'] = get_checkby_date(date('Y-m-d H:i:s', strtotime('-1 days')),'-90 days');
			$results[] = $this->update_user_data_cache($userId, $dataPoints);
			
			$result = get_decision($results);
			log_message('debug', '_score/update_store_survey_statistics:: [3] checkby date updated: '.get_decision($results));
		}
		
		
		
		# 4. update the related stores the user shopped at with related categories
		if(!empty($result) && $result) $relatedStores = $this->get_related_stores($userId, $storeId);
		
		if(!empty($relatedStores)) {
			$results = array();
			foreach($relatedStores AS $rStore) {
				# only work with stores that already have existing cache tables
				if($this->_query_reader->get_count('check_if_table_exists',array('database'=>'clout_v1_3cron', 'table_name'=>'datatable__store_'.$rStore.'_data')) > 0){
					$results[] = $this->_query_reader->run('set_datatable_value',array(
							'user_id'=>$userId,
							'table_name'=>'datatable__store_'.$rStore.'_data',
							'data_point'=>'did_related_categories_survey_last90days',
							'data_value'=>'Y'
					));
				}
				if($this->_query_reader->get_count('check_if_table_exists',array('database'=>'clout_v1_3cron', 'table_name'=>'datatable__store_'.$rStore.'_data__age')) > 0){
					$dataPoints = array();
					$dataPoints['did_related_categories_survey_last90days']['data_value'] = '';
					$dataPoints['did_related_categories_survey_last90days']['new_checkby_date'] = get_checkby_date(date('Y-m-d H:i:s', strtotime('-1 days')),'-90 days');
					
					$results[] = $this->update_store_data_cache($userId, $rStore, $dataPoints);
				}
			}
			
			log_message('debug', '_score/update_store_survey_statistics:: [4] related stores results: '.json_encode($results));
			$result = get_decision($results, TRUE);
		}
		
		
		log_message('debug', '_score/update_store_survey_statistics:: [5] final results: '.((!empty($result) && $result)? 'SUCCESS': 'FAIL'));
		# send back final result to the user 
		return (!empty($result) && $result);
	}
	
	
	
	
	# get related stores
	function get_related_stores($userId, $storeId)
	{
		log_message('debug', '_score/get_related_stores:: [1] ');
		$shoppedStores = $this->_query_reader->get_single_column_as_array('get_cached_stores_shopped', 'store_id',array('user_id'=>$userId));
		
		log_message('debug', '_score/get_related_stores:: [2] shopped stores: '.json_encode($shoppedStores));
		# if we have stores where the user shopped proceed
		if(!empty($shoppedStores)){
			$thisStore = $this->_query_reader->get_row_as_array('mongodb__get_store_by_id',array('store_id'=>$storeId));
			if(!empty($thisStore['categories'])){
				$storeCategories = (!is_array($thisStore['categories'])? explode(',',$thisStore['categories']): $thisStore['categories']);
				$relatedStores = $this->_query_reader->get_single_column_as_array('mongodb__get_stores_with_categories','store_id',array(
						'store_ids'=>implode("','",$shoppedStores),
						'category_ids'=>implode("','",$storeCategories)
				));
				
				log_message('debug', '_score/get_related_stores:: [3] related stores: '.json_encode($relatedStores));
			}
		}
		
		# the related stores
		return (!empty($relatedStores)? $relatedStores: array());
	}
	
	
	
	
	
	# update transaction statistics
	function update_transaction_statistics($userId)
	{
		log_message('debug', '_score/update_transaction_statistics:: [1] ');
		# 1. Pre-load:
		# ---------------------------------------------------------
		# a) new transactions
		$newTransactions =$this->_query_reader->get_list('get_unprocessed_user_transactions', array('user_id'=>$userId));
		if(empty($newTransactions)) return FALSE;
		
		# parameters directly extracted from the new transactions
		$newTransactionStats = $this->get_statistics_from_transactions($newTransactions);
		# b) competitors to all store ids the user has shopped at from store_competitors table
		$newTransactionStats['competitors'] = $this->_query_reader->get_list('get_stores_and_their_competitors', array('store_ids'=>implode("','",$newTransactionStats['store_ids']) ));
		# c) the user network and all referrers whose network includes this user 
		$newTransactionStats['network'] = $this->_query_reader->get_list('get_user_network', array('user_id'=>$userId));
		log_message('debug', '_score/update_transaction_statistics:: [4] newTransactionStats: '.json_encode($newTransactionStats));
		# d) cached user record
		$userCache = $this->_query_reader->get_row_as_array('get_user_data_record', array('user_id'=>$userId ));
		# e) cached store records
		$storeCache = array();
		foreach($newTransactionStats['store_ids'] AS $newStoreId){
			if($this->_query_reader->get_count('check_if_table_exists',array('database'=>'clout_v1_3cron', 'table_name'=>'datatable__store_'.$newStoreId.'_data')) > 0) {
				$storeCache[$newStoreId] = $this->_query_reader->get_row_as_array('get_user_store_data_record', array('user_id'=>$userId, 'store_id'=>$newStoreId));
			}
		}
		# f) cached subcategory records
		$subCategoryCache = array();
		foreach($newTransactionStats['sub_category_ids'] AS $newSubCategoryId){
			if($this->_query_reader->get_count('check_if_table_exists',array('database'=>'clout_v1_3cron', 'table_name'=>'datatable__subcategory_'.$newSubCategoryId.'_data')) > 0) {
				$subCategoryCache[$newSubCategoryId] = $this->_query_reader->get_row_as_array('get_user_sub_category_data_record', array('user_id'=>$userId, 'sub_category_id'=>$newSubCategoryId));
			}
		}
		# ---------------------------------------------------------
		log_message('debug', '_score/update_transaction_statistics:: [4] newTransactionStats: '.json_encode($newTransactionStats));
		
		# update the user data parameters
		$results[] = $this->update_user_data_params($userId, $userCache, $newTransactionStats);
		# update the store data parameters
		$results[] = $this->update_store_data_params($userId, $storeCache, $newTransactionStats);
		# update the sub-category data parameters
		$results[] = $this->update_sub_category_data_params($userId, $subCategoryCache, $newTransactionStats);
		
		log_message('debug', '_score/update_transaction_statistics:: [5] computation results: '.json_encode($results));
		return get_decision($results);
	}
	
	
	
	
	# compute any direct statistics from the transaction records
	function get_statistics_from_transactions($list)
	{
		log_message('debug', '_score/get_statistics_from_transactions:: [1]');
		# initial parameters
		$last180days = $last360days = $total = 0;
		$storeIds = $transactions = $storeSpending = $categorySpending = $subCategorySpending = array();
		$LastDayForlast180days = $LastDayForlast360days = '';
		# get transaction ids
		$transactionIds = get_column_from_multi_array($list, 'transaction_id');
		
		# get all transaction sub-categories (and hence categories) from transaction_sub_categories table
		$categoryRecords = $this->_query_reader->get_list('get_user_transaction_categories', array('transaction_ids'=>implode("','", $transactionIds) ));
		$transactionsWithCategories = get_column_from_multi_array($categoryRecords, 'transaction_id');
		
		foreach($list AS $row){
			# get totals
			if(strtotime($row['date_entered']) >= strtotime('-180 days')) {
				$last180days += $row['amount'];
				$LastDayForlast180days = $row['date_entered'];
			}
			if(strtotime($row['date_entered']) >= strtotime('-360 days')) {
				$last360days += $row['amount'];
				$LastDayForlast360days = $row['date_entered'];
			}
			$total += $row['amount'];
			
			# get store ids and breakdown the transaction totals by stores
			if(!empty($row['store_id'])){
				if(!in_array($row['store_id'], $storeIds)) array_push($storeIds,$row['store_id']);
				
				# spending broken down by store
				if(strtotime($row['date_entered']) >= strtotime('-90 days')) {
					if(empty($storeSpending[$row['store_id']]['last90days'])) $storeSpending[$row['store_id']]['last90days'] = 0;
					$storeSpending[$row['store_id']]['last90days'] += $row['amount'];
					$storeSpending[$row['store_id']]['last90days_last_date'] = $row['date_entered'];
				}
				if(strtotime($row['date_entered']) >= strtotime('-12 months')) {
					if(empty($storeSpending[$row['store_id']]['last12months'])) $storeSpending[$row['store_id']]['last12months'] = 0;
					$storeSpending[$row['store_id']]['last12months'] += $row['amount'];
					$storeSpending[$row['store_id']]['last12months_last_date'] = $row['date_entered'];
				}
				if(empty($storeSpending[$row['store_id']]['lifetime'])) $storeSpending[$row['store_id']]['lifetime'] = 0;
				$storeSpending[$row['store_id']]['lifetime'] += $row['amount'];
			}

			# breakdown the transaction totals by categories and sub-categories
			if(!empty($transactionsWithCategories) && in_array($row['transaction_id'], $transactionsWithCategories)){
				
				foreach($categoryRecords AS $catRow){
					if($row['transaction_id'] == $catRow['transaction_id']){
						if(strtotime($row['date_entered']) >= strtotime('-90 days')) {
							if(empty($categorySpending[$catRow['category']]['last90days'])) $categorySpending[$catRow['category']]['last90days'] = 0;
							$categorySpending[$catRow['category']]['last90days'] += $row['amount'];
							$categorySpending[$catRow['category']]['last90days_last_date'] = $row['date_entered'];
							
							if(empty($subCategorySpending[$catRow['sub_category']]['last90days'])) $subCategorySpending[$catRow['sub_category']]['last90days'] = 0;
							$subCategorySpending[$catRow['sub_category']]['last90days'] += $row['amount'];
							$subCategorySpending[$catRow['sub_category']]['last90days_last_date'] = $row['date_entered'];
						}
						
						if(strtotime($row['date_entered']) >= strtotime('-12 months')) {
							if(empty($categorySpending[$catRow['category']]['last12months'])) $categorySpending[$catRow['category']]['last12months'] = 0;
							$categorySpending[$catRow['category']]['last12months'] += $row['amount'];
							$categorySpending[$catRow['category']]['last12months_last_date'] = $row['date_entered'];
							
							if(empty($subCategorySpending[$catRow['sub_category']]['last12months'])) $subCategorySpending[$catRow['sub_category']]['last12months'] = 0;
							$subCategorySpending[$catRow['sub_category']]['last12months'] += $row['amount'];
							$subCategorySpending[$catRow['sub_category']]['last12months_last_date'] = $row['date_entered'];
						}
						
						if(empty($categorySpending[$catRow['category']]['lifetime'])) $categorySpending[$catRow['category']]['lifetime'] = 0;
						$categorySpending[$catRow['category']]['lifetime'] += $row['amount'];
						if(empty($subCategorySpending[$catRow['sub_category']]['lifetime'])) $subCategorySpending[$catRow['sub_category']]['lifetime'] = 0;
						$subCategorySpending[$catRow['sub_category']]['lifetime'] += $row['amount'];
					}
				}
			}
			
		}
		
		return array('spending_last180days'=>$last180days, 'spending_last360days'=>$last360days, 'spending_total'=>$total, 'store_ids'=>$storeIds, 'ids'=>$transactionIds, 
				'spending_last180days_last_date'=>$LastDayForlast180days, 'spending_last360days_last_date'=>$LastDayForlast360days, 'store_spending'=>$storeSpending, 
				'category_records'=>$categoryRecords, 'category_spending'=>$categorySpending, 'sub_category_spending'=>$subCategorySpending,
				'category_ids'=>array_keys($categorySpending), 'sub_category_ids'=>array_keys($subCategorySpending)
			);
	}
	
	
	
	
	
	# update the cached user data-points
	function update_user_data_params($userId, $userCache, $stats)
	{
		log_message('debug', '_score/update_user_data_params:: [1] ');
		
		$results = array();
		
		# this user's data-points to be updated
		$dataPoints['spending_total']['data_value'] = ($stats['spending_total'] + (!empty($userCache['spending_total'])? $userCache['spending_total']: 0));
		$dataPoints['spending_total']['is_ranked'] = 'Y';
		
		$dataPoints['spending_last360days']['data_value'] = ($stats['spending_last360days'] + (!empty($userCache['spending_last360days'])? $userCache['spending_last360days']: 0));
		$dataPoints['spending_last360days']['is_ranked'] = 'Y';
		$dataPoints['spending_last360days']['new_checkby_date'] = get_checkby_date($stats['spending_last360days_last_date'],'-360 days');
		
		$dataPoints['spending_last180days']['data_value'] = ($stats['spending_last180days'] + (!empty($userCache['spending_last180days'])? $userCache['spending_last180days']: 0));
		$dataPoints['spending_last180days']['is_ranked'] = 'Y';
		$dataPoints['spending_last180days']['new_checkby_date'] = get_checkby_date($stats['spending_last180days_last_date'],'-180 days');
		# now apply the changes for this user
		$results[] = $this->update_user_data_cache($userId, $dataPoints);
		
		# other user's data-points (where this user is in their network) that are to be updated
		foreach($stats['network'] AS $row){
			if($row['select_level'] != 'my_network'){
				# is it a direct or network record?
				$level = ($row['select_level'] == 'level_1'? 'direct': 'network');
				
				# generate the data-points
				$dataPoints['total_spending_of_'.$level.'_referrals']['data_value'] = ($stats['spending_total'] + (!empty($userCache['total_spending_of_'.$level.'_referrals'])? $userCache['total_spending_of_'.$level.'_referrals']: 0));
				$dataPoints['total_spending_of_'.$level.'_referrals']['is_ranked'] = 'Y';
				
				$dataPoints['spending_of_'.$level.'_referrals_last360days']['data_value'] = ($stats['spending_last360days'] + (!empty($userCache['spending_of_'.$level.'_referrals_last360days'])? $userCache['spending_of_'.$level.'_referrals_last360days']: 0));
				$dataPoints['spending_of_'.$level.'_referrals_last360days']['is_ranked'] = 'Y';
				# update checkby date in checking function
				
				$dataPoints['spending_of_'.$level.'_referrals_last180days']['data_value'] = ($stats['spending_last180days'] + (!empty($userCache['spending_of_'.$level.'_referrals_last180days'])? $userCache['spending_of_'.$level.'_referrals_last180days']: 0));
				$dataPoints['spending_of_'.$level.'_referrals_last180days']['is_ranked'] = 'Y';
				# update checkby date in checking function - as other users are also updating this data-point
				
				# apply the data-points
				$results[] = $this->update_user_data_cache($row['user_id'], $dataPoints);
			}
		}
		
		return get_decision($results);
	}
	
	
	
	
	# update the user data cache
	function update_user_data_cache($userId, $dataPoints)
	{
		log_message('debug', '_score/update_user_data_cache:: [1] ');
		$results = array();
		
		# update each data-point as provided in the array list
		foreach($dataPoints AS $key=>$values){
			$results[] = $this->_query_reader->run('call_update_user_cache_and_frequency',array(
					'user_id'=>$userId,
					'data_point'=>$key,
					'data_value'=>$values['data_value'],
					'new_checkby_date'=>(!empty($values['new_checkby_date'])? $values['new_checkby_date']: ''),
					'is_ranked'=>(!empty($values['is_ranked'])? $values['is_ranked']: 'N')
			));
		}
		
		return get_decision($results, TRUE);
	}
	
	
	





	# update the cached user store data-points
	function update_store_data_params($userId, $storeCache, $stats)
	{
		log_message('debug', '_score/update_store_data_params:: [1] ');
		log_message('debug', '_score/update_store_data_params:: [2] parameters: '.json_encode(array('user_id'=>$userId, 'store_cache'=>$storeCache, 'stats'=>$stats)));
		
		$results = array();
		
		# 1. FOR MATCHED STORES: update data-points in the new transactions
		foreach($stats['store_ids'] AS $storeId){
			$dataPoints = array();
			# if table does not exist, create it
			if($this->_query_reader->get_count('check_if_table_exists',array('database'=>'clout_v1_3cron', 'table_name'=>'datatable__store_'.$storeId.'_data')) == 0) {
				$results[] = $this->_query_reader->run('create_store_data_cache', array('store_id'=>$storeId));
			}
			
			# fix data gaps in the stats
			$stats['store_spending'][$storeId]['lifetime'] = (!empty($stats['store_spending'][$storeId]['lifetime'])? $stats['store_spending'][$storeId]['lifetime']: 0);
			$stats['store_spending'][$storeId]['last12months'] = (!empty($stats['store_spending'][$storeId]['last12months'])? $stats['store_spending'][$storeId]['last12months']: 0);
			$stats['store_spending'][$storeId]['last90days'] = (!empty($stats['store_spending'][$storeId]['last90days'])? $stats['store_spending'][$storeId]['last90days']: 0);
			
			$stats['store_spending'][$storeId]['last12months_last_date'] = !empty($stats['store_spending'][$storeId]['last12months_last_date'])? $stats['store_spending'][$storeId]['last12months_last_date']: '';
			$stats['store_spending'][$storeId]['last90days_last_date'] = !empty($stats['store_spending'][$storeId]['last90days_last_date'])? $stats['store_spending'][$storeId]['last90days_last_date']: '';
			
			# prepare the data-points for update
			# --------------------------------------------
			# a) for this store
			$dataPoints['my_store_spending_lifetime']['data_value'] = ($stats['store_spending'][$storeId]['lifetime'] + (!empty($storeCache[$storeId]['my_store_spending_lifetime'])? $storeCache[$storeId]['my_store_spending_lifetime']: 0));
			$dataPoints['my_store_spending_last12months']['data_value'] = ($stats['store_spending'][$storeId]['last12months'] + (!empty($storeCache[$storeId]['my_store_spending_last12months'])? $storeCache[$storeId]['my_store_spending_last12months']: 0));
			$dataPoints['my_store_spending_last90days']['data_value'] = ($stats['store_spending'][$storeId]['last90days'] + (!empty($storeCache[$storeId]['my_store_spending_last90days'])? $storeCache[$storeId]['my_store_spending_last90days']: 0));
			$dataPoints['my_chain_spending_lifetime']['data_value'] = ($stats['store_spending'][$storeId]['lifetime'] + (!empty($storeCache[$storeId]['my_chain_spending_lifetime'])? $storeCache[$storeId]['my_chain_spending_lifetime']: 0));
			$dataPoints['my_chain_spending_last12months']['data_value'] = ($stats['store_spending'][$storeId]['last12months'] + (!empty($storeCache[$storeId]['my_chain_spending_last12months'])? $storeCache[$storeId]['my_chain_spending_last12months']: 0));
			$dataPoints['my_chain_spending_last90days']['data_value'] = ($stats['store_spending'][$storeId]['last90days'] + (!empty($storeCache[$storeId]['my_chain_spending_last90days'])? $storeCache[$storeId]['my_chain_spending_last90days']: 0));
			
			$dataPoints['my_store_spending_lifetime']['is_ranked'] = 'Y';
			$dataPoints['my_store_spending_last12months']['is_ranked'] = 'Y';
			$dataPoints['my_store_spending_last90days']['is_ranked'] = 'Y';
			$dataPoints['my_chain_spending_lifetime']['is_ranked'] = 'Y';
			$dataPoints['my_chain_spending_last12months']['is_ranked'] = 'Y';
			$dataPoints['my_chain_spending_last90days']['is_ranked'] = 'Y';
			
			$dataPoints['my_store_spending_last12months']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last12months_last_date'],'-12 months');
			$dataPoints['my_store_spending_last90days']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last90days_last_date'],'-90 days');
			$dataPoints['my_chain_spending_last12months']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last12months_last_date'],'-12 months');
			$dataPoints['my_chain_spending_last90days']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last90days_last_date'],'-90 days');
			
			
			
			# b) my category and related category spending - for this store
			$dataPoints['my_category_spending_lifetime']['data_value'] = ($stats['store_spending'][$storeId]['lifetime'] + (!empty($storeCache[$storeId]['my_category_spending_lifetime'])? $storeCache[$storeId]['my_category_spending_lifetime']: 0));
			$dataPoints['my_category_spending_last12months']['data_value'] = ($stats['store_spending'][$storeId]['last12months'] + (!empty($storeCache[$storeId]['my_category_spending_last12months'])? $storeCache[$storeId]['my_category_spending_last12months']: 0));
			$dataPoints['my_category_spending_last90days']['data_value'] = ($stats['store_spending'][$storeId]['last90days'] + (!empty($storeCache[$storeId]['my_category_spending_last90days'])? $storeCache[$storeId]['my_category_spending_last90days']: 0));
			$dataPoints['related_categories_spending_lifetime']['data_value'] = ($stats['store_spending'][$storeId]['lifetime'] + (!empty($storeCache[$storeId]['related_categories_spending_lifetime'])? $storeCache[$storeId]['related_categories_spending_lifetime']: 0));
			$dataPoints['related_categories_spending_last12months']['data_value'] = ($stats['store_spending'][$storeId]['last12months'] + (!empty($storeCache[$storeId]['related_categories_spending_last12months'])? $storeCache[$storeId]['related_categories_spending_last12months']: 0));
			$dataPoints['related_categories_spending_last90days']['data_value'] = ($stats['store_spending'][$storeId]['last90days'] + (!empty($storeCache[$storeId]['related_categories_spending_last90days'])? $storeCache[$storeId]['related_categories_spending_last90days']: 0));
			
			$dataPoints['my_category_spending_lifetime']['is_ranked'] = 'Y';
			$dataPoints['my_category_spending_last12months']['is_ranked'] = 'Y';
			$dataPoints['my_category_spending_last90days']['is_ranked'] = 'Y';
			$dataPoints['related_categories_spending_lifetime']['is_ranked'] = 'Y';
			$dataPoints['related_categories_spending_last12months']['is_ranked'] = 'Y';
			$dataPoints['related_categories_spending_last90days']['is_ranked'] = 'Y';
			
			$dataPoints['my_category_spending_last12months']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last12months_last_date'],'-12 months');
			$dataPoints['my_category_spending_last90days']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last90days_last_date'],'-90 days');
			$dataPoints['related_categories_spending_last12months']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last12months_last_date'],'-12 months');
			$dataPoints['related_categories_spending_last90days']['new_checkby_date'] = get_checkby_date($stats['store_spending'][$storeId]['last90days_last_date'],'-90 days');
				
			
			# now apply the changes for this store
			$results[] = $this->update_store_data_cache($userId, $storeId, $dataPoints);
		}
		
		# 2. FOR COMPETITORS
		$competitorsById = bundle_by_column_multi_array($stats['competitors'], 'store_id', 'competitor_id');
		log_message('debug', '_score/update_store_data_params:: [3] competitorsById: '.json_encode($competitorsById));
		
		foreach($competitorsById AS $storeId=>$competitors){
			$dataPoints = array();
			# fix data gaps in the stats
			$stats['store_spending'][$storeId]['lifetime'] = (!empty($stats['store_spending'][$storeId]['lifetime'])? $stats['store_spending'][$storeId]['lifetime']: 0);
			$stats['store_spending'][$storeId]['last12months'] = (!empty($stats['store_spending'][$storeId]['last12months'])? $stats['store_spending'][$storeId]['last12months']: 0);
			$stats['store_spending'][$storeId]['last90days'] = (!empty($stats['store_spending'][$storeId]['last90days'])? $stats['store_spending'][$storeId]['last90days']: 0);
			
			# update data points for competitors	
			foreach($competitors AS $competitor){
				if($this->_query_reader->get_count('check_if_table_exists',array('database'=>'clout_v1_3cron', 'table_name'=>'datatable__store_'.$competitor.'_data')) > 0) {
					$storeCache[$competitor] = $this->_query_reader->get_row_as_array('get_user_store_data_record', array('user_id'=>$userId, 'store_id'=>$competitor));
					
					# only update for those with tables
					$dataPoints['my_direct_competitors_spending_lifetime']['data_value'] = ($stats['store_spending'][$storeId]['lifetime'] + (!empty($storeCache[$competitor]['my_direct_competitors_spending_lifetime'])? $storeCache[$competitor]['my_direct_competitors_spending_lifetime']: 0));
					$dataPoints['my_direct_competitors_spending_last12months']['data_value'] = ($stats['store_spending'][$storeId]['last12months'] + (!empty($storeCache[$competitor]['my_direct_competitors_spending_last12months'])? $storeCache[$competitor]['my_direct_competitors_spending_last12months']: 0));
					$dataPoints['my_direct_competitors_spending_last90days']['data_value'] = ($stats['store_spending'][$storeId]['last90days'] + (!empty($storeCache[$competitor]['my_direct_competitors_spending_last90days'])? $storeCache[$competitor]['my_direct_competitors_spending_last90days']: 0));
					
					$results[] = $this->update_store_data_cache($userId, $competitor, $dataPoints);
				}
			}
		}
		
		
		# 3. FOR OTHER STORES: my category and related category spending
		# TODO: update category spending for other active stores in the specified categories
		# CHECK: Is this necessary with category data also captured separately with the same data-points?
		/*
		datatable__store_[store-id]_data
		-------------------------------------------
		my_category_spending_last90days
		my_category_spending_last12months
		my_category_spending_lifetime
		related_categories_spending_last90days
		related_categories_spending_last12months
		related_categories_spending_lifetime
		*/
		
		log_message('debug', '_score/update_store_data_params:: [4] results: '.json_encode($results));
		return get_decision($results);
	}
	
	
	
	
	
	
	
	

	# update the store data cache
	function update_store_data_cache($userId, $storeId, $dataPoints)
	{
		log_message('debug', '_score/update_store_data_cache:: [1] ');
		log_message('debug', '_score/update_store_data_cache:: [2] parameters: '.json_encode(array('user_id'=>$userId, 'store_id'=>$storeId, 'data_points'=>$dataPoints)));
		
		$results = array();
		# update each data-point as provided in the array list
		foreach($dataPoints AS $key=>$values){
			$results[] = $this->_query_reader->run('call_update_store_cache_and_frequency',array(
					'user_id'=>$userId,
					'store_id'=>$storeId,
					'data_point'=>$key,
					'data_value'=>$values['data_value'],
					'new_checkby_date'=>(!empty($values['new_checkby_date'])? $values['new_checkby_date']: ''),
					'is_ranked'=>(!empty($values['is_ranked'])? $values['is_ranked']: 'N')
			));
		}
		
		log_message('debug', '_score/update_store_data_cache:: [3] results: '.json_encode($results));
		return get_decision($results, TRUE);
	}








	# update the cached user sub-category data-points
	function update_sub_category_data_params($userId, $subCategoryCache, $stats)
	{
		log_message('debug', '_score/update_sub_category_data_params:: [1] ');
		log_message('debug', '_score/update_sub_category_data_params:: [2] parameters: '.json_encode(array('user_id'=>$userId, 'sub_category_cache'=>$subCategoryCache, 'stats'=>$stats)));
		
		$results = array();
		
		# 1. FOR CATEGORIES IDENTIFIED IN TRANSACTIONS: update the respective data-points
		$categoryBreakdown = bundle_by_column_multi_array($stats['category_records'], 'category_id', 'sub_category_id');
		log_message('debug', '_score/update_sub_category_data_params:: [3] categoryBreakdown: '.json_encode($categoryBreakdown));
		
		foreach($stats['sub_category_ids'] AS $subCatId){
			# fix data gaps in the stats
			$stats['sub_category_spending'][$subCatId]['lifetime'] = (!empty($stats['sub_category_spending'][$subCatId]['lifetime'])? $stats['sub_category_spending'][$subCatId]['lifetime']: 0);
			$stats['sub_category_spending'][$subCatId]['last12months'] = (!empty($stats['sub_category_spending'][$subCatId]['last12months'])? $stats['sub_category_spending'][$subCatId]['last12months']: 0);
			$stats['sub_category_spending'][$subCatId]['last90days'] = (!empty($stats['sub_category_spending'][$subCatId]['last90days'])? $stats['sub_category_spending'][$subCatId]['last90days']: 0);
			
			$stats['sub_category_spending'][$subCatId]['last12months_last_date'] = !empty($stats['sub_category_spending'][$subCatId]['last12months_last_date'])? $stats['sub_category_spending'][$subCatId]['last12months_last_date']: '';
			$stats['sub_category_spending'][$subCatId]['last90days_last_date'] = !empty($stats['sub_category_spending'][$subCatId]['last90days_last_date'])? $stats['sub_category_spending'][$subCatId]['last90days_last_date']: '';
			
			# then update the spending for this sub-category
			$dataPoints['my_category_spending_lifetime']['data_value'] = ($stats['sub_category_spending'][$subCatId]['lifetime'] + (!empty($subCategoryCache[$subCatId]['my_category_spending_lifetime'])? $subCategoryCache[$subCatId]['my_category_spending_lifetime']: 0));
			$dataPoints['my_category_spending_last12months']['data_value'] = ($stats['sub_category_spending'][$subCatId]['last12months'] + (!empty($subCategoryCache[$subCatId]['my_category_spending_last12months'])? $subCategoryCache[$subCatId]['my_category_spending_last12months']: 0));
			$dataPoints['my_category_spending_last90days']['data_value'] = ($stats['sub_category_spending'][$subCatId]['last90days'] + (!empty($subCategoryCache[$subCatId]['my_category_spending_last90days'])? $subCategoryCache[$subCatId]['my_category_spending_last90days']: 0));
			
			$dataPoints['my_category_spending_lifetime']['is_ranked'] = 'Y';
			$dataPoints['my_category_spending_last12months']['is_ranked'] = 'Y';
			$dataPoints['my_category_spending_last90days']['is_ranked'] = 'Y';
			
			$dataPoints['my_category_spending_last12months']['new_checkby_date'] = get_checkby_date($stats['sub_category_spending'][$subCatId]['last12months_last_date'],'-12 months');
			$dataPoints['my_category_spending_last90days']['new_checkby_date'] = get_checkby_date($stats['sub_category_spending'][$subCatId]['last90days_last_date'],'-90 days');
			
			foreach($categoryBreakdown AS $categoryId=>$subCategories){
				if(in_array($subCatId, $subCategories)){
					$dataPoints['related_categories_spending_lifetime']['data_value'] = ($stats['category_spending'][$categoryId]['lifetime'] + (!empty($subCategoryCache[$subCatId]['related_categories_spending_lifetime'])? $subCategoryCache[$subCatId]['related_categories_spending_lifetime']: 0));
					$dataPoints['related_categories_spending_last12months']['data_value'] = ($stats['category_spending'][$categoryId]['last12months'] + (!empty($subCategoryCache[$subCatId]['related_categories_spending_last12months'])? $subCategoryCache[$subCatId]['related_categories_spending_last12months']: 0));
					$dataPoints['related_categories_spending_last90days']['data_value'] = ($stats['category_spending'][$categoryId]['last90days'] + (!empty($subCategoryCache[$subCatId]['related_categories_spending_last90days'])? $subCategoryCache[$subCatId]['related_categories_spending_last90days']: 0));
				}
				
				$dataPoints['related_categories_spending_last12months']['is_ranked'] = 'Y';
				$dataPoints['related_categories_spending_last90days']['is_ranked'] = 'Y';
				
				$dataPoints['related_categories_spending_last12months']['new_checkby_date'] = get_checkby_date((!empty($stats['category_spending'][$categoryId]['last12months_last_date'])? $stats['category_spending'][$categoryId]['last12months_last_date']: 0),'-12 months');
				$dataPoints['related_categories_spending_last90days']['new_checkby_date'] = get_checkby_date((!empty($stats['category_spending'][$categoryId]['last90days_last_date'])? $stats['category_spending'][$categoryId]['last90days_last_date']: 0),'-90 days');
			}
			
			$results[] = $this->update_sub_category_data_cache($userId, $subCatId, $dataPoints);
		}
		
		log_message('debug', '_score/update_sub_category_data_params:: [4] results: '.json_encode($results));
		return get_decision($results);
	}


	
	
	
	# update the sub-category data cache
	function update_sub_category_data_cache($userId, $subCategoryId, $dataPoints)
	{
		log_message('debug', '_score/update_sub_category_data_cache:: [1] ');
		log_message('debug', '_score/update_sub_category_data_cache:: [2] parameters: '.json_encode(array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId, 'data_points'=>$dataPoints)));
		
		$results = array();
		
		# update each data-point as provided in the array list
		foreach($dataPoints AS $key=>$values){
			$results[] = $this->_query_reader->run('call_update_sub_category_cache_and_frequency',array(
					'user_id'=>$userId,
					'sub_category_id'=>$subCategoryId,
					'data_point'=>$key,
					'data_value'=>$values['data_value'],
					'new_checkby_date'=>(!empty($values['new_checkby_date'])? $values['new_checkby_date']: ''),
					'is_ranked'=>(!empty($values['is_ranked'])? $values['is_ranked']: 'N')
			));
		}
		
		log_message('debug', '_score/update_sub_category_data_cache:: [3] results: '.json_encode($results));
		return get_decision($results, TRUE);
	}
	
	
	
	
	
	
	
	# recompute old statistics - aged beyond expected 
	function recompute_old_statistics($userId)
	{
		log_message('debug', '_score/recompute_old_statistics:: [1] ');
		$ageStatsTables = $this->_query_reader->get_single_column_as_array('get_data_point_age_tables', 'table_name', array('database'=>'clout_v1_3cron'));
		$rawStatistics = $fields = $results = array();
		
		log_message('debug', '_score/recompute_old_statistics:: [2] ageStatsTables: '.json_encode($ageStatsTables));
		# 1. get statistics to recompute - for the user
		foreach($ageStatsTables AS $table){
			# the main user record
			if($table == 'datatable__user_data__age'){
				$rawStatistics['user'] = $this->_query_reader->get_row_as_array('get_user_age_point_dates', array('user_id'=>$userId));
				$fields['user'] = array();
				# keep track of all fields to be computed today
				foreach($rawStatistics['user'] AS $fieldKey=>$fieldCheckDate){
					if(strpos($fieldCheckDate, ' ') !== FALSE && strtotime(current(explode(' ',$fieldCheckDate))) <= strtotime(date('Y-m-d'))) array_push($fields['user'], $fieldKey);
				}
			}
			# store cache tables
			else if(strpos($table, 'datatable__store_') !== FALSE) {
				$storeId = current(explode('_', str_replace('datatable__store_','',$table)));
				$rawStatistics['store'][$storeId] = $this->_query_reader->get_row_as_array('get_store_age_point_dates', array('user_id'=>$userId, 'store_id'=>$storeId));
				
				# keep track of all fields to be computed today
				if(!empty($rawStatistics['store'][$storeId])){
					$fields['store'][$storeId] = array();
					foreach($rawStatistics['store'][$storeId] AS $fieldKey=>$fieldCheckDate){
						if(strpos($fieldCheckDate, ' ') !== FALSE && strtotime(current(explode(' ',$fieldCheckDate))) <= strtotime(date('Y-m-d'))) array_push($fields['store'][$storeId], $fieldKey);
					}
				}
			} 
			# sub-category cache tables
			else if(strpos($table, 'datatable__subcategory_') !== FALSE) {
				$subCategoryId = current(explode('_', str_replace('datatable__subcategory_','',$table)));
				$rawStatistics['subcategory'][$subCategoryId] = $this->_query_reader->get_row_as_array('get_subcategory_age_point_dates', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
				
				# keep track of all fields to be computed today
				if(!empty($rawStatistics['subcategory'][$subCategoryId])){
					$fields['subcategory'][$subCategoryId] = array();
					foreach($rawStatistics['subcategory'][$subCategoryId] AS $fieldKey=>$fieldCheckDate){
						if(strpos($fieldCheckDate, ' ') !== FALSE && strtotime(current(explode(' ',$fieldCheckDate))) <= strtotime(date('Y-m-d'))) array_push($fields['subcategory'][$subCategoryId], $fieldKey);
					}
				}
			}
		}
		
		log_message('debug', '_score/recompute_old_statistics:: [3] fields: '.json_encode($fields));
		
		# 2. recompute those values that need to be refreshed today
		if(!empty($fields['user'])) {
			$results[] = $this->recompute_user_data_points($userId, $fields['user']);
		}
		if(!empty($fields['store'])) {
			foreach($fields['store'] AS $storeId=>$storeFields) {
				if(!empty($storeFields)) $results[] = $this->recompute_store_data_points($userId, $storeId, $storeFields);
			}
		}
		if(!empty($fields['subcategory'])) {
			foreach($fields['subcategory'] AS $subCategoryId=>$subCategoryFields) {
				if(!empty($subCategoryFields)) $results[] = $this->recompute_sub_category_data_points($userId, $subCategoryId, $subCategoryFields);
			}
		}
		
		log_message('debug', '_score/recompute_old_statistics:: [4] final results: '.(!empty($results)? json_encode($results): ''));
		return get_decision($results);
	}
	
	
	
	
	
	
	
	# recompute the user data points
	function recompute_user_data_points($userId, $fields)
	{
		log_message('debug', '_score/recompute_user_data_points:: [1] ');
		log_message('debug', '_score/recompute_user_data_points:: [2] parameters: '.json_encode(array('type'=>'user', 'user_id'=>$userId, 'fields'=>$fields)));
		
		if(in_array('number_of_surveys_answered_in_last90days', $fields)) $data['number_of_surveys_answered_in_last90days'] = $this->_query_reader->get_row_as_array('datavalue__number_of_surveys_answered_in_last90days', array('user_id'=>$userId));
		if(in_array('number_of_direct_referrals_last180days', $fields)) $data['number_of_direct_referrals_last180days'] = $this->_query_reader->get_row_as_array('datavalue__number_of_direct_referrals_last180days', array('user_id'=>$userId));
		if(in_array('number_of_direct_referrals_last360days', $fields)) $data['number_of_direct_referrals_last360days'] = $this->_query_reader->get_row_as_array('datavalue__number_of_direct_referrals_last360days', array('user_id'=>$userId));
		if(in_array('number_of_network_referrals_last180days', $fields)) $data['number_of_network_referrals_last180days'] = $this->_query_reader->get_row_as_array('datavalue__number_of_network_referrals_last180days', array('user_id'=>$userId));
		if(in_array('number_of_network_referrals_last360days', $fields)) $data['number_of_network_referrals_last360days'] = $this->_query_reader->get_row_as_array('datavalue__number_of_network_referrals_last360days', array('user_id'=>$userId));
		if(in_array('spending_of_direct_referrals_last180days', $fields)) $data['spending_of_direct_referrals_last180days'] = $this->_query_reader->get_row_as_array('datavalue__spending_of_direct_referrals_last180days', array('user_id'=>$userId));
		if(in_array('spending_of_direct_referrals_last360days', $fields)) $data['spending_of_direct_referrals_last360days'] = $this->_query_reader->get_row_as_array('datavalue__spending_of_direct_referrals_last360days', array('user_id'=>$userId));
		if(in_array('spending_of_network_referrals_last180days', $fields)) $data['spending_of_network_referrals_last180days'] = $this->_query_reader->get_row_as_array('datavalue__spending_of_network_referrals_last180days', array('user_id'=>$userId));
		if(in_array('spending_of_network_referrals_last360days', $fields)) $data['spending_of_network_referrals_last360days'] = $this->_query_reader->get_row_as_array('datavalue__spending_of_network_referrals_last360days', array('user_id'=>$userId));
		if(in_array('spending_last180days', $fields)) $data['spending_last180days'] = $this->_query_reader->get_row_as_array('datavalue__spending_last180days', array('user_id'=>$userId));
		if(in_array('spending_last360days', $fields)) $data['spending_last360days'] = $this->_query_reader->get_row_as_array('datavalue__spending_last360days', array('user_id'=>$userId));
		if(in_array('ad_spending_last180days', $fields)) $data['ad_spending_last180days'] = $this->_query_reader->get_row_as_array('datavalue__ad_spending_last180days', array('user_id'=>$userId));
		if(in_array('ad_spending_last360days', $fields)) $data['ad_spending_last360days'] = $this->_query_reader->get_row_as_array('datavalue__ad_spending_last360days', array('user_id'=>$userId));
		if(in_array('average_cash_balance_last24months', $fields)) $data['average_cash_balance_last24months'] = $this->_query_reader->get_row_as_array('datavalue__average_cash_balance_last24months', array('user_id'=>$userId));
		if(in_array('average_credit_balance_last24months', $fields)) $data['average_credit_balance_last24months'] = $this->_query_reader->get_row_as_array('datavalue__average_credit_balance_last24months', array('user_id'=>$userId));
		if(in_array('has_public_checkin_last7days', $fields)) $data['has_public_checkin_last7days'] = $this->_query_reader->get_row_as_array('datavalue__has_public_checkin_last7days', array('user_id'=>$userId));
		if(in_array('has_answered_survey_in_last90days', $fields)) $data['has_answered_survey_in_last90days'] = $this->_query_reader->get_row_as_array('datavalue__has_answered_survey_in_last90days', array('user_id'=>$userId));
		
		log_message('debug', '_score/recompute_user_data_points:: [3] response: '.json_encode($data));
		
		return $this->apply_data_cache(array('type'=>'user', 'user_id'=>$userId, 'fields'=>$fields, 'data'=>$data));
	}
	
	
	
	
	
	
	
	# recompute the store data-points
	function recompute_store_data_points($userId, $storeId, $fields)
	{
		log_message('debug', '_score/recompute_store_data_points:: [1] ');
		log_message('debug', '_score/recompute_store_data_points:: [2] parameters: '.json_encode(array('type'=>'store', 'user_id'=>$userId, 'store_id'=>$storeId, 'fields'=>$fields)));
		
		if(in_array('my_store_spending_last90days', $fields)) $data['my_store_spending_last90days'] = $this->_query_reader->get_row_as_array('datavalue__my_store_spending_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_store_spending_last12months', $fields)) $data['my_store_spending_last12months'] = $this->_query_reader->get_row_as_array('datavalue__my_store_spending_last12months', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_chain_spending_last90days', $fields)) $data['my_chain_spending_last90days'] = $this->_query_reader->get_row_as_array('datavalue__my_chain_spending_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_chain_spending_last12months', $fields)) $data['my_chain_spending_last12months'] = $this->_query_reader->get_row_as_array('datavalue__my_chain_spending_last12months', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_direct_competitors_spending_last90days', $fields)) $data['my_direct_competitors_spending_last90days'] = $this->_query_reader->get_row_as_array('datavalue__my_direct_competitors_spending_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_direct_competitors_spending_last12months', $fields)) $data['my_direct_competitors_spending_last12months'] = $this->_query_reader->get_row_as_array('datavalue__my_direct_competitors_spending_last12months', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_category_spending_last90days', $fields)) $data['my_category_spending_last90days'] = $this->_query_reader->get_row_as_array('datavalue__my_category_spending_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('my_category_spending_last12months', $fields)) $data['my_category_spending_last12months'] = $this->_query_reader->get_row_as_array('datavalue__my_category_spending_last12months', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('did_my_category_survey_last90days', $fields)) $data['did_my_category_survey_last90days'] = $this->_query_reader->get_row_as_array('datavalue__did_my_category_survey_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('related_categories_spending_last90days', $fields)) $data['related_categories_spending_last90days'] = 0;#$this->_query_reader->get_row_as_array('datavalue__related_categories_spending_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('related_categories_spending_last12months', $fields)) $data['related_categories_spending_last12months'] = 0;#$this->_query_reader->get_row_as_array('datavalue__related_categories_spending_last12months', array('user_id'=>$userId, 'store_id'=>$storeId));
		if(in_array('did_related_categories_survey_last90days', $fields)) $data['did_related_categories_survey_last90days'] = $this->_query_reader->get_row_as_array('datavalue__did_related_categories_survey_last90days', array('user_id'=>$userId, 'store_id'=>$storeId));
		
		log_message('debug', '_score/recompute_sub_category_data_points:: [3] response: '.json_encode($data));
		
		return $this->apply_data_cache(array('type'=>'store', 'user_id'=>$userId, 'store_id'=>$storeId, 'fields'=>$fields, 'data'=>$data));
	}






	# recompute the sub-category data-points
	function recompute_sub_category_data_points($userId, $subCategoryId, $fields)
	{
		log_message('debug', '_score/recompute_sub_category_data_points:: [1] ');
		log_message('debug', '_score/recompute_sub_category_data_points:: [2] parameters: '.json_encode(array('type'=>'sub_category', 'user_id'=>$userId, 'sub_category_id'=>$subCategoryId, 'fields'=>$fields)));
		
		if(in_array('my_category_spending_last90days', $fields)) $data['my_category_spending_last90days'] = $this->_query_reader->get_row_as_array('datavalue__my_category_spending_last90days__subcategory', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
		if(in_array('my_category_spending_last12months', $fields)) $data['my_category_spending_last12months'] = $this->_query_reader->get_row_as_array('datavalue__my_category_spending_last12months__subcategory', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
		if(in_array('did_my_category_survey_last90days', $fields)) $data['did_my_category_survey_last90days'] = $this->_query_reader->get_row_as_array('datavalue__did_my_category_survey_last90days__subcategory', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
		if(in_array('related_categories_spending_last90days', $fields)) $data['related_categories_spending_last90days'] = 0;#$this->_query_reader->get_row_as_array('datavalue__related_categories_spending_last90days__subcategory', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
		if(in_array('related_categories_spending_last12months', $fields)) $data['related_categories_spending_last12months'] = 0;#$this->_query_reader->get_row_as_array('datavalue__related_categories_spending_last12months__subcategory', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
		if(in_array('did_related_categories_survey_last90days', $fields)) $data['did_related_categories_survey_last90days'] = $this->_query_reader->get_row_as_array('datavalue__did_related_categories_survey_last90days__subcategory', array('user_id'=>$userId, 'sub_category_id'=>$subCategoryId));
		
		log_message('debug', '_score/recompute_sub_category_data_points:: [3] response: '.json_encode($data));
		
		return $this->apply_data_cache(array('type'=>'sub_category', 'user_id'=>$userId, 'sub_category_id'=>$subCategoryId, 'fields'=>$fields, 'data'=>$data));
	}
	
	





	# apply the data cache based on the received values
	function apply_data_cache($parameters)
	{
		log_message('debug', '_score/apply_data_cache:: [1] ');
		
		# TEMPORARY: for now, only rank for user data-points and NOT store or sub-category data-points
		#$ranked = ($ranked == 'Y' && (!empty($parameters['store_id']) || !empty($parameters['sub_category_id']) ))? 'N': $ranked;
		log_message('debug', '_score/apply_data_cache:: [2] parameters: '.json_encode($parameters));
		
		# go through the fields and update the respective value with that you get from the query
		$dataPoints = array();
		foreach($parameters['data'] AS $field=>$valueRow){
			if(!empty($valueRow['checkby_date'])){
				$dataPoints[$field]['data_value'] = (!empty($valueRow[$field])? $valueRow[$field]: '');
				$dataPoints[$field]['is_ranked'] = (strpos($field, 'has_') !== FALSE || strpos($field, 'did_') !== FALSE? 'N': 'Y');
				$dataPoints[$field]['new_checkby_date'] = $valueRow['checkby_date'];
			}
		}
	
		log_message('debug', '_score/apply_data_cache:: [3] parameters: '.json_encode($dataPoints));
		
		# now apply the changes for this user/store/sub_category
		if($parameters['type'] == 'user') return $this->update_user_data_cache($parameters['user_id'], $dataPoints);
		else if($parameters['type'] == 'store') return $this->update_store_data_cache($parameters['user_id'], $parameters['store_id'], $dataPoints);
		else if($parameters['type'] == 'sub_category') return $this->update_sub_category_data_cache($parameters['user_id'], $parameters['sub_category_id'], $dataPoints);
	}
	
	
	
	
	
}


?>