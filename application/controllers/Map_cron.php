<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles running cron jobs related to maps.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/11/2015
 */

class Map_cron extends CI_Controller 
{
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
	}
	
	
	
	
	# save raw transactions from the Plaid API
	# (BASE_URL)/map_cron/pull_store_maps/locations/(latitude_longitude_store-id-1__latitude_longitude_store-id-2__latitude_longitude_store-id-3)
	function pull_store_maps()
	{
		log_message('debug', 'Map_cron/pull_store_maps:: [1] ');
		$data = filter_forwarded_data($this);
		
		# extract and breakdown passed details into a usable array
		if(!empty($data['locations'])) {
			$this->load->model('_data');
			$locations = array();
			$rawLocations = explode('__',$data['locations']);
			log_message('debug', 'Map_cron/pull_store_maps:: [2] locations='.json_encode($rawLocations));
			
			foreach($rawLocations AS $row){
				$locationParts = explode('_',$row);
				if(count($locationParts) == 3) array_push($locations, array('latitude'=>$locationParts[1],'longitude'=>$locationParts[0],'file_name'=>'banner_'.$locationParts[2].'.png'));
			}
			
			if(!empty($locations)) $result = $this->_data->download_maps($locations);  
		}
		
		log_message('debug', 'Map_cron/pull_store_maps:: [3] '.(!empty($result) && $result? 'SUCCESS': 'FAIL'));
		echo (!empty($result) && $result);
	}
	
	
	
	
	
}

/* End of controller file */