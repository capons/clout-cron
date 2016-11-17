<?php
/**
 * This class processes business logic related to webhooks. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 05/04/2015
 */
class _webhook extends CI_Model
{
	
	# update the user webhook by contacting the API
	function update($userId, $hookBase = BASE_URL, $api = 'plaid')
	{
		log_message('debug', '_webhook/update:: [1] ');
		log_message('debug', '_webhook/update:: [2] parameters: '.json_encode(array('user_id'=>$userId, 'hook_base'=>$hookBase, 'api'=>$api)));
		$token = $this->_query_reader->get_row_as_array('get_user_access_tokens',  array('user_ids'=>$userId ));
		
		# if user has a valid access token, 
		if(!empty($token['access_token'])){
			$parameters = array(
				'client_id'=>PLAID_CLIENT_ID, 
				'secret'=>PLAID_SECRET, 
				'access_token'=>$token['access_token'], 
				'options'=>array('webhook'=>$hookBase.'h/'.encrypt_value($userId))
			);
			
			# now run on API
			$response = run_on_api(PLAID_CONNECTION_URL.'/connect', $parameters, 'PATCH');
		}
		
		log_message('debug', '_webhook/update:: [3] response: '.(!empty($response)? 'RECEIVED': 'NOT RECEIVED'));
	}
	
	
	
	
	# generate job xml and post to the rundeck server about the new job
	function send_job_to_rundeck($userId, $jobType, $details=array())
	{
		log_message('debug', '_webhook/send_job_to_rundeck:: [1] ');
		
		$details['project'] = !empty($details['project'])? $details['project']: 'scoring-jobs';
		$xmlFile = $this->generate_job_xml($userId, $jobType, $details);
		log_message('debug', '_webhook/send_job_to_rundeck:: [2] xmlFile: '.$xmlFile);
		
		if(!empty($xmlFile) && file_exists(CRON_HOME_URL.'jobs/'.$xmlFile)) {
			$postUrl = !empty($details['scheduler'])? 'https://'.$details['scheduler'].'upload_job.php': RUNDECK_POST_URL;
			$postUrl .= '?project='.$details['project'].'&xml_file='.$xmlFile;
			# post to the scheduler (rundeck) server
			$response = run_on_api($postUrl, array('file_url'=>CRON_HOME_URL.'jobs/'.$xmlFile), 'XML');
			$response = trim($response);
			# remove the job file if successfully sent to rundeck
			if(empty($response)) {
				@unlink(CRON_HOME_URL.'jobs/'.$xmlFile);
				$isSent = 'Y';
			}
		}
		
		log_message('debug', '_webhook/send_job_to_rundeck:: [3] response: '.(!empty($isSent)? 'SUCCESS': 'FAIL: '.(!empty($response)? json_encode($response): '') ));
		# if the job was posted and there were no errors, return TRUE
		return !empty($isSent);
	}
	
	
	
	
	
	
	
	
	# generate an XML file for the job
	function generate_job_xml($userId, $jobType, $details)
	{
		log_message('debug', '_webhook/generate_job_xml:: [1] ');
		
		$details['processor'] = !empty($details['processor'])? $details['processor']: RUNDECK_DEFAULT_JOB_PROCESSOR;
		$details['scheduler'] = !empty($details['scheduler'])? $details['scheduler']: RUNDECK_SERVER;
		
		$schedulerUrl = !empty($details['scheduler'])? 'https://'.$details['scheduler']: RUNDECK_SERVER_URL;
		$jobId = (!empty($details['job_id'])? $details['job_id']: "job-user-".$userId."-".strtolower(replace_bad_chars(str_replace(' ','-',$jobType)))."-".@strtotime('now').'-'.rand(0,1000000));
		
		
		
		$jobString = "<joblist>
  <job>
    <description>job for user ".$userId." (".strtoupper(str_replace('_',' ',$jobType)).")</description>
    <dispatch>
      <excludePrecedence>true</excludePrecedence>
      <keepgoing>false</keepgoing>
      <rankOrder>ascending</rankOrder>
      <threadcount>1</threadcount>
    </dispatch>
    <executionEnabled>true</executionEnabled>
    <group>".$jobType."</group>
    <id>".$jobId."</id>
    <loglevel>INFO</loglevel>
    <name>job-user-".$userId."-".$jobType."</name>	
    <nodefilters>
      <filter>".$details['processor']."</filter>
    </nodefilters>
    <nodesSelectedByDefault>true</nodesSelectedByDefault>";
    
	if(!empty($details['schedule']) && $details['schedule'] == 'end-of-day'){
		# stagger the minutes and seconds for the job
		$jobString .= "
    		<schedule>
			    <month month='*' />
			    <time hour='0' minute='".rand(0,60)."' seconds='".rand(0,60)."' />
			    <weekday day='*' />
			    <year year='*' />
		    </schedule>
	    <scheduleEnabled>true</scheduleEnabled>";
	}
	else if(!empty($details['schedule']) && $details['schedule'] == 'every-day-3am'){
		# stagger the seconds for the job
		$jobString .= "
				<schedule>
			      <month month='*' />
			      <time hour='3' minute='".rand(0,60)."' seconds='".rand(0,60)."' />
			      <weekday day='*' />
			      <year year='*' />
			    </schedule>
      		<scheduleEnabled>true</scheduleEnabled>";
	}
	else if(!empty($details['schedule']) && $details['schedule'] == 'every-minute'){
		# stagger the seconds for the job
		$jobString .= "
				<schedule>
			      <month month='*' />
			      <time hour='*' minute='*' seconds='".rand(0,60)."' />
			      <weekday day='*' />
			      <year year='*' />
			    </schedule>
      		<scheduleEnabled>true</scheduleEnabled>";
	}
	else {
		# one-time execution
		$jobString .= "<scheduleEnabled>false</scheduleEnabled>";
	}
    
	
		$jobString .= "<sequence keepgoing='false' strategy='node-first'>
	      <command>
	        <description>Run scoring job.</description>
	        <errorhandler>
	          <jobref group='utility-jobs' name='send-job-failure-notification' nodeStep='true'>
	            <arg line='-jobid ".$jobId."' />
	            <dispatch>
	              <keepgoing>false</keepgoing>
	            </dispatch>
	            <nodefilters>
	              <filter>".$details['scheduler']."</filter>
	            </nodefilters>
	          </jobref>
	        </errorhandler>
	        <script><![CDATA[#! /bin/bash
	
	job=\$1
	job_status=\$(/usr/bin/php /var/www/html/index.php \${job})
	if [[ \${job_status} == *success* ]]
	  then
	        echo \"Job [\$1] was successful\";
	        exit 0;
	  else
	        echo \"Job [\$1] failed\";
	        exit 1;
	fi]]></script>
	        <scriptargs>".$this->generate_script_url($userId, $jobId, $jobType, $details)."</scriptargs>
	        
	      </command>";
		
		# do not remove scheduled jobs
		if(empty($details['schedule']) || (!empty($details['schedule']) && $details['schedule'] == 'once')){
	      $jobString .= "<command>
	        <description>Mark completed temporary job for future removal</description>
	        <jobref group='utility-jobs' name='mark-job-complete'>
	          <arg line='-jobid ".$jobId." -projectid ".$details['project']."' />
	        </jobref>
	      </command>";
		}
		
	    $jobString .= "</sequence>
      <uuid>".$jobId."</uuid>
  </job>
</joblist>";
		
		
		# now create and add the job string to the XML file
		$fileUrl = CRON_HOME_URL.'jobs/'.$jobId.'.xml';
		file_put_contents($fileUrl, $jobString);
		
  		return (file_exists($fileUrl) && filesize($fileUrl) > 0? $jobId.'.xml': '');
	}
	
	
	
	
	
	# determine which script url to send the job to execute
	function generate_script_url($userId, $jobId, $jobType, $details=array(), $baseUrl=BASE_URL)
	{
		# determine the base URL to use
		#$baseUrl = !empty($details['processor'])? 'http'.(!empty($details['is_ssl_processor']) && $details['is_ssl_processor'] == 'Y'? 's': '').'://'.$details['processor'].'/': $baseUrl;
			
		# pre-defined URL
		if(!empty($jobType) && empty($details['job_string'])){
			# use standard job codes to define the URL
			switch($jobType){
				case 'initial-pull': $url = 'scoring_cron/save_raw_transactions/token/'.$details['access_token'].'/is_new/yes'; break;
				case 'historical-pull': $url = 'scoring_cron/save_raw_transactions/token/'.$details['access_token'].'/date/'.strtotime('-10 years').'/is_new/no'; break;
				case 'transaction-update': $url = 'scoring_cron/save_raw_transactions/token/'.$details['access_token'].'/date/'.strtotime('-1 years').'/is_new/no'; break;
				case 'transaction-delete': $url = 'scoring_cron/delete_transactions_by_api/token/'.$details['access_token'].'/transactions/'.implode('__',$details['api_transaction_ids']); break;
				case 'webhook-update': $url = 'plaid_webhook/update_api_webhook_details/token/'.$details['access_token'].'/user/'.$userId; break;
				case 'webhook-error': $url = 'plaid_webhook/report_webhook_error/user/'.$userId; break;
				default: $url = ''; break;
			}
		}
		# processor given for the job string
		else if(!empty($details['processor']) && !empty($details['job_string'])) $url = $details['job_string'];
		# user-defined URL
		else if(!empty($details['job_string'])) $url = $details['job_string'];
		# no resolved job URL
		else $url = '';
		
		
		return $url;
	}
	
	
	
	
	
	
	
	
}


?>