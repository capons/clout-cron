<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * This class handles running the queries or over-the-api commands to the cron server.
 *
 * @author Al Zziwa <azziwa@clout.com>
 * @version 1.3.0
 * @copyright Clout
 * @created 02/02/2016
 */

class Main extends CI_Controller
{
	#Constructor to set some default values at class load
	function __construct()
    {
        parent::__construct();
		$this->load->model('_bank_api');
		$this->load->model('_transaction');
		$this->load->model('_change');
		$this->load->model('_event');
	}


	# the receiver for all the queries
	function index()
	{
		log_message('debug', 'Main/index');

		$_POST = !empty($_POST)? $_POST: array();

		# testing on the API
		$data = filter_forwarded_data($this);
		if(!empty($data) && !empty($data['ctest'])) $_POST = array_merge($_POST, $data);
		if(!empty($_GET) && !empty($_GET['__check'])) $_POST = array_merge($_POST, $_GET);

		# return error if there is no post
		if(empty($_POST) || empty($_POST['__action'])) {
			echo json_encode(array('code'=>'1000', 'message'=>'bad request', 'resolve'=>'No instruction data posted.'));
			return 0;
		}
		log_message('debug', 'Main/index:: [1] _action ['.$_POST['__action'].']');

		# instruct the query reader to use the read db for read requests from other servers
		$_POST['variables']['__source'] = 'read';


		# Test IAM DB connection through the API
		if($_POST['__action'] == 'test_db')
		{
			$mysqli = new mysqli(HOSTNAME_CRON_WRITE, USERNAME_CRON_WRITE, PASSWORD_CRON_WRITE, DATABASE_CRON_WRITE, DBPORT_CRON_WRITE);
			echo json_encode(array('IS'=>($mysqli->ping()? 'CONNECTED': 'NO CONNECTION') ));
		}



		# Run a generic query on the database
		else if($_POST['__action'] == 'run')
		{
			log_message('debug', 'Main/index/run');

			$result = $this->_query_reader->run($_POST['query'], $_POST['variables'], (!empty($_POST['strict']) && $_POST['strict'] == 'true'));
			log_message('debug', 'Main/index/run:: [1] result='.$result);
			# determine what to return
			if(!empty($_POST['return']) && $_POST['return'] == 'plain') echo json_encode($result);
			else echo json_encode(array('result'=>($result? 'SUCCESS': 'FAIL') ));
		}


		# Run a generic query on the database
		else if($_POST['__action'] == 'add_data')
		{
			log_message('debug', 'Main/index/add_data');

			$id = $this->_query_reader->add_data($_POST['query'], $_POST['variables']);
			log_message('debug', 'Main/index/add_data:: [1] id='.json_encode($id));
			# determine what to return
			if(!empty($_POST['return']) && $_POST['return'] == 'plain') echo json_encode($id);
			else echo json_encode(array('id'=>$id));
		}


		# Run a generic query on the database
		else if($_POST['__action'] == 'get_list')
		{
			log_message('debug', 'Main/index/get_list');

			$list = $this->_query_reader->get_list($_POST['query'], $_POST['variables']);
			log_message('debug', 'Main/index/get_list:: [1] list='.json_encode($list));
			echo json_encode($list);
		}


		# Run a generic query on the database
		else if($_POST['__action'] == 'get_row_as_array')
		{
			log_message('debug', 'Main/index/get_row_as_array');

			$row = $this->_query_reader->get_row_as_array($_POST['query'], $_POST['variables']);
			log_message('debug', 'Main/index/get_row_as_array:: [1] list='.json_encode($row));
			echo json_encode($row);
		}


		# Run a generic query on the database
		else if($_POST['__action'] == 'get_single_column_as_array')
		{
			log_message('debug', 'Main/index/get_single_column_as_array');

			$list = $this->_query_reader->get_single_column_as_array($_POST['query'], $_POST['column'], $_POST['variables']);
			log_message('debug', 'Main/index/get_single_column_as_array:: [1] list='.json_encode($list));
			echo json_encode($list);
		}


		# Record the first bank connection for the user
		else if($_POST['__action'] == 'record_first_bank_connection')
		{
			log_message('debug', 'Main/index/record_first_bank_connection');

			$this->load->model('_importer');

			$result = $this->_importer->record_bank_connection($_POST['userId'], $_POST['bankId'], $_POST['accountData']); 
			log_message('debug', 'Main/index/record_first_bank_connection:: [1] result='.$result);
			echo json_encode(array('result'=>($result? 'SUCCESS': 'FAIL') ));
		}


		# delete API accounts
		else if($_POST['__action'] == 'delete_plaid_user_accounts')
		{
			log_message('debug', 'Main/index/delete_plaid_user_accounts');

			$result = $this->_bank_api->delete_api_accounts($_POST['user_ids']);
			log_message('debug', 'Main/index/delete_plaid_user_accounts:: [1] result='.$result);
			echo json_encode(array('result'=>($result? 'SUCCESS': 'FAIL') ));
		}


		# connect to bank
		else if($_POST['__action'] == 'connect_to_bank')
		{
			log_message('debug', 'Main/index/connect_to_bank');

			$response = $this->_bank_api->connect($_POST['credentials'], $_POST['post_data'], $_POST['user_id']);
			log_message('debug', 'Main/index/connect_to_bank:: [2] response='.json_encode($response));
			echo json_encode($response);
		}


		# get transaction match status
		else if($_POST['__action'] == 'get_transaction_match_status')
		{
			log_message('debug', 'Main/index/get_transaction_match_status');

			$response = $this->_transaction->status_summary($_POST['admin_id']);
			log_message('debug', 'Main/index/get_transaction_match_status:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# get transaction descriptors
		else if($_POST['__action'] == 'get_transaction_descriptor')
		{
			log_message('debug', 'Main/index/get_transaction_descriptor');

			$response = $this->_transaction->descriptor($_POST['data_type'], $_POST['descriptor_id'], $_POST['user_id'], $_POST['offset'], $_POST['limit'], (!empty($_POST['filters'])? $_POST['filters']: array()));
			log_message('debug', 'Main/index/get_transaction_descriptor:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# get transaction changes
		else if($_POST['__action'] == 'get_transaction_changes')
		{
			log_message('debug', 'Main/index/get_transaction_changes');

			$response = $this->_transaction->change($_POST['data_type'], $_POST['data_id'], $_POST['offset'], $_POST['limit'], $_POST['phrase'], $_POST['user_id']);
			log_message('debug', 'Main/index/get_transaction_changes:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# add a flag
		else if($_POST['__action'] == 'add_flag')
		{
			log_message('debug', 'Main/index/add_flag');

			$response = $this->_transaction->add_flag($_POST['data_type'], $_POST['change_id'], $_POST['stage'], $_POST['user_id'], $_POST['details']);
			log_message('debug', 'Main/index/add_flag:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# delete a flag
		else if($_POST['__action'] == 'delete_flag')
		{
			log_message('debug', 'Main/index/delete_flag');

			$response = $this->_transaction->delete_flag($_POST['data_type'], $_POST['flag_id'], $_POST['stage'], $_POST['user_id']);
			log_message('debug', 'Main/index/delete_flag:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# update descriptor scope
		else if($_POST['__action'] == 'update_descriptor_scope')
		{
			log_message('debug', 'Main/index/update_descriptor_scope');

			$response = $this->_transaction->update_scope($_POST['descriptor_id'], $_POST['scope_id'], $_POST['action'], $_POST['user_id'], $_POST['other_details']);
			log_message('debug', 'Main/index/update_descriptor_scope:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# add transaction sub-category
		else if($_POST['__action'] == 'add_transaction_sub_category')
		{
			log_message('debug', 'Main/index/add_transaction_sub_category');

			$response = $this->_transaction->add_sub_category($_POST['descriptor_id'], $_POST['category_id'], $_POST['new_sub_category'], $_POST['action'], $_POST['user_id']);
			log_message('debug', 'Main/index/add_transaction_sub_category:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# update descriptor categories
		else if($_POST['__action'] == 'update_descriptor_categories')
		{
			log_message('debug', 'Main/index/update_descriptor_categories');

			$response = $this->_transaction->update_categories($_POST['descriptor_id'], $_POST['sub_categories'], $_POST['suggested_sub_categories'], $_POST['action'], $_POST['user_id'], (!empty($_POST['other_details']) ? $_POST['other_details'] : array()));
			log_message('debug', 'Main/index/update_descriptor_categories:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# update transaction location
		else if($_POST['__action'] == 'update_transaction_location')
		{
			log_message('debug', 'Main/index/update_transaction_location');

			$response = $this->_transaction->update_location($_POST['descriptor_id'], $_POST['chain'], $_POST['store'], $_POST['action'], $_POST['user_id'], $_POST['other_details']);
			log_message('debug', 'Main/index/update_transaction_location:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# get matching rules
		else if($_POST['__action'] == 'get_matching_rules')
		{
			log_message('debug', 'Main/index/get_matching_rules');

			$response = $this->_transaction->matching_rules($_POST['descriptor_id'], $_POST['types'], $_POST['user_id'], $_POST['offset'], $_POST['limit'], $_POST['phrase']);
			log_message('debug', 'Main/index/get_matching_rules:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# add matching rule
		else if($_POST['__action'] == 'add_rule')
		{
			log_message('debug', 'Main/index/add_rule');

			$response = $this->_transaction->add_rule($_POST['descriptor_id'], $_POST['criteria'], $_POST['action'], $_POST['phrase'], $_POST['category'], $_POST['store_id'], $_POST['user_id']);
			log_message('debug', 'Main/index/add_rule:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# delete matching rule
		else if($_POST['__action'] == 'delete_rule')
		{
			log_message('debug', 'Main/index/delete_rule');

			$response = $this->_transaction->delete_rule($_POST['rule_id'], $_POST['stage'], $_POST['user_id']);
			log_message('debug', 'Main/index/delete_rule:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# update possible transaction matches
		else if($_POST['__action'] == 'update_matches')
		{
			log_message('debug', 'Main/index/update_matches');

			$response = $this->_transaction->update_matches($_POST['descriptor_id'], $_POST['rule_ids'], $_POST['action'], $_POST['user_id'], $_POST['other_details']);
			log_message('debug', 'Main/index/update_matches:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# update possible transaction matches
		else if($_POST['__action'] == 'get_records_for_data_cron')
		{
			log_message('debug', 'Main/index/get_records_for_data_cron');

			$this->load->model('_data');
			$response = $this->_data->get_table_records($_POST['table_name'], $_POST['field_list'], $_POST['condition'], $_POST['limit'], $_POST['offset']);
			log_message('debug', 'Main/index/get_records_for_data_cron:: [1] response='.json_encode($response));
			echo json_encode($response);
		}


		# add a job to the queue
		else if($_POST['__action'] == 'add_job_to_queue')
		{
			log_message('debug', 'Main/index/add_job_to_queue');
		
			$this->load->model('_queue_publisher');
			$data['id'] = !empty($_POST['jobId'])? $_POST['jobId']: '';
			$data['user'] = !empty($_POST['userId'])? $_POST['userId']: '';
			$data['code'] = !empty($_POST['jobCode'])? $_POST['jobCode']: '';
			$data['job'] = !empty($_POST['jobUrl'])?str_replace('__', '/', $_POST['jobUrl']):'';
			
			# add extra job details if passed
			$data['parameters'] = !empty($_POST['parameters'])? $_POST['parameters']: array();
			$result = $this->_queue_publisher->add_job_to_queue($data);
		}
		
		
		# get event list in inbox
		else if($_POST['__action'] == 'get_event_list')
		{
			$response = $this->_event->get_event_list($_POST['user_id'], $_POST['location'], $_POST['details'], $_POST['filters'], $_POST['limit'], $_POST['offset']);
			echo json_encode($response);
		}

		# update promotion_notice for event response
		else if($_POST['__action'] == 'update_event_notice')
		{
			$response = $this->_event->update_event_notice($_POST['user_id'], $_POST['promotion_id'], $_POST['store_id'], $_POST['attend_status'], $_POST['event_status'], $_POST['schedule_time'], $_POST['base_url']);
			echo json_encode($response);
		}

		# get event details by id
		else if($_POST['__action'] == 'get_event_details')
		{
			$response = $this->_event->get_event_details($_POST['user_id'], $_POST['event_id']);
			echo json_encode($response);
		}




		# get list of transactions by date
		else if($_POST['__action'] == 'get_transaction_list_by_date')
		{
			log_message('debug', 'Main/index/get_transaction_list_by_date');
			$filters = !empty($_POST['filters'])? $_POST['filters']: array();
			$filters['transaction_id'] = !empty($_POST['transaction_id'])? $_POST['transaction_id']: '';
			$filters['data_type'] = !empty($_POST['data_type'])? $_POST['data_type']: '';
			
			$response = $this->_transaction->get_transactions_list_by_date($_POST['user_id'], $_POST['offset'], $_POST['limit'], $filters);
			
			log_message('debug', 'Main/index/get_transaction_list_by_date:: [1] response='.json_encode($response));
			echo json_encode($response);
		}

		
		# update transaction categories
		else if($_POST['__action'] == 'update_transaction_categories')
		{
			$response = $this->_transaction->update_transaction_categories($_POST['user_id'], $_POST['transaction_id'], $_POST['sub_categories'],
						array('action'=>$_POST['action'], 'other_details'=>$_POST['other_details'])
					);
			echo json_encode($response);
		}








		# run a test function
		else if($_POST['__action'] == 'test_this')
		{
			$this->test_function();
		}


	}



	# this is a test function
	function test_function()
	{
		$this->load->model('_cron');
		$this->_query_reader->load_queries_into_cache();

	}














}

/* End of controller file */
