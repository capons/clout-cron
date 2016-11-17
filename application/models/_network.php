<?php
/**
 * This class processes network business logic. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.2
 * @copyright Clout
 * @created 05/12/2015
 */
class _network extends CI_Model
{
	
	# add user to the referrer network
	function add_user_to_referrer_network($userId, $referrerId, $level)
	{
		log_message('info', '_score/add_user_to_referrer_network:: [1] ');
		log_message('info', '_score/add_user_to_referrer_network:: [2] user_id:'.$userId.', referrer_id:'.$referrerId.', level: '.$level);
		
		# add direct network
		if($level == 'level_1') $result = $this->_query_reader->run('add_cached_direct_network_data', array('user_id'=>$userId, 'referrer_id'=>$referrerId));
		# add other levels of the network
		else {
			$levelParts = explode('_',$level);
			$result = $this->_query_reader->run('add_cached_other_network_data', array(
					'user_id'=>$userId, 'referrer_id'=>$referrerId, 'this_network_level'=>$level, 
					'higher_network_level'=>'level_'.(array_pop($levelParts) - 1)
				));
		}
			
		log_message('info', '_score/add_user_to_referrer_network:: [3] result: '.($result? 'SUCCESS': 'NONE'));
		return array('result'=>($result? 'success': 'fail'));
	}
	
	
	
	
}


?>