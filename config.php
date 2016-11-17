<?php
/*
 * This document includes global system-specific settings
 *
 */
 
 
/*
 *---------------------------------------------------------------
 * GLOBAL SETTINGS
 *---------------------------------------------------------------
 */

	define('SITE_TITLE', "Clout");
		
	define('SITE_SLOGAN', "");

	define('SYS_TIMEZONE', "America/Los_Angeles");
	
	define('NUM_OF_ROWS_PER_PAGE', "10");
		
	define('NUM_OF_LISTS_PER_VIEW', "10");
	
	define('IMAGE_URL', BASE_URL."assets/images/");
	
	define('HOME_URL', getcwd()."/");
	
	define('DEFAULT_CONTROLLER', 'main');
	
	define('UPLOAD_DIRECTORY',  HOME_URL."assets/uploads/");
	
	define('MAX_FILE_SIZE', 40000000);
	
	define('ALLOWED_EXTENSIONS', ".doc,.docx,.txt,.pdf,.xls,.xlsx,.jpeg,.png,.jpg,.gif");
	
	define('MAXIMUM_FILE_NAME_LENGTH', 100);

	define('DOWNLOAD_LIMIT', 10000); # Max number of rows that can be downloaded

	define('API_REQUIRES_KEY', FALSE);
	
	define('UNSUBSCRIBE_EXPIRY', 12); # number of months it takes for unsubscribe request to expire





/*
 *---------------------------------------------------------------
 * SEARCH SETTINGS
 *---------------------------------------------------------------
 */
	
	define('LOOKUP_START_LATITUDE', '34.070436');
	
	define('LOOKUP_START_LONGITUDE', '-118.35048');

	define('DEFAULT_SEARCH_DISTANCE', 1); # the default search distance is 1km=1000 meters (3,280ft)
	
	define('MIN_POPULARITY_FREQUENCY', 10);
	


/*
 *---------------------------------------------------------------
 * CRON JOB SETTINGS
 *---------------------------------------------------------------
 */
	define('CRON_FILE_NAME', "cron.list");
	
	define('CRON_FILE', CRON_HOME_URL.CRON_FILE_NAME);

	define('CRON_FILE_LOG', CRON_HOME_URL."cron.log");
	
	define('GLOBAL_CRON_FILE', DEFAULT_CRON_HOME_URL."global.".CRON_FILE_NAME);
	
	# queue IDs used for lining up jobs for processing
	define('SCORING_CRON_QUEUE', 9999);
	
	define('GENERAL_CRON_QUEUE', 9998);


/*
 *---------------------------------------------------------------
 * QUERY CACHE SETTINGS
 *---------------------------------------------------------------
 */
 	
	
 	define('QUERY_FILE', CRON_HOME_URL.'html/application/helpers/queries_list_helper.php'); 
	
	
	


/*
 *---------------------------------------------------------------
 * MESSAGE SETTINGS
 *---------------------------------------------------------------
 */
 	
 	define('MESSAGE_FILE', CRON_HOME_URL.'html/application/helpers/message_list_helper.php');  	
	
	define('MAXIMUM_INVITE_BATCH_LIMIT', 10000);
	
	define('FIRST_INVITE_PERIOD', 3); # days old
	
	define('SECOND_INVITE_PERIOD', 10); # days old








/*
 *---------------------------------------------------------------
 * SMS CREDENTIALS
 *---------------------------------------------------------------
 */

 	
	


/*
 *---------------------------------------------------------------
 * IMPORT SETTINGS
 *---------------------------------------------------------------
 */
 	define('MAX_EMAILS_TO_IMPORT', 100); 
 
 
 
 
 
 
 
 
 
 

/*
 *---------------------------------------------------------------
 * COMMUNICATION SETTINGS
 *---------------------------------------------------------------
 */
	
	define("NOREPLY_EMAIL", "no-reply@clout.com");
	
	define("APPEALS_EMAIL", "appeals@clout.com");
	
	define("FRAUD_EMAIL", "fraud@clout.com");
	
	define("SECURITY_EMAIL", "security@clout.com");
	
	define("HELP_EMAIL", "support@clout.com");
	        	        
	define('SITE_ADMIN_MAIL', "al@clout.com");
	
	define("SIGNUP_EMAIL", "register@clout.com");
	
	define('SITE_ADMIN_NAME', "Clout Admin");
	
	define('SITE_GENERAL_NAME', "Clout");
		
	define('DEV_TEST_EMAIL', "al@clout.com");
		
	define('SYSTEM_REPORTS_EMAIL', "al@clout.com"); # system@clout.com

/*
 * If "FLAG_TO_REDIRECT" is set to 1, it will redirect all the mails from this site
 * to the email address  defined in "MAILID_TO_REDIRECT".
 */
		
	define('MAILID_TO_REDIRECT', DEV_TEST_EMAIL);
?>