<?php

require_once 'TeamDataBase.php';
require_once 'TeamDataTables.php';

/**
* TeamData plugin installation class. This primarily deals with installing and upgrading the plugin.
*/
class TeamDataInstaller extends TeamDataBase {

	public function add_actions() {
		if ( $this->actions_added ) return;

		$this->check_for_update();
		$this->actions_added = true;
	}

	public function check_for_update() {
		$installed_version = floatval($this->get_option('version'));
		if ($this->version > $installed_version) {
			$this->debug("Triggering upgrade from version '$installed_version' to version '$this->version'");
			$this->update_tables();
		}
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
				info_link VARCHAR(1024) DEFAULT '',
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
				nick_name VARCHAR(127),
				active TINYINT NOT NULL DEFAULT 1,
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

			$sql_part = "CREATE TABLE $tables->list (
				id INT NOT NULL AUTO_INCREMENT,
				name VARCHAR(50) NOT NULL,
				comment VARCHAR(255),
				auto_enroll TINYINT NOT NULL DEFAULT 0,
				display_name VARCHAR(50) NOT NULL DEFAULT '',
				admin_only TINYINT NOT NULL DEFAULT 1
				PRIMARY KEY  (id)
			) $charset_collate";
			$all_sql = $all_sql . $sql_sep . $sql_part;

			$sql_part = "CREATE TABLE $tables->member_list (
				id INT NOT NULL AUTO_INCREMENT,
				member_id INT NOT NULL,
				list_id INT NOT NULL,
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
				opposition_id INT,
				our_score INT,
				opposition_score INT,
				season_id INT NOT NULL,
				result VARCHAR(20) NOT NULL DEFAULT '',
				tourney_name VARCHAR(150) DEFAULT '',
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
				$tables->member_list => array(
					'member_id' => array(
						'table' => $tables->member,
						'action' => ' ON DELETE CASCADE'
					),
					'list_id' => array(
						'table' => $tables->list,
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

	public function uninstall() {
		$this->delete_tables();
		$this->delete_options();
	}

	public function delete_tables() {
		global $wpdb;

		$tables = $this->tables;
		if ( is_null( $tables ) ) $tables = new TeamDataTables();

		foreach($tables as $table_name) {
			$drop_sql = "DROP TABLE IF EXISTS $table_name";
			$wpdb->query($drop_sql);
		}
	}

	public function delete_options() {
		foreach ($this->permitted_options as $option) {
			delete_option( 'team_data_' . $option );
		}
	}
}
?>