<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles running cron jobs for the system.
 *
 * @author Al Zziwa <azziwa@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/24/2015
 */

class Cron extends CI_Controller 
{
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
	}
	
	
	# Update the query cache
	function update_query_cache()
	{
		# DISABLE IF IN DEV TO SEE IMMEDIATE CHANGES IN YOUR QUERIES
		log_message('debug', 'Cron/update_query_cache');
		# this cron server
		if(ENABLE_QUERY_CACHE) $this->_query_reader->load_queries_into_cache();
		# IAM server
		server_curl(IAM_SERVER_URL, array('__action'=>'load_queries_into_cache' ));
		# Message server
		server_curl(MESSAGE_SERVER_URL, array('__action'=>'load_queries_into_cache' ));
		# Mysql server
		server_curl(MYSQL_SERVER_URL, array('__action'=>'load_queries_into_cache' ));
		# Security server
		server_curl(SECURITY_SERVER_URL, array('__action'=>'load_queries_into_cache' ));
	}
	
	
	# Update the message cache
	function update_message_cache()
	{
		log_message('debug', 'Cron/update_message_cache');
		server_curl(MESSAGE_SERVER_URL, array('__action'=>'load_messages_into_cache' ));
	}
	
	
	
	# Fetch and run all system jobs
	function fetch_and_run_sys_jobs()
	{
		log_message('debug', 'Cron/fetch_and_run_sys_jobs');
		# delay execution for 10 seconds to avoid clashing with per_minute cronjobs.
		sleep(10);
		
		$data = filter_forwarded_data($this);
		
		$this->load->model('_cron');
		$result = $this->_cron->run_available_jobs();
		
		#Log the results from the run if not successful
		if(!empty($data['jobid']) && !$result['boolean']) $this->_cron->update_status($data['jobid'], array(
			'job_type'=>'cron', 
			'job_code'=>'fetch_and_run_sys_jobs', 
			'result'=>($result['boolean']? 'success': 'fail'),
			'job_details'=>$result
		)); 
	}
	
	
	
	# clear all cron jobs
	function clear_jobs()
	{
		log_message('debug', 'Cron/clear_jobs');
		$this->load->model('_cron');
		$result = $this->_cron->clear_server_jobs();
		
		$this->_cron->log_cron_job_results(array(
			'job_type'=>'cron', 
			'job_code'=>'clear_all_logs', 
			'result'=>($result? 'success': 'fail'),
			'job_details'=>'All jobs cleared.'
		));
	}
	
	
	
	
	# Backup the cron log
	function backup_cron_log()
	{
		$data = filter_forwarded_data($this);
		
		$this->load->model('_cron');
		$result = $this->_cron->backup_cron_log();
		
		#Log the results from the run
		if(!empty($data['jobid'])) $this->_cron->update_status($data['jobid'], array(
			'job_type'=>'cron', 
			'job_code'=>'backup_cron_log', 
			'result'=>($result['boolean']? 'success': 'fail'),
			'job_details'=>$result
		)); 
	}
	
	
	
	
}

/* End of controller file */