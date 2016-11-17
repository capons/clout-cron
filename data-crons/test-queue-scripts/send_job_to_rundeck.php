<?php 
$response = run_on_api('http://pro-dw-rnd1.clout.com:8843/upload_job.php?project=test-project&xml_file=sample-remote-job.xml', array('xml_file'=>'sample-remote-job.xml'), 'POST');

# remove the job file if successfully sent to rundeck
echo PHP_EOL.(empty($response)? 'SUCCESS': 'FAIL');

# Run on API
function run_on_api($url, $data, $runType='POST', $returnType='array')
{
        #Prepare for sending
        $ch = curl_init();

        $file = fopen($data['xml_file'], 'r');
				curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
				curl_setopt($ch, CURLOPT_URL, $url);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,  fread($file, filesize($data['xml_file'])) );
				curl_setopt($ch, CURLOPT_POST, 1);
				
				fclose($file);

        #Run the value as passed
        $result = curl_exec($ch);
        
        #Show error
        if (curl_errno($ch))
        {
                $error = curl_error($ch);
                $errorResult = array('code' => 404, 'message' => 'system error', 'resolve' => $error );
        }
        #Close the channel
        curl_close($ch);

        #Determine the type of data to return
        if($returnType == 'string') return !empty($error)? $error: $result;
        else return !empty($errorResult)? $errorResult: json_decode($result, TRUE);
}
