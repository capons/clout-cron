<?php
/**
 * This class generates and formats transaction details. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/29/2015
 */
class _transaction extends CI_Model
{
	
	# Get a summary of the transaction status in the system
	function status_summary($adminId)
	{
		return $this->_query_reader->get_list('get_transaction_status_summary', array('admin_id'=>$adminId));
	}
	
	
	# Get data related to a transaction descriptor in line with the sent data-type
	function descriptor($dataType, $descriptorId, $userId, $offset, $limit, $filters=array())
	{
		log_message('debug', '_transaction/descriptor');
		log_message('debug', '_transaction/descriptor:: [1] dataType='.$dataType.' descriptorId='.$descriptorId.' userId='.$userId.' offset='.$offset.' limit='.$limit.' filters='.json_encode($filters));
		
		switch($dataType)
		{
			case 'scope_list':
				$value = $this->_query_reader->get_list('get_transaction_scope_list', array('descriptor_id'=>$descriptorId));
			break;
			
			case 'problem_flags':
				$value = $this->_query_reader->get_list('get_transaction_problem_flags', array('descriptor_id'=>$descriptorId));
			break;
			
			case 'category_list':
				$value['level_1'] = $this->_query_reader->get_list('get_category_level_1_list', array('descriptor_id'=>$descriptorId));
				$value['level_2'] = $this->_query_reader->get_list('get_category_level_2_list', array('descriptor_id'=>$descriptorId));
				$value['level_2_suggestions'] = $this->_query_reader->get_list('get_category_level_2_suggestion_list', array('descriptor_id'=>$descriptorId));
				$value['level_1to2_mapping'] = array();
				 
				# Add the sub-categories
				foreach($value['level_2'] AS $row){
					if(empty($value['level_1to2_mapping'][$row['level_1_id']])) $value['level_1to2_mapping'][$row['level_1_id']] = array();
					array_push($value['level_1to2_mapping'][$row['level_1_id']], array('id'=>$row['id'], 'name'=>$row['name'], 'is_selected'=>$row['is_selected'], 'is_suggestion'=>'N'));
					if($row['is_selected'] == 'Y'){
						$value['level_1'][$row['level_1_id']-1]['is_selected'] = 'Y';
					}
				}

				# Now add the sub-category suggestions
				foreach($value['level_2_suggestions'] AS $row){
					if(empty($value['level_1to2_mapping'][$row['level_1_id']])) $value['level_1to2_mapping'][$row['level_1_id']] = array();
					array_push($value['level_1to2_mapping'][$row['level_1_id']], array('id'=>$row['id'], 'name'=>$row['name'], 'is_selected'=>$row['is_selected'], 'is_suggestion'=>'Y'));
					if($row['is_selected'] == 'Y'){
						$value['level_1'][$row['level_1_id']-1]['is_selected'] = 'Y';
					}
				}
			break;
			
			case 'location_list':
				$value['chains'] = $this->_query_reader->get_list('get_chain_match_attempts_by_descriptor', array('descriptor_id'=>$descriptorId, 'limit_text'=>" LIMIT ".$offset.",".$limit." " ));
				$value['stores'] = array();
				foreach($value['chains'] AS $chain){
					$value['stores'][$chain['id']] = $this->_query_reader->get_list('get_store_match_attempts_by_descriptor', array('descriptor_id'=>$descriptorId, 'chain_id'=>$chain['id'], 'limit_text'=>" LIMIT ".$offset.",".$limit." "));
				}
			break;
			
			
			
			case 'descriptor_list':
				$bankFilter = $statusFilterBefore = $statusFilterAfter = $adminFilter = '';
				/* if(!empty($filters['bankId']) && $filters['bankId'] != 'all'){
					if($filters['bankId'] == 'banks_not_featured') $bankFilter = " AND R._bank_id NOT IN (SELECT id FROM banks WHERE is_featured = 'Y') ";
					else $bankFilter = " AND R._bank_id = '".$filters['bankId']."' ";
				} */
			
				if(!empty($filters['status']) && $filters['status'] != 'all_transactions'){
					if($filters['status'] == 'admin_matched') $statusFilterBefore = " AND 'admin' = (SELECT user_type FROM clout_v1_3.user_security_settings WHERE _user_id=CH._entered_by LIMIT 1) ";
					if($filters['status'] == 'auto_matched') $statusFilterAfter = " HAVING possible_matches > 0 ";
					if($filters['status'] == 'edits_pending') $statusFilterBefore = " AND D.status='pending' ";
					if($filters['status'] == 'has_problem_flag') $statusFilterBefore = " AND (SELECT _flag_id FROM clout_v1_3.change_flags CF LEFT JOIN flags F ON (CF._flag_id=F.id) WHERE F.type='problem' AND _change_id=CH.id LIMIT 1) IS NOT NULL ";
					if($filters['status'] == 'not_found') $statusFilterAfter = " HAVING possible_matches = 0 ";
					if($filters['status'] == 'unqualified') $statusFilterBefore = " AND D.status='unqualified' ";
				}
				
				if(!empty($filters['adminId']) && $filters['adminId'] != 'all'){
					$adminFilter = " AND 'admin' = (SELECT user_type FROM clout_v1_3.user_security_settings WHERE _user_id=CH._entered_by LIMIT 1) AND CH._entered_by = '".extract_id($filters['adminId'])."' ";
				}
				
				$value = $this->_query_reader->get_list('get_descriptor_list', array(
					'user_id'=>$userId, 
					'phrase'=>(!empty($filters['phrase'])? htmlentities($filters['phrase'], ENT_QUOTES): ''), 
					'bank_filter'=>$bankFilter, 
					'status_filter_before'=>$statusFilterBefore, 
					'status_filter_after'=>$statusFilterAfter, 
					'admin_filter'=>$adminFilter, 
					'limit_text'=>" LIMIT ".$offset.",".$limit." "
				));
			break;
			
			
			
			default:
				$value = array();
			break;
		}
		
		log_message('debug', '_transaction/descriptor:: [2] value='.json_encode($value));
		return $value;
	}
	
	
	
	
	
	# Get data related to a transaction descriptor change in line with the sent data-type
	function change($dataType, $changeId, $offset='', $limit='', $phrase='', $userId='')
	{
		log_message('debug', '_transaction/change');
		log_message('debug', '_transaction/change:: [1] dataType='.$dataType);
		
		$details = array('data_id'=>$changeId, 'offset'=>$offset, 'limit'=>$limit, 'phrase'=>$phrase, 'user_id'=>$userId);
		log_message('debug', '_transaction/change:: [2] details='.json_encode($details));
		
		switch($dataType)
		{
			case 'user_flags':
				$value =  $this->_change->get_change_flags('by_change', $details);
			break;
			
			
			case 'user_flag_list':
				$value =  $this->_change->get_change_flags('all', $details);
			break;
			
			
			case 'change_list':
				$value =  $this->_change->get_list($details);
			break;
			
			
			
			
			default:
				$value = array();
			break;
		}
		
		log_message('debug', '_transaction/change:: [3] value='.json_encode($value));
		return $value;
	}
	
	
	
	
	
	# Delete a flag
	function delete_flag($dataType, $dataId, $stage, $userId)
	{
		log_message('debug', '_transaction/delete_flag');
		log_message('debug', '_transaction/delete_flag:: [1] dataType='.$dataType.' dataId='.$dataId.' stage='.$stage.' userId='.$userId);
		
		$result = $this->_change->remove_change_flag($dataId, $userId);
		log_message('debug', '_transaction/delete_flag:: [2] result='.$result);
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	
	# Add a change flag
	function add_flag($dataType, $dataId, $stage, $userId, $details=array())
	{
		log_message('debug', '_transaction/add_flag');
		log_message('debug', '_transaction/add_flag:: [1] dataType='.$dataType.' dataId='.$dataId.' stage='.$stage.' userId='.$userId);
		
		$result = $this->_change->add_change_flag($dataId, $userId, $details);
		log_message('debug', '_transaction/add_flag:: [2] result='.$result);
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	# Update the transaction descriptor scope
	function update_scope($descriptorId, $scopeId, $action, $userId, $otherDetails=array())
	{
		log_message('debug', '_transaction/update_scope');
		log_message('debug', '_transaction/update_scope:: [1] descriptorId='.$descriptorId.' scopeId='.$scopeId.' action='.$action.' userId='.$userId.' otherDetails='.json_encode($otherDetails));
		
		$result = FALSE;
		# Collect the extra scope information
		$scope = $this->_query_reader->get_row_as_array('get_previous_and_new_descriptor_scope', array('descriptor_id'=>$descriptorId, 'new_scope_id'=>$scopeId));
		log_message('debug', '_transaction/update_scope:: [2] scope='.json_encode($scope));
		
		# Verified change of scope
		if($action == 'confirm'){
			# Actually apply the change 
			$result = $this->_query_reader->run('update_descriptor_scope', array('scope_id'=>$scopeId, 'descriptor_id'=>$descriptorId, 'user_id'=>$userId ));
			log_message('debug', '_transaction/update_scope:: [3] result='.$result);
			
			$result = $result && $this->_query_reader->run('add_matching_rule_due_to_scope', array('descriptor_id'=>$descriptorId ));
			log_message('debug', '_transaction/update_scope:: [4] result='.$result);
		}
		
		# Record the change
		if(($action == 'confirm' && $result) || $action != 'confirm') {
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId, 
				'description'=>htmlentities('Descriptor Used For: changed from <b>'.$scope['previous_scope'].'</b> to <b>'.$scope['new_scope'].'</b>'.
							(!empty($otherDetails['flags'])? '<br>Flags added'.(!empty($otherDetails['notes'])? ' with a note: '.$otherDetails['notes']: ''): ''), ENT_QUOTES), 
				'change_code'=>'scope_changed', 
				'change_value'=>'previous_scope_id='.$scope['previous_id'].'|new_scope_id='.$scopeId, 
				'old_status'=>'',
				'new_status'=>($action == 'confirm'? 'verified': 'pending'),
				'flag_details'=>($action == 'flag' && !empty($otherDetails)? $otherDetails: array()),
				'user_id'=>$userId
			));
			log_message('debug', '_transaction/update_scope:: [5] result='.$result);
		}
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'), 'newScope'=>($result && !empty($scope['new_scope'])? $scope['new_scope']: ''));
	}
	
	
	
	
	
	
	
	
	
	
	# Update the transaction descriptor location
	function update_location($descriptorId, $chain, $store, $action, $userId, $otherDetails=array())
	{
		log_message('debug', '_transaction/update_location');
		log_message('debug', '_transaction/update_location:: [1] descriptorId='.$descriptorId.' chain='.$chain.' store='.$store.' action='.$action.' userId='.$userId.' otherDetails='.json_encode($otherDetails));
		
		$details = $this->_query_reader->get_row_as_array('get_store_chain_details', array('store_id'=>$store, 'chain_id'=>$chain));
		log_message('debug', '_transaction/update_location:: [2] details='.json_encode($details));
		
		# Verified change of location
		if($action == 'confirm'){
			$result = $this->_query_reader->run('remove_matching_rules_due_to_location', array('descriptor_id'=>$descriptorId));
			if($result && !empty($store)) $result = $this->_query_reader->run('add_matching_rule_due_to_location', array('descriptor_id'=>$descriptorId, 'store_id'=>$store ));
			if($result) $result = $this->_query_reader->run('mark_store_as_selected', array('store_id'=>$store, 'chain_id'=>$chain));
			
			$result = $this->_query_reader->run('remove_matching_rules_due_to_chain', array('descriptor_id'=>$descriptorId));
			if($result) $result = $this->_query_reader->run('add_matching_rule_due_to_chain', array('descriptor_id'=>$descriptorId, 'chain_name'=>$details['chain_name']));
			
			#Update the descriptor attachments
			if($result) $result = $this->_query_reader->run('mark_chain_as_selected', array('descriptor_id'=>$descriptorId, 'chain_id'=>$chain));
			
		}
		log_message('debug', '_transaction/update_location:: [3] result='.$result);
		
		# Record the change
		if(($action == 'confirm' && $result) || $action != 'confirm') {
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId, 
				'description'=>htmlentities('Location changed to <b>'.$details['chain_name'].' > '.$details['store_name'].'</b> locations '.
							(!empty($otherDetails['flags'])? '<br>Flags added'.(!empty($otherDetails['notes'])? ' with a note: '.$otherDetails['notes']: ''): ''), ENT_QUOTES), 
				'change_code'=>'location_changed', 
				'change_value'=>'new_chain_id='.$chain.'|new_store_id='.$store, 
				'old_status'=>'',
				'new_status'=>($action == 'confirm'? 'verified': 'pending'),
				'flag_details'=>($action == 'flag' && !empty($otherDetails)? $otherDetails: ''),
				'user_id'=>$userId
			));
			log_message('debug', '_transaction/update_location:: [4] result='.$result);
		}
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'), 'locationCount'=>($result? $details['location_count']: ''));
	}
	
	
	
	
	
	
	# Update sub-category
	function add_sub_category($descriptorId, $categoryId, $newSubCategory, $action, $userId)
	{
		log_message('debug', '_transaction/add_sub_category');
		log_message('debug', '_transaction/add_sub_category:: [1] descriptorId='.$descriptorId.' categoryId='.$categoryId.' newSubCategory='.$newSubCategory.' action='.$action.' userId='.$userId);
		
		$result = FALSE;
		# Collect the extra sub-category information
		$category = $this->_query_reader->get_row_as_array('get_category_details', array('category_id'=>$categoryId));
		log_message('debug', '_transaction/add_sub_category:: [2] category='.json_encode($category));
		
		# Verified change of scope
		if($action == 'verify'){
			$newSubCategoryId = server_curl(MYSQL_SERVER_URL, array('__action'=>'add_data', 'query'=>'add_sub_category', 'return'=>'plain', 'variables'=>array('category_id'=>$categoryId, 'name'=>strtoupper($newSubCategory)) ));
		} else {
			$newSubCategoryId = server_curl(MYSQL_SERVER_URL, array('__action'=>'add_data', 'query'=>'add_sub_category_suggestion', 'return'=>'plain', 'variables'=>array('descriptor_id'=>$descriptorId, 'category_id'=>$categoryId, 'suggestion'=>strtoupper($newSubCategory), 'user_id'=>$userId) ));
		}
		log_message('debug', '_transaction/add_sub_category:: [3] newSubCategoryId='.$newSubCategoryId);
		
		$descriptorSubCategoryId = !empty($newSubCategoryId)? $this->_query_reader->add_data('add_descriptor_sub_category', array('descriptor_id'=>$descriptorId, 'sub_category_id'=>$newSubCategoryId )): '';
		log_message('debug', '_transaction/add_sub_category:: [4] descriptorSubCategoryId='.$descriptorSubCategoryId);
		
		# Record the change
		if(!empty($descriptorSubCategoryId)) {
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId, 
				'description'=>htmlentities('<b>'.$category['name'].'</b> > <b green>'.$newSubCategory.'</b> sub-category added.', ENT_QUOTES), 
				'change_code'=>'sub_category_added', 
				'change_value'=>'category_id='.$categoryId.'|new_category='.$newSubCategory.'|descriptor_id='.$descriptorId, 
				'old_status'=>'',
				'new_status'=>'verified',
				'flag_details'=>array(),
				'user_id'=>$userId
			));
			log_message('debug', '_transaction/add_sub_category:: [5] result='.$result);
		}
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'), 'new_sub_category_id'=>$newSubCategoryId);
	}
	
	
	
	
	
	
	
	
	
	# Update a transaction descriptor categories
	function update_categories($descriptorId, $subCategories, $suggestedSubCategories, $action, $userId, $otherDetails=array())
	{
		log_message('debug', '_transaction/update_categories');
		log_message('debug', '_transaction/update_categories:: [1] descriptorId='.$descriptorId.' subCategories='.json_encode($subCategories).' suggestedSubCategories='.json_encode($suggestedSubCategories).' action='.$action.' userId='.$userId.' otherDetails='.json_encode($otherDetails));
		
		$result = FALSE;
		$subCategoriesFlat = $suggestedSubCategoriesFlat = array();
		
		# Collect all subcategories as one array
		foreach($subCategories AS $category=>$subCatGroup) foreach($subCatGroup AS $subCat) array_push($subCategoriesFlat,$subCat);
		# Collect all suggested sub-categories as one array
		foreach($suggestedSubCategories AS $category=>$subCatGroup) foreach($subCatGroup AS $subCat) array_push($suggestedSubCategoriesFlat,$subCat);
		
		
		# Collect the extra category information
		$subCategoriesText = $this->_query_reader->get_row_as_array('get_sub_category_name_list', array('id_list'=>implode("','", $subCategoriesFlat)));
		$suggestedSubCategoriesText = $this->_query_reader->get_row_as_array('get_suggested_sub_category_name_list', array('id_list'=>implode("','", $suggestedSubCategoriesFlat) ));
		
				
		# Verified change of scope
		if($action == 'confirm'){
			# 1. Remove all previous descriptor sub-categories
			$result = $this->_query_reader->run('remove_descriptor_categories', array('descriptor_id'=>$descriptorId));
			log_message('debug', '_transaction/update_categories:: [2] result='.$result);
			
			# 2. Add the new descriptor sub-categories
			if($result) $result = $this->_query_reader->run('add_descriptor_categories', array('descriptor_id'=>$descriptorId, 'id_list'=>implode("','", $subCategoriesFlat) ));
			log_message('debug', '_transaction/update_categories:: [3] result='.$result);
			# TRIGGER: Update the store sub-categories linked to the descriptor. 
			
			
			# Repeat 2. for suggested descriptor sub-categories if they do not exist
			if($result) $result = $this->_query_reader->run('add_suggested_descriptor_categories', array('descriptor_id'=>$descriptorId, 'id_list'=>implode("','", $subCategoriesFlat) ));
			log_message('debug', '_transaction/update_categories:: [4] result='.$result);
			
			# Also get a sample category from the list of sub-categories
			if($result) $category =  $this->_query_reader->get_row_as_array('get_sample_descriptor_category', array('descriptor_id'=>$descriptorId ));
			log_message('debug', '_transaction/update_categories:: [5] category='.json_encode($category));
		}
		
		
		
		# Record the change
		if(($action == 'confirm' && $result) || $action != 'confirm') {
			$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId, 
				'description'=>htmlentities('Sub-category list changed to <b>'.$subCategoriesText['list'].'</b> and suggested sub-categories <b>'.$suggestedSubCategoriesText['list'].'</b>'.
							(!empty($otherDetails['flags'])? '<br>Flags added'.(!empty($otherDetails['notes'])? ' with a note: '.$otherDetails['notes']: ''): ''), ENT_QUOTES), 
				'change_code'=>'sub_categories_changed', 
				'change_value'=>'sub_category_ids='.implode(',',$subCategoriesFlat).'|suggested_sub_category_ids='.implode(',',$suggestedSubCategoriesFlat), 
				'old_status'=>'',
				'new_status'=>($action == 'confirm'? 'verified': 'pending'),
				'flag_details'=>($action == 'flag' && !empty($otherDetails)? $otherDetails: array()),
				'user_id'=>$userId
			));
			log_message('debug', '_transaction/update_categories:: [6] result='.$result);
		}
		
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'), 'sampleCategory'=>($result && !empty($category['sample_category'])? $category['sample_category']: ''));
		
	}
	
	
	
	
	
	
	
	# Get matching rules attached to descriptor
	function matching_rules($descriptorId, $types, $userId, $offset, $limit, $phrase)
	{
		log_message('debug', '_transaction/matching_rules');
		log_message('debug', '_transaction/matching_rules:: [1] descriptorId='.$descriptorId.' types='.json_encode($types).' userId='.$userId.' offset='.$offset.' limit='.$limit.' phrase='.$phrase);
		
		$result = $this->_query_reader->get_list('get_matching_rules', array('descriptor_id'=>$descriptorId, 'phrase'=>"%".$phrase."%", 'limit_text'=>" LIMIT ".$offset.",".$limit." ", 'types'=>implode("','", explode(',', $types)) ));
		log_message('debug', '_transaction/matching_rules:: [2] result='.json_encode($result));
		return $result;
	}
	
	
	
	
	
	# Add a matching rule
	function add_rule($descriptorId, $criteria, $action, $phrase, $category, $matchId, $userId)
	{
		log_message('debug', '_transaction/add_rule');
		log_message('debug', '_transaction/add_rule:: [1] descriptorId='.$descriptorId.' criteria='.$criteria.' action='.$action.' phrase='.$phrase.' category='.$category.' matchId='.$matchId.' userId='.$userId);
		
		$hphrase = htmlentities($phrase, ENT_QUOTES);
		switch($criteria){
			case 'contains': $searchRule = '%'.$hphrase.'%'; break;
			case 'starting_with': $searchRule = $hphrase.'%'; break;
			case 'ending_with': $searchRule = '%'.$hphrase; break;
			case 'equal_to': $searchRule = $hphrase; break;
			default: $searchRule = $hphrase; break;
		}
		
		$searchRule = "'_PAYEE_NAME_' LIKE '".$searchRule."' OR '_EXTENDED_PAYEE_NAME_' LIKE '".$searchRule."'";
		$newRuleId = $this->_query_reader->add_data('add_matching_rule', array('rule_type'=>$action, 'confidence'=>'100', 'match_id'=>$matchId, 'descriptor_id'=>$descriptorId, 'details'=>addslashes($searchRule), 'is_active'=>'Y', 'category'=>$category));  
		log_message('debug', '_transaction/add_rule:: [2] newRuleId='.$newRuleId);
		
		# Record the change
		$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId, 
				'description'=>htmlentities('New matching rule added <b green>'.strtoupper($action).' descriptor which '.strtoupper(str_replace('_', ' ', $criteria)).': '.$phrase.($action == 'match'? ' to '.$category: '').'</b> ', ENT_QUOTES), 
				'change_code'=>'new_rule_added', 
				'change_value'=>'new_rule_id='.$newRuleId, 
				'old_status'=>'',
				'new_status'=>'verified',
				'flag_details'=>array(),
				'user_id'=>$userId
		));
		log_message('debug', '_transaction/add_rule:: [2] result='.$result);
		
		return array('result'=>(!empty($newRuleId)? 'SUCCESS': 'FAIL'), 'new_rule_id'=>(!empty($newRuleId)? $newRuleId: ''));
	}
	
	
	
	
	# Delete a rule
	function delete_rule($ruleId, $stage, $userId)
	{
		log_message('debug', '_transaction/delete_rule');
		log_message('debug', '_transaction/delete_rule:: [1] ruleId='.$ruleId.' stage='.$stage.' userId='.$userId);
		
		$idParts = explode('-', $ruleId);
		$result = $this->_query_reader->run('remove_match_rule', array('category'=>$idParts[0], 'rule_id'=>$idParts[1]));
		log_message('debug', '_transaction/delete_rule:: [2] result='.$result);
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	
	# Update the transaction descriptor rule matches
	function update_matches($descriptorId, $ruleIds, $action, $userId, $otherDetails=array())
	{
		log_message('debug', '_transaction/update_matches');
		log_message('debug', '_transaction/update_matches:: [1] descriptorId='.$descriptorId.' ruleIds='.$ruleIds.' action='.$action.' userId='.$userId.' otherDetails='.json_encode($otherDetails));
		
		$storeIds = $chainIds = array();
		
		foreach($ruleIds AS $idPair){
			$id = explode('-', $idPair);
			if(!empty($id[1])){
				if($id[0] == 'store') array_push($storeIds, $id[1]);
				if($id[0] == 'chain') array_push($chainIds, $id[1]);
			}
		}
		
		# Record the change
		$result = $this->_change->add(array(
				'descriptor_id'=>$descriptorId, 
				'description'=>htmlentities('Possible Matches: Attached matching rules updated to <b>'.count($storeIds).' store rules</b> and <b>'.count($chainIds).' chain rules</b>'.
							(!empty($otherDetails['flags'])? '<br>Flags added'.(!empty($otherDetails['notes'])? ' with a note: '.$otherDetails['notes']: ''): ''), ENT_QUOTES), 
				'change_code'=>'attached_rules_updated', 
				'change_value'=>'store_rule_ids='.implode(',',$storeIds).'|chain_rule_ids='.implode(',',$chainIds), 
				'old_status'=>'',
				'new_status'=>($action == 'confirm'? 'verified': 'pending'),
				'flag_details'=>($action == 'flag' && !empty($otherDetails)? $otherDetails: array()),
				'user_id'=>$userId
			));
		log_message('debug', '_transaction/update_matches:: [2] result='.$result);
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	
	# get list of transaction by date
	function get_transactions_list_by_date($userId, $offset, $limit, $filters)
	{		
		log_message('debug', '_transaction/get_transactions_list_by_date');
		log_message('debug', '_transaction/get_transactions_list_by_date:: [1] userId='.$userId.' offset='.$offset.' limit='.$limit.' filters='.json_encode($filters));
		
		$dataType = !empty($filters['data_type'])? $filters['data_type']: '';
		
		switch($dataType){
			case 'category_list':
				return $this->_query_reader->get_list('get_transaction_match_category_list', array('transaction_id'=>$filters['transaction_id']));
			break;
			
		
			default:
				$phraseGiven = !empty($filters['phrase']);
				$adminIdGiven = !empty($filters['adminId']) && $filters['adminId'] != 'all';
				$bankIdGiven = !empty($filters['bankId']) && $filters['bankId'] != 'all';
				$statusGiven = !empty($filters['status']) && $filters['status'] != 'all_transactions';
				
				return $this->_query_reader->get_list('get_transactions_list_by_date', array(
						'phrase_condition'=>($phraseGiven? "WHERE MATCH(T.raw_store_name) AGAINST ('+\"".htmlentities($filters['phrase'])."\"') ": ''), 
						'admin_condition'=>($adminIdGiven? (!$phraseGiven? ' WHERE': ' AND')." _admin_id='".$filters['adminId']."' ": ''),
						'bank_condition'=>($bankIdGiven? (!$phraseGiven && !$adminIdGiven? ' WHERE': ' AND')." _bank_id ".($filters['bankId'] == 'other'? 
								" NOT IN (SELECT id FROM clout_v1_3cron.banks WHERE is_featured='Y')": "='".$filters['bankId']."' "): ''),
						'status_condition'=>($statusGiven? (!$phraseGiven && !$adminIdGiven && !$bankIdGiven? ' WHERE': ' AND')." match_status='".$filters['status']."' ": ''),
						'limit_text'=>' LIMIT '.$offset.','.$limit
				));
			break;
		}
	}
	
	
	
	
	
	
	
	
	
	# update the transaction categories
	function update_transaction_categories($userId, $transactionId, $subCategories, $otherDetails)
	{
		# a) remove all the current transaction sub category matches
		$result = $this->_query_reader->run('remove_transaction_sub_categories', array('transaction_id'=>$transactionId));
		
		# b) add the new transaction sub-category matches
		if($result) {
			$result = $this->_query_reader->run('add_transaction_sub_categories', array(
					'sub_category_ids'=>implode("','",$subCategories),
					'transaction_id'=>$transactionId
			));
		}
		
		return array('result'=>($result? 'SUCCESS': 'FAIL'));
	}
	
	
	
	
	
	
	
	
}


?>