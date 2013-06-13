<?php

class TeamDataTables {

	public static $permitted_tables = array( 'cap', 'level', 'list', 'match', 'match_stat', 'member', 'member_list', 'team', 'season', 'stat', 'venue');
	
	public $cap;
	public $level;
	public $list;
	public $match;
	public $match_stat;
	public $member;
	public $member_list;
	public $team;
	public $season;
	public $stat;
	public $venue;
	
	public function __construct() {
		global $wpdb;
		$table_prefix = $wpdb->prefix . 'team_data_';
		
		$this->cap = $table_prefix . 'cap';
		$this->level = $table_prefix . 'level';
		$this->list = $table_prefix . 'list';
		$this->match = $table_prefix . 'match';
		$this->match_stat = $table_prefix . 'match_stat';
		$this->member = $table_prefix . 'member';
		$this->member_list = $table_prefix . 'member_list';
		$this->team = $table_prefix . 'team';
		$this->season = $table_prefix . 'season';
		$this->stat = $table_prefix . 'stat';
		$this->venue = $table_prefix . 'venue';
	}

}
?>