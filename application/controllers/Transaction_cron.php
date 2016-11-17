<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles running cron jobs related to transactions.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 09/09/2015
 */

class Transaction_cron extends CI_Controller 
{
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
		$this->load->model('_cron');
		$this->load->model('_importer');
	}
	
	
	
	
	
	# Pull all user transactions
	function pull_all_user_transactions()
	{ 
		log_message('debug', 'Transaction_cron/pull_all_user_transactions');
		$urlData = $this->uri->ruri_to_assoc(2);
		$data = array();
		
		log_message('debug', 'Transaction_cron/pull_all_user_transactions:: [1] urlData='.json_encode($urlData));
		#First determine where the user is coming from and get the appropriate data
		#Get response from API and decode it as JSON
		if(!empty($urlData['h']))
		{
			$apiResponse = !empty($HTTP_RAW_POST_DATA)? $HTTP_RAW_POST_DATA : @file_get_contents("php://input");
			$response = json_decode($apiResponse, TRUE);
			#$response = array('code'=>'0', 'message'=>"Initial transaction pull finished");
			if(!empty($urlData['h']) && (!empty($response['code']) || $response['code']=='0') && in_array($response['code'], array('0', '1','2','3'))) 
			{
				$user  = $this->_query_reader->get_row_as_array('get_user_by_email', array('email_address'=>decrypt_value($urlData['h']) ));
				#Now get the actual data details as needed for the processing
				if(!empty($user['user_id']))
				{
					#Delete the pending transactions if we receive this code
					if($response['code'] == '3')
					{
						#Are removed transactions not empty
						if(!empty($response['removed_transactions']))
						{
							$deactivateActive = $this->_query_reader->run('remove_clout_transactions_by_api_ids', array('api_ids'=>"'".implode("','", $response['removed_transactions'])."'"));
							$deactivateRaw = $this->_query_reader->run('remove_raw_transactions_by_api_ids', array('api_ids'=>"'".implode("','", $response['removed_transactions'])."'"));
							
							$response = $this->_cron->log_cron_job_results(array('user_id'=>$user['user_id'], 'job_type'=>'transaction_cron', 'job_code'=>'api_hook_delete', 'result'=>(get_decision(array($deactivateRaw,$deactivateActive))? 'success':'fail'), 'job_details'=>'Raw Transaction API ID List='.implode(",", $response['removed_transactions']), 'record_count'=>count($response['removed_transactions']) ));
						}
					}
					#data for a user pulling the information
					else
					{
						$cron = $this->_query_reader->get_row_as_array('get_cron_schedules', array('is_done'=>'N', 'extra_conditions'=>" AND (cron_value LIKE 'user=".$user['userId'].",%' AND job_type='transaction_cron' AND activity_code='pull_all_user_transactions' ) ORDER BY id DESC ", 'limit_text'=>' LIMIT 0,1; '));
					
						if(!empty($cron['cron_value']))
						{
							$valueParts = explode(',', $cron['cron_value']);
							foreach($valueParts AS $part)
							{
								$partValues = explode('=', $part);
								$data[$partValues[0]] = $partValues[1];
							}
							$data['jobid'] = $cron['id'];
						}
					}
					
				}
			}
		}
		else
		{
			$data = filter_forwarded_data($this);
		}
		log_message('debug', 'Transaction_cron/pull_all_user_transactions:: [2] data='.json_encode($data));
		
		# the data is found, continue
		if(!empty($data['user']) && !empty($data['bankid']) && !empty($data['bankcode']))
		{
			$msg = '';
			$user = $this->_query_reader->get_row_as_array('get_user_by_id', array('user_id'=>$data['user']));
			$token = $this->_query_reader->get_row_as_array('get_access_token', array('user_email'=>$user['email_address'], 
			'bank_code'=>$data['bankcode']));
			
			if(!empty($token))
			{
				$results = array();
				# update the access token and backup the old one if a new access token is given
				if(!empty($response['new_access_token']))
				{
					$archiveResult = $this->_query_reader->run('disable_plaid_access_token', $token);
					$token['access_token'] = $response['new_access_token'];
					$updateResult = $this->_query_reader->run('add_access_token', array('user_id'=>$data['user'], 'bank_code'=>$data['bankcode'], 'bank_id'=>$data['bankid'], 'user_email'=>$user['email_address'], 'access_token'=>$token['access_token'], 'is_active'=>'Y'));
				}
				
				# check if this is a first time user
				$firstTime = $this->_query_reader->get_count('get_raw_bank_account_record',array('user_id'=>$data['user'])) > 0? FALSE: TRUE;
				
				$importResult = $this->_importer->import_all_user_transactions(
					$data['user'], 
					$data['bankid'], 
					$token['access_token'], 
					array(
						'after_date'=>($firstTime? date('Y-m-d', strtotime('-30 days')): date('Y-m-d', strtotime('-5 years'))) ,
						'is_first_time'=>$firstTime
					)
				);
			}
			else $msg = 'Token is empty';
		}
		# get here if this is not a delete webhook
		else if(!(!empty($response['code']) && $response['code'] == '3'))
		{
			$msg = "Parameter missing. Passed list (".json_encode($data).")";
		}
		
		
		
		# mark the cron job with appropriate updates
		if(!empty($data['jobid']))
		{
			if(!empty($importResult) && $importResult) 
			{
				if($firstTime) $result = $this->_cron->remove_job_from_server('<'.$data['jobid'].'>');
					
				# make repeat code end of day if the user schedule was successfully pulled
				$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"repeat_code", 'field_value'=>"end_of_day", 'id'=>$data['jobid']));
				if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>"success", 'id'=>$data['jobid']));
				
				# then add the job again to the server but this time as end-of-day user (scheduled-hour)
				if($result && $firstTime) $result = $this->_cron->add_job_to_server('<'.$data['jobid'].'>');
			}
			else
			{
				$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>"fail", 'id'=>$data['jobid']));	
			}
			if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"when_ran", 'field_value'=>date('Y-m-d H:i:s'), 'id'=>$data['jobid']));
			
			# log cron results
			$jobDetails['user_id'] = !empty($data['user'])? $data['user']:'';
			$jobDetails['job_type'] = 'transaction_cron';
			$jobDetails['job_code'] = 'pull_all_user_transactions';
			$jobDetails['result'] = (!empty($importResult) && $importResult)? 'success': 'fail';
			$jobDetails['job_details'] = (!empty($data['bankid']) && !empty($data['bankcode']))? array('bank_id='.$data['bankid'], 'bank_code='.$data['bankcode'], 'msg='.$msg): array('msg='.$msg);
			
			$this->_cron->update_status($data['jobid'], $jobDetails);
		}
	}
	
	
	
	
	
	
	
	
	# --------------------------------------------------------------
	# TEST FUNCTIONS - NOT PERMANENT!
	# --------------------------------------------------------------
	
	
	
	# test addition and removal of cron jobs
	function modify_cron(){
		
		log_message('debug', 'Transaction_cron/modify_cron');
		$data = filter_forwarded_data($this);
		log_message('debug', 'Transaction_cron/modify_cron: [1] data='.json_encode($data));
		
		if($data['action'] == 'remove') $result = $this->_cron->remove_job_from_server('<'.$data['jobid'].'>');
		else if($data['action'] == 'add') $result = $this->_cron->add_job_to_server('<'.$data['jobid'].'>');
		log_message('debug', 'Transaction_cron/modify_cron: [2] result='.$result);
		
		echo "RESULT: ".$data['action'].' '.($result? 'SUCCESS': 'FAIL');
	}
	
	
	
	
	
	# test presearching for store score
	function pre_search_stores()
	{
		log_message('debug', 'Transaction_cron/pre_search_stores');
		$sentResult = server_curl(MESSAGE_SERVER_URL,  array('__check'=>'Y', '__action'=>'send', 'receiverId'=>'364', 'return'=>'plain', 'message'=>array('emailaddress' => 'al@clout.com',
    'firstname' => 'Al',
    'newstorescores' => 'Krankies Local Market (540), Banana Republic (428), Octane Coffee Bar & Lounge (440)',
    'code' => 'new_store_scores'
), 'requiredFormats'=>array('email','sms'), 'strictFormatting'=>FALSE));
		
		log_message('debug', 'Transaction_cron/pre_search_stores:: [1] sentResult='.json_encode($sentResult));
		#$topSearches = $this->_query_reader->get_list('mongodb__search_stores_without_zipcode', array('name'=>'papa johns', 'address'=>'2625', 'limit_text'=>' LIMIT 5 ' ));
		
		#print_r($topSearches);
		/*$data = filter_forwarded_data($this);
		$importResult = $this->_importer->pre_search_matching_stores($data['user']);
		#$importResult = $this->_importer->move_transaction_data($data['user'], $data['bank']);
		echo "<BR>RESULT: ".($importResult? 'SUCCESS': 'FAIL');
		*/
	}
	
	
	
}

/* End of controller file */