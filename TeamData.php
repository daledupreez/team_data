<?php
/*
Plugin Name: Team Data
Plugin URI: http://github.com/daledupreez/team_data/
Description: An initial attempt to modularize the custom data needs of the Mystic River site in a plugin.
Version: 0.21
Author: Dale du Preez
License: GPL2
*/
/*  Copyright 2013  Dale du Preez  (email: daledupreez+teamdataplugin@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

require_once 'TeamDataAdmin.php';
require_once 'TeamDataTables.php';
require_once 'TeamDataAjax.php';
require_once 'TeamDataAdminAjax.php';
require_once 'TeamData_LastMatchWidget.php';
require_once 'TeamData_NextMatchWidget.php';

$team_data = new TeamData();
$team_data->add_actions();

/**
* TeamData plugin Base class.
*/
class TeamDataBase {
	public $tables;
	
	public $permitted_options;
	
	public $debug_flag = true;

	public function __construct() {
		$this->tables = new TeamDataTables();
		$this->permitted_options = array( 'version', 'max_matches', 'current_season', 'our_team' );
	}

	public function add_actions() {
		// for sub-class to implement
	}
	
	public function debug($debug_output) {
		if ($this->debug_flag) {
			$debug_file = dirname(__FILE__) . '/debug.log';
			$date = new DateTime();
			$date = $date->format('Y-m-d H:i:s');
			$sep = "\n-------------------------------------------------------\n";
			file_put_contents($debug_file, $sep . $date . " Activation output:" . $sep . $debug_output, FILE_APPEND);
		}
	}

	/* Wrapper around the core WordPress update_option() function to restrict the options that we set.
	 * @parm $option string Name of the option
	 * @parm $value string New value of the option
	 */
	public function set_option($option, $value) {
		if (($option !== '') && ($option !== null) && in_array($option,$this->permitted_options)) {
			$this->debug("updating option '$option' to value '$value'");
			update_option('team_data_' . $option, $value);
		}
		else {
			$this->debug("Failed to trigger update_option('$option', '$value')");
		}
	}

	/* Wrapper around the core WordPress get_option() function to restrict the options that we get.
	 * @parm $option string Name of the option
	 * @return string Current value of the option
	 */
	public function get_option($option) {
		if (($option !== '') && ($option !== null) && in_array($option,$this->permitted_options)) {
			return get_option('team_data_' . $option);
		}
		else {
			$this->debug("Failed to trigger get_option('$option')");
		}
		return null;
	}
}

/**
* TeamData plugin main class. This primarily includes data accessors and the installation code.
*/
class TeamData extends TeamDataBase {

	public $version = 0.3;

	public function add_actions() {
		global $wpdb;
		/* check for an upgrade */
		$installed_version = floatval($this->get_option('version'));
		if ($this->version > $installed_version) {
			$this->debug("Triggering upgrade from version '$installed_version' to version '$this->version'");
			$this->update_tables();
		}
		register_uninstall_hook( __FILE__, array( 'TeamData', 'delete_tables' ) );

		add_action( 'widgets_init', array( $this, 'register_widgets' ) );

		/* Admin actions */
		$team_data_admin = new TeamDataAdmin();
		$team_data_admin->add_actions();
		/* Admin AJAX actions */
		$admin_ajax = new TeamDataAdminAjax();
		$admin_ajax->add_actions();
		/* Public AJAX actions */
		$public_ajax = new TeamDataAjax();
		$public_ajax->add_actions();
	}
	
	public function register_widgets() {
		if ( class_exists('TeamData_LastMatchWidget') ) register_widget( 'TeamData_LastMatchWidget' );
		if ( class_exists('TeamData_NextMatchWidget') ) register_widget( 'TeamData_NextMatchWidget' );
	}

	public function debug_returned_output() {
		$this->debug(ob_get_contents());
	}

	public function update_tables() {
		global $wpdb;
		$installed_version = floatval($this->get_option('version'));
		if ($installed_version < $this->version) {
			$charset_collate = '';

			if ( !empty($wpdb->charset) ) {
				$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
			}
			if ( !empty($wpdb->collate) ) {
				$charset_collate .= " COLLATE $wpdb->collate";
			}

			$tables = $this->tables;
			if (is_null($tables)) $tables = new TeamDataTables();

			$all_sql = '';
			$sql_sep = "; \n";

			$sql_part = "CREATE TABLE $tables->venue (
				id INT NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				info_link VARCHAR(1024) DEFAULT '',
				directions_link VARCHAR(2048) DEFAULT '',
				is_home TINYINT NOT NULL DEFAULT 0,
				abbreviation VARCHAR(80) DEFAULT '',
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql .= $sql_part;

			$sql_part = "CREATE TABLE $tables->team (
				id INT NOT NULL AUTO_INCREMENT,
				name VARCHAR(255) NOT NULL,
				logo_link VARCHAR(1024) DEFAULT '',
				abbreviation VARCHAR(80) DEFAULT '',
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql .= $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->level (
				id INT NOT NULL AUTO_INCREMENT,
				name VARCHAR(50) NOT NULL,
				abbreviation VARCHAR(15),
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql .= $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->member (
				id INT NOT NULL AUTO_INCREMENT,
				last_update DATETIME,
				last_name VARCHAR(255) NOT NULL,
				first_name VARCHAR(127) NOT NULL,
				email VARCHAR(255),
				backup_email VARCHAR(255),
				cell VARCHAR(20),
				tel_home VARCHAR(20),
				tel_work VARCHAR(20),
				address1 VARCHAR(255),
				address2 VARCHAR(255),
				city VARCHAR(127),
				state VARCHAR(63),
				postal_code VARCHAR(20),
				country VARCHAR(127),
				date_of_birth DATE,
				height VARCHAR(20),
				weight VARCHAR(20),
				college_or_school VARCHAR(255),
				position VARCHAR(127),
				joined DATE,
				past_clubs VARCHAR(255),
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->role (
				id INT NOT NULL AUTO_INCREMENT,
				name VARCHAR(50) NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->member_role (
				id INT NOT NULL AUTO_INCREMENT,
				member_id INT NOT NULL,
				role_id INT NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->match (
				id INT NOT NULL AUTO_INCREMENT,
				date DATE NOT NULL,
				time TIME NOT NULL DEFAULT '13:00:00',
				venue_id INT NOT NULL,
				level_id INT NOT NULL,
				is_league TINYINT NOT NULL DEFAULT 0,
				is_postseason TINYINT NOT NULL DEFAULT 0,
				opposition_id INT NOT NULL,
				our_score INT,
				opposition_score INT,
				season_id INT NOT NULL,
				result VARCHAR(1) NOT NULL DEFAULT '',
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->stat (
				id INT NOT NULL AUTO_INCREMENT,
				name VARCHAR(50) NOT NULL,
				value_type TINYINT DEFAULT 0,
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->match_stat (
				id INT NOT NULL AUTO_INCREMENT,
				member_id INT NOT NULL,
				match_id INT NOT NULL,
				stat_id INT NOT NULL,
				stat_intvalue INT,
				stat_stringvalue VARCHAR(50),
				stat_floatvalue DECIMAL(11,3),
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->cap (
				id INT NOT NULL AUTO_INCREMENT,
				member_id INT NOT NULL,
				match_id INT NOT NULL,
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->season (
				id INT NOT NULL AUTO_INCREMENT,
				year VARCHAR(10) NOT NULL,
				season VARCHAR(20) NOT NULL,
				PRIMARY KEY  (id),
				UNIQUE KEY fullname (year,season)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($all_sql);

			// Process all FOREIGN KEY constraints separately as they muck up dbDelta
			$foreign_keys = array(
				$tables->cap => array(
					'member_id' => array(
						'table' => $tables->member,
						'action' => ' ON DELETE CASCADE'
					),
					'match_id' => array (
						'table' => $tables->match,
						'action' => ' ON DELETE CASCADE'
					)
				),
				$tables->member_role => array(
					'member_id' => array(
						'table' => $tables->member,
						'action' => ' ON DELETE CASCADE'
					),
					'role_id' => array(
						'table' => $tables->role,
						'action' => ' ON DELETE CASCADE'
					)
				),
				$tables->match => array(
					'venue_id' => array(
						'table' => $tables->venue
					),
					'level_id' => array(
						'table' => $tables->level
					),
					'opposition_id' => array(
						'table' => $tables->team
					),
					'season_id' => array(
						'table' => $tables->season
					)
				),
				$tables->match_stat => array(
					'member_id' => array(
						'table' => $tables->member,
						'action' => ' ON DELETE CASCADE'
					),
					'match_id' => array(
						'table' => $tables->match
					),
					'stat_id' => array(
						'table' => $tables->stat,
						'action' => ' ON DELETE CASCADE'
					)
				)
			);

			foreach(array_keys($foreign_keys) as $curr_table) {
				foreach(array_keys($foreign_keys[$curr_table]) as $curr_field) {
					$fk_name = 'fk_' . $curr_table . '__' . $curr_field;
					$exists_sql = "SELECT COUNT(*) As fk_exists FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = '$curr_table' AND CONSTRAINT_NAME = '$fk_name' AND CONSTRAINT_TYPE = 'FOREIGN KEY'";
					$fk_exists = $wpdb->get_var($exists_sql);
					if (!$fk_exists) {
						$key_target = $foreign_keys[$curr_table][$curr_field]['table'];
						$alter_sql = "ALTER TABLE $curr_table ADD CONSTRAINT $fk_name FOREIGN KEY ($curr_field) REFERENCES $key_target (id)";
						if (isset($foreign_keys[$curr_table][$curr_field]['action'])) {
							$alter_sql .= $foreign_keys[$curr_table][$curr_field]['action'];
						}
						$createOK = $wpdb->query($alter_sql);
					}
				}
			}

			$this->debug("Completed team_data::update_tables() for version '$this->version'");
			$this->set_option('version', $this->version);

			$max_matches = $this->get_option('max_matches');
			if (!$max_matches) {
				$this->set_option('max_matches', 3);
			}
		}
	}

	public static function delete_tables() {
		global $wpdb;

		$tables = new TeamDataTables();
		foreach($tables as $table_name) {
			$drop_sql = "DROP TABLE IF EXISTS $table_name";
			$wpdb->query($drop_sql);
		}
	}
}
?>