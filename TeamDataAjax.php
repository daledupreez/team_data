<?php

/**
* TeamData plugin PUBLIC AJAX handler.
*/
class TeamDataAjax extends TeamDataBase {

	public function add_actions() {
		// match
		//add_action('wp_ajax_team_data_get_basic_match', array($this, 'get_basic_match'));
		add_action('wp_ajax_nopriv_team_data_get_matches', array($this, 'get_matches'));
		
	}
	
	public function get_matches() {
		global $wpdb;
		
		// variables:
		// start_season [ID]
		// end_season [ID]
		// level [ID]
		// opponent [ID]
		// start_year
		// end_year
		// venue
		// useIDs
		
		// columns
		// Season
		// Date
		// Time
		// Level
		// Team
		// Our_score
		// their_score
		// Result
		// Venue
		// Is_Home
		$sql_select = 'SELECT season.name As season, match.`date` As `date`, DAYOFMONTH(match.`date`) As `day`, DAYNAME(match.`date`) As day_name, MONTHNAME(match.`date`) As month_name, YEAR(match.`date`) As `year`';
		$sql_select .= ', IF(level.abbreviation = '', level.name, level.abbreviation) As level, IF(team.abbreviation = '', team.name, team.abbreviation) As team';
		$sql_select .= ', match.our_score As our_score, match.opposition_score As their_score, match.result As result';
		$sql_select .= ', IF(venue.abbreviation = '', venue.name, venue.abbreviation) AS venue, (venue.is_home = 1) As is_home';
		
		$sql_from = "FROM $this->tables->season As season, $this->tables->match As match, $this->tables->level As level, $this->tables->opposition As team, $this->tables->venue As venue";
		
		$sql_where = 'WHERE match.season_id = season.id AND match.level_id = level.id AND match.opposition_id = team.id AND match.venue_id = venue.id';
		
		$sql = $sql_select . ' ' . $sql_from . ' ' . $sql_where;
		
		$where_data = $this->build_where();
		$sql_sub_where = implode(' AND ',$where_data['statement']);
		if ($sql_sub_where <> '') {
			$sql = $wpdb->prepare($sql . ' AND ' . $sql_sub_where, $where_data['args']);
		}
		
		$results = $wpdb->get_results($sql, ARRAY_A);
		
		header('Content-Type: application/json');
		echo json_encode($results);
		exit;
	}
	
	private function build_where() {
		$where = array(
			"args" => array(),
			"statement" => array()
		);

		$season = $this->get_start_end('season',true);
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

		$year = $this->get_start_end('year',false);
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

		if (isset($_POST['level'])) {
			$where['statement'][] = 'level.id = %d';
			$where['args'][] = intval($_POST['level']);
		}

		if (isset($_POST['opposition'])) {
			$where['statement'][] = 'team.id = %d';
			$where['args'][] = intval($_POST['opposition']);
		}

		if (isset($_POST['venue'])) {
			$where['statement'][] = 'venue.id = %d';
			$where['args'][] = intval($_POST['venue']);
		}
		
		return $where;
	}
	
	private get_start_end($field_name,$is_int = false) {
		$start = '';
		$end = '';
		$count = 0;
		$data = array();
		if (isset($_POST['start_' . $field_name])) {
			$start = $_POST['start_' . $field_name];
			if ($is_int) $start = intval($start);
			$count++;
		}
		if (isset($_POST['end_' . $field_name)) {
			$end = $_POST['end_' . $field_name];
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
		else if ($seasonCount == 1) {
			$value = ($start == '' ? $end : $start);
			$data['value'] = value;
		}
		$data['count'] = $count;
		return $data;
	}
	
	private check_nonce() {
		return (isset($_POST['nonce']) && wp_verify_nonce($_POST['nonce'], 'team_data_nonce'));
	}
}
?>