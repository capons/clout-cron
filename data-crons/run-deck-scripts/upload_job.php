<?php
# script to add a job posted to this script to rundeck server
$xml = file_get_contents('php://input');

if(!empty($_GET['project']) && !empty($_GET['xml_file']) && !empty($xml)){
	
	#1. download file from URL into local file with same name
	$tempXMLFile = '/var/www/jobs/'.$_GET['xml_file'];
	file_put_contents($tempXMLFile, $xml);

	#2. post job to rundeck
	if(file_exists($tempXMLFile) && filesize($tempXMLFile) > 0) {
		$response = shell_exec("sudo /etc/rundeck/tools/bin/rd-jobs load -p ".$_GET['project']." --file ".$tempXMLFile);
		# clean up. remove the file when done.
		if(empty($response) || (!empty($response) && strpos($response, 'succeeded:') !== FALSE)) {
			@unlink($tempXMLFile);
			
			# now run the job by its ID - same as the file name
			$response = shell_exec("sudo /etc/rundeck/tools/bin/run -i ".pathinfo(basename($tempXMLFile), PATHINFO_FILENAME));
		}
	}
	
	# echo appropriate response if failed so that the job is shown as failed.
	if(!isset($response) || (!empty($response) && strpos($response, 'succeeded:') === FALSE)){
		echo "ERROR: can not load xml file. ".PHP_EOL.PHP_EOL."DETAILS: ".(isset($response)? json_encode($response): 'NONE');
		return false;
	}
	else return true;
}
# XML file not found!
else {
	echo "ERROR: The rundeck project or XML file could not be resolved.";
	return false;
}

?>