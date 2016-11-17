<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles queuing jobs on server.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 05/19/2015
 */

class Queue extends CI_Controller 
{
	# add job to queue to be executed
	# job url in the format class-name__function-name__variable__value 
	# e.g., plaid_webhook__report_webhook_error__user__23 which is equivalent to running 
	# (BASE_URL)/plaid_webhook/report_webhook_error/user/23
	#
	# how to call:
	# (BASE_URL)/queue/add_job/job/(job url)[/id/(job id)][/code/(job code)][/project/(scheduler project)][/user/(user id)][/scheduler/(rundeck OR other scheduler server address)]
	# (continued..) [/processor/(processing server address)][/publisher/(publishing server address i.e. host of the queue)]
	# (continued..) [/schedule/(schedule-type e.g., end-of-day, per-minute, once)]
	function add_job()
	{
		log_message('debug', 'Queue/add_job:: [1] ');
		$data = filter_forwarded_data($this);
		$this->load->model('_queue_publisher');
		log_message('debug', 'Queue/add_job:: [2] parameters '.json_encode($data));
		
		# add job to the queue
		$data['job'] = !empty($data['job'])?str_replace('__', '/', $data['job']):'';
		$result = $this->_queue_publisher->add_job_to_queue($data);
		
		log_message('debug', 'Queue/add_job:: [3] result '.($result? 'SUCCESS': 'FAIL'));
		return $result;
	}
	
	
	
	
	# schedule the next job on the queue for running
	# (BASE_URL)/queue/schedule_next_job[/code/(queue code)][/limit/(max number of jobs to schedule)]
	# (continued..) [/publisher/(publishing server address i.e. host of the queue)]
	function schedule_next_job()
	{
		log_message('debug', 'Queue/schedule_next_job:: [1] ');
		$data = filter_forwarded_data($this);
		$this->load->model('_queue_consumer');
		log_message('debug', 'Queue/schedule_next_job:: [2] parameters '.json_encode($data));
		
		# get job from queue and process it
		$result = $this->_queue_consumer->get_job_from_queue($data);
		
		log_message('debug', 'Queue/schedule_next_job:: [3] listen exited with result: '.($result? 'SUCCESS': 'FAIL'));
		return $result;
	}
	
	
	
	
	
}

/* End of controller file */