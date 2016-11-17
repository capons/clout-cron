<?php
/**
 * This class generates and formats promotion details. 
 *
 * @author Al Zziwa <al@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 07/21/2015
 */
class _promotion extends CI_Model
{
	
	
	#Display extra offer conditions in a mode a user can view
	function details($promotionId, $fields, $userId='')
	{
		log_message('debug', '_promotion/details');
		log_message('debug', '_promotion/details:: [1] promotionId='.$promotionId.' fields='.json_encode($fields).' userId='.$userId);
		
		$offer = $this->_query_reader->get_row_as_array('get_promotion_by_id',array('promotion_id'=>$promotionId )); 
		log_message('debug', '_promotion/details:: [2] offer='.json_encode($offer));
		
		#Pick the required fields from the returned data
		$result = array();
		foreach($fields AS $field){
			$result[$field] = !empty($offer[$field])? $offer[$field]: '';
		}
		if(in_array('extra_conditions', $fields)) $result['extra_conditions'] = $this->extra_offer_conditions($promotionId, $userId);
		if(in_array('offer_bar_code', $fields) && !empty($offer['date_entered'])) $result['offer_bar_code'] = $this->bar_code($promotionId, $offer['date_entered']);
		
		log_message('debug', '_promotion/details:: [2] result='.json_encode($result));
		return $result;
	}
	
	
	
	# Check if a promotion requires scheduling
	function requires_scheduling($promotionId)
	{
		log_message('debug', '_promotion/requires_scheduling');
		log_message('debug', '_promotion/requires_scheduling:: [1] promotionId='.$promotionId);
		return $this->does_promotion_have_rule($promotionId, 'requires_scheduling')? 'Y': 'N';
	}
	
	
	
	# Generate the offer bar-code
	function bar_code($promotionId, $promotionDate)
	{
		log_message('debug', '_promotion/bar_code');
		log_message('debug', '_promotion/bar_code:: [1] promotionId='.$promotionId.' promotionDate='.$promotionDate);
		
		$date = date('Ymd-Hi', strtotime($promotionDate)).'-'.str_pad(strtoupper(dechex($promotionId)),10,'0',STR_PAD_LEFT);
		log_message('debug', '_promotion/bar_code:: [1] date='.json_encode($date));
		
		return $date;
	}
	
	
	
	
	#Does promotion has a given rule attached to it
	function does_promotion_have_rule($promotionId, $ruleCode)
	{
		log_message('debug', '_promotion/does_promotion_have_rule');
		log_message('debug', '_promotion/does_promotion_have_rule:: [1] promotionId='.$promotionId.' ruleCode='.$ruleCode);
		
		$rule = $this->_query_reader->get_row_as_array('get_rule_for_promotion', array('promotion_id'=>$promotionId, 'rule_type'=>$ruleCode));
		log_message('debug', '_promotion/does_promotion_have_rule:: [2] rule='.json_encode($rule));
		
		return !empty($rule);
	}
	
	
	
	
	
	#Display extra offer conditions in a mode a user can view
	function extra_offer_conditions($promotionId, $userId='')
	{
		log_message('debug', '_promotion/apply_rules');
		log_message('debug', '_promotion/apply_rules:: [1] promotionId='.$promotionId.' userId='.$userId);
		
		$display = array();
		#1. Get all active rules of the promotion
		$promotionRules = $this->_query_reader->get_list('get_promotion_rules', array('promotion_id'=>$promotionId )); 
		log_message('debug', '_promotion/apply_rules:: [2] promotionRules='.json_encode($promotionRules));
		
		#2. Now format the rule into values readable by a user
		foreach($promotionRules AS $rule)
		{
			$amountBreakdown = explode('|', $rule['rule_amount']);
			$valueBreakdown = !empty($amountBreakdown[1])? explode('-', $amountBreakdown[1]): array();
			
			switch($rule['rule_type'])
			{
				case 'schedule_available':
					array_push($display, "On ".date('l', strtotime($amountBreakdown[0]))."s at ".date('g:ia', strtotime($valueBreakdown[0]))." to ".(!empty($valueBreakdown[1])? date('g:ia', strtotime($valueBreakdown[1])): 'Late'));
				break;
				
				case 'schedule_blackout':
					array_push($display, "Except On ".date('l', strtotime($amountBreakdown[0]))."s at ".date('g:ia', strtotime($valueBreakdown[0]))." to ".(!empty($valueBreakdown[1])? date('g:ia', strtotime($valueBreakdown[1])): 'Late'));
				break;
				
				case 'how_many_uses':
					array_push($display, "Max ".$amountBreakdown[0]." uses");
				break;
				
				case 'distance_from_location':
					array_push($display, "Atleast ".$amountBreakdown[0]." miles from ".$valueBreakdown[0]);
				break;
				
				case 'at_the_following_stores':
					$storeIdList = explode(',', $amountBreakdown[0]);
					$storeAddress = "";
					foreach($storeIdList AS $id)
					{
						$store = $this->_query_reader->get_row_as_array('get_store_locations_by_id', array('store_id'=>$id, 'user_id'=>$userId));
						$storeAddress .= "<br>".$store['full_address'];
					}
					array_push($display, "At the following stores: ".$storeAddress);
				break;
				
				case 'for_new_customers':
					array_push($display, "New customers");
				break;
				
				case 'per_transaction_spending_greater_than':
					array_push($display, "For spending greater than ".$amountBreakdown[0]);
				break;
				
				case 'life_time_spending_greater_than':
					array_push($display, "For lifetime spending greater than ".$amountBreakdown[0]);
				break;
				
				case 'life_time_visits_greater_than':
					array_push($display, "For lifetime visits greater than ".$amountBreakdown[0]);
				break;
				
				case 'last_visit_occurred':
					array_push($display, "If last visit occured after ".date('m/d/Y', strtotime($amountBreakdown[0])));
				break;
				
				case 'only_those_who_visited_competitors':
					array_push($display, "If you visited our competitor");
				break;
				
				case 'accepted_gender':
					array_push($display, ucwords($amountBreakdown[0])."s only");
				break;
				
				case 'age_range':
					array_push($display, "Age ".implode('-', $amountBreakdown).'yrs');
				break;
				
				case 'network_size_greater_than':
					array_push($display, "If your network size is greater than ".$amountBreakdown[0]);
				break;
				
				default:
				break;
			}
		}
		
		log_message('debug', '_promotion/apply_rules:: [3] display='.json_encode($display));
		return $display;
	}


	
	
	
	
	
	
	
	# STUB: Apply the promo rules to check if a user qualifies for the chosen offerlist
	function apply_rules($storeId, $userId, $offers)
	{
		log_message('debug', '_promotion/apply_rules');
		log_message('debug', '_promotion/apply_rules:: [1] storeId='.$storeId.' userId='.$userId.' offsers='.$offers);
		$offersList = $offers;
		
		#TODO: Apply rules to check if user qualifies
		
		
		return $offersList;
	}
	
	
	
	
}

?>