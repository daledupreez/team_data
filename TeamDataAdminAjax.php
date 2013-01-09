<?php

/**
* TeamData plugin Admin AJAX handler.
*/
class TeamDataAdminAjax extends TeamDataBase {

	public function add_actions($group = 'simple') {
		// to decide: member, match_stat, cap, match
		$actions = array();
		$actions = array( 'venue', 'level', 'role', 'opposition', 'stat' );
		foreach($actions as $simple_action) {
			add_action('wp_ajax_team_data_get_' . $simple_action, array($this, 'get_' . $simple_action));
			add_action('wp_ajax_team_data_put_' . $simple_action, array($this, 'put_' . $simple_action));
			add_action('wp_ajax_team_data_get_all_' . $simple_action . 's', array($this, 'get_all_' . $simple_action . 's'));
		}
		
		// member
		add_action('wp_ajax_team_data_get_member', array($this, 'get_member'));
		add_action('wp_ajax_team_data_put_member', array($this, 'put_member'));
		add_action('wp_ajax_team_data_get_all_members', array($this, 'get_all_members'));
		// set_option
		add_action('wp_ajax_team_data_set_option', array($this, 'ajax_set_option'));
		// match
		add_action('wp_ajax_team_data_put_new_matches', array($this, 'put_new_matches'));
		add_action('wp_ajax_team_data_update_score', array($this, 'update_score'));
		add_action('wp_ajax_team_data_update_match', array($this, 'update_match'));
		add_action('wp_ajax_team_data_get_basic_match', array($this, 'get_basic_match'));
	}
	
	public function ajax_set_option() {
		header('Content-Type: application/json');
		$response_data = array( 'set' => false );
		if (isset($_POST['option_name']) && isset($_POST['option_value'])) {
			$option = $_POST['option_name'];
			$op_value = $_POST['option_value'];
			if (($op_value != 'null') && ($op_value !== '')) {
				$this->set_option($option, $op_value);
				$response_data['set'] = true;
			}
		}
		echo json_encode($response_data);
		exit;
	}

	public function put_new_matches() {
		global $wpdb;

		header('Content-Type: application/json');
		$response_data = array( 'result' => 'error', 'results' => array() );
		if (isset($_POST['match_data'])) {
			$match_data = $_POST['match_data'];
			$match_data = stripslashes($match_data);
			$matches = json_decode($match_data,true);
			if ($matches) {
				$match_count = 0;
				$all_good = true;
				$any_good = false;
				foreach($matches as $match) {
					$match_count = $match_count + 1;
					if ($this->is_valid_match($match,false)) {
						$insertOK = $wpdb->insert($this->tables->match,$match);
						if ($insertOK) {
							$any_good = true;
							$responseData['results'][] = $wpdb->insert_id;
						}
						else {
							$all_good = false;
							$responseData['results'][] = $wpdb->last_error;
						}
					}
					else {
						$response_data['results'][] = false;
						$all_good = false;
					}
				}
				if ($all_good) {
					$response_data['result'] = $match_count;
				}
				else if ($any_good) {
					$response_data['result'] = 'partial';
				}
			}
			else {
				$response_data['error_message'] = 'unable to parse match_data';
			}
		}
		else {
			$response_data['error_message'] = 'no match_data';
		}
		echo json_encode($response_data);
		exit;
	}
	
	public function get_basic_match() {
		$this->run_select($this->tables->match,'match_id');
		exit;
	}

	public function update_match() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'date' => '',
			'venue_id' => '',
			'level_id' => '',
			'is_league' => '0',
			'is_postseason' => '0',
			'opposition_id' => '',
			'our_score' => '',
			'opposition_score' => '',
		);
		$match_id = $this->get_post_values($fields);
		// remove our_score and opposition_score if not set
		if ($fields['our_score'] === '') {
			unset($fields['our_score']);
		}
		if ($fields['opposition_score'] === '') {
			unset($fields['opposition_score']);
		}
		$response_data = array( 'result' => 'error' );
		// id is required for update
		if ($match_id == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'id');
		}
		else {
			$response_data = $this->run_update($this->tables->match,$fields,$match_id);
		}

		echo json_encode($response_data);
		exit;
	}

	private function is_valid_match($match,$check_score) {
		if (!$match) return false;
		$fields = array( 'time', 'venue_id', 'date', 'opposition_id', 'level_id', 'is_league', 'is_postseason' );
		if ($check_score) {
			$fields[] = 'our_score';
			$fields[] = 'opposition_score';
		}
		$is_valid = true;
		foreach ($fields as $field) {
			if ((!isset($match[$field])) || ($match[$field] == '')) {
				$is_valid = false;
				$this->debug("declaring invalid because match['$field'] is " . (isset($match[$field]) ? '[empty]' : 'unset'));
				break;
			}
		}
		return $is_valid;
	}
	
	public function update_score() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'our_score' => '',
			'opposition_score' => '',
		);
		$match_id = $this->get_post_values($fields);

		$response_data = array( 'result' => 'error' );
		// our_score and opposition_score are required
		if ($fields['our_score'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'our_score');
		}
		else if ($fields['opposition_score'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'opposition_score');
		}
		else {
			$response_data = $this->run_update($this->tables->match,$fields,$match_id);
		}
		
		echo json_encode($response_data);
		exit;
	}
	
	public function get_venue() {
		$this->run_select($this->tables->venue,'venue_id');
		exit;
	}

	public function put_venue() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'is_home' => '0',
			'info_link' => '',
			'directions_link' => '',
			'abbreviation' => '',
		);
		$venue_id = $this->get_post_values($fields);

		$response_data = array( 'result' => 'error' );
		// name is required
		if ($fields['name'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$response_data = $this->run_update($this->tables->venue,$fields,$venue_id);
		}
		
		echo json_encode($response_data);
		exit;
	}

	public function get_all_venues() {
		$this->run_select_all($this->tables->venue);
		exit;
	}

	public function get_level() {
		$this->run_select($this->tables->level,'level_id');
		exit;
	}

	public function put_level() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'abbreviation' => '',
		);
		$level_id = $this->get_post_values($fields);

		$responseData = array( "result" => "error" );
		// name is required
		if ($fields["name"] == '') {
			$responseData['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$responseData = $this->run_update($this->tables->level,$fields,$level_id);
		}

		echo json_encode($responseData);
		exit;
	}
	
	public function get_all_levels() {
		$this->run_select_all($this->tables->level);
		exit;
	}

	public function get_member() {
		$this->run_select($this->tables->member,'member_id');
		exit;
	}

	public function put_member() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'first_name' => '',
			'email' => '',
			'backup_email' => '',
			'cell' => '',
			'tel_home' => '',
			'tel_work' => '',
			'address1' => '',
			'address2' => '',
			'city' => '',
			'state' => '',
			'postal_code' => '',
			'country' => '',
			'date_of_birth' => '',
			'height' => '',
			'weight' => '',
			'college_or_school' => '',
			'position' => '',
			'joined' => '',
			'past_clubs' => ''
		);

		$member_id = $this->get_post_values($fields);

		$responseData = array( "result" => "error" );
		// first_name and last_name are required
		if ($fields['first_name'] == '') {
			$responseData['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'first_name');
		}
		if ($fields['last_name'] == '') {
			$responseData['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'last_name');
		}
		else {
			$responseData = $this->run_update($this->tables->member,$fields,$member_id);
		}

		echo json_encode($responseData);
		exit;
	}
	
	public function get_all_members() {
		$this->run_select_all($this->tables->member,"CONCAT(first_name,' ',last_name)");
		exit;
	}

	public function get_role() {
		$this->run_select($this->tables->role,'role_id');
		exit;
	}

	public function put_role() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => ''
		);
		$role_id = $this->get_post_values($fields);

		$responseData = array( 'result' => 'error' );
		// name is required
		if ($fields['name'] == '') {
			$responseData['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$responseData = $this->run_update($this->tables->role,$fields,$role_id);
		}
		
		echo json_encode($responseData);
		exit;
	}

	public function get_all_roles() {
		$this->run_select_all($this->tables->role);
		exit;
	}

	public function get_opposition() {
		$this->run_select($this->tables->opposition,'opposition_id');
		exit;
	}

	public function put_opposition() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'logo_link' => '',
			'abbreviation' => '',
		);
		$opposition_id = $this->get_post_values($fields);

		$responseData = array( 'result' => 'error' );
		// name is required
		if ($fields['name'] == '') {
			$responseData['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$responseData = $this->run_update($this->tables->opposition,$fields,$opposition_id);
		}
		
		echo json_encode($responseData);
		exit;
	}

	public function get_all_oppositions() {
		$this->run_select_all($this->tables->opposition);
		exit;
	}
	
	public function get_stat() {
		$this->run_select($this->tables->stat,'stat_id');
		exit;
	}

	public function put_stat() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'value_type' => '0',
		);
		$stat_id = $this->get_post_values($fields);

		$responseData = array( 'result' => 'error' );
		// name is required
		if ($fields['name'] == '') {
			$responseData['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$responseData = $this->run_update($this->tables->stat,$fields,$stat_id);
		}
		
		echo json_encode($responseData);
		exit;
	}

	public function get_all_stats() {
		$this->run_select_all($this->tables->stat);
		exit;
	}

	/**
	 * Private helper function to populate $fields
	 * with data from the incoming POST values and clean out "id" from POST data
	 *
	 * @param array $fields Array of key/value pairs where we will check for the presence of the keys in the POSTed data and update in-place
	 * @return string $id_value Value of posted 'id' field
	 */
	private function get_post_values(&$fields) {
		foreach (array_keys($fields) as $fieldName ) {
			if (isset($_POST[$fieldName])) {
				$fields[$fieldName] = $_POST[$fieldName];
			}
		}
		$id_value = $fields['id'];
		unset($fields['id']);
		return $id_value;
	}

	/**
	 * Private helper function to run an INSERT or UPDATE against the table specified
	 * in $table, using $fields as the column/value pairs to update.
	 *
	 * @param string $table Name of table to update
	 * @param array $fields Array of column/value pairs to update
	 * @param string $id_val ID value to update. If empty or null, will perform an INSERT, otherwise do an UPDATE
	 * @return array $responseData associate array to return as a response
	 */
	private function run_update($table,$fields,$id_val) {
		global $wpdb;

		$responseData = array( 'result' => 'error' );
		$showErrors = $wpdb->hide_errors();
		if (($id_val == '') || ($id_val == null)) {
			$insertOK = $wpdb->insert($table,$fields);
			if ($insertOK) {
				$responseData['result'] = $wpdb->insert_id;
			}
			else {
				$responseData['error_message'] = $wpdb->last_error;
			}
		}
		else {
			$updateCount = $wpdb->update($table, $fields, array("id" => $id_val));
			if ($updateCount >= 1) {
				$responseData['result'] = $id_val;
			}
			else {
				$responseData['error_message'] = $wpdb->last_error;
			}
		}
		if ($showErrors) $wpdb->show_errors();
		return $responseData;
	}

	/**
	 * Private helper function to SELECT a row from $table where ID is the value in the POSTed variable $id_field
	 * and write out the data as a JSON object
	 *
	 * @param string $table Name of table
	 * @param string $id_field Name of the POSTed variable that should contain the ID
	 */
	private function run_select($table,$id_field) {
		global $wpdb;

		header('Content-Type: application/json');
		if (isset($_POST[$id_field])) {
			$id_value = $wpdb->escape(intval($_POST[$id_field]));
			$query = "SELECT * FROM $table WHERE id = $id_value";
			$row_data = $wpdb->get_row($query);

			echo json_encode($row_data);
		}
		else {
			echo 'null';
		}
	}

	/**
	 * Private helper function to SELECT id and name from all rows in table specified by $table
	 * and then write out the data in JSON format
	 *
	 * @param string $table Name of table
	 * @param string $name_col Name/expression for name value
	 */
	private function run_select_all($table,$name_col = "name") {
		global $wpdb;
		
		header('Content-Type: application/json');
		$all_query = "SELECT id, $name_col FROM $table";
		$results = $wpdb->get_results($all_query, ARRAY_A);
		echo json_encode($results);
	}
}
?>