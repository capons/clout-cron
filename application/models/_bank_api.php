<?php
/**
 * This class generates and formats connections to the bank API. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 09/09/2015
 */
class _bank_api extends CI_Model
{
	
	#TODO: Remove when live
	#WELLSFARGO: 'bank_id'=>'16989' no MFA
	#US BANK: 'bank_id'=>'301' question-based MFA
	#BANK OF AMERICA: 'bank_id'=>'283' question/code-based MFA
	#CHASE: 'bank_id'=>'16818' code-based MFA
	#$credentials = array('user_name'=>'plaid_test', 'password'=>'plaid_good');
	#Plaid code for test: 1234
	#print_r($data); echo "==="; print_r($_POST);
	#=====================================
		
		
	# Connect and process access to the API 
	function connect($credentials, $postData, $userId)
	{
		
		log_message('debug', '_bank_api/connect');
		log_message('debug', '_bank_api/connect:: [1] credentials='.json_encode($credentials).' postData='.json_encode($postData).' userId='.$userId);
		
		#1. Initiate login process
		$response = $this->login_new_user_into_api(array_merge($credentials, $postData));
		$response = !empty($response)? $response: array();
		$userId = !empty($postData['user_id'])? $postData['user_id']: $userId;
		$bankId = !empty($postData['bank_id'])? $postData['bank_id']: '';
		
		#Attempt getting the bank ID
		$bankCode = !empty($postData['bank_id'])? $this->get_bank_code($postData['bank_id']): '';
		
		#Record user access token for future access if it is not already recorded
		if(!empty($response['access_token']) && !empty($bankCode) && !empty($bankId)) 
		{
			# Add the new plaid access details
			$tokenRecordId = $this->_query_reader->add_data('add_access_token', array('user_id'=>$userId, 'bank_code'=>$bankCode, 'bank_id'=>$bankId, 'user_email'=>$postData['email_address'], 'access_token'=>$response['access_token'], 'is_active'=>'Y'));
			
			# Log initial access to API
			$this->_logger->add_event(array(
				'user_id'=>$userId, 
				'activity_code'=>'plaid_api_login', 
				'result'=>(!empty($tokenRecordId)? 'SUCCESS':'FAIL'), 
				'log_details'=>"user_email=".$postData['email_address']."|user_id=".$userId."|bank_code=".$bankCode
			));
		}
		else $response['access_token'] = !empty($postData['email_address'])? $this->get_user_access_token($postData['email_address'], $bankCode): '';
		
		
		#2 Login was successful
		if(array_key_exists('accounts', $response))
		{
			$response['action'] = 'success';
			
			# Schedule the data processing for right now (repeat_code=never) 
			# which will be replaced with a different job with (repeat_code=end_of_day) when successfully run
			#$result = $this->_query_reader->run('add_to_cron_schedule', array('cron_value'=>'user='.$userId.',bankcode='.$bankCode.',bankid='.$bankId, 'activity_code'=>'pull_all_user_transactions', 'job_type'=>'transaction_cron', 'repeat_code'=>'now'));
			$this->load->model('_queue_publisher');
			# schedule the matching job in the queue
			$result = $this->_queue_publisher->add_job_to_queue(array(
				'id'=>'j'.$userId.'-'.strtotime('now'),
				'job'=>'scoring_cron/save_raw_transactions/user/'.$userId.'/token/'.$response['access_token'].'/is_new/yes',
				'code'=>'save_raw_transactions',
				'user'=>$userId
			));
			
			
			# record first time user data
			if($result){
				$this->load->model('_importer');
				$cronResponse2 = $this->_importer->record_bank_connection($userId, $bankId, $response['accounts']);
				$result = (!empty($cronResponse2['result']) && $cronResponse2['result'] == 'SUCCESS');
			}
			
			# Record log of cron schedule - successful import
			$this->_logger->add_event(array(
				'user_id'=>$userId, 
				'activity_code'=>'plaid_api_response', 
				'result'=>($result? 'SUCCESS':'FAIL'), 
				'log_details'=>"user_id=".$userId."|bank_code=".$bankCode."|bank_id=".$bankId
			));
			
			
		} 
		#There was an error in the process - log this
		else if(array_key_exists('code', $response))
		{
			$this->_logger->add_event(array(
				'user_id'=>$userId, 
				'activity_code'=>'plaid_api_access_fail', 
				'result'=>'FAIL', 
				'log_details'=>"user_id=".$userId."|bank_code=".$bankCode."|bank_id=".$bankId."|messsage=".(!empty($response['resolve'])? $response['resolve']: '')
			));
		}
		log_message('debug', '_bank_api/connect:: [2] response='.json_encode($response));
		
		return $response;
	}
	
	
	
	
	
	# Log into the user's online bank account though the Plaid API
	function login_new_user_into_api($loginData) 
	{
		log_message('debug', '_bank_api/login_new_user_into_api');
		log_message('debug', '_bank_api/login_new_user_into_api:: [1] loginData='.json_encode($loginData));
		
		# The unique code for the bank organization with the API
		$bankCode = $this->get_bank_code($loginData['bank_id']);
		
		# Check if user is already registered with the API
		$plaidToken = $this->get_user_access_token($loginData['email_address'], $bankCode);
		
		# User already registered
		if(!empty($plaidToken) && !empty($loginData['mfa']))
		{
			$dataArray = array(
				'client_id'=>PLAID_CLIENT_ID, 
				'secret'=>PLAID_SECRET, 
				'type'=>$bankCode, 
      			'access_token'=>$plaidToken
			);
			
			#If requires an access token sent to the device
			if(!empty($loginData['send_method'])) $dataArray['options'] = array('send_method'=>array('type'=>$loginData['send_method']));
			else $dataArray['mfa'] = $loginData['mfa'];
			
			$url = PLAID_CONNECTION_URL.'/connect/step';
		}
		#New user with the API
		else
		{
			#Prepare data for running on API
			$dataArray = array(
				'client_id'=>PLAID_CLIENT_ID, 
				'secret'=>PLAID_SECRET, 
				'credentials'=>array('username'=>$loginData['user_name'],'password'=>$loginData['password']), 
      			'type'=>$bankCode,
				'email'=>$loginData['email_address'],
				'options'=>array('pending'=>true, 'list'=>true, 'login'=>true, 'webhook'=>BASE_URL.'h/'.encrypt_value($loginData['user_id']))
			);
			#If requires a pin number too, add it
			if(!empty($loginData['bank_pin'])) $dataArray['credentials']['pin'] = $loginData['bank_pin'];
			
			$url = PLAID_CONNECTION_URL.'/connect';
		}
		log_message('debug', '_bank_api/login_new_user_into_api:: [2] dataArray='.json_encode($dataArray));
		
		return run_on_api($url, $dataArray);
	}
	
	
	
	
	
	
	
	
	#Function to determine the code of the bank to return 
	function get_bank_code($bankId)
	{
		log_message('debug', '_bank_api/get_bank_code');
		log_message('debug', '_bank_api/get_bank_code:: [1] bankId='.$bankId);
		
		$bankDetails = $this->_query_reader->get_row_as_array('get_bank_details', array('bank_id'=>$bankId, 'field_list'=>'institution_code' ));
		log_message('debug', '_bank_api/get_bank_code:: [2] bankDetails='.(!empty($bankDetails['institution_code'])? $bankDetails['institution_code']: ''));
		
		return (!empty($bankDetails['institution_code'])? $bankDetails['institution_code']: '');
	}
	
	
	

	
	#Get the user's access token
	function get_user_access_token($userEmail, $bankCode)
	{
		log_message('debug', '_bank_api/get_user_access_token');
		log_message('debug', '_bank_api/get_user_access_token:: [1] userEmail='.$userEmail.' bankCode='.$bankCode);
		
		$token = $this->_query_reader->get_row_as_array('get_access_token', array('user_email'=>$userEmail, 'bank_code'=>$bankCode));
		log_message('debug', '_bank_api/get_bank_code:: [2] bankDetails='.(!empty($token['access_token'])? $token['access_token']: ''));
		
		return !empty($token['access_token'])? $token['access_token']: '';
	}
	
	
	
	
	
	
	
	# delete the plaid API accounts for the given users
	function delete_api_accounts($userIds)
	{
		log_message('debug', '_bank_api/delete_api_accounts');
		log_message('debug', '_bank_api/delete_api_accounts:: [1] userIds='.json_encode($userIds));
		
		# Collect the user's registered API tokens
		$tokens = $this->_query_reader->get_single_column_as_array('get_user_access_tokens',  'access_token', array('user_ids'=>implode("','",$userIds) ));
		
		# Remove the account from the API
		if(!empty($tokens)){
			foreach($tokens AS $accessToken) {
				$data = array(
					'client_id'=>PLAID_CLIENT_ID, 
					'secret'=>PLAID_SECRET, 
					'access_token'=>$accessToken
				);
			
				$ch = curl_init();
				$string = http_build_query($data);
				curl_setopt($ch, CURLOPT_URL, PLAID_CONNECTION_URL.'/connect?'.$string); 
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, '10000');
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
				#Run the value as passed
				$result = curl_exec($ch);
			
				if(curl_errno($ch)) $msg .= curl_error($ch)." | "; 
				#Close the channel
				curl_close($ch);
			}
		}
		log_message('debug', '_bank_api/delete_api_accounts:: [1] tokens='.!empty($tokens));
		
		return !empty($tokens);
	}
	
	
	
	
}


?>