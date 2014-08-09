<?php

/**
* TeamData plugin PUBLIC AJAX handler.
*/
class TeamDataAjax extends TeamDataBase {

	public function add_actions() {
		if ( $this->actions_added ) return;
		$ajax_prefix = 'wp_ajax_team_data_';
		$public_ajax_prefix = 'wp_ajax_nopriv_team_data_';
		$actions = array( 'venue', 'level', 'team', 'stat', 'season' );
		foreach($actions as $simple_action) {
			add_action($ajax_prefix . 'get_all_' . $simple_action . 's', array($this, 'get_all_' . $simple_action . 's_ajax'));
			add_action($public_ajax_prefix . 'get_all_' . $simple_action . 's', array($this, 'get_all_' . $simple_action . 's_ajax'));
		}
		// match
		//add_action('wp_ajax_team_data_get_basic_match', array($this, 'get_basic_match'));
		add_action($public_ajax_prefix . 'public_get_matches', array($this, 'get_matches_ajax'));
		add_action($ajax_prefix . 'public_get_matches', array($this, 'get_matches_ajax'));
		add_action($public_ajax_prefix . 'register_member', array($this, 'register_member'));
		// allow logged in members to call register_member
		add_action($ajax_prefix . 'register_member', array($this, 'register_member'));
		$this->actions_added = true;
	}

	public function register_member() {
		global $wpdb;

		header('Content-Type: application/json');
		// ensure we don't overwrite an existing ID
		$_POST['id'] = '';
		if (!$this->check_nonce()) {
			$response_data = array(
				'result' => 'error',
				'error_message' => __("Invalid nonce", 'team_data'),
			);
		}
		else {
			$admin_ajax = new TeamDataAdminAjax();
			$response_data = $admin_ajax->put_member();
			// ensure we never send the member ID back via this channel
			if (($response_data['result'] != 'error') && ($response_data['result'])) {
				$response_data['result'] = 1;
				$new_member_to = $this->get_option('email_new_member_to');
				if ( !empty( $new_member_to ) ) {
					$this->send_new_member_email( $new_member_to );
				}
			}
		}

		echo json_encode($response_data);
		exit;
	}

	public function get_all_venues() {
		return $this->run_select_all($this->tables->venue);
	}

	public function get_all_venues_ajax() {
		$this->run_select_all_ajax($this->tables->venue);
	}

	public function get_all_levels() {
		return $this->run_select_all($this->tables->level, 'name', array( array( 'display_group', 'ASC' ), array( 'display_rank', 'ASC' ) ) );
	}

	public function get_all_levels_ajax() {
		$this->run_select_all_ajax($this->tables->level, 'name', array( array( 'display_group', 'ASC' ), array( 'display_rank', 'ASC' ) ) );
	}

	public function get_all_teams() {
		return $this->run_select_all($this->tables->team);
	}

	public function get_all_teams_ajax() {
		$this->run_select_all_ajax($this->tables->team);
	}

	public function get_all_stats() {
		return $this->run_select_all($this->tables->stat);
	}

	public function get_all_stats_ajax() {
		$this->run_select_all_ajax($this->tables->stat);
	}

	public function get_all_seasons() {
		return $this->run_select_all($this->tables->season,"CONCAT(`year`,' ',`season`) As name");
	}

	public function get_all_seasons_ajax() {
		$this->run_select_all_ajax($this->tables->season,"CONCAT(`year`,' ',`season`) As name");
	}

	/*
	 *	See the comments for the get_matches function in this class for the supported filters and return columns.
	 *	Note that get_ajax_conditions() creates a condition array based on the POSTed fields of the same names.
	 */
	public function get_matches_ajax() {
		$conditions = $this->get_ajax_conditions();

		$results = $this->get_matches($conditions);

		header('Content-Type: application/json');
		echo json_encode($results);
		exit;
	}

	/*
	 * The supported filters are as follows:
	 *	ID values:
	 *		start_season, end_season, season
	 *		level
	 *		team
	 *		venue
	 *	String values:
	 *		start_year, end_year, year
	 *
	 *	The return columns are as follows:
	 *		season, _date, _day, day_name, _month, _year, _time, level, team, tourney_name, our_score, their_score, result, venue, is_home
	 */
	public function get_matches($conditions) {
		global $wpdb;

		$sql_select = array( 
			"CONCAT(season.year,' ',season.season) AS season",
			"match.date AS `_date`",
			"DAYOFMONTH(match.date) AS `_day`",
			"DAYNAME(match.date) As day_name",
			"MONTHNAME(match.date) AS `_month`",
			"YEAR(match.date) AS `_year`",
			"match.time AS `_time`",
			"match.level_name",
			"match.team",
			"match.tourney_name AS tourney_name",
			"match.our_score AS our_score",
			"match.opposition_score AS their_score",
			"match.result AS result",
			"IF(venue.abbreviation = '', venue.name, venue.abbreviation) AS venue",
			"(venue.is_home = 1) AS is_home",
			"match.comment AS comment",
		);

		$sql_from = array(
			$this->tables->season . ' AS season',
			"( SELECT m.season_id, m.level_id, m.venue_id, m.date, m.time, m.tourney_name, m.our_score, m.opposition_score, m.result, m.comment, IF(m.opposition_id IS NULL, '', t.name) AS team, IF(level.abbreviation = '', level.name, level.abbreviation) AS level_name, level.display_group, level.display_rank FROM " . $this->tables->match . ' m LEFT OUTER JOIN ' . $this->tables->team . ' t ON m.opposition_id = t.id JOIN ' . $this->tables->level . ' level ON m.level_id = level.id ) AS `match`',
			$this->tables->venue . ' AS venue',
		);

		$sql_where = array(
			'match.season_id = season.id',
			'match.venue_id = venue.id',
		);

		$sql = 'SELECT ' . implode(', ',$sql_select) . ' FROM ' . implode(', ',$sql_from) . ' WHERE ' . implode(' AND ',$sql_where);
		$where_data = $this->build_where($conditions);
		$sql_sub_where = implode(' AND ',$where_data['statement']);
		if ($sql_sub_where != '') {
			$sql = $wpdb->prepare($sql . ' AND ' . $sql_sub_where, $where_data['args']);
		}
		$sql .= ' ORDER BY match.date ASC, match.time ASC, match.display_group ASC, match.display_rank ASC, match.level_name ASC';

		return $wpdb->get_results($sql, ARRAY_A);
	}

	protected function get_ajax_conditions() {
		$conditions = array();

		$rangeFields = array( 'season' => true, 'year' => false );
		$prefixes = array( 'start_', 'end_', '');

		foreach ($rangeFields as $field => $is_int) {
			foreach ($prefixes as $prefix) {
				$fieldName = $prefix . $field;
				if (isset($_POST[$fieldName])) $conditions[$fieldName] = ($is_int ? intval($_POST[$fieldName]) : $_POST[$fieldName]);
			}
		}

		// TODO - support multiple levels

		$simpleFields = array( 'level' => true, 'team' => true, 'venue' => true );

		foreach ($simpleFields as $field => $is_int) {
			if (isset($_POST[$field])) $conditions[$field] = ($is_int ? intval($_POST[$field]) : $_POST[$field]);
		}

		return $conditions;
	}

	protected function build_where($conditions) {
		$where = array(
			"args" => array(),
			"statement" => array()
		);

		$season = $this->get_start_end($conditions,'season',true);
		if ($season['count'] > 0) {
			if ($season['count'] == 1) {
				$where['statement'][] = 'season.id = %d';
				$where['args'][] = $season['value'];
			}
			else if ($season['count'] == 2) {
				$where['statement'][] = 'season.id >= %d AND season.id <= %d';
				$where['args'][] = $season['start'];
				$where['args'][] = $season['end'];
			}
		}

		$year = $this->get_start_end($conditions,'year',false);
		if ($year['count'] > 0) {
			if ($year['count'] == 1) {
				$where['statement'][] = 'season.year = %s';
				$where['args'][] = $year['value'];
			}
			else if ($year['count'] == 2) {
				$where['statement'][] = 'season.year >= %s AND season.year <= %s';
				$where['args'][] = $year['start'];
				$where['args'][] = $year['end'];
			}
		}

		$integerFields = array(
			'level' => 'match.level_id',
			'team' => 'team.id',
			'venue' => 'venue.id',
		);
		foreach ($integerFields as $field => $fieldRef) {
			if (isset($conditions[$field])) {
				$where['statement'][] = $fieldRef . ' = %d';
				$where['args'][] = intval($conditions[$field]);
			}
		}

		return $where;
	}

	protected function get_start_end($conditions,$field_name,$is_int = false) {
		$start = '';
		$end = '';
		$count = 0;
		$data = array();
		if (isset($conditions['start_' . $field_name])) {
			$start = $conditions['start_' . $field_name];
			if ($is_int) $start = intval($start);
			$count++;
		}
		if (isset($conditions['end_' . $field_name])) {
			$end = $conditions['end_' . $field_name];
			if ($is_int) $end = intval($end);
			$count++;
		}
		if ($count == 2) {
			if ($start > $end) {
				$temp = $end;
				$end = $start;
				$start = $temp;
			}
			$data['start'] = $start;
			$data['end'] = $end;
		}
		else if ($count == 1) {
			$value = ($start == '' ? $end : $start);
			$data['value'] = value;
		}
		else if (($count == 0) && isset($conditions[$field_name])) {
			$count = 1;
			$data['value'] = ( $is_int ? intval($conditions[$field_name]) : $conditions[$field_name] );
		}
		$data['count'] = $count;
		return $data;
	}

	/**
	 * Protected helper function to SELECT id and name from all rows in table specified by $table
	 * and then write out the data in JSON format
	 *
	 * @param string $table Name of table
	 * @param string $name_col Name/expression for name value
	 * @param array $order_by_cols Ordered list of columns to sort by in the form [ [ 'colname', 'ASC'], [ 'colname', 'DESC' ] ]. Both the column name and sort order are required.
	 */
	protected function run_select_all($table,$name_col = "name",$order_by_cols = null) {
		global $wpdb;

		$all_query = "SELECT id, $name_col FROM $table";

		$order_by = array();
		if ( isset($order_by_cols) ) {
			foreach ($order_by_cols as $pos => $order_by_pair) {
				if ( (!empty($order_by_pair)) && ( !empty($order_by_pair[0]) ) && ( !empty($order_by_pair[1]) ) ) {
					$sort_order = strtoupper( $order_by_pair[1] );
					if ( ($sort_order == 'ASC') || ($sort_order == 'DESC') ) {
						$colname = $order_by_pair[0];
						$order_by[] = $colname . ' ' . $sort_order;
					}
				}
			}
		}
		if ( count($order_by) > 0) {
			$all_query .= ' ORDER BY ' . implode(', ', $order_by);
		}
		else {
			$all_query .= ' ORDER BY id ASC';
		}
		return $wpdb->get_results($all_query, ARRAY_A);
	}

	protected function run_select_all_ajax($table,$name_col = "name") {
		header('Content-Type: application/json');
		$results = $this->run_select_all($table,$name_col);
		echo json_encode($results);
		exit;
	}

	/**
	 * Protected helper function to check that a TeamData nonce has been 
	 * supplied by the user in the "nonce" request variable.
	 */
	protected function check_nonce() {
		return (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'team_data_nonce'));
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
	 * Protected helper function to get the list of member $fields.
	 * @return array Expected fields
	 */
	protected function get_member_fields() {
		return array(
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
	}

	/**
	 * Helper function to send an email when a new member registers
	 * @param  string $to_list The list of email recipients for new member registrations
	 * @return boolean         The return value from the Send() function
	 */
	protected function send_new_member_email( $to_list ) {
		$to_list = explode(';', $to_list);

		$fields = $this->get_member_fields();
		$fields['comments'] = '';
		$this->get_post_values($fields);
		// scrub fields we don't want to email out
		unset($fields['id']);
		unset($fields['active']);

		$mailer_API = new TeamDataMailer();
		$mailer = $mailer_API->get_mailer();

		foreach ($to_list as $to_email) {
			$mailer->AddAddress($to_email);
		}

		$subject_prefix = $this->get_option('email_prefix');
		if ($subject_prefix != '') {
			$subject_prefix .= ' ';
		}

		$mailer->Subject = $subject_prefix . sprintf( __('New member registration: %s', 'team_data'), $fields['first_name'] . ' ' . $fields['last_name']);

		$text = array();
		$include_empty_fields = ( $this->get_option('email_new_member_include_empty_fields') == '1' );
		foreach ($fields as $field_name => $field_value) {
			if ( $include_empty_fields || !empty($field_value) ) {
				$text[] = ucwords( str_replace('_', ' ', $field_name) ) . ':  ' . $field_value;
			}
		}

		$lists = array();
		if (isset( $_POST[ 'list_names' ] )) {
			foreach ($_POST[ 'list_names' ] as $list_name => $chosen) {
				if ($chosen) {
					$lists[] = $list_name;
				}
			}
		}
		$text[] = __('Lists:', 'team_data') . ' ' . implode(', ', $lists);

		$mailer->Body = implode("\r\n",$text);

		return $mailer->Send();
	}
}
?>