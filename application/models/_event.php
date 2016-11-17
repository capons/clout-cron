<?php
/**
 * This class controls obtaining actions related to events.
 *
 * @author Rebecca Lin <rebeccal@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 06/02/2016
 */
class _event extends CI_Model
{
   # Get different list type of events with search functionality
   function get_event_list($userId, $location, $details, $filters, $limit, $offset)
   {
		log_message('debug', '_event/get_event_list');
		log_message('debug', '_event/get_event_list:: [1] userId='.$userId.' location='.json_encode($location).' details='.json_encode($details).' filters='.json_encode($filters).' limit='.$limit.' offset=',$offset);

      $status = (!empty($filters['status'])? $filters['status']: 'active');

      $searchString = '';
      $searchPhrase = (!empty($filters['searchString'])? ' MATCH(store_name) AGAINST ("'.$filters['searchString'].'") AND': '');
      $searchCatogory = (!empty($filters['categoryId'])? ' (SELECT _category_id FROM clout_v1_3.store_sub_categories WHERE _store_id =P.store_id AND _category_id='.$filters['categoryId'].' LIMIT 1) IS NOT NULL AND': '');
      $searchDate = (!empty($filters['eventDate'])? ' UNIX_TIMESTAMP("'.date('Y-m-d H:i:s',strtotime($filters['eventDate'])).'") BETWEEN UNIX_TIMESTAMP(start_date) AND UNIX_TIMESTAMP(end_date) AND': '');
	  $searchString .= ($searchPhrase.$searchDate.$searchDate);

      log_message('debug', '_event/get_event_list:: [2] searchstring='.$searchString);

      $result = $this->_query_reader->get_list('get_list_of_events',array(
			'user_id'=>$userId,
			'user_latitude'=>$location['latitude'],
			'user_longitude'=>$location['longitude'],
         'owner_type'=>$details['ownerType'],
         'promotion_types'=>$details['promotionTypes'],
         'status'=>$status,
         'list_type'=>$filters['listType'],
         'max_search_distance'=>$details['maxSearchDistance'],
			'search_string'=>$searchString,
         'limit_text'=>'limit '.$offset.', '.$limit
			));

      log_message('debug', '_event/get_event_list:: [3] result='.json_encode($result));
      return $result;
   }

   # Get event details by user id and event id
   function get_event_details($userId, $eventId)
   {
		log_message('debug', '_event/get_event_list');
		log_message('debug', '_event/get_event_list:: [1] userId='.$userId.' eventId='.$eventId);

      $result = $this->_query_reader->get_list('get_event_details',array(
			'user_id'=>$userId,
			'event_id'=>$eventId
			));

      log_message('debug', '_event/get_event_list:: [2] result='.json_encode($result));
      return $result;
   }

   # Record the response of user to an event
   function update_event_notice($userId, $promotionId, $storeId, $attendStatus, $eventStatus, $scheduledTime, $baseUrl)
	{
		log_message('debug', '_event/update_event_notice');
		log_message('debug', '_event/update_event_notice:: [1] userId='.$userId.' promotionId='.$promotionId.' storeId='.$storeId.' attendStatus='.$attendStatus.' eventStatus='.$eventStatus.' scheduledTime='.json_encode($scheduledTime));
      $msg='';

      $status = $this->_query_reader->get_row_as_array('get_previous_attend_status',array(
         'promotion_id'=>$promotionId,
		   'user_id'=>$userId
      ));

      log_message('debug', '_event/update_event_notice:: [2] status='.json_encode($status));

      $result = $this->_query_reader->run('add_promotion_notice',array(
         'promotion_id'=>$promotionId,
         'user_id'=>$userId,
         'store_id'=>$storeId,
         'attend_status'=>$attendStatus,
         'event_status'=>$eventStatus
      ));

      # Get details of the event
      $eventDetails = $this->get_event_details($userId, $promotionId);
      $eventDetails = $eventDetails[0];
      log_message('debug', '_event/update_event_notice:: [3] eventDetails='.json_encode($eventDetails));

      # If user response maybe(pending) and need reservation then schedule two reminder to send out in the future
		if($attendStatus == 'pending' && !empty($eventDetails) && $eventDetails['requires_reservation'] == 'Y'){

            $templateVariables = array('storename'=>$eventDetails['store_name'],
                                       'promotiontitle'=>$eventDetails['promotion_title'],
                                       'eventlink'=>$baseUrl."c/".encrypt_value($eventDetails['event_id']."--".format_id($userId)."--reserve"));

            $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                     'query' => 'get_message_template',
                     'variables' => array('message_type'=>'first_event_reminder')
            ));
            log_message('debug', '_event/update_event_notice:: [4] template='.json_encode($template));

            $reminder1 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
   						'message'=>array(
   							'senderType'=>'user',
   							'sendToType'=>'list',
   							'sendTo'=>array($userId),
                        'template'=>$template,
                        'templateId'=>$template['id'],
   							'subject'=>$template['subject'],
   							'body'=>$template['details'],
                        'sms'=>$template['sms'],
   							'saveTemplate'=>'N',
   							'scheduledSendDate'=>$scheduledTime[0],
   							'sendDate'=>'',
   							'methods'=>array("system","email","sms"),
                        'templateVariables'=> $templateVariables
   						),
   						'userId'=>$userId,
   						'organizationId'=>'',
   						'organizationType'=>''
   			));

            $template = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                     'query' => 'get_message_template',
                     'variables' => array('message_type'=>'second_event_reminder')
            ));
            log_message('debug', '_event/update_event_notice:: [5] template='.json_encode($template));

            $reminder2 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'schedule_send',
   						'message'=>array(
   							'senderType'=>'user',
   							'sendToType'=>'list',
   							'sendTo'=>array($userId),
                        'template'=>$template,
                        'templateId'=>$template['id'],
   							'subject'=>$template['subject'],
   							'body'=>$template['details'],
                        'sms'=>$template['sms'],
   							'saveTemplate'=>'N',
   							'scheduledSendDate'=>$scheduledTime[1],
   							'sendDate'=>'',
   							'methods'=>array("system","email","sms"),
                        'templateVariables'=>$templateVariables
   						),
   						'userId'=>$userId,
   						'organizationId'=>'',
   						'organizationType'=>''
   			));

		}

      # If the status was pending then delete the reminder messages
      if( $status['attend_status'] == 'pending' && !empty($eventDetails) && $eventDetails['requires_reservation'] == 'Y' && $result) {

         $template1 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'first_event_reminder')
         ));

         $template2 = server_curl(MESSAGE_SERVER_URL, array('__action'=>'get_row_as_array',
                  'query' => 'get_message_template',
                  'variables' => array('message_type'=>'second_event_reminder')
         ));

         $result = $this->_query_reader->run('delete_reminder_messages',array(
            'user_id'=>$userId,
            'store_name'=>$eventDetails['store_name'],
            'template_id'=>implode("','", array($template1['id'],$template2['id']))
         ));

      # If the event doesn't need reservation then delete record if user change their mind to maybe or not going
      } else if ( $status['attend_status'] == 'confirmed' && !empty($eventDetails) && $eventDetails['requires_reservation'] == 'N' && $result){

         $result = $this->_query_reader->run('delete_reservation_record',array(
            'user_id'=>$userId,
            'promotion_id'=>$promotionId
         ));
      }

		log_message('debug', '_event/update_event_notice:: [6] result='.json_encode($result));
      return array('result'=>(!empty($result) && $result? 'SUCCESS': 'FAIL'), 'msg'=>$msg);
	}

}
?>
