<?php
/**
 * This class handles generic data queries.
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 03/09/2016
 */
class _data extends CI_Model
{
	
	# get table data records based off this criteria
	function get_table_records($tableName, $fieldList, $condition, $limit, $offset)
	{
		log_message('debug', '_data/get_table_records');
		return $this->_query_reader->get_list('get_generic_table_data', array('table_name'=>$tableName, 'field_list'=>$fieldList, 'condition'=>(!empty($condition)? $condition: '1=1'), 'limit_text'=>' LIMIT '.$offset.', '.$limit));
	}
	
	
	
	# download maps passed in the array
	function download_maps($locations)
	{
		log_message('debug', '_data/download_maps:: [1] locations='.json_encode($locations));
		$results = array();
		foreach($locations AS $location){
			$fileName = download_from_url("http://maps.googleapis.com/maps/api/staticmap?center="
					.$location['latitude'].",".$location['longitude']."&zoom=15&size=400x125&markers=icon:http://pro-fe-web1.clout.com/assets/images/map_marker.png|"
					.$location['latitude'].",".$location['longitude'], FALSE, 'name', $location['file_name']);
			
			array_push($results, !empty($fileName));
		}
		
		log_message('debug', '_data/download_maps:: [2] results='.json_encode($results));
		return get_decision($results);
	}
	
	
	
	
	
	
	
}


?>