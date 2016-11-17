<?php
/**
 * This class manages system cron jobs.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/24/2015
 */
class _cron extends CI_Model
{
	
	#Function to run available cron jobs
	public function run_available_jobs($runtime='', $restrictions=array())
	{
		log_message('debug', '_cron/run_available_jobs');
		log_message('debug', '_cron/run_available_jobs:: [1] runtime='.$runtime.' restrictions='.json_encode($restrictions));
		
		$runtime = !empty($runtime)? $runtime: date('Y-m-d H:i:s');
		
		#Note the time the cron batch is being updated - hence the start of the new cron job batch run - if any
		$batchMarker = 	PHP_EOL.
					 	PHP_EOL."----------------------------------------------------".
						PHP_EOL."BATCH TIME: ".$runtime.
						PHP_EOL."----------------------------------------------------".
						PHP_EOL;
						
		
		# if totally refreshing the cronjobs clear all user cron jobs
		if(!empty($restrictions['scope']) && $restrictions['scope'] == 'all') $this->clear_server_jobs();
		
		
		$crons = $this->get_crons_to_run($runtime, $restrictions); 
		$cronFile = !empty($restrictions['cron_file'])? $restrictions['cron_file']: CRON_FILE;
		
		# add the cron jobs to the cron file for backup
		file_put_contents($cronFile, $this->generate_job_list_for_crontab($crons, $runtime));
		
		# only update the server crontab if you are in the DEFAULT_CRON Installation
		if(CRON_HOME_URL == DEFAULT_CRON_HOME_URL) 
		{
			# combine the cron job files based on the defined installations
			$cronInstallations = unserialize(CRON_INSTALLATIONS);
			$cronFileContents = "";
			foreach($cronInstallations AS $i=>$fileLocation)
			{
				$cronFileContents = file_get_contents($fileLocation.CRON_FILE_NAME); 
				# report if new jobs are to be added
				if(empty($newJobs) && !empty($cronFileContents)) $newJobs = TRUE;
				
				if($i > 0) file_put_contents(GLOBAL_CRON_FILE, $cronFileContents, FILE_APPEND); 
				else file_put_contents(GLOBAL_CRON_FILE, $cronFileContents);
			}
			
			if(!empty($newJobs)) {
				file_put_contents(CRON_FILE_LOG, $batchMarker, FILE_APPEND);
				# add the collected cron jobs for all virtual servers to the actual physical server
				$addResult = $this->add_jobs_to_crontab($restrictions);
				
				# report any errors encountered while adding the cron jobs
				if(!empty($addResult['errors'])) {
					file_put_contents(CRON_FILE_LOG, PHP_EOL.'ERRORS DURING ADDITION: '.PHP_EOL.$addResult['errors'].PHP_EOL, FILE_APPEND);
				}
				
				$result = $addResult['boolean'];
			}
		}
		
		# make it true by default for those servers that do not actually update the cronjobs or there are no new jobs
		if(empty($result)) $result = TRUE;
		log_message('debug', '_cron/run_available_jobs:: [1] return='.json_encode(array('boolean'=>$result, 'total'=>count($crons), 'runtime'=>$runtime)));
		
		return array('boolean'=>$result, 'total'=>count($crons), 'runtime'=>$runtime); 
	}
	
	
	
	
	
	# Add cron jobs to the the server for running
	private function add_jobs_to_crontab($restrictions)
	{
		log_message('debug', '_cron/add_jobs_to_crontab');
		log_message('debug', '_cron/add_jobs_to_crontab:: [1] restrictions='.json_encode($restrictions));
		
		# get the jobs into an array
		$rawJobs = file(GLOBAL_CRON_FILE, FILE_IGNORE_NEW_LINES);
		
		# process each job to determine where to put it
		$errors = $success = array();
		foreach($rawJobs AS $job){
			# if not a comment or invalid job (without output)
			if(strpos($job, '>>') != ''){
				$parts = explode(' ', $job); # our interest are the first five parts (time schedule)
				$runUser = $this->get_cron_user(array_slice($parts, 0, 5));
				$runResult = $this->add_job_to_server($job, $runUser, $restrictions);
				
				# record an error if addition failed
				if(!$runResult) array_push($errors, "ERROR: Adding to user:".$runUser." job_details:".$job);
				else array_push($success, "user:".$runUser." job_details:".$job);
			}
		}
		log_message('debug', '_cron/add_jobs_to_crontab:: [2] return='.json_encode(array('boolean'=>!empty($success), 'errors'=>(!empty($errors)? implode(PHP_EOL, $errors): ''))));
		
		return array('boolean'=>!empty($success), 'errors'=>(!empty($errors)? implode(PHP_EOL, $errors): ''));
	}
	
	
	
	# add a cron job to the server
	public function add_job_to_server($jobString, $runUser='', $restrictions=array())
	{
		log_message('debug', '_cron/add_job_to_server');
		log_message('debug', '_cron/add_job_to_server:: [1] jobString='.$jobString.' runUser='.$runUser.' restrictions='.json_encode($restrictions));
		
		# if only the job ID is given
		if(substr($jobString,0,1) == '<' && substr($jobString,-1) == '>'){
			$job = $this->_query_reader->get_row_as_array('get_cron_schedules', array('is_done'=>'N', 'extra_conditions'=>" AND id='".substr($jobString, 1, -1)."' ", 'limit_text'=>' LIMIT 1'));
			$jobString = (!empty($job)? $this->generate_job_string_from_db_record($job): '');
		}
		
		# run user
		if(empty($runUser)){
			$parts = explode(' ', $jobString); # our interest are the first five parts (time schedule)
			$runUser = $this->get_cron_user(array_slice($parts, 0, 5));
		}
		echo PHP_EOL.'RUN USER: '.$runUser;
		# if a crontab is empty and the system is being totally refreshed, add the mailto line
		$crontabContents = shell_exec('sudo crontab -u '.$runUser.' -l');
		if(empty($crontabContents) && !empty($restrictions['scope']) && $restrictions['scope'] == 'all') {
			$jobString = PHP_EOL."MAILTO=".SITE_ADMIN_MAIL.PHP_EOL.$jobString;
		}
		echo PHP_EOL.PHP_EOL.'CRON TAB CONTENTS: '.$crontabContents;
		
		# definition:
		# [get cron jobs from file as one string] | [search for unique part of new job in this string] || [IF NOT FOUND: append new job | [apply new full job string to user's cron file] 
		# sudo crontab -u cron-user -l | grep -q "unique-part-of-job-string" || (sudo crontab -u cron-user -l ; echo "full-job-string") | sudo crontab -u cron-user - 
		echo PHP_EOL.'sudo crontab -u '.$runUser.' -l | grep -q "'.get_string_between($jobString, PHP_LOCATION, '>>').'" || (sudo crontab -u '.$runUser.' -l ; echo "'.$jobString.'") | sudo crontab -u '.$runUser.' - ';
		$response = shell_exec('sudo crontab -u '.$runUser.' -l | grep -q "'.get_string_between($jobString, PHP_LOCATION, '>>').'" || (sudo crontab -u '.$runUser.' -l ; echo "'.$jobString.'") | sudo crontab -u '.$runUser.' - ');
		log_message('debug', '_cron/add_job_to_server:: [2] response'.json_encode($response));
		echo PHP_EOL.'RESPONSE '.$response;
		return empty($response);
	}
	
	
	
	# remove a cron job from a server user
	public function remove_job_from_server($jobString, $runUser='')
	{
		log_message('debug', '_cron/remove_job_from_server');
		log_message('debug', '_cron/remove_job_from_server:: [1] jobString='.$jobString.' runUser='.$runUser);
		
		# if only the job ID is given
		if(substr($jobString,0,1) == '<' && substr($jobString,-1) == '>'){
			$job = $this->_query_reader->get_row_as_array('get_cron_schedules', array('is_done'=>'N', 'extra_conditions'=>" AND id='".substr($jobString, 1, -1)."' ", 'limit_text'=>' LIMIT 1'));
			$jobString = (!empty($job)? $this->generate_job_string_from_db_record($job): '');
		}
		
		if(empty($runUser)) {
			$parts = explode(' ', $jobString); # our interest are the first five parts (time schedule)
			$runUser = $this->get_cron_user(array_slice($parts, 0, 5));
		}
		
		$runResult = !empty($jobString) && !empty($runUser)? shell_exec("sudo crontab -u ".$runUser." -l | grep -v '".get_string_between($jobString, PHP_LOCATION, '>>')."' | sudo crontab -u ".$runUser." - "): 'FALSE';
		
		log_message('debug', '_cron/remove_job_from_server:: [1] runResult='.json_encode($runResult));
		
		return empty($runResult);
	}
	
	
	
	
	# clear all jobs on the server for the system cron users
	public function clear_server_jobs()
	{
		log_message('debug', '_cron/clear_server_jobs');
		
		$result[0] = shell_exec("sudo crontab -u scheduled-weekday ".CRON_HOME_URL."clear-cron.action | sudo crontab -u scheduled-weekday -");
		
		$result[1] = shell_exec("sudo crontab -u scheduled-month ".CRON_HOME_URL."clear-cron.action | sudo crontab -u scheduled-month -");
		
		$result[2] = shell_exec("sudo crontab -u scheduled-day ".CRON_HOME_URL."clear-cron.action | sudo crontab -u scheduled-day -");
		
		$result[3] = shell_exec("sudo crontab -u scheduled-hour ".CRON_HOME_URL."clear-cron.action | sudo crontab -u scheduled-hour -");
		
		$result[4] = shell_exec("sudo crontab -u scheduled-minute ".CRON_HOME_URL."clear-cron.action | sudo crontab -u scheduled-minute -");
		
		$result[5] = shell_exec("sudo crontab -u per-minute ".CRON_HOME_URL."clear-cron.action | sudo crontab -u per-minute -");
		
		log_message('debug', '_cron/clear_server_jobs:: [1] array='.json_encode(array(empty($result[0]), empty($result[1]), empty($result[2]), empty($result[3]), empty($result[4]), empty($result[5]) )));
		return get_decision(array(empty($result[0]), empty($result[1]), empty($result[2]), empty($result[3]), empty($result[4]), empty($result[5]) ));
	}
	
	
	
	
	# get the server user to whom the cron job will be assigned
	private function get_cron_user($timeParts)
	{
		log_message('debug', '_cron/get_cron_user');
		log_message('debug', '_cron/get_cron_user:: [1] timeParts='.json_encode($timeParts));
		
		if(count($timeParts) == 5){
			if($timeParts[4] != '*') $user = 'scheduled-weekday';
			else if($timeParts[3] != '*') $user = 'scheduled-month';
			else if($timeParts[2] != '*') $user = 'scheduled-day';
			else if($timeParts[1] != '*') $user = 'scheduled-hour';
			else if($timeParts[0] != '*' && $timeParts[0] != '*/1') $user = 'scheduled-minute';
			else $user = 'per-minute';
		}
		else $user = 'per-minute';
		log_message('debug', '_cron/get_cron_user:: [2] user='.$user);
		
		return $user;
	}
	
	
	
	
	private function generate_job_list_for_crontab($cronjobs, $runtime)
	{
		log_message('debug', '_cron/generate_job_list_for_crontab');
		log_message('debug', '_cron/generate_job_list_for_crontab:: [1] cronjobs='.json_encode($cronjobs).' runtime='.$runtime);
		
		$cronString="";
		#Fill the cron file with the jobs to run
		foreach($cronjobs AS $job) $cronString .= $this->generate_job_string_from_db_record($job, $runtime);
		log_message('debug', '_cron/generate_job_list_for_crontab:: [2] return='.(!empty($cronString)? $cronString.PHP_EOL: ''));
		
		return (!empty($cronString)? $cronString.PHP_EOL: '');
	}
	
	
	
	# generate a job string from a cron job database record
	public function generate_job_string_from_db_record($job, $runtime='')
	{
		log_message('debug', '_cron/generate_job_string_from_db_record');
		log_message('debug', '_cron/generate_job_string_from_db_record:: [1] job='.json_encode($job).' runtime='.$runtime);
		
		$job = $this->format_cron_run_url($job);
		$runtime = !empty($runtime)? $runtime: date('Y-m-d H:i:s');
		
		return $this->get_time_placements($job, $runtime).' '.PHP_LOCATION.' '.CRON_HOME_URL.'index.php '.$job['job_type'].' '.$job['run_url'].' >> '.CRON_FILE_LOG.PHP_EOL;
	}
	
	
	
	
	# Get cron jobs to run at the passed time
	public function get_crons_to_run($runtime='', $restrictions=array())
	{
		log_message('debug', '_cron/get_crons_to_run');
		log_message('debug', '_cron/get_crons_to_run:: [1] runtime='.$runtime.' restrictions='.json_encode($restrictions));
		
		$readyCrons = array();
		
		# if all jobs are required, then do not get only those scheduled
		if(!empty($restrictions['scope']) && $restrictions['scope'] == 'all') $conditions = "";
		else $conditions = " AND is_scheduled='N' ";
		
		# Get the cron list
		$cronList = $this->_query_reader->get_list('get_cron_schedules', array('is_done'=>'N', 'extra_conditions'=>$conditions, 'limit_text'=>''));
		
		# Format query ready for running
		foreach($cronList AS $key=>$cron) $readyCrons[$key] = $this->format_cron_run_url($cron); 
		log_message('debug', '_cron/get_crons_to_run:: [2] readyCrons='.json_encode($readyCrons));
		
		return $readyCrons;
	}
	
	
	
	# format the run url to the platform format if not included in the database cron array
	public function format_cron_run_url($cron)
	{
		log_message('debug', '_cron/format_cron_run_url');
		log_message('debug', '_cron/format_cron_run_url:: [1] cron='.json_encode($cron));
		
		if(empty($cron['run_url'])) $cron['run_url'] = $cron['activity_code'].(!empty($cron['cron_value'])? '/'.str_replace(',', '/', str_replace('=', '/', $cron['cron_value'])) :'')."/jobid/".$cron['id']; 
		
		return $cron;
	}
	
	
	
	#Get the time format for passing to the queries
	public function get_time_placements($job, $runtime)
	{
		log_message('debug', '_cron/get_time_placements');
		log_message('debug', '_cron/get_time_placements:: [1] job='.json_encode($job).' runtime='.$runtime);
		
		$time = "* * * * * ";
		
		switch($job['repeat_code'])
		{
				case 'never':
					$timestamp = strtotime($runtime);
					$time = (date('i',$timestamp)+0)." ".date('G',$timestamp)." ".date('j',$timestamp)." ".date('n',$timestamp)." ".date('w',$timestamp);
				break;
				
				case 'every_half_hour':
					$time = "0,30 * * * * ";
				break;
				
				case 'every_hour':
					$time = "0 * * * * ";
				break;
				
				case 'end_of_day':
					$time = "0 0 * * * ";
				break;
				
				case 'end_of_week':
					$time = "0 0 * * 0 ";
				break;
				
				case 'end_of_month':
					$time = "0 0 * ".date("t", strtotime($runtime))." * ";
				break;
				
				case 'default':
					$timeParts = explode(' ', CRON_REFRESH_PERIOD);
					
					$time = (strpos($timeParts[1], 'minute') !== FALSE? '*/'.$timeParts[0]: '*')." ".
					(strpos($timeParts[1], 'hour') !== FALSE? '*/'.$timeParts[0]: '*')." ".
					(strpos($timeParts[1], 'day of month') !== FALSE? '*/'.preg_replace("/[^0-9]/","",$timeParts[0]): '*').
					" * ". #Run every year :-) - Not configurable [for now]
					(strpos($timeParts[1], 'day of week') !== FALSE? '*/'.preg_replace("/[^0-9]/","",$timeParts[0]): '*');
				break;
				
				
				# handle special time instructions here
				default:
					# e.g., every_3_minutes
					if(strpos($job['repeat_code'], 'every_') !== FALSE && strpos($job['repeat_code'], '_minute') !== FALSE){
						$codeParts = explode('_', $job['repeat_code']);
						if(!empty($codeParts[1]) && is_numeric($codeParts[1])) {
							$time = ($codeParts[1] == 1)? "* * * * * ": "*/".$codeParts[1]." * * * * ";
						}
					}
				break;
		}
		
		log_message('debug', '_cron/get_time_placements:: [2] time='.$time);
		
		return $time;
	}
	
	
	
	
	
	
	# Update the status of a cron job after it has been run
	public function update_status($jobId, $jobDetails)
	{
		log_message('debug', '_cron/update_status');
		log_message('debug', '_cron/update_status:: [1] jobId='.$jobId.' jobDetails='.json_encode($jobDetails));
		
		$runtime = date('Y-m-d H:i:s');
		$cron = $this->_query_reader->get_row_as_array('get_cron_schedules', array('is_done'=>'N', 'extra_conditions'=>" AND id='".$jobId."' ", 'limit_text'=>' LIMIT 1'));
		
		#If the repeat code is never, mark the cron as done
		if(!empty($cron['repeat_code']) && $cron['repeat_code'] == 'never' && $jobDetails['result'] == 'success') {
			$result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>'is_done', 'field_value'=>'Y', 'id'=>$jobId));
			
			# remove job from server schedule if done
			if($result){
				$jobString = $this->generate_job_string_from_db_record($cron, $runtime);
				$result = $this->remove_job_from_server($jobString);
			}
		}
		
		if((!empty($result) && $result) || empty($result)) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>'when_ran', 'field_value'=>$runtime, 'id'=>$jobId));
		
		if($result) $result = $this->_query_reader->run('update_cron_schedule_field', array('field_name'=>'last_result', 'field_value'=>$jobDetails['result'], 'id'=>$jobId));
		
		#Log details of the cron job run
		$jobDetails['job_id'] = $jobId;
		$this->log_cron_job_results($jobDetails);
	}
	


	
	
	
	
	
	
	
	#Log cron job results
	public function log_cron_job_results($jobDetails)
	{
		log_message('debug', '_cron/log_cron_job_results');
		log_message('debug', '_cron/log_cron_job_results:: [1] jobDetails='.json_encode($jobDetails));
		
		$details = "";
		$jobResult = "";
		#Cater for details in arrays
		if(is_array($jobDetails))
		{
			#Log success details
			if(!empty($jobDetails['results_array']) && array_key_exists('success', $jobDetails['results_array']))
			{
				$details .= "SUCCESS: ".(is_array($jobDetails['results_array']['success'])? implode(', ', $jobDetails['results_array']['success']): $jobDetails['results_array']['success']);
				$jobResult = "SUCCESS";
			}
			#Log failure details
			else if(!empty($jobDetails['results_array']) && array_key_exists('fails', $jobDetails['results_array']) && !empty($jobDetails['results_array']['fails']))
			{
				$details .= "FAIL: ".(is_array($jobDetails['results_array']['fails'])? implode(', ', $jobDetails['results_array']['fails']): $jobDetails['results_array']['fails']);
				$jobResult = "FAIL";
			}
		}
		else
		{
			$details = $jobDetails;
		}
		
		if(!empty($jobResult)) $recordCount = is_array($jobDetails['results_array']['success'])? count($jobDetails['results_array']['success']): count($jobDetails['results_array']['fails']);
		else $recordCount = (!empty($jobDetails['record_count'])? $jobDetails['record_count']: 1);
		
		# Record in database
		$result = $this->_query_reader->run('add_cron_log', array('job_id'=>(!empty($jobDetails['job_id'])? $jobDetails['job_id']: ''), 'user_id'=>(!empty($jobDetails['user_id'])? $jobDetails['user_id']: ''), 'job_type'=>$jobDetails['job_type'], 'activity_code'=>$jobDetails['job_code'], 'result'=>(!empty($jobResult)? $jobResult: $jobDetails['result']), 'record_count'=>$recordCount, 'uri'=>uri_string(), 'log_details'=>$details, 'ip_address'=>$this->input->ip_address()  ));
		log_message('debug', '_cron/log_cron_job_results:: [2] result='.$result);
		
	}
	
	
	
	
	
	
	
	#Log cron job results
	public function backup_cron_log()
	{
		log_message('debug', '_cron/backup_cron_log');
		
		# 1. Get file size and check if it is bigger than 500KB
		$fileSize = filesize(CRON_FILE_LOG)/1024;
		# 2. If bigger back it up and create a new log file
		if($fileSize > 500){
			$zipName = CRON_HOME_URL.'archive/'.@strtotime('now').'.zip';
			$zip = new ZipArchive;
			$zip->open($zipName, ZipArchive::CREATE);
			$zip->addFile(CRON_FILE_LOG, basename(CRON_FILE_LOG));
			$zip->close();
			
			# Then clear the log file
			$log = fopen(CRON_FILE_LOG, "w+");
			fwrite($log, '');
			fclose($log);
		}
		
		$bool = (!empty($zipName) && file_exists($zipName) && filesize($zipName) > 0) || $fileSize < 500;
		
		return array('archive'=>($bool && $fileSize > 500? 'Archive: '.basename($zipName): ''), 'boolean'=>$bool);
	}
	
	
	
	
	
	
	
	
	
	
	
}


?>