<?php
/**
 * Logs events on the system.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/24/2015
 */
class _logger extends CI_Model
{
	
	# Add an event
	function add_event($eventDetails)
	{
		$userId = !empty($eventDetails['user_id'])? $eventDetails['user_id']: '1';
		$parameters['db_server_url'] = BASE_URL.'main/index';
		$parameters['action'] = 'run';
		$parameters['query'] = 'add_event_log';
		$parameters['variables'] = array(
			'user_id'=>(!empty($eventDetails['user_id'])? $eventDetails['user_id']: ''), 
			'activity_code'=>$eventDetails['activity_code'], 
			'result'=>$eventDetails['result'], 
			'uri'=>(!empty($eventDetails['uri'])? $eventDetails['uri']: uri_string()), 
			'log_details'=>$eventDetails['log_details'], 
			'ip_address'=>(!empty($eventDetails['ip_address'])? $eventDetails['ip_address']: $this->input->ip_address())
		);
		
		return server_curl(BASE_URL.'main/index',  array('__action'=>'add_job_to_queue', 
					'return'=>'plain',
					'jobId'=>'j'.$userId.'-'.strtotime('now').'-'.rand(0,1000000),
					'jobUrl'=>'query',
					'userId'=>$userId,
					'jobCode'=>'delayed_query',
					'parameters'=>$parameters
				));
	}
		
}


?>