<?php
require '/var/www/composer/vendor/autoload.php';
use Pheanstalk\Pheanstalk;


/**
 * This class consumes (takes) jobs from the queue and posts them for processing to rundeck.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 05/31/2016
 */
class _queue_consumer extends CI_Model
{
	# the queue variable
	private $queue;
	

	# Constructor 
	function __construct()
	{
		parent::__construct();
		log_message('debug', '_queue_consumer/__construct:: [1] new _queue_consumer instance created');
	}
	
	
	
	# create instance of Pheanstalk
	function create(array $args)
	{
		log_message('debug', '_queue_consumer/create:: [1] ');
		log_message('debug', '_queue_consumer/create:: [2] args: '.json_encode($args));
		
		$this->config = ['queue' => ['host' => $args['host'] ]];
		$this->queue = $args['queue'];
		$this->client = new Pheanstalk($this->config['queue']['host']);
		log_message('debug', '_queue_consumer/create:: [3] new Pheanstalk instance created');
	}
	
	
	
	
	# listens for jobs coming through the queue
	function listen($limit = 'unlimited')
	{
		log_message('debug', '_queue_consumer/listen:: [1] ');
		# the queue to watch
		$this->client->watch($this->queue);
		$postCount = 0;
		$status = false;
		
		# do this [forever or until reach limit] so that the script is always listening 
		while ($job = $this->client->reserve()) { 
			# decode message sent down the queue
			$message = json_decode($job->getData(), true);
			# process the job and return success or not.
			$status = $this->process($message);
			
			# do this if job is successful
			if($status) $this->client->delete($job);
			# do this if job fails
			else $this->client->delete($job);
			
			$postCount++;
			if($limit != 'unlimited' && $limit >= $postCount) break;
		}
		
		log_message('debug', '_queue_consumer/listen:: [2] listen - END');
		
		return $status;
	}
	
	
	
	
	
	
	# do the actual processing work on the job based on the passed message details
	function process($msg)
	{
		// Do some operation and return true if success or false
		#file_put_contents('/var/www/html/cron/data-crons/test-queue-scripts/'.str_replace(' ','-',$msg).'.txt', "Message pulled from queue - name:{$msg} \n");
		log_message('debug', '_queue_consumer/process:: [1] ');
		
		if(!empty($msg['job_string']) && !empty($msg['user_id']))
		{
			$details['job_id'] = (!empty($msg['job_id'])? $msg['job_id']: 'job-auto-id-'.strtotime('now'));
			$details['job_string'] = $msg['job_string'];
			$details['scheduler'] = $msg['scheduler'];
			$details['processor'] = $msg['processor'];
			$details['schedule'] = !empty($msg['schedule'])? $msg['schedule']: '';
			$logString = json_encode($msg);
			
			# a) process jobs by running them on this server
			if(!empty($msg['parameters'])){
				$result = $this->process_server_jobs($msg);
			}
			# b) send job to rundeck where desired to be visible
			else {
				$this->load->model('_webhook');
				$result = $this->_webhook->send_job_to_rundeck($msg['user_id'], $msg['job_code'], array_merge($msg, $details));
			}
			log_message('debug', '_queue_consumer/process:: [2] job schedule result at '.date('Y-m-d H:i:s').': '.($result? 'SUCCESS': 'FAIL').' details: '.$logString);
		}
		else $result = false;
		
		return $result;
	}
	
	
	
	
	
	
	# get job from queue
	function get_job_from_queue($data)
	{
		log_message('debug', '_queue_consumer/get_job_from_queue:: [1] ');
		$details['queue_code'] = (!empty($data['code'])? $data['code']: '');
		$details['publisher'] = (!empty($data['publisher'])? $data['publisher']: 'localhost');
		$details['limit'] = (!empty($data['limit'])? $data['limit']: 'unlimited');
		
		$this->create(array('host'=>$details['publisher'], 'queue'=>$this->get_queue_id($details['queue_code']) ));
		log_message('debug', '_queue_consumer/get_job_from_queue:: [2] after creation of _queue_consumer object');
		return $this->listen($details['limit']);
	}
	
	
	

	# get the real ID of the queue to add a job
	function get_queue_id($queueCode)
	{
		log_message('debug', '_queue_consumer/get_queue_id:: [1] ');
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
		log_message('debug', '_queue_consumer/get_queue_id:: [2] job ('.$queueCode.'): '.$id);
	
		return $id;
	}
	
	
	
	
	
	# process jobs to be run on this server
	function process_server_jobs($details)
	{
		log_message('debug', '_queue_consumer/process_server_jobs:: [1] ');
		#process based on code
		switch($details['code']){ 
			case 'delayed_query':
				$result = server_curl($details['parameters']['db_server_url'],  
					array('__action'=>$details['parameters']['action'], 
							'return'=>'plain', 
							'query'=>$details['parameters']['query'], 
							'variables'=>$details['parameters']['variables'] 
					));
				break;
				
			case 'basic_curl':	
				$result = server_curl($details['parameters']['db_server_url'], array()); 
				break;
			
			default:
				$result = TRUE;
				break;
		}
		
		log_message('debug', '_queue_consumer/process_server_jobs:: [2] result='.($result? 'SUCCESS': 'FAIL'));
		return $result;
	}
	
	
	
	
}
