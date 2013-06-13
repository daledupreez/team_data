<?php

/**
* TeamData plugin Admin AJAX handler.
*/
class TeamDataAdminAjax extends TeamDataAjax {

	/**
	*  Declare all the AJAX method handlers for Wordpress
	*/
	public function add_actions() {
		if ( $this->actions_added ) return;
		// to decide: member, match_stat, cap, match
		$ajax_prefix = 'wp_ajax_team_data_';
		$actions = array( 'venue', 'level', 'list', 'team', 'stat', 'season', 'member');
		foreach($actions as $action) {
			add_action($ajax_prefix . 'get_' . $action, array($this, 'get_' . $action . '_ajax'));
			add_action($ajax_prefix . 'put_' . $action, array($this, 'put_' . $action , '_ajax'));
		}

		$get_only = array( 'all_members', 'basic_match', 'season_names' );
		foreach ($get_only as $get_action) {
			add_action($ajax_prefix . 'get_' . $get_action, array( $this, 'get_' . $get_action . '_ajax'));
		}

		$put_only = array( 'new_matches', 'season_repeat' );
		foreach ($put_only as $put_action) {
			add_action($ajax_prefix . 'put_' . $put_action, array( $this, 'put_' . $put_action . '_ajax'));
		}

		// set_option
		add_action($ajax_prefix . 'set_option', array($this, 'set_option_ajax'));

		// match updates
		add_action($ajax_prefix . 'update_score', array($this, 'update_score_ajax'));
		add_action($ajax_prefix . 'update_match', array($this, 'update_match_ajax'));

		add_action('wp_enqueue_scripts', array( $this, 'add_ajax_url' ) );

		$this->actions_added = true;
	}

	/**
	* Callback that will insert the AJAX URL and a nonce into the page.
	*/
	public function add_ajax_url() {
		wp_localize_script( 'team_data', 'team_data_ajax', array( 'ajaxurl' => admin_url( 'admin-ajax.php' ), 'nonce' => wp_create_nonce('team_data_nonce') ) );
	}

	/**
	* Handler to allow options to be set via AJAX.
	*/
	public function set_option_ajax() {
		header('Content-Type: application/json');
		$response_data = array( 'set' => false );
		if ($this->check_nonce() && isset($_POST['option_name']) && isset($_POST['option_value'])) {
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

	/**
	 * Handler to deal with multi-match submissions.
	*/
	public function put_new_matches_ajax() {
		global $wpdb;

		header('Content-Type: application/json');
		$response_data = array( 'result' => 'error', 'results' => array() );
		if ($this->check_nonce() && isset($_POST['match_data'])) {
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
							$response_data['results'][] = $wpdb->insert_id;
						}
						else {
							$all_good = false;
							$response_data['results'][] = $wpdb->last_error;
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

	/**
	 * Get basic match data
	*/
	public function get_basic_match_ajax() {
		$this->run_select($this->tables->match,'match_id');
		exit;
	}

	/**
	 * Update a match.
	*/
	public function update_match_ajax() {
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
			'season_id' => '',
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
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		else if ($match_id == '') { // id is required for update
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'id');
		}
		else {
			$response_data = $this->run_update($this->tables->match,$fields,$match_id);
		}

		echo json_encode($response_data);
		exit;
	}

	/**
	* Helper method to validate submitted match data.
	*/
	private function is_valid_match($match,$check_score) {
		if (!$match) return false;
		$fields = array( 'time', 'venue_id', 'date', 'opposition_id', 'level_id', 'is_league', 'is_postseason', 'season_id' );
		$is_valid = true;
		foreach ($fields as $field) {
			if ((!isset($match[$field])) || ($match[$field] === '')) {
				$is_valid = false;
				$this->debug("declaring invalid because match['$field'] is " . (isset($match[$field]) ? '[empty]' : 'unset'));
				break;
			}
		}
		if ($is_valid && $check_score) {
			if (isset($match['result'])) {
				if (($match['result'] == 'W') || ($match['result'] == 'D') || ($match['result'] == 'L')) {
					$is_valid = (!isset($match['our_score'])) && (!isset($match['opposition_score']));
					if (!$is_valid) $this->debug("declaring invalid because match['our_score'] or match['opposition_score'] is defined");
				}
				else {
					$is_valid = false;
					$this->debug("declaring invalid because match['result'] is " . ($match['result'] === '') ? '[empty]' : $match['result']);
				}
			}
			else {
				if ((!isset($match['our_score'])) || ($match['our_score'] === '')) {
					$is_valid = false;
					$this->debug("declaring invalid because match['our_score'] is " . (isset($match['our_score']) ? '[empty]' : 'unset'));
				}
				elseif ((!isset($match['opposition_score'])) || ($match['opposition_score'] === '')) {
					$is_valid = false;
					$this->debug("declaring invalid because match['opposition_score'] is " . (isset($match['opposition_score']) ? '[empty]' : 'unset'));
				}
			}
		}
		return $is_valid;
	}

	public function update_score_ajax() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'our_score' => '',
			'opposition_score' => '',
			'result' => '',
		);
		$match_id = $this->get_post_values($fields);

		$response_data = array( 'result' => 'error' );
		$ok = false;
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields['result'] !== '') {
			if (($fields['result'] == 'W') || ($fields['result'] == 'D') || ($fields['result'] == 'L')) {
				$ok = true;
				unset($fields['our_score']);
				unset($fields['opposition_score']);
			}
			else {
				$response_data['error_message'] = sprintf(__("Property '%s' is invalid", 'team_data'),'result');
			}
		}
		else {
			if ($fields['our_score'] === '') { // our_score and opposition_score are required
				$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'our_score');
			}
			elseif ($fields['opposition_score'] === '') {
				$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'opposition_score');
			}
			else {
				$ok = true;
				$fields['result'] = '';
			}
		}
		if ($ok) $response_data = $this->run_update($this->tables->match,$fields,$match_id);

		echo json_encode($response_data);
		exit;
	}

	public function get_venue_ajax() {
		$this->run_select($this->tables->venue,'venue_id');
		exit;
	}

	public function put_venue_ajax() {
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
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields['name'] == '') { // name is required
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$response_data = $this->run_update($this->tables->venue,$fields,$venue_id);
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_level_ajax() {
		$this->run_select($this->tables->level,'level_id');
		exit;
	}

	public function put_level_ajax() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'abbreviation' => '',
		);
		$level_id = $this->get_post_values($fields);

		$response_data = array( "result" => "error" );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields["name"] == '') { // name is required
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$response_data = $this->run_update($this->tables->level,$fields,$level_id);
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_member_ajax() {
		$this->run_select($this->tables->member,'member_id');
		exit;
	}

	public function put_member_ajax() {
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

		$response_data = array( "result" => "error" );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields['first_name'] == '') { // first_name and last_name are required
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'first_name');
		}
		elseif ($fields['last_name'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'last_name');
		}
		else {
			$response_data = $this->run_update($this->tables->member,$fields,$member_id);
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_all_members_ajax() {
		$this->run_select_all_ajax($this->tables->member,"CONCAT(first_name,' ',last_name) As name");
	}

	public function get_list_ajax() {
		$this->run_select($this->tables->list,'list_id');
		exit;
	}

	public function put_list_ajax() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'comment' => '',
		);
		$list_id = $this->get_post_values($fields);

		$response_data = array( 'result' => 'error' );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields['name'] == '') { // name is required
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$response_data = $this->run_update($this->tables->list,$fields,$list_id);
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_team_ajax() {
		$this->run_select_with_option($this->tables->team,'team_id','our_team','is_us');
		exit;
	}

	public function put_team_ajax() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'logo_link' => '',
			'abbreviation' => '',
		);
		$team_id = $this->get_post_values($fields);

		$response_data = array( 'result' => 'error' );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields['name'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$response_data = $this->run_update($this->tables->team,$fields,$team_id);
			if (($response_data >= 1) && isset($_POST['is_us']) && ($_POST['is_us'] == 1)) {
				$this->set_option('our_team',$team_id);
			}
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_stat_ajax() {
		$this->run_select($this->tables->stat,'stat_id');
		exit;
	}

	public function put_stat_ajax() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'name' => '',
			'value_type' => '0',
		);
		$stat_id = $this->get_post_values($fields);

		$response_data = array( 'result' => 'error' );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields['name'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'name');
		}
		else {
			$response_data = $this->run_update($this->tables->stat,$fields,$stat_id);
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_season_ajax() {
		$this->run_select_with_option($this->tables->season,'season_id','current_season','is_current');
		exit;
	}

	public function put_season_ajax() {
		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'year' => '',
			'season' => ''
		);
		$season_id = $this->get_post_values($fields);

		$response_data = array( "result" => "error" );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($fields["year"] == '') { // year and season are required
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'year');
		}
		elseif ($fields["season"] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'season');
		}
		else {
			$response_data = $this->run_update($this->tables->season,$fields,$season_id);
			if ($response['result'] == $season_id) {
				if (isset($_POST['is_current']) && ($_POST['is_current'] == 1)) {
					$this->set_option('current_season',$season_id);
				}
			}
		}

		echo json_encode($response_data);
		exit;
	}

	public function put_season_repeat_ajax() {
		header('Content-Type: application/json');

		$response_data = array( "result" => "error" );
		$year = '';
		if (isset($_POST['year'])) {
			$year = $_POST['year'];
		}
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($year == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'year');
		}
		else {
			$response_data['error_message'] = '';
			$response_data['result'] = '';
			$table = $this->tables->season;
			$last_season_query = "SELECT `season` FROM `$table` s 
				JOIN 
					( SELECT `year` FROM `$table` ORDER BY ID DESC Limit 1) sub 
				ON s.`year` = sub.`year`
				ORDER BY s.ID ASC";
			$seasons = $wpdb->get_results($last_season_query);
			$showErrors = $wpdb->hide_errors();
			foreach($seasons as $season) {
				$fields = array(
					'year' => $year,
					'season' => $season
				);
				$insertOK = $wpdb->insert($this->tables->season,$fields);
				if ($insertOK) {
					$response_data['result'] .= $wpdb->insert_id . ',';
				}
				else {
					$response_data['error_message'] .= $wpdb->last_error . ',';
				}
			}
			if ($response_data['result'] == '') unset($response_data['result']);
			if ($response_data['error_message'] == '') unset($response_data['error_message']);
			if ($showErrors) $wpdb->show_errors();
		}
		echo json_encode($response_data);
		exit;
	}

	public function get_season_names_ajax() {
		global $wpdb;
		header('Content-Type: application/json');
		if (!$this->check_nonce()) {
			echo 'null';
		}
		else {
			$name_query = "SELECT DISTINCT `season` FROM $this->tables->season GROUP BY `season`";
			$results = $wpdb->get_results($name_query, ARRAY_A);
			echo json_encode($results);
		}
		exit;
	}

	/**
	 * Protected helper function to populate $fields
	 * with data from the incoming POST values and clean out "id" from POST data
	 *
	 * @param array $fields Array of key/value pairs where we will check for the presence of the keys in the POSTed data and update in-place
	 * @return string $id_value Value of posted 'id' field
	 */
	protected function get_post_values(&$fields) {
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
	 * Protected helper function to run an INSERT or UPDATE against the table specified
	 * in $table, using $fields as the column/value pairs to update.
	 *
	 * @param string $table Name of table to update
	 * @param array $fields Array of column/value pairs to update
	 * @param string $id_val ID value to update. If empty or null, will perform an INSERT, otherwise do an UPDATE
	 * @return array $response_data associate array to return as a response
	 */
	protected function run_update($table,$fields,$id_val) {
		global $wpdb;

		$response_data = array( 'result' => 'error' );
		$showErrors = $wpdb->hide_errors();
		if (($id_val == '') || ($id_val == null)) {
			$insertOK = $wpdb->insert($table,$fields);
			if ($insertOK) {
				$response_data['result'] = $wpdb->insert_id;
			}
			else {
				$response_data['error_message'] = $wpdb->last_error;
			}
		}
		else {
			$updateCount = $wpdb->update($table, $fields, array("id" => $id_val));
			if ($updateCount >= 1) {
				$response_data['result'] = $id_val;
			}
			else {
				$response_data['error_message'] = $wpdb->last_error;
			}
		}
		if ($showErrors) $wpdb->show_errors();
		return $response_data;
	}

	/**
	 * Protected helper function to SELECT a row from $table where ID is the value in the POSTed variable $id_field
	 * and write out the data as a JSON object
	 *
	 * @param string $table Name of table
	 * @param string $id_field Name of the POSTed variable that should contain the ID
	 */
	protected function run_select($table,$id_field) {
		global $wpdb;

		header('Content-Type: application/json');
		if ($this->check_nonce() && isset($_POST[$id_field])) {
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
	 * Protected helper function to SELECT a row from $table where ID is the value in the POSTed variable $id_field,
	 * as well as including a 0 or 1 in property $option_field to indicate whether the value of option $option_name
	 * matches the value of $id_field.
	 * The combined data is then written out as a JSON object
	 *
	 * @param string $table Name of table
	 * @param string $id_field Name of the POSTed variable that should contain the ID
	 * @param string $option_name Name of the TeamData option to compare against
	 * @param string $option_field Name of the field in the returned JSON
	 */
	protected function run_select_with_option($table,$id_field,$option_name,$option_field) {
		global $wpdb;

		header('Content-Type: application/json');
		if ($this->check_nonce() && isset($_POST[$id_field])) {
			$id_value = $wpdb->escape(intval($_POST[$id_field]));
			$query = "SELECT *, 0 As $option_field FROM $table WHERE id = $id_value";
			$row_data = $wpdb->get_row($query);

			$option_value = $this->get_option($option_name);
			if (($option_value <> '') && ($option_value == $id_value)) {
				$row_data->$option_field = 1;
			}
			echo json_encode($row_data);
		}
		else {
			echo 'null';
		}
	}

	protected function check_nonce() {
		return (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'team_data_nonce'));
	}
}
?>
