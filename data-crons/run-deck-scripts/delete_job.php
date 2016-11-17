<?php
# remove the job
if(!empty($_GET['project']) && !empty($_GET['job_id'])) {
	$response = shell_exec("sudo /etc/rundeck/tools/bin/rd-jobs purge -p ".$_GET['project']." --idlist ".$_GET['job_id']);
}

# respond to the rundeck job caller
if(!isset($response) || (!empty($response) && strpos($response, 'successfully deleted:') === FALSE)) {
	echo "fail";
}
else echo "success";
?>