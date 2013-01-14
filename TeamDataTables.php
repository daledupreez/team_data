<?php

class TeamDataTables {

	public static $permitted_tables = array( 'cap', 'level', 'match', 'match_stat', 'member', 'member_role', 'opposition', 'role', 'season', 'stat', 'venue');
	
	public $cap;
	public $level;
	public $match;
	public $match_stat;
	public $member;
	public $member_role;
	public $opposition;
	public $role;
	public $season;
	public $stat;
	public $venue;
	
	public function __construct() {
		global $wpdb;
		$table_prefix = $wpdb->prefix . 'team_data_';
		
		$this->cap = $table_prefix . 'cap';
		$this->level = $table_prefix . 'level';
		$this->match = $table_prefix . 'match';
		$this->match_stat = $table_prefix . 'match_stat';
		$this->member = $table_prefix . 'member';
		$this->member_role = $table_prefix . 'member_role';
		$this->opposition = $table_prefix . 'opposition';
		$this->role = $table_prefix . 'role';
		$this->season = $table_prefix . 'season';
		$this->stat = $table_prefix . 'stat';
		$this->venue = $table_prefix . 'venue';
	}

}
?>