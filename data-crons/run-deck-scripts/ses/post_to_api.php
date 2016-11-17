<?php 
require_once "SimpleEmailServiceRequest.php";
require_once "SimpleEmailServiceMessage.php";
require_once "SimpleEmailService.php";

# proceed if the parameters to send are included
if(!empty($_GET['to']) && !empty($_GET['subject']) && !empty($_GET['message'])){
	# get the passed parameters
	$to = $_GET['to'];
	$subject = htmlentities($_GET['subject']);
	$message = htmlentities($_GET['message']);

	# create the message object
	$m = new SimpleEmailServiceMessage();
	$m->addTo($to);
	$m->setFrom('no-reply@clout.com');
	$m->addReplyTo('no-reply@clout.com');
	$m->setSubject($subject);
	$m->setMessageFromString('',$message);

	# finaly send the message
	$ses = new SimpleEmailService('AKIAJJSXRBVS5LQCBNYA', 'usORAaERJ4wx1mm6/DerphBCejJlVr6XWYw0vIcK');
	$response = $ses->sendEmail($m);
}

# use this line to check the AWS api response
# echo "SES API RESPONSE: "; print_r($response);
echo !empty($response['MessageId'])? 'success': 'fail';
?>
