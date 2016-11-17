<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles running cron jobs related to scoring.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 05/04/2015
 */

class Scoring_cron extends CI_Controller 
{
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
	}
	
	
	
	
	
	# save raw transactions from the Plaid API
	# (BASE_URL)/scoring_cron/save_raw_transactions/user/(user id)/token/(token id)[/date/(epoch date)][/is_new/(yes/no)][/processor/(processor domain name)]
	# processor domain is given in the format: pro_DOT_dw_DOT_crn1_DOT_clout_DOT_com
	function save_raw_transactions()
	{
		log_message('debug', 'Scoring_cron/save_raw_transactions:: [1] ');
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('save_raw_transactions_start');
		
		if(!empty($data['token'])) {
			$this->load->model('_importer');
			$this->load->model('_queue_publisher');
			
			$data['date'] = !empty($data['date'])? format_epoch_date($data['date'],'Y-m-d H:i:s',''): date('Y-m-d H:i:s', strtotime('- 30 days'));
			log_message('debug', 'Scoring_cron/save_raw_transactions:: [2] after-date: '.$data['date']);
			$isNew = (!empty($data['is_new'])? strtoupper(substr($data['is_new'],0,1)): 'N');
			$result = $this->_importer->save_raw_api_bank_data($data['user'], $data['token'], $data['date'], $isNew);
			
			$isSaved = (!empty($result['result']) && $result['result'] == 'SUCCESS');
			# queue the next job if this user is new, otherwise await the batch processing to handle the jobs
			if($isSaved && $isNew == 'Y' && !empty($result['transaction_count'])) {
				# schedule the matching job in the queue
				$isSaved = $this->_queue_publisher->add_job_to_queue(array(
					'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
					'job'=>'scoring_cron/match_transactions/user/'.$data['user'],
					'code'=>'match_transactions',
					'user'=>$data['user'],
					'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
				));
				
				# schedue a job to run at end-of-day for this user
				if($isSaved){
					$isSaved = $this->_queue_publisher->add_job_to_queue(array(
							'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
							'job'=>'scoring_cron/recompute_old_statistics/user/'.$data['user'],
							'code'=>'recompute_old_statistics',
							'schedule'=>'every-day-3am',
							'user'=>$data['user'],
							'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
					));
				}
			} 
			# there were no transactions in the last 30 days, retry with the longest transaction pull
			else if($result['import_account_result'] && empty($result['transaction_count']) && $isNew == 'Y' && strtotime('- 1 years') < strtotime($data['date'])) {
				$isSaved = $this->_queue_publisher->add_job_to_queue(array(
						'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
						'job'=>'scoring_cron/save_raw_transactions/user/'.$data['user'].'/token/'.$data['token'].'/is_new/yes/date/'.strtotime('- 10 years'),
						'code'=>'save_raw_transactions',
						'user'=>$data['user'],
						'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
				));
			}
		}
		
		$this->benchmark->mark('save_raw_transactions_end');
		log_message('debug', 'Scoring_cron/save_raw_transactions:: [3] '.(!empty($result['result'])? $result['result'].', message: '.$result['message']: 'FAIL'));
		log_message('debug', 'Scoring_cron/save_raw_transactions:: [4] run-time:'.$this->benchmark->elapsed_time('save_raw_transactions_start', 'save_raw_transactions_end'));
		
		echo (!empty($isSaved) && $isSaved? 'success': 'fail');
	}
	
	
	
	
	
	
	
	
	# notify about new store scores
	# (BASE_URL)/scoring_cron/report_new_store_scores/user/(user id)
	function report_new_store_scores()
	
	
	{/*AUG-2016 HOT-FIX: Turn off email and sms notifications with scores for every user
		log_message('debug', 'Scoring_cron/report_new_store_scores:: [1] ');
		$data = filter_forwarded_data($this);
		
		$this->benchmark->mark('report_new_store_scores_start');
		if(!empty($data['user'])){
			$user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$data['user']));
			if(!empty($user['email_address'])){
				$this->load->model('_importer');
				$newStoreScores = $this->_importer->get_new_store_scores($data['user'], 'array');
				
				if(!empty($newStoreScores['email_string'])) {
					$message = array('emailaddress'=>$user['email_address'], 
							'firstname'=>$user['first_name'], 
							'emailstring'=>$newStoreScores['email_string'], 
							'smsstring'=>$newStoreScores['sms_string'],
							'code'=>'new_store_scores'
						); 
				}
				# Send a message if there is a change in the scores
				if(!empty($message)) {
					$sentResult = server_curl(MESSAGE_SERVER_URL,  array('__action'=>'send', 
							'receiverId'=>$data['user'], 
							'return'=>'plain', 
							'message'=>$message, 
							'requiredFormats'=>array('email','sms'), 
							'strictFormatting'=>FALSE
						));
					
					# mark stores as reported if successfully sent
					if($sentResult) $this->_query_reader->run('mark_stores_as_reported', array('user_id'=>$data['user']));
				}
			}
			else log_message('debug', 'Scoring_cron/report_new_store_scores:: [2] ERROR - no user with ID found');
		}
		else log_message('debug', 'Scoring_cron/report_new_store_scores:: [3] ERROR - user ID not provided');
		
		# log result
		$this->_logger->add_event(array(
				'user_id'=>(!empty($data['user'])? $data['user']: ''),
				'activity_code'=>'report_new_store_scores',
				'result'=>(!empty($sentResult) && $sentResult? 'success': 'fail'),
				'log_details'=>"sent to email: ".(!empty($user['email_address'])? $user['email_address']: '')
				.", no_of_scores_reported: ".(!empty($newStoreScores['list'])? count($newStoreScores['list']): 0)
			));
		
		$this->benchmark->mark('report_new_store_scores_end');
		log_message('debug', 'Scoring_cron/report_new_store_scores:: [4] run-time:'.$this->benchmark->elapsed_time('report_new_store_scores_start', 'report_new_store_scores_end'));
		
	
	echo (!empty($sentResult) && $sentResult? 'success': 'fail');
	*/ 
		echo 'success';
	
	
	}
	
	
	
	
	
	
	
	# compute score for the given user
	# (BASE_URL)/scoring_cron/compute/user/(user id)[/scope/(store_score)][/processor/(processor domain name)]
	# - processor domain is given in the format: pro_DOT_dw_DOT_crn1_DOT_clout_DOT_com
	# - options for scope: store_score, clout_score, all
	function compute()
	{
		log_message('debug', 'Scoring_cron/compute:: [1] ');
		$data = filter_forwarded_data($this);
		
		$this->benchmark->mark('compute_start');
		# make sure the user ID is given
		if(!empty($data['user'])){
			$this->load->model('_score');
			
			# determine which score type the command wants to compute
			if(empty($data['scope'])) $data['scope'] = 'all';
			
			log_message('debug', 'Scoring_cron/compute:: [2] computing scope: '.$data['scope']);
			
			if($data['scope'] == 'all') {
				$response1 = $this->_score->generate_clout_score($data['user']);
				$response2 = ($response1['result'] == 'SUCCESS')? $this->_score->generate_store_score($data['user']): array('result'=>'FAIL');
				
				# merge the results
				$response = array_merge($response1, $response2);
			}
			else if($data['scope'] == 'clout_score') $response = $this->_score->generate_clout_score($data['user']);
			else if($data['scope'] == 'store_score') $response = $this->_score->generate_store_score($data['user']);
			
			# queue reporting if this is successful
			if($response['result']){
				$this->load->model('_queue_publisher');
				# schedule the matching job in the queue
				$result = $this->_queue_publisher->add_job_to_queue(array(
					'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
					'job'=>'scoring_cron/report_new_store_scores/user/'.$data['user'],
					'code'=>'report_new_store_scores',
					'user'=>$data['user'],
					'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
				));
				$response['result'] = $result? 'SUCCESS': 'FAIL';
			}
			
			log_message('debug', 'Scoring_cron/compute:: [3] scoring result: '.json_encode($response));
		}
		else log_message('debug', 'Scoring_cron/compute:: [4] ERROR - user ID not provided');
		

		# log result
		$this->_logger->add_event(array(
				'user_id'=>(!empty($data['user'])? $data['user']: ''),
				'activity_code'=>'compute_store_scores',
				'result'=>(!empty($response['result']) && $response['result']? strtolower($response['result']): 'fail'),
				'log_details'=>"user_email: ".(!empty($user['email_address'])? $user['email_address']: '')
							.", store_ids: ".(!empty($response['store_ids'])? implode(", ", $response['store_ids']): '')
							.", sub_category_ids: ".(!empty($response['sub_category_ids'])? implode(", ", $response['sub_category_ids']): 0)
			));
		
		$this->benchmark->mark('compute_end');
		log_message('debug', 'Scoring_cron/compute:: [5] run-time:'.$this->benchmark->elapsed_time('compute_start', 'compute_end'));
		
		echo (!empty($response['result']) && $response['result'] == 'SUCCESS'? 'success': 'fail');
	}
	
	
	
	
	
	
	







	# call to flatten user network data for use in other areas as soon as a referral signs up
	# (BASE_URL)/scoring_cron/update_user_network_cache/user/(user id)/referrer/(referrer id)
	function update_user_network_cache()
	{
		log_message('debug', 'Scoring_cron/update_user_network_cache:: [1] ');
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('update_user_network_cache_start');
		
		# make sure the user ID is given
		if(!empty($data['user']) && !empty($data['referrer'])){
			$this->load->model('_network');
			$results[0] = $this->_network->add_user_to_referrer_network($data['user'], $data['referrer'], 'level_1');
			$results[1] = $this->_network->add_user_to_referrer_network($data['user'], $data['referrer'], 'level_2');
			$results[2] = $this->_network->add_user_to_referrer_network($data['user'], $data['referrer'], 'level_3');
			$results[3] = $this->_network->add_user_to_referrer_network($data['user'], $data['referrer'], 'level_4');
			
			$result = get_decision($results);
			log_message('debug', 'Scoring_cron/update_user_network_cache:: [3] addition results: '.json_encode($result));
		}
		else log_message('debug', 'Scoring_cron/update_user_network_cache:: [4] ERROR - user ID or referrer ID not provided');
		
		
		# log result
		$this->_logger->add_event(array(
			'user_id'=>(!empty($data['user'])? $data['user']: ''),
			'activity_code'=>'update_user_network_cache',
			'result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'),
			'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')
				.", referrer_id: ".(!empty($data['referrer'])? $data['referrer']: '')
		));
		
		$this->benchmark->mark('update_user_network_cache_end');
		log_message('debug', 'Scoring_cron/update_user_network_cache:: [5] run-time:'.$this->benchmark->elapsed_time('update_user_network_cache_start', 'update_user_network_cache_end'));
		
		echo (!empty($result) && $result? 'success': 'fail');
	}
	
	
	
	
	
	
	
	
	# recompute account balance averages for the given user
	# these include: average_cash_balance_last24months, average_credit_balance_last24months
	# (BASE_URL)/scoring_cron/recompute_account_balance_averages/user/(user id)
	function recompute_account_balance_averages()
	{
		log_message('debug', 'Scoring_cron/recompute_account_balance_averages:: [1] ');
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('recompute_account_balance_averages_start');
		
		# make sure the user ID is given
		if(!empty($data['user'])){
			$this->load->model('_score');
			$result = $this->_score->recompute_account_balance_averages($data['user']);
			log_message('debug', 'Scoring_cron/recompute_account_balance_averages:: [2] addition results: '.($result?'SUCCESS':'FALSE'));
		}
		else log_message('debug', 'Scoring_cron/recompute_account_balance_averages:: [3] ERROR - user ID not provided');
		
		
		# log result
		$this->_logger->add_event(array(
			'user_id'=>(!empty($data['user'])? $data['user']: ''),
			'activity_code'=>'recompute_account_balance_averages',
			'result'=>(!empty($result) && $result? 'success': 'fail'),
			'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')
		));

		$this->benchmark->mark('recompute_account_balance_averages_end');
		log_message('debug', 'Scoring_cron/recompute_account_balance_averages:: [4] run-time:'.$this->benchmark->elapsed_time('recompute_account_balance_averages_start', 'recompute_account_balance_averages_end'));
		
		echo (!empty($result) && $result? 'success': 'fail');
	}
	








	# update the store survey statistics. call <IF> a user has answered a survey added by a store
	# (BASE_URL)/scoring_cron/update_store_survey_statistics/user/(user id)/store/(store id)
	function update_store_survey_statistics()
	{
		log_message('debug', 'Scoring_cron/update_store_survey_statistics:: [1] ');
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('update_store_survey_statistics_start');
	
		# make sure the user ID is given
		if(!empty($data['user']) && !empty($data['store'])){
			$this->load->model('_score');
			$result = $this->_score->update_store_survey_statistics($data['user'],$data['store']);
			log_message('debug', 'Scoring_cron/update_store_survey_statistics:: [2] addition results: '.($result?'SUCCESS':'FALSE'));
		}
		else log_message('debug', 'Scoring_cron/update_store_survey_statistics:: [3] ERROR - user ID or store ID not provided');
	
	
		# log result
		$this->_logger->add_event(array(
			'user_id'=>(!empty($data['user'])? $data['user']: ''),
			'activity_code'=>'update_store_survey_statistics',
			'result'=>(!empty($result) && $result? 'success': 'fail'),
			'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')
				."store_id: ".(!empty($data['store'])? $data['store']: '')
		));

		$this->benchmark->mark('update_store_survey_statistics_end');
		log_message('debug', 'Scoring_cron/update_store_survey_statistics:: [4] run-time:'.$this->benchmark->elapsed_time('update_store_survey_statistics_start', 'update_store_survey_statistics_end'));
		
		echo (!empty($result) && $result? 'success': 'fail');
	}









	# update the transaction statistics
	# (BASE_URL)/scoring_cron/update_transaction_statistics/user/(user id)[/processor/(processor domain name)]
	# processor domain is given in the format: pro_DOT_dw_DOT_crn1_DOT_clout_DOT_com
	function update_transaction_statistics()
	{
		log_message('debug', 'Scoring_cron/update_transaction_statistics:: [1] ');
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('update_transaction_statistics_start');
		
		# make sure the user ID is given
		if(!empty($data['user'])){
			$this->load->model('_score');
			$result = $this->_score->update_transaction_statistics($data['user']);
			log_message('debug', 'Scoring_cron/update_transaction_statistics:: [2] addition results: '.($result?'SUCCESS':'FALSE'));
		}
		else log_message('debug', 'Scoring_cron/update_transaction_statistics:: [3] ERROR - user ID not provided');
		
		
		# transaction statistics were successfully updated
		if($result) {
			$this->load->model('_queue_publisher');
			# schedule the matching job in the queue
			$result = $this->_queue_publisher->add_job_to_queue(array(
				'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
				'job'=>'scoring_cron/compute/user/'.$data['user'],
				'code'=>'compute',
				'user'=>$data['user'],
				'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
			));
		}
	
		# log result
		$this->_logger->add_event(array(
			'user_id'=>(!empty($data['user'])? $data['user']: ''),
			'activity_code'=>'update_transaction_statistics',
			'result'=>(!empty($result) && $result? 'success': 'fail'),
			'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')
		));

		$this->benchmark->mark('update_transaction_statistics_end');
		log_message('debug', 'Scoring_cron/update_transaction_statistics:: [4] run-time:'.$this->benchmark->elapsed_time('update_transaction_statistics_start', 'update_transaction_statistics_end'));
		
		echo (!empty($result) && $result? 'success': 'fail');
	}
	
	
	









	# recompute the aged scoring statistics
	# (BASE_URL)/scoring_cron/recompute_old_statistics/user/(user id)[/processor/(processor domain name)]
	# processor domain is given in the format: pro_DOT_dw_DOT_crn1_DOT_clout_DOT_com
	function recompute_old_statistics()
	{
		log_message('debug', 'Scoring_cron/recompute_old_statistics:: [1] ');
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('recompute_old_statistics_start');
		
		# make sure the user ID is given
		if(!empty($data['user'])){
			$this->load->model('_score');
			$result = $this->_score->recompute_old_statistics($data['user']);
			log_message('debug', 'Scoring_cron/recompute_old_statistics:: [2] recomputing results: '.($result?'SUCCESS':'FALSE'));
		}
		else log_message('debug', 'Scoring_cron/recompute_old_statistics:: [3] ERROR - user ID not provided');
	
		# transaction statistics were successfully updated
		if($result) {
			$this->load->model('_queue_publisher');
			# schedule the matching job in the queue
			$result = $this->_queue_publisher->add_job_to_queue(array(
				'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
				'job'=>'scoring_cron/compute/user/'.$data['user'],
				'code'=>'compute',
				'user'=>$data['user'],
				'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
			));
		}
	
		# log result
		$this->_logger->add_event(array(
			'user_id'=>(!empty($data['user'])? $data['user']: ''),
			'activity_code'=>'recompute_old_statistics',
			'result'=>(!empty($result) && $result? 'success': 'fail'),
			'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')
		));

		$this->benchmark->mark('recompute_old_statistics_end');
		log_message('debug', 'Scoring_cron/recompute_old_statistics:: [4] run-time:'.$this->benchmark->elapsed_time('recompute_old_statistics_start', 'recompute_old_statistics_end'));
		
		echo (!empty($result) && $result? 'success': 'fail');
	}
	
	
	
	
	
	
	
	
	
	# TODO: delete raw transactions (and hence any processed transaction data due to them)
	# if we receive notification from the API that these were temporary transactions and have been removed from the user's bank account record
	# - the transaction API id list is separated by two underscores i.e. tx-id1__tx-id2__tx-id3__etc
	# (BASE_URL)/scoring_cron/delete_transactions_by_api/token/(token id)/transactions/(transaction API id list)
	function delete_transactions_by_api()
	{
		log_message('debug', 'Scoring_cron/delete_transactions_by_api:: [1] ');
		$data = filter_forwarded_data($this);
		
		$this->benchmark->mark('delete_transactions_by_api_start');
		
		/* 
		 $deactivateActive = $this->_query_reader->run('remove_clout_transactions_by_api_ids', array('api_ids'=>"'".implode("','", $response['removed_transactions'])."'"));
		 $deactivateRaw = $this->_query_reader->run('remove_raw_transactions_by_api_ids', array('api_ids'=>"'".implode("','", $response['removed_transactions'])."'"));
		 $result = get_decision(array($deactivateRaw,$deactivateActive))? 'success':'fail';
		 $recordCount = count($response['removed_transactions']);
		 */

		$this->benchmark->mark('delete_transactions_by_api_end');
		log_message('debug', 'Scoring_cron/delete_transactions_by_api:: [4] run-time:'.$this->benchmark->elapsed_time('delete_transactions_by_api_start', 'delete_transactions_by_api_end'));
		
		return TRUE;
	}
	
	


	
	
	# match transactions to a store
	# (BASE_URL)/scoring_cron/match_transactions[/user/(user id)][/ignore_new_users/(yes/no)][/read_limit/(number)][/processor/(processor domain name)]
	# processor domain is given in the format: pro_DOT_dw_DOT_crn1_DOT_clout_DOT_com
	function match_transactions()
	{
		log_message('debug', 'Scoring_cron/match_transactions:: [1] ');
		
		#Set to run until transactions are complete
		ignore_user_abort(true);
		set_time_limit(0);
		
		$data = filter_forwarded_data($this);
		$this->benchmark->mark('match_transactions_start');
		
		# make sure the user ID is given
		$this->load->model('_match');
		$parameters['user_id'] = !empty($data['user'])? $data['user']: '';
		$parameters['read_limit'] = !empty($data['read_limit'])? $data['read_limit']: '';
		$parameters['ignore_new_users'] = !empty($data['ignore_new_users'])? $data['ignore_new_users']: 'no';
		$result = $this->_match->match_transactions_to_stores($parameters);
		
		log_message('debug', 'Scoring_cron/match_transactions:: [2] match result: '.json_encode($result));
		
		if($result['boolean'] && !empty($result['transactions'])) $result['boolean'] = $this->_match->match_transactions_to_categories($result['transactions']);
		if($result['boolean'] && !empty($result['raw_ids'])) $this->_query_reader->run('batch_update_raw_transaction_field', array('field_name'=>'is_saved', 'field_value'=>'Y', 'raw_ids'=>implode("','",$result['raw_ids'])));
		log_message('debug', 'Scoring_cron/match_transactions:: [3] match result: '.json_encode($result));

		# transaction statistics were successfully updated
		if($result['boolean'] && !empty($result['user_ids'])) {
			$this->load->model('_queue_publisher');
			$results = array();
			
			# schedule the next statistics computation job for each user in the queue
			foreach($result['user_ids'] AS $i=>$userId){
				$results[] = $this->_queue_publisher->add_job_to_queue(array(
					'id'=>'j'.$userId.'-'.$i.'-'.strtotime('now').'-'.rand(0,1000000),
					'job'=>'scoring_cron/update_transaction_statistics/user/'.$userId,
					'code'=>'update_transaction_statistics',
					'user'=>$userId,
					'processor'=>(!empty($data['processor'])? str_replace('_DOT_','.',$data['processor']): RUNDECK_DEFAULT_JOB_PROCESSOR)
				));
			}
			# final result from the scheduling
			$result['boolean'] = get_decision($results);
		}
		
		# log result
		$this->_logger->add_event(array(
			'user_id'=>(!empty($data['user'])? $data['user']: ''),
			'activity_code'=>'match_transactions',
			'result'=>(!empty($result['boolean']) && $result['boolean']? 'success': 'fail'),
			'log_details'=>(!empty($data['user'])? 'transactions for user ID: '.$data['user']: 'batch-transactions as of: '.date('Y-m-d H:i:s', strtotime('now').(!empty($result['user_ids'])? ' for '.implode(',',$result['user_ids']): '')))
		));

		$this->benchmark->mark('match_transactions_end');
		log_message('debug', 'Scoring_cron/match_transactions:: [4] run-time:'.$this->benchmark->elapsed_time('match_transactions_start', 'match_transactions_end'));
		
		echo (!empty($result) && $result? 'success': 'fail');
	}
	
}

/* End of controller file */