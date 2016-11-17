<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles running cron jobs related to messages.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 12/14/2015
 */

class Message_cron extends CI_Controller 
{
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
		$this->load->model('_cron');
	}
	
	
	
	# activate more messages for sending - for restricted users
	function activate_more_messages()
	{
		log_message('debug', 'Message_cron/activate_more_messages');
		$data = filter_forwarded_data($this);
		# do not proceed if the stop sending rule is activated
		if(rule_check($this,'stop_all_invite_sending')) exit;
		
		$response = server_curl(MESSAGE_SERVER_URL, array('__action'=>'activate_more_messages'));
		
		#Mark the cron job with appropriate updates
		if(!empty($data['jobid']) && $response['reason'] != 'no-users')
		{
			$activateResult = ($response['result'] == 'SUCCESS');
			# run time
			$runTime = @date('Y-m-d H:i:s');
			$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>($activateResult? 'success': 'fail'), 'id'=>$data['jobid']));	
			if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"when_ran", 'field_value'=>$runTime, 'id'=>$data['jobid']));
			
			#Log cron results
			$jobDetails['user_id'] = 'system';
			$jobDetails['job_type'] = 'message_cron';
			$jobDetails['job_code'] = 'activate_more_messages';
			$jobDetails['result'] = (!empty($activateResult) && $activateResult)? 'success': 'fail';
			$jobDetails['job_details'] = "run_time=".$runTime."|run_count=".$response['count'];
			
			$this->_cron->update_status($data['jobid'], $jobDetails);
		}
	}
	
	
	
	# send first reminder
	function send_first_reminder()
	{
		log_message('debug', 'Message_cron/send_first_reminder');
		
		$data = filter_forwarded_data($this);
		# do not proceed if the stop sending rule is activated
		if(rule_check($this,'stop_all_invite_sending')) exit;
		
		$response = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send_first_reminder'));
		
		# mark the cron job with appropriate updates
		if(!empty($data['jobid']) && $response['reason'] != 'no-users')
		{
			$activateResult = ($response['result'] == 'SUCCESS');
			# run time
			$runTime = @date('Y-m-d H:i:s');
			$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>($activateResult? 'success': 'fail'), 'id'=>$data['jobid']));	
			if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"when_ran", 'field_value'=>$runTime, 'id'=>$data['jobid']));
			
			#Log cron results
			$jobDetails['user_id'] = 'system';
			$jobDetails['job_type'] = 'message_cron';
			$jobDetails['job_code'] = 'send_first_reminder';
			$jobDetails['result'] = (!empty($activateResult) && $activateResult)? 'success': 'fail';
			$jobDetails['job_details'] = "run_time=".$runTime."|run_count=".$response['count'];
			
			$this->_cron->update_status($data['jobid'], $jobDetails);
		}
	}
	
	
	
	# send second reminder
	function send_second_reminder()
	{
		log_message('debug', 'Message_cron/send_second_reminder');
		
		$data = filter_forwarded_data($this);
		# do not proceed if the stop sending rule is activated
		if(rule_check($this,'stop_all_invite_sending')) exit;
		
		$response = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send_second_reminder'));
		
		# mark the cron job with appropriate updates
		if(!empty($data['jobid']) && $response['reason'] != 'no-users')
		{
			$activateResult = ($response['result'] == 'SUCCESS');
			# run time
			$runTime = @date('Y-m-d H:i:s');
			$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>($activateResult? 'success': 'fail'), 'id'=>$data['jobid']));	
			if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"when_ran", 'field_value'=>$runTime, 'id'=>$data['jobid']));
			
			#Log cron results
			$jobDetails['user_id'] = 'system';
			$jobDetails['job_type'] = 'message_cron';
			$jobDetails['job_code'] = 'send_second_reminder';
			$jobDetails['result'] = (!empty($activateResult) && $activateResult)? 'success': 'fail';
			$jobDetails['job_details'] = "run_time=".$runTime."|run_count=".$response['count'];
			
			$this->_cron->update_status($data['jobid'], $jobDetails);
		}
	}
	
	
	
	
	
	# send pending invitations
	function send_pending_invitations()
	{ 
		log_message('debug', 'Message_cron/send_pending_invitations');
		
		$data = filter_forwarded_data($this);
		
		# do not proceed if the stop sending rule is activated
		if(rule_check($this,'stop_all_invite_sending')) exit;
		
		$response = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send_pending_invitations'));
		
		#Mark the cron job with appropriate updates
		if(!empty($data['jobid']) && !empty($response) && $response['reason'] != 'no-users')
		{
			$sendResult = ($response['result'] == 'SUCCESS');
			
			# run time
			$runTime = @date('Y-m-d H:i:s');
			$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>($sendResult? 'success': 'fail'), 'id'=>$data['jobid']));	
			if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"when_ran", 'field_value'=>$runTime, 'id'=>$data['jobid']));
			
			#Log cron results
			$jobDetails['user_id'] = 'system';
			$jobDetails['job_type'] = 'message_cron';
			$jobDetails['job_code'] = 'send_pending_invitations';
			$jobDetails['result'] = (!empty($sendResult) && $sendResult)? 'success': 'fail';
			$jobDetails['job_details'] = (!empty($response['codes'])? 'messages='.implode(',',$response['codes']).'|': '').(!empty($response['reason'])? 'reason='.$response['reason'].'|': '')."run_time=".$runTime."|run_count=".$response['count'];
			
			$this->_cron->update_status($data['jobid'], $jobDetails);
		}
	}
	
	
	
	
	
	
	# send pending messages
	function send_pending_messages()
	{ 
		log_message('debug', 'Message_cron/send_pending_messages');
		
		$data = filter_forwarded_data($this);
		$response = server_curl(MESSAGE_SERVER_URL, array('__action'=>'send_pending_messages'));
		
		#Mark the cron job with appropriate updates
		if(!empty($data['jobid']) && !empty($list))
		{
			# run time
			$runTime = @date('Y-m-d H:i:s');
			$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"last_result", 'field_value'=>($sendResult? 'success': 'fail'), 'id'=>$data['jobid']));	
			if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>"when_ran", 'field_value'=>$runTime, 'id'=>$data['jobid']));
			
			#Log cron results
			$jobDetails['user_id'] = 'system';
			$jobDetails['job_type'] = 'message_cron';
			$jobDetails['job_code'] = 'send_pending_messages';
			$jobDetails['result'] = (!empty($sendResult) && $sendResult)? 'success': 'fail';
			$jobDetails['job_details'] = (!empty($reason)? 'reason='.$reason.'|': '')."run_time=".$runTime."|run_count=".$runCount;
			
			$this->_cron->update_status($data['jobid'], $jobDetails);
		}
	}
	
	
	
	
	
	
	# TEST FUNCTION. REMOVE ON LIVE
	function test_ses_send()
	{
		$this->load->model('_messenger');
		$isSent = $this->_messenger->send_direct_email('6786442425@mms.att.net', '', array(
				'code'=>'new_store_scores', 
				'firstname'=>'Aloysious', 
				'newstorescores'=>'Apple (560), Green Heart (670)..',
				'messageid'=>'24890234890'
			));
			
		echo $isSent? "SENT":"FAIL";
	}
	
}

/* End of controller file */