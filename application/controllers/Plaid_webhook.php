<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles receiving, interpreting, redirecting and processing Plaid web-hook notices.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 05/04/2015
 */

class Plaid_webhook extends CI_Controller 
{
	# Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
		$this->load->model('_queue_publisher');
		$this->load->model('_queue_publisher');
	}
	
	
	
	# receive the webhook notification
	function receive_notification()
	{
		log_message('debug', 'Plaid_webhook/receive_notification:: [1] ');
		
		$data = $this->uri->ruri_to_assoc(3);
		log_message('debug', 'Plaid_webhook/receive_notification:: [1-2] data: '.json_encode($data));
		if(!empty($data['h']))
		{
			$apiResponse = !empty($HTTP_RAW_POST_DATA)? $HTTP_RAW_POST_DATA : @file_get_contents("php://input");
			$response = json_decode($apiResponse, TRUE);
			log_message('debug', 'Plaid_webhook/receive_notification:: [2] response: '.json_encode($response).', hook id: '.$data['h']);
			
			# check if the response codes are recognized
			if(isset($response['code']) && in_array($response['code'], array('0', '1','2','3','4')))
			{
				$user  = $this->_query_reader->get_row_as_array('get_user_by_id_or_email', array('user_id'=>decrypt_value($data['h']) ));
				
				/*# now get the actual data details as needed for the processing
				if(!empty($user['email_address']))
				{
					$recordCount = 0;
					# 0: initial transaciton pull is completed
					if($response['code'] == '0' && !empty($response['access_token'])){
						$job = 'plaid_api_webhook_initial_transaction_pull';
						# add job to the queue for processing
						$result = $this->_queue_publisher->add_job_to_queue(array(
							'user'=>$user['user_id'],
							'code'=>'initial-pull',
							'access_token'=>$response['access_token']
						));
					}
					# 1: historical transaction pull is completed
					else if($response['code'] == '1' && !empty($response['access_token'])){
						$job = 'plaid_api_webhook_historical_transaction_pull';
						# to post job to rundeck
						$result = $this->_queue_publisher->add_job_to_queue(array(
							'user'=>$user['user_id'],
							'code'=>'historical-pull',
							'access_token'=>$response['access_token']
						));
					}
					# 2: there has been an update on the user's data
					else */
					if(!empty($user) && $response['code'] == '2' && !empty($response['access_token'])){
						$job = 'plaid_api_webhook_transaction_update';
						$data['user'] = $user['user_id'];
						$data['token'] = $response['access_token'];
						
						# to post job to rundeck
						$result = $this->_queue_publisher->add_job_to_queue(array(
							'id'=>'j'.$data['user'].'-'.strtotime('now').'-'.rand(0,1000000),
							'job'=>'scoring_cron/save_raw_transactions/user/'.$data['user'].'/token/'.$data['token'].'/is_new/no',
							'code'=>'save_raw_transactions',
							'user'=>$data['user'],
							'processor'=>RUNDECK_DEFAULT_JOB_PROCESSOR
						));
					}
					/*# 3: transactions have been removed from the system
					else if($response['code'] == '3' && !empty($response['removed_transactions']) && !empty($response['access_token'])){
						$job = 'plaid_api_webhook_transaction_delete';
						
						$details = 'Raw Transaction API ID List='.implode(",", $response['removed_transactions']); 
						$result = $this->_queue_publisher->add_job_to_queue(array(
							'user'=>$user['user_id'],
							'code'=>'transaction-delete',
							'access_token'=>$response['access_token'],
							'api_transaction_ids'=>$response['removed_transactions']
						));
					}
					
					# 4: a user's webhook has been updated via a webhook without credentials
					else if($response['code'] == '4' && !empty($response['access_token'])){
						$job = 'plaid_api_webhook_update';
						$result = $this->_queue_publisher->add_job_to_queue(array(
							'user'=>$user['user_id'],
							'code'=>'webhook-update',
							'access_token'=>$response['access_token']
						));
					}	

					# OTHER: plaid error notification
					else {
						$job = 'plaid_api_webhook_error_notification';
						$result = $this->_queue_publisher->add_job_to_queue(array(
							'user'=>$user['user_id'],
							'code'=>'webhook-error',
							'access_token'=>$response['access_token'],
							'details'=>json_encode($response)
						));
					}
					
					$this->_logger->add_event(array(
							'user_id'=>$user['user_id'],
							'activity_code'=>$job,
							'result'=>$result,
							'log_details'=>"record count: ".$recordCount.", details: ".$details
					));*/
					
					log_message('debug', 'Plaid_webhook/receive_notification:: [3] hook processed as '.$job);
				/*}
				else log_message('debug', 'Plaid_webhook/receive_notification:: [4] ERROR - user email can not be found - may be user id does not exist.');
				 */
			}
			else log_message('debug', 'Plaid_webhook/receive_notification:: [5] ERROR - hook code not recognized.');
		}
		else log_message('debug', 'Plaid_webhook/receive_notification:: [6] ERROR - no hook id resolved.');
	}
	
	
	
	
	
	
	
	# update webhook at the plaid API for the given user
	function update_webhook()
	{
		$data = filter_forwarded_data($this);
		
		if(!empty($data['u'])) $response = $this->_webhook->update($data['u'], 'http://pro-dw-crn1.clout.com/');
		
		# record the result of the webhook update
		if(empty($respose['result'])) $response['result'] = 'FAIL';
		log_message('debug', 'Plaid_webhook/update_webhook:: [1] '.$response['result']);
	}
	
	
	
	



	# TODO: update the user webhook details after receiving notification from the API
	# (BASE_URL)/plaid_webhook/update_api_webhook_details/token/(token id)/user/(user id)
	function update_api_webhook_details()
	{
		log_message('debug', 'Plaid_webhook/update_api_webhook_details:: [1] ');
		$data = filter_forwarded_data($this);
	
		$isSent = $this->_messenger->email(array(
				'emailaddress'=>SYSTEM_REPORTS_EMAIL,
				'emailfrom'=>'no-reply@clout.com',
				'subject'=>'WEBHOOK UPDATED: user id '.$data['user'],
				'details'=>'DETAILS: '.json_encode($data['details'])
		), 'ses');
		
		log_message('debug', 'Plaid_webhook/update_api_webhook_details:: [2] result '.($isSent? 'SUCCESS': 'FAIL'));
		$this->_logger->add_event(array(
				'user_id'=>(!empty($data['user'])? $data['user']: ''),
				'activity_code'=>'report_webhook_update',
				'result'=>($isSent? 'success': 'fail'),
				'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')." details: ".json_encode($data['details'])
		));
		
		echo $isSent? 'success':'fail';
	}
	
	
	
	
	
	# report an error received from webhook
	# (BASE_URL)/plaid_webhook/report_webhook_error/user/(user id)
	function report_webhook_error()
	{
		log_message('debug', 'Plaid_webhook/report_webhook_error:: [1] ');
		$data = filter_forwarded_data($this);
	
		$isSent = $this->_messenger->email(array('emailaddress'=>SYSTEM_REPORTS_EMAIL,
				'emailfrom'=>'no-reply@clout.com',
				'subject'=>'WEBHOOK ERROR: on user id '.$data['user'],
				'details'=>'DETAILS: '.json_encode($data['details'])
		), 'ses');
	
		log_message('debug', 'Plaid_webhook/report_webhook_error:: [2] result '.($isSent? 'SUCCESS': 'FAIL'));
		$this->_logger->add_event(array(
				'user_id'=>(!empty($data['user'])? $data['user']: ''),
				'activity_code'=>'report_webhook_error',
				'result'=>($isSent? 'success': 'fail'),
				'log_details'=>"user_id: ".(!empty($data['user'])? $data['user']: '')
		));
	
		echo $isSent? 'success':'fail';
	}
	
	
	
}

/* End of controller file */