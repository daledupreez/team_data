<?php
class TeamData_WidgetUtils extends TeamDataBase {
	
	/**
	 * Helper function to get the logo link for our team.
	 * 
	 * @return string The link for our team's logo.
	 */
	public static function get_our_logo_link() {
		global $wpdb;

		$utils_instance = new TeamData_WidgetUtils();
		$logo_link = '';
		$our_team = $utils_instance->get_option('our_team');
		if ( !empty($our_team) ) {
			$sql = $wpdb->prepare('SELECT logo_link FROM ' . $utils_instance->tables->team . ' WHERE id = %d', $our_team);
			$logo_link = $wpdb->get_var($sql);
		}
		return $logo_link;
	}

	/**
	 * Helper function to get the data for the last match played for the level specified in $level_id.
	 * 
	 * @param integer $level_id ID of level we want to get the match for.
	 * @param bool $get_logos Flag to control whether logos should be retrieved.
	 * @return array match data with the following data when a match is found:
	 * <code>
	 * $match_data = array(
	 *	'_date' => 'datestring',
	 *	'_time' => 'timestring',
	 *	'result' => 'string',
	 *	'our_score' => integer,
	 *	'opposition_score' => integer,
	 *	'tourney_name' => 'string',
	 *	'level' => 'string',
	 *	'team' => 'string',
	 *	'team_logo' => 'url_string',
	 *	'is_home' => integer,
	 *	'venue' => 'string',
	 *	'info_link' => 'url_string',
	 *	'directions_link' => 'url_string',
	 * );
	 * </code>
	 */
	public static function get_last_match($level_id = -1, $get_logos = true) {
		global $wpdb;

		$match = null;
		$utils_instance = new TeamData_WidgetUtils();

		$select = array(
			"DATE_FORMAT(match.date,'%M %D %Y') AS `_date`",
			"DATE_FORMAT(match.time,'%l:%i %p') AS `_time`",
			'match.result',
			'match.our_score',
			'match.opposition_score',
			'match.tourney_name',
			"IF(level.abbreviation = '', level.name, level.abbreviation) AS level",
			"match.team_name AS team",
			"match.team_logo",
			'(venue.is_home = 1) AS is_home',
			"IF(venue.abbreviation = '', venue.name, venue.abbreviation) AS venue",
			'venue.info_link',
			'venue.directions_link'
		);
		$from = array(
			"( SELECT m.date, m.time, m.tourney_name, m.venue_id, m.level_id, m.result, m.our_score, m.opposition_score, IF(m.opposition_id IS NULL, '', IF(t.abbreviation = '', t.name, t.abbreviation)) AS team_name, " . ($get_logos ? "IF(m.opposition_id IS NULL, '', t.logo_link)" : "''") . " AS team_logo FROM " . $utils_instance->tables->match . " m LEFT OUTER JOIN " . $utils_instance->tables->team . " t ON m.opposition_id = t.id ) `match`",
			$utils_instance->tables->level . ' AS level',
			$utils_instance->tables->venue . ' AS venue',
		);
		$where = array(
			'match.venue_id = venue.id',
			'match.level_id = level.id',
			"( match.result <> '' OR ( match.our_score IS NOT NULL AND match.opposition_score IS NOT NULL ) )",
			'match.date <= CURDATE()'
		);
		if ($level_id > 0) $where[] = 'level.id = ' . intval($level_id);

		$sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . implode(', ', $from) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY match.date DESC, match.time DESC LIMIT 1';
		$matches = $wpdb->get_results($sql, ARRAY_A);
		if (isset($matches[0])) $match = $matches[0];

		return $match;
	}

	/**
	 * Helper function to get the data for the next match scheduled for the level specified in $level_id.
	 * 
	 * @param integer $level_id ID of level we want to get the match for.
	 * @return array match_data, which has the following fields when a match is found:
	 * <code>
	 * match_data = array(
	 *	'_date' => 'datestring',
	 *	'_time' => 'timestring',
	 *	'level' => 'string',
	 *	'team' => 'string',
	 *	'team_logo' => 'url_string',
	 *	'tourney_name' => 'string',
	 *	'is_home' => integer,
	 *	'venue' => 'string',
	 *	'info_link' => 'url_string',
	 *	'directions_link' => 'url_string',
	 * );
	 * </code>
	 */
	public static function get_next_match($level_id = -1, $get_logos = true) {
		global $wpdb;

		$match = null;
		$utils_instance = new TeamData_WidgetUtils();

		$select = array(
			"DATE_FORMAT(match.date,'%M %D %Y') AS `_date`",
			"DATE_FORMAT(match.time,'%l:%i %p') AS `_time`",
			"IF(level.abbreviation = '', level.name, level.abbreviation) AS level",
			"match.team_name AS team",
			"match.team_logo",
			'match.tourney_name',
			'(venue.is_home = 1) AS is_home',
			"IF(venue.abbreviation = '', venue.name, venue.abbreviation) AS venue",
			'venue.info_link',
			'venue.directions_link'
		);
		$from = array(
			"( SELECT m.date, m.time, m.tourney_name, m.venue_id, m.level_id, m.result, m.our_score, m.opposition_score, IF(m.opposition_id IS NULL, '', IF(t.abbreviation = '', t.name, t.abbreviation)) AS team_name, " . ( $get_logos ? "IF(m.opposition_id IS NULL, '', t.logo_link)" : "''" ) . " AS team_logo FROM " . $utils_instance->tables->match . " m LEFT OUTER JOIN " . $utils_instance->tables->team . " t ON m.opposition_id = t.id ) `match`",
			$utils_instance->tables->level . ' AS level',
			$utils_instance->tables->venue . ' AS venue',
		);
		$where = array(
			'match.venue_id = venue.id',
			'match.level_id = level.id',
			"( match.result = '' AND ( match.our_score IS NULL AND match.opposition_score IS NULL ) )",
			'match.date >= CURDATE()'
		);
		if ($level_id > 0) $where[] = 'level.id = ' . intval($level_id);

		$sql = 'SELECT ' . implode(', ', $select) . ' FROM ' . implode(', ', $from) . ' WHERE ' . implode(' AND ', $where) . ' ORDER BY match.date ASC, match.time ASC LIMIT 1';
		$matches = $wpdb->get_results($sql, ARRAY_A);
		if (isset($matches[0])) $match = $matches[0];
		return $match;
	}

}
?>