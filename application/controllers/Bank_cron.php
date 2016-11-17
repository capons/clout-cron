<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class controls obtaining and updating bank information.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 02/15/2015
 */
class Bank_cron extends CI_Controller 
{
	
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
	}
	
	
	
	function refresh_bank_list()
	{
		log_message('debug', 'Bank_cron/refresh_bank_list');
		set_time_limit(0); # remove time limit
		
		$hasMore = TRUE;
		$batchNumber = 100;
		$offset = 12300;
		$bankCount = 0;
		
		# for each of the banks, get any extra details and record all for future reuse		
		while($hasMore) {
			$response = server_curl('https://tartan.plaid.com/institutions/longtail', 
					array('client_id'=>PLAID_CLIENT_ID, 
							'secret'=>PLAID_SECRET,
							'count'=>$batchNumber,
							'offset'=>$offset 
					));
			log_message('debug', 'Bank_cron/refresh_bank_list:: [1] response='.json_encode($response));
			
			$banks = !empty($response['results'])? $response['results']: array();
			log_message('debug', 'Bank_cron/refresh_bank_list:: [2] banks='.json_encode($banks));
			
			foreach($banks AS $bank)
			{
				if(!empty($bank['name'])){
					$bank['name'] = trim($bank['name']);
					$rawDetails = json_decode(curl_get_contents('https://tartan.plaid.com/institutions/search?q='.str_replace(' ','%20',$bank['name'])), TRUE);
				
					$details = !empty($rawDetails)? current($rawDetails): array();
					$thirdPartyId = !empty($details['id'])? $details['id']: '';
				
					if(!empty($details['logo'])){
						$bank['logoUrl'] = download_from_url(base64_decode($details['logo']), FALSE, 'name', 'banklogo_'.$thirdPartyId.'.png', 'content');
					}
					
					$result = $this->_query_reader->run('add_raw_bank', array(
						'third_party_id'=>$thirdPartyId, 
						'institution_name'=>htmlentities($bank['name'], ENT_QUOTES), 
						'institution_code'=>(!empty($bank['type'])? $bank['type']: ''), 
						'home_url'=>(!empty($bank['url'])? $bank['url']: ''), 
						'logo_url'=>(!empty($bank['logoUrl'])? $bank['logoUrl']: ''), 
						'country_code'=>(!empty($bank['currencyCode']) && $bank['currencyCode'] == 'USD'? 'USA': ''), 
						'currency_code'=>(!empty($bank['currencyCode'])? $bank['currencyCode']: ''), 
						'username_placeholder'=>(!empty($bank['credentials']['username'])? htmlentities($bank['credentials']['username'], ENT_QUOTES): ''),  
						'password_placeholder'=>(!empty($bank['credentials']['password'])? htmlentities($bank['credentials']['password'], ENT_QUOTES): ''),
						'has_mfa'=>(!empty($bank['has_mfa'])? $bank['has_mfa']: ''), 
						'mfa_details'=>implode("|", $bank['mfa']), 
						'phone_number'=>'', 'is_featured'=>'N', 'address_line_1'=>'', 'address_line_2'=>'', 'city'=>'', 
						'state'=>'','email_address'=>'', 'status'=>'active', 'user_id'=>'1'));
				
					#TODO: Add the rest of the fields for extra login customization returned by $details
					
					$bankCount++;
				}
			}
			
			$offset += $batchNumber;
			
			# continue with the insertion if the banks are not complete
			if(empty($banks)) {
				$hasMore = FALSE;
				break;
			}
		}
		log_message('debug', 'Bank_cron/refresh_bank_list:: [3] bankCount='.$bankCount);
		
		echo ($bankCount > 0? 'SUCCESS': 'FAIL');
	}

	
	
	
	
	
}


/* End of controller file */