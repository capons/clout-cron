<?php 
/**
 * This file helps with checking and applying rules
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 12/11/2015
 */


# check if a rule applies for the given parameters
function rule_check($obj, $code, $parameters=array())
{
	log_message('debug', 'rules_helper/rule_check');
	log_message('debug', 'rules_helper/rule_check:: [1] array='.json_encode(array('__action'=>'rule_check', 'return'=>'plain', 'code'=>$code, 'parameters'=>$parameters)));
	return server_curl(IAM_SERVER_URL, array('__action'=>'rule_check', 'return'=>'plain', 'code'=>$code, 'parameters'=>$parameters)); 
}











# apply a rule based on the passed parameters
function apply_rule($obj, $code, $parameters=array())
{
	log_message('debug', 'rules_helper/apply_rule');
	log_message('debug', 'rules_helper/apply_rule:: [1] code='.$code.' parameters='.json_encode($parameters));
	# TODO: put in this array rules that do not need a user id to be applied
	$nonUserRules = array('does_not_need_user_id');
	# reject rule without a user id
	if(empty($parameters['user_id']) && !in_array($code, $nonUserRules)) return FALSE;
	# pick the rule details
	$rule = $obj->_query_reader->get_row_as_array('get_rule_by_code', array('code'=>$code));
	log_message('debug', 'rules_helper/apply_rule:: [2] rule='.json_encode($rule));
	# switch to apply based on the rule
	switch($code){
		case 'new_inclusion_list_user':
			return apply_new_inclusion_list_user($obj, $rule, $parameters);
		break;
		
		case 'new_user_referred_by_invited_user':
			return apply_new_user_referred_by_user_type($obj, $rule, $parameters, 'invited_shopper');
		break;
		
		case 'new_user_referred_by_random_user':
			return apply_new_user_referred_by_user_type($obj, $rule, $parameters, 'random_shopper');
		break;
		
		case 'new_random_user':
			return apply_new_random_user($obj, $rule, $parameters);
		break;
		
		case 'permission_update_to_invited_user':
			return apply_permission_update_to_new_group_type($obj, $rule, $parameters, 'invited_shopper');
		break;
		
		case 'permission_update_to_random_user':
			return apply_permission_update_to_new_group_type($obj, $rule, $parameters, 'random_shopper');
		break;
		
		case 'invite_daily_limit_10':
			return apply_invite_daily_limit($obj, $rule, $parameters, 10);
		break;
		
		case 'invite_daily_limit_30':
			return apply_invite_daily_limit($obj, $rule, $parameters, 30);
		break;
		
		case 'invite_daily_limit_unlimited':
			return apply_invite_daily_limit($obj, $rule, $parameters, '');
		break;
		
		case 'stop_new_invite_sending':
			return apply_stop_invite_sending($obj, $rule, $parameters, 'pending');
		break;
		
		case 'stop_all_invite_sending':
			return apply_stop_invite_sending($obj, $rule, $parameters, '');
		break;
		
		
		
		default:
			return FALSE;
		break;
	}
}















# apply stop invite sending
function apply_stop_invite_sending($obj, $rule, $parameters, $status)
{
	log_message('debug', 'rules_helper/apply_stop_invite_sending');
	log_message('debug', 'rules_helper/apply_stop_invite_sending:: [1] rule='.json_encode($rule).' parameters='.json_encode($parameters).' ststus='.$status);
	
	$inviteStatus = extract_rule_setting_value($rule['details'], 'invite_status', 'value');
	log_message('debug', 'rules_helper/apply_stop_invite_sending:: [2] inviteStatus='.$inviteStatus);
	
	if($inviteStatus == $status || ($inviteStatus == 'any' && $status == '')) {
		$statusCondition = "";
		if($inviteStatus == 'pending') $statusCondition = "pending";
		else if($inviteStatus == 'any') $statusCondition = "pending','paused";
		
		$result = $obj->_query_reader->run('update_invite_status_with_limit', array(
			'user_id'=>$parameters['user_id'], 
			'limit_text'=>'',
			'status_condition'=>" AND message_status IN ('".$statusCondition."') ",
			'new_status'=>'paused'
		)); 
	}
	log_message('debug', 'rules_helper/apply_stop_invite_sending:: [3] result='.!empty($result) && $result);
	
	return !empty($result) && $result;
}





# apply invite daily limit
function apply_invite_daily_limit($obj, $rule, $parameters, $limit)
{
	log_message('debug', 'rules_helper/apply_invite_daily_limit');
	log_message('debug', 'rules_helper/apply_invite_daily_limit:: [1] rule='.json_encode($rule).' parameters='.json_encode($parameters).' limit='.$limit);
	
	$dailyLimit = extract_rule_setting_value($rule['details'], 'daily_limit', 'value');
	log_message('debug', 'rules_helper/apply_invite_daily_limit:: [2] dailyLimit='.$dailyLimit);
	
	if($dailyLimit == $limit || ($dailyLimit == 'unlimited' && $limit == '')) 
	{
		$result = $obj->_query_reader->run('update_invite_status_with_limit', array(
			'user_id'=>$parameters['user_id'], 
			# select any more user invites not the first [$limit] and mark them as paused
			'limit_text'=>(!empty($limit)? " LIMIT ".$limit.",1000000000": ''), 
			'status_condition'=>" AND message_status = 'pending' ",
			'new_status'=>'paused' 
		)); 
	}
	log_message('debug', 'rules_helper/apply_invite_daily_limit:: [3] result='.$result);
	
	return !empty($result) && $result;
}









# update permission group of referred users based on group change for referrer user
function apply_permission_update_to_new_group_type($obj, $rule, $parameters, $groupType)
{
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type');
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [1] rule='.json_encode($rule).' parameters='.json_encode($parameters).' groupType='.$groupType);
	
	$permissionGroup = extract_rule_setting_value($rule['details'], 'permission_group', 'value');
	$permissionType = $obj->_query_reader->get_row_as_array('get_user_permission_types', array('user_ids'=>$parameters['user_id'], 'order_condition'=>""));
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [2] permissionGroup='.json_encode($permissionGroup));
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [3] permissionType='.json_encode($permissionType));
	
	# proceed if the new user permission group type matches the rule instruction group type
	if($groupType == $permissionType['type']) {
		$referrals = $obj->_query_reader->get_single_column_as_array('get_user_network_referral_ids', 'user_id', array('user_id'=>$parameters['user_id']));
		
		$result = TRUE;
		# apply this group type on the referred users
		foreach($referrals AS $referral){
			if($result) $result = update_user_group_by_name($obj, $referral, $permissionGroup);
		}
	}
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [4] result='.!empty($result) && $result);
	
	return !empty($result) && $result;
}





# apply new random user group permission
function apply_new_random_user($obj, $rule, $parameters)
{
	log_message('debug', 'rules_helper/apply_new_random_user');
	log_message('debug', 'rules_helper/apply_new_random_user:: [1] rule='.json_encode($rule).' parameters='.json_encode($parameters));
	
	$permissionGroup = extract_rule_setting_value($rule['details'], 'permission_group', 'value');
	if(!empty($permissionGroup)) $group = $obj->_query_reader->get_row_as_array('get_group_by_name', array('group_name'=>$permissionGroup));
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [2] permissionGroup='.json_encode($permissionGroup));
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [3] group='.json_encode($group));
	
	if(!empty($group['group_type']) && $group['group_type'] == 'random_shopper'){
		return update_user_group_by_name($obj, $parameters['user_id'], $permissionGroup);
	}else {
		log_message('debug', 'rules_helper/apply_new_random_user:: [2] return false');
		return FALSE;
	}
}





# apply new user referred by specified user type
function apply_new_user_referred_by_user_type($obj, $rule, $parameters, $groupType)
{
	log_message('debug', 'rules_helper/apply_new_user_referred_by_user_type');
	log_message('debug', 'rules_helper/apply_new_user_referred_by_user_type:: [1] rule='.json_encode($rule).' parameters='.json_encode($parameters).' groupType='.$groupType);
	
	$user = $obj->_query_reader->get_row_as_array('get_users_in_id_list', array('id_list'=>$parameters['user_id']));
	$permissionGroup = extract_rule_setting_value($rule['details'], 'permission_group', 'value');
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [2] user='.json_encode($user));
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [3] permissionGroup='.json_encode($permissionGroup));
	
	if(!empty($user['email_address']) && !empty($permissionGroup)){
		$referrers = $obj->_query_reader->get_single_column_as_array('get_list_of_inviters', 'user_id', array('email_address'=>$user['email_address'], 'inviter_condition'=>" AND LOWER(G.name) = LOWER('".$permissionGroup."') " ));
		
		if(!empty($referrers)) $permissionTypes = $obj->_query_reader->get_list('get_user_permission_types', array('user_ids'=>implode("','", $referrers), 'order_condition'=>" ORDER BY  field(A.user_id, ".implode(",", $referrers).") "));
		
		if(!empty($permissionTypes)) {
			foreach($permissionTypes AS $permissionRow){
				if($permissionRow['type'] == $groupType) {
					$result = update_user_group_by_name($obj, $parameters['user_id'], $permissionGroup);
					break;
				}
			}
		}
	}
	log_message('debug', 'rules_helper/apply_permission_update_to_new_group_type:: [4] result='.!empty($result) && $result);
	
	return !empty($result) && $result;
}








# apply inclusion list referrer permission and to the inclusion list user's network
function apply_new_inclusion_list_user($obj, $rule, $parameters)
{
	log_message('debug', 'rules_helper/apply_new_inclusion_list_user');
	log_message('debug', 'rules_helper/apply_new_inclusion_list_user:: [1] rule='.json_encode($rule).' parameters='.json_encode($parameters));
	
	$inclusion = $obj->_query_reader->get_row_as_array('get_rule_by_code', array('code'=>'the_inclusion_list'));
	# if inclusion list rule is active
	if(!empty($inclusion['details'])){
		$list = explode(',',str_replace(' ','',extract_rule_setting_value($inclusion['details'], 'inclusion_list', 'value')));
		$user = $obj->_query_reader->get_row_as_array('get_users_in_id_list', array('id_list'=>$parameters['user_id']));
		# get users who invited this user
		if(!empty($list)){
			$referrers = $obj->_query_reader->get_single_column_as_array('get_list_of_inviters', 'user_id', array('email_address'=>$user['email_address'], 'inviter_condition'=>" AND (U.email_address LIKE '".str_replace('*','%',implode("' OR U.email_address LIKE '",$list))."')" ));
		}
		
		if(!empty($referrers)){
			$permissionGroup = extract_rule_setting_value($rule['details'], 'permission_group', 'value');
			# determine which one to assign the referral if there are more than one
			if(count($referrers) > 1){
				$permissionTypes = $obj->_query_reader->get_list('get_user_permission_types', array('user_ids'=>implode("','", $referrers), 'order_condition'=>" ORDER BY  field(A.user_id, ".implode(",", $referrers).") "));
				$typeOrder = array('clout_owner', 'clout_admin_user', 'store_owner_owner', 'store_owner_admin_user', 'invited_shopper', 'random_shopper');
						
				$assignTo = '';
				foreach($typeOrder AS $i=>$type){
					foreach($permissionTypes AS $permissionRow){
						if($permissionRow['type'] == $type) {
							$assignTo = $permissionRow['user_id'];
							break 2;
						}
					}
				}
						
				if(!empty($assignTo)) $result = $obj->_query_reader->run('add_user_referral', array('user_id'=>$parameters['user_id'], 'referred_by'=>$assignTo, 'referrer_type'=>'normal','sent_referral_by'=>'email'));
			}
			# assign to this referrer
			else {
				$result = $obj->_query_reader->run('add_user_referral', array('user_id'=>$parameters['user_id'], 'referred_by'=>$referrers[0],'referrer_type'=>'normal','sent_referral_by'=>'email'));
			}
					
			# apply permission group
			if(!empty($result) && $result && !empty($permissionGroup)) $result = update_user_group_by_name($obj, $parameters['user_id'], $permissionGroup);
		}
	}
	log_message('debug', 'rules_helper/apply_new_inclusion_list_user:: [4] result='.!empty($result) && $result);
	
	return !empty($result) && $result;
}





# update the user permission group given their id and permission group name
function update_user_group_by_name($obj, $userId, $groupName)
{
	log_message('debug', 'rules_helper/update_user_group_by_name');
	log_message('debug', 'rules_helper/update_user_group_by_name:: [1] userId='.$userId.' groupName='.$groupName);
	
	$result = $obj->_query_reader->run('update_user_access_by_group_name', array('group_name'=>$groupName, 'user_id'=>$userId));
	
	if($result) $result = $obj->_query_reader->run('update_user_type_by_group_name', array('group_name'=>$groupName, 'user_id'=>$userId));
	log_message('debug', 'rules_helper/update_user_group_by_name:: [2] result='.$result);
	
	return $result;
}




