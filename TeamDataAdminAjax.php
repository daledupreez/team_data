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
			add_action($ajax_prefix . 'put_' . $action, array($this, 'put_' . $action . '_ajax'));
		}

		$get_only = array( 'all_lists', 'all_members', 'all_member_data', 'basic_match', 'season_names' );
		foreach ($get_only as $get_action) {
			add_action($ajax_prefix . 'get_' . $get_action, array( $this, 'get_' . $get_action . '_ajax'));
		}

		$put_only = array( 'member_simple', 'new_matches', 'season_repeat' );
		foreach ($put_only as $put_action) {
			add_action($ajax_prefix . 'put_' . $put_action, array( $this, 'put_' . $put_action . '_ajax'));
		}

		// set_option
		add_action($ajax_prefix . 'set_option', array($this, 'set_option_ajax'));

		// send email
		add_action($ajax_prefix . 'send_email', array($this, 'send_email_ajax'));

		// match updates
		add_action($ajax_prefix . 'update_score', array($this, 'update_score_ajax'));
		add_action($ajax_prefix . 'update_match', array($this, 'update_match_ajax'));
		add_action($ajax_prefix . 'delete_match', array($this, 'delete_match_ajax'));

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
			$op_value = stripslashes($_POST['option_value']);
			if ($op_value != null) {
				if (($option == 'smtp_password') && ($op_value == '-1')) {
					$op_value = '';
				}
				$this->set_option($option, $op_value);
				$response_data['set'] = true;
				$response_data['option'] = $option;
			}
		}
		echo json_encode($response_data);
		exit;
	}

	public function send_email_ajax() {
		header('Content-Type: application/json');
		$response_data = array( 'sent' => false );
		if ($this->check_nonce()) {
			$fields = array(
				"subject" => '',
				"replyto" => '',
				"message" => '',
				"list_id" => -1,
			);
			$this->get_post_values($fields,false);
			if ($fields['list_id'] == -1) {
				$list_ids = -1;
			}
			else {
				$list_ids = array( $fields['list_id'] );
			}
			if (($fields['message'] != '') && ($fields['subject'] != '')) {
				$options = array();
				if ( !empty($fields['replyto']) ) $options['ReplyTo'] = $fields['replyto'];
				$mailer = new TeamDataMailer();
				$response_data['sent'] = $mailer->send_mail($list_ids,$fields['subject'],$fields['message'],$options);
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
						$clean_match = $this->clean_match($match);
						$insertOK = $wpdb->insert($this->tables->match,$clean_match);
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
	 * Delete a match.
	*/
	public function delete_match_ajax() {
		global $wpdb;

		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
		);
		$match_id = $this->get_post_values($fields);
		$response_data = array( 'result' => 'error' );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		else if ( empty($match_id) ) { // id is required for delete
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'id');
		}
		else {
			$sql = $wpdb->prepare('DELETE FROM ' . $this->tables->match . ' WHERE id = %d', $match_id);
			$showErrors = $wpdb->hide_errors();
			$deleteOK = $wpdb->query($sql);
			if ($deleteOK) {
				$response_data['result'] = true;
			}
			else {
				$response_data['error_message'] = $wpdb->last_error;
			}
		}

		echo json_encode($response_data);
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
			'time' => '',
			'venue_id' => '',
			'level_id' => '',
			'is_league' => '0',
			'is_postseason' => '0',
			'opposition_id' => '',
			'tourney_name' => '',
			'our_score' => '',
			'opposition_score' => '',
			'result' => '',
			'season_id' => '',
			'comment' => '',
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
		else if (($fields['opposition_id'] === '') && ($fields['tourney_name'] === '')) {
			$response_data['error_message'] = __('Either tourney name or opposition_id must be supplied');
		}
		else {
			if ($fields['opposition_id'] === '') {
				unset($fields['opposition_id']);
			}
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
		$fields = array( 'time', 'venue_id', 'date', 'level_id', 'is_league', 'is_postseason', 'season_id' );
		$is_valid = true;
		foreach ($fields as $field) {
			if ((!isset($match[$field])) || ($match[$field] === '')) {
				$is_valid = false;
				$this->debug("declaring invalid because match['$field'] is " . (isset($match[$field]) ? '[empty]' : 'unset'));
				break;
			}
		}
		if ((!isset($match['tourney_name']) || ($match['tourney_name'] === '')) && (!isset($match['opposition_id']) || ($match['opposition_id'] === ''))) {
			$is_valid = false;
			$this->debug("declaring invalid because neither tourney_name nor opposition_id have been supplied");
		}
		if ($is_valid && $check_score) {
			if (isset($match['result'])) {
				if ($match['result'] != '') {
					$is_valid = (!isset($match['our_score'])) && (!isset($match['opposition_score']));
					if (!$is_valid) $this->debug("declaring invalid because match['our_score'] or match['opposition_score'] is defined");
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
			$ok = true;
			unset($fields['our_score']);
			unset($fields['opposition_score']);
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

	public function put_member_simple_ajax() {
		global $wpdb;

		header('Content-Type: application/json');
		$fields = array(
			'id' => '',
			'first_name' => '',
			'last_name' => '',
			'nick_name' => '',
			'email' => '',
			'cell' => '',
			'active' => true,
		);

		$member_id = $this->get_post_values($fields);

		$response_data = array( "result" => "error" );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __('Invalid nonce', 'team_data');
		}
		elseif ($this->validate_member($member_id,$fields,$response_data)) {
			$id_check = $wpdb->get_var( $wpdb->prepare('SELECT ID FROM ' . $this->tables->member . ' WHERE ID = %s LIMIT 0, 1', $member_id ) );
			$this->debug('$id_check = ' . $id_check);
			if ( $id_check != $member_id ) {
				$this->debug('Failed to find matching ID for supplied member_id \'' . $member_id . '\'');
				$response_data['error_message'] = sprintf( __("Member with ID '%s' not found", 'team_data'), $member_id );
			}
			else {
				// It would be preferable to wrap these two sets of updates in a transaction but that will need to wait for another day
				$list_error = $this->update_member_lists($member_id);
				if ($list_error != '') {
					$this->debug('list update failure: ' . $list_error);
					$response_data['error_message'] = $list_error;
				}
				else {
					$response_data = $this->run_update($this->tables->member,$fields,$member_id);
					if ($response_data['result'] != 'error') {
						$response_data['member'] = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $this->tables->member . ' WHERE ID = %s', $member_id ), ARRAY_A );
						$response_data['member']['lists'] = $wpdb->get_col( $wpdb->prepare( 'SELECT list_id FROM ' . $this->tables->member_list . ' WHERE member_id = %s', $member_id ), 0 );
					}
				}
			}
		}
		echo json_encode($response_data);
		exit;
	}

	public function put_member_ajax() {
		header('Content-Type: application/json');
		$response_data = $this->put_member();
		echo json_encode($response_data);
		exit;
	}

	/**
	 * Helper method to save a member. 
	 */
	public function put_member() {
		global $wpdb;

		$fields = array(
			'id' => '',
			'first_name' => '',
			'last_name' => '',
			'nick_name' => '',
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
			'past_clubs' => '',
			'active' => true,
		);

		$member_id = $this->get_post_values($fields);

		$response_data = array( "result" => "error" );
		if (!$this->check_nonce()) {
			$response_data['error_message'] = __("Invalid nonce", 'team_data');
		}
		elseif ($this->validate_member($member_id,$fields,$response_data)) {
			$lists = array();

			$show_errors = $wpdb->hide_errors();
			if (isset( $_POST[ 'list_names' ] )) {
				foreach ($_POST[ 'list_names' ] as $list_name => $chosen) {
					$list_id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $this->tables->list . ' WHERE display_name = %s ORDER BY id ASC', $list_name ) );
					if (!$list_id) {
						$list_id = $wpdb->get_var( $wpdb->prepare( 'SELECT id FROM ' . $this->tables->list . ' WHERE name = %s ORDER BY id ASC', $list_name ) );
					}
					if ($list_id) {
						$lists[ $list_id ] = $chosen;
					}
				}
			}
			// Only check hidden auto_enroll for new members
			if ($member_id == '') {
				$auto_lists = $wpdb->get_results( 'SELECT id FROM ' . $this->tables->list . ' WHERE auto_enroll = 1' );
				foreach ($auto_lists as $auto_list) {
					if (!isset($lists[ $auto_list->id ])) {
						$lists[ $auto_list->id ] = true;
					}
				}
			}
			if ($show_errors) $wpdb->show_errors();

			if ($member_id == '') $fields['joined'] = date("Y-m-d");

			$response_data = $this->run_update($this->tables->member,$fields,$member_id);
			// run list operations *AFTER* main UPDATE as we might be dealing with a new registration
			if ($response_data['result'] != 'error') {
				// get current member ID
				$curr_member_id = $response_data['result'];

				// make sure we have a translated lists value - non-admins should only see display names and NOT row ID's
				if (!isset($_POST[ 'lists' ])) {
					$_POST[ 'lists' ] = $lists;
				}
				$list_error = $this->update_member_lists($curr_member_id);
			}
		}
		return $response_data;
	}

	/**
	 * Private helper function to validate that the data in $fields contains enough data to be a valid member record.
	 *
	 * @param string $member_id The ID of the member being validated.
	 * @param array $fields The array containing the field values to be validated.
	 * @param array $response_data The array containing the eventual AJAX response data, which is passed by reference so any error messages can be returned.
	 */
	private function validate_member($member_id = '',&$fields,&$response_data) {
		global $wpdb;

		$is_valid = false;
		$fields['email'] = sanitize_email($fields['email']);
		if ($fields['first_name'] == '') { // first_name and last_name are required
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'first_name');
		}
		elseif ($fields['last_name'] == '') {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'last_name');
		}
		elseif (($fields['email'] == '') || (is_email($fields['email']) === false)) {
			$response_data['error_message'] = sprintf(__("Property '%s' is required", 'team_data'),'email');
		}
		else {
			$emailOK = true;
			if ($fields['email'] != '') {
				// check that we don't introduce duplicate email addresses
				$id_for_email = $wpdb->get_var( $wpdb->prepare('SELECT ID FROM ' . $this->tables->member . ' WHERE email = %s AND ID <> %s LIMIT 0, 1', $fields['email'], $member_id ) );
				if ((!is_null($id_for_email)) && ($id_for_email != $member_id)) {
					$this->debug('Email failed; id_for_email = ' . $id_for_email . '; member_id = ' . $member_id);
					$emailOK = false;
					$response_data['error_message'] = __( 'This email address has already been registered. Please contact the website administrator for more information.' , 'team_data' );
				}
			}
			if ($emailOK) {
				if (isset($fields['date_of_birth']) && ($fields['date_of_birth'] == '')) unset($fields['date_of_birth']);
				$is_valid = true;
			}
		}

		return $is_valid;
	}

	/**
	 * Private helper function to update the lists for a given member based on the data in the $_POSTS['lists'] array.
	 *
	 * @param string $member_id The ID of the member to run the update for.
	 */
	private function update_member_lists($member_id) {
		global $wpdb;

		$lists = array();
		if (isset($_POST['lists'])) {
			$lists = $_POST['lists'];
		}
		// turn off errors while running the update/check queries
		$showErrors = $wpdb->hide_errors();
		$problems = array();
		foreach ($lists as $list_id => $add_member) {
			$query_ok = true;
			if (!$add_member) {
				$query_ok = $wpdb->query( $wpdb->prepare( 'DELETE FROM ' . $this->tables->member_list . ' WHERE member_id = %s AND list_id = %s', $member_id, $list_id ) );
			}
			else {
				// check if the list actually exists
				if ($wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $this->tables->list . ' WHERE ID = %s', $list_id ) ) ) {
					// check if the member is registered for the list 
					if (!$wpdb->get_var( $wpdb->prepare( 'SELECT ID FROM ' . $this->tables->member_list . ' WHERE member_id = %s AND list_id = %s', $member_id, $list_id ) ) ) {
						// not on the list, register the member
						$query_ok = $wpdb->insert( $this->tables->member_list, array( 'member_id' => $member_id, 'list_id' => $list_id ), array( '%d', '%d' ) );
					}
				}
			}
			// Make SURE this is false, as the DELETE may return 0 as a row count
			if ($query_ok === false) {
				$problems[] = $list_id;
			}
		}
		// reinstate error display
		if ($showErrors) $wpdb->show_errors();

		if (count( $problems ) > 0) {
			return sprintf( __("Failed to update list memberships: %s"), implode(', ', $problems) );
		}
		return '';
	}

	public function get_all_members_ajax() {
		$this->run_select_all_ajax($this->tables->member,"CONCAT(first_name,' ',last_name) As name");
	}

	public function get_all_member_data_ajax() {
		header('Content-Type: application/json');
		if (!$this->check_nonce()) {
			$results = null;
		}
		else {
			$results = $this->get_all_member_data();
		}
		echo json_encode($results);
		exit;
	}

	public function get_all_member_data() {
		global $wpdb;

		$member_query = 'SELECT * FROM ' . $this->tables->member;
		$list_query = 'SELECT list_id FROM ' . $this->tables->member_list . ' WHERE member_id = ';
		$results = $wpdb->get_results($member_query, ARRAY_A);
		foreach ($results as &$result) {
			if (isset($result['id']) && ($result['id'] > 0)) {
				$lists = $wpdb->get_col($list_query . $result['id'], 0);
				$result['lists'] = $lists;
			}
			else {
				$result['lists'] = array();
			}
		}
		return $results;
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
			'auto_enroll' => 0,
			'display_name' => '',
			'admin_only' => 1,
			'from_email' => '',
			'from_name' => '',
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

	public function get_all_lists_ajax() {
		header('Content-Type: application/json');
		$response_data = $this->get_all_lists();

		echo json_encode($response_data);
		exit;
	}

	public function get_all_lists() {
		return $this->run_select_all($this->tables->list);
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
			'info_link' => '',
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
			if ($response_data['result'] == $season_id) {
				if (isset($_POST['is_current']) && ($_POST['is_current'] == 1)) {
					$this->set_option('current_season',$season_id);
				}
			}
		}

		echo json_encode($response_data);
		exit;
	}

	public function put_season_repeat_ajax() {
		global $wpdb;
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
			$showErrors = $wpdb->hide_errors();
			$seasons = $wpdb->get_results($last_season_query);
			foreach($seasons as $season) {
				$fields = array(
					'year' => $year,
					'season' => $season->season
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
	 * @param boolean $extract_id Flag to control whether we try to extract the ID field from the incoming data
	 * @return string $id_value Value of posted 'id' field
	 */
	protected function get_post_values(&$fields, $extract_id = true) {
		foreach (array_keys($fields) as $fieldName ) {
			if (isset($_POST[$fieldName])) {
				$fields[$fieldName] = stripslashes($_POST[$fieldName]);
			}
		}
		$id_value = '';
		if ($extract_id) {
			$id_value = $fields['id'];
			unset($fields['id']);
		}
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
			if (($updateCount >= 1) || ($wpdb->last_error == '')) {
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
	protected function run_select( $table, $id_field ) {
		global $wpdb;

		header('Content-Type: application/json');
		if ($this->check_nonce() && isset( $_POST[ $id_field ] )) {
			$id_value = intval( stripslashes( $_POST[ $id_field ] ) );
			$query = $wpdb->prepare( "SELECT * FROM $table WHERE id = %d", $id_value );
			$row_data = $wpdb->get_row( $query );

			echo json_encode( $row_data );
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
	protected function run_select_with_option( $table, $id_field, $option_name, $option_field ) {
		global $wpdb;

		header('Content-Type: application/json');
		if ($this->check_nonce() && isset( $_POST[ $id_field ] ) ) {
			$id_value = intval( stripslashes( $_POST[ $id_field ] ) );
			$query = $wpdb->prepare( "SELECT *, 0 AS $option_field FROM $table WHERE id = %d", $id_value );
			$row_data = $wpdb->get_row( $query );

			$option_value = $this->get_option( $option_name );
			if ( ( $option_value <> '' ) && ( $option_value == $id_value ) ) {
				$row_data->$option_field = 1;
			}
			echo json_encode( $row_data );
		}
		else {
			echo 'null';
		}
	}

	/**
	 * Protected helper function to ensure that the match JSON passed in doesn't contain any extra fields
	 *
	 * @param array $match Array of key/value pairs passed in to represent a match
	 * @return array $clean_match Array containing scrubbed key/value pairs
	 */
	protected function clean_match($match) {
		$clean_match = array(
			'id' => '',
			'date' => '',
			'time' => '',
			'venue_id' => '',
			'level_id' => '',
			'is_league' => '0',
			'is_postseason' => '0',
			'opposition_id' => '',
			'tourney_name' => '',
			'season_id' => '',
			'result' => '',
			'comment' => '',
			'our_score' => '',
			'opposition_score' => '',
		);
		foreach ($clean_match as $field_name => $field_value) {
			if ( isset($match[$field_name]) ) {
				$clean_match[$field_name] = $match[$field_name];
			}
		}
		if ( empty($clean_match['our_score'] ) ) {
			unset($clean_match['our_score']);
		}
		if ( empty($clean_match['opposition_score'] ) ) {
			unset($clean_match['opposition_score']);
		}
		return $clean_match;
	}

}
?>
