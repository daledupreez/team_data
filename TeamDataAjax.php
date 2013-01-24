<?php

/**
* TeamData plugin PUBLIC AJAX handler.
*/
class TeamDataAjax extends TeamDataBase {

	public function add_actions() {
		$ajax_prefix = 'wp_ajax_team_data_';
		$public_ajax_pefix = 'wp_ajax_nopriv_team_data';
		$actions = array( 'venue', 'level', 'role', 'opposition', 'stat', 'season' );
		foreach($actions as $simple_action) {
			add_action($ajax_prefix . 'get_all_' . $simple_action . 's', array($this, 'get_all_' . $simple_action . 's_ajax'));
		}
		// match
		//add_action('wp_ajax_team_data_get_basic_match', array($this, 'get_basic_match'));
		add_action('wp_ajax_nopriv_team_data_public_get_matches', array($this, 'get_matches_ajax'));
		add_action('wp_ajax_team_data_public_get_matches', array($this, 'get_matches_ajax'));
	}

	public function get_all_venues() {
		return $this->run_select_all($this->tables->venue);
	}

	public function get_all_venues_ajax() {
		$this->run_select_all_ajax($this->tables->venue);
	}


	public function get_all_levels() {
		return $this->run_select_all($this->tables->level);
	}

	public function get_all_levels_ajax() {
		$this->run_select_all_ajax($this->tables->level);
	}


	public function get_all_roles() {
		return $this->run_select_all($this->tables->role);
	}

	public function get_all_roles_ajax() {
		$this->run_select_all_ajax($this->tables->role);
	}


	public function get_all_oppositions() {
		return $this->run_select_all($this->tables->opposition);
	}

	public function get_all_oppositions_ajax() {
		$this->run_select_all_ajax($this->tables->opposition);
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
	 *		opposition
	 *		venue
	 *	String values:
	 *		start_year, end_year, year
	 *
	 *	The return columns are as follows:
	 *		season, _date, _day, day_name, _month, _year, _time, level, team, our_score, their_score, result, venue, is_home
	 */
	public function get_matches($conditions) {
		global $wpdb;

		$sql_select = array( 
			"CONCAT(season.year,' ',season.season) As season",
			"match.date As `_date`",
			"DAYOFMONTH(match.date) As `_day`",
			"DAYNAME(match.date) As day_name",
			"MONTHNAME(match.date) As `_month`",
			"YEAR(match.date) As `_year`",
			"match.time As `_time`",
			"IF(level.abbreviation = '', level.name, level.abbreviation) As level",
			"IF(team.abbreviation = '', team.name, team.abbreviation) As team",
			"match.our_score As our_score",
			"match.opposition_score As their_score",
			"match.result As result",
			"IF(venue.abbreviation = '', venue.name, venue.abbreviation) As venue",
			"(venue.is_home = 1) As is_home",
		);

		$sql_from = array(
			$this->tables->season . ' As season',
			$this->tables->match . ' As `match`',
			$this->tables->level . ' As level',
			$this->tables->opposition . ' As team',
			$this->tables->venue . ' As venue',
		);

		$sql_where = array(
			'match.season_id = season.id',
			'match.level_id = level.id',
			'match.opposition_id = team.id',
			'match.venue_id = venue.id',
		);

		$sql = 'SELECT ' . implode(', ',$sql_select) . ' FROM ' . implode(', ',$sql_from) . ' WHERE ' . implode(' AND ',$sql_where);
		$where_data = $this->build_where($conditions);
		$sql_sub_where = implode(' AND ',$where_data['statement']);
		if ($sql_sub_where <> '') {
			$sql = $wpdb->prepare($sql . ' AND ' . $sql_sub_where, $where_data['args']);
		}
		$sql .= ' ORDER BY match.date ASC, match.time ASC';

		return $wpdb->get_results($sql, ARRAY_A);
	}

	private function get_ajax_conditions() {
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

		$simpleFields = array( 'level' => true, 'opposition' => true, 'venue' => true );

		foreach ($simpleFields as $field => $is_int) {
			if (isset($_POST[$field])) $conditions[$field] = ($is_int ? intval($_POST[$field]) : $_POST[$field]);
		}

		return $conditions;
	}

	private function build_where($conditions) {
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
			'level' => 'level',
			'opposition' => 'team',
			'venue' => 'venue'
		);
		foreach ($integerFields as $field => $table) {
			if (isset($conditions[$field])) {
				$where['statement'][] = $table . '.id = %d';
				$where['args'][] = intval($conditions[$field]);
			}
		}

		return $where;
	}

	private function get_start_end($conditions,$field_name,$is_int = false) {
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
	 * Private helper function to SELECT id and name from all rows in table specified by $table
	 * and then write out the data in JSON format
	 *
	 * @param string $table Name of table
	 * @param string $name_col Name/expression for name value
	 */
	private function run_select_all($table,$name_col = "name") {
		global $wpdb;

		$all_query = "SELECT id, $name_col FROM $table";
		return $wpdb->get_results($all_query, ARRAY_A);
	}

	private function run_select_all_ajax($table,$name_col = "name") {
		header('Content-Type: application/json');
		$results = $this->run_select_all($table,$name_col);
		echo json_encode($results);
		exit;
	}
}
?>