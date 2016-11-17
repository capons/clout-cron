<?php
//require '/var/www/composer/vendor/autoload.php';
require '../../vendor/autoload.php'; //default in models folder

use Pheanstalk\Pheanstalk;


/**
 * This class publishes (adds) jobs to the queue.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 05/31/2016
 */
class _queue_publisher extends CI_Model
{
	# the queue variable
	private $queue;
	
	# constructor
	function create($args)
	{

		log_message('debug', '_queue_publisher/create:: [1] ');
		log_message('debug', '_queue_publisher/create:: [2] args: '.json_encode($args));
		
		$this->config = ['queue' => ['host' => $args['host'] ]];
		$this->queue = $args['queue'];
		$this->client = new Pheanstalk($this->config['queue']['host']);
		log_message('debug', '_queue_publisher/create:: [3] new Pheanstalk instance created');
	}
	
	
	
	# send the job request to the queue (json encoded)
	# this can be anything (string, array or object)
	function send($request)
	{
		log_message('debug', '_queue_publisher/send:: [1] ');
		$jobString = json_encode($request);
		$result = $this->client->useTube($this->queue)->put($jobString); 
		log_message('debug', '_queue_publisher/send:: [2] result '.($result? 'SUCCESS': 'FAIL'));
		
		# log a database record for UI traceability in the system
		log_message('debug', '_queue_publisher/send:: [3] job-details='.json_encode(array(
				'user_id'=>(!empty($request['user_id'])? $request['user_id']: ''),
				'activity_code'=>'_queue_publisher-send',
				'result'=>(!empty($result) && $result? 'success': 'fail'),
				'log_details'=>$jobString
		)));
		
		return $result;
	}
	
	
	
	# add job to queue
	function add_job_to_queue($data)
	{
		log_message('debug', '_queue_publisher/add_job_to_queue:: [1] ');
		$details['job_id'] = (!empty($data['id'])? $data['id']: '');
		$details['job_string'] = (!empty($data['job'])? $data['job']: '');
		$details['job_code'] = (!empty($data['code'])? $data['code']: '');
		$details['user_id'] = (!empty($data['user'])? $data['user']: '');
		$details['schedule'] = (!empty($data['schedule'])? $data['schedule']: '');
		$details['scheduler'] = (!empty($data['scheduler'])? $data['scheduler']: '');
		$details['processor'] = (!empty($data['processor'])? $data['processor']: '');
		$details['publisher'] = (!empty($data['publisher'])? $data['publisher']: 'localhost');
		
		log_message('debug', '_queue_publisher/add_job_to_queue:: [2] details: '.json_encode($details));
		$this->create(array('host'=>$details['publisher'], 'queue'=>$this->get_queue_id($details['job_code']) ));
		return $this->send(array_merge($data, $details));
	}
	
	




	# get the ID of the queue to add this job
	function get_queue_id($queueCode)
	{
		log_message('debug', '_queue_publisher/get_queue_id:: [1] ');
		switch($queueCode){
			case 'scoring':
			case 'save_raw_transactions':
			case 'match_transactions':
			case 'compute':
			case 'update_transaction_statistics':
			case 'report_new_store_scores':
			case 'update_user_network_cache':
			case 'recompute_account_balance_averages':
			case 'update_store_survey_statistics':
			case 'recompute_old_statistics':
			case 'delete_transactions_by_api':
			case 'pull_store_maps':
				$id = SCORING_CRON_QUEUE; break;
				
			default: 
				$id = GENERAL_CRON_QUEUE; break;
		}
		log_message('debug', '_queue_publisher/get_queue_id:: [2] job ('.$queueCode.'): '.$id);
	
		return $id;
	}
	
	
	
}
