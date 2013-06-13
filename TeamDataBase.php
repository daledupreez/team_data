<?php

/// Included in TeamDataBase constructor, so must be included manually.
require_once 'TeamDataTables.php';

/**
* TeamData plugin Base class.
*/
class TeamDataBase {

	public $version = 0.32;

	public $actions_added = false;

	public $tables;

	public $permitted_options;

	public $debug_flag = true;

	public function __construct() {
		$this->tables = new TeamDataTables();
		$this->permitted_options = array( 'version', 'max_matches', 'current_season', 'our_team' );
	}

	public function add_actions() {
		if ( $this->actions_added ) return;

		spl_autoload_register( array( 'TeamDataBase', 'autoload' ) );

		/* check for installation actions first */
		$team_data_installer = new TeamDataInstaller();
		$team_data_installer->add_actions();

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

		$this->actions_added = true;
	}

	public function register_widgets() {
		if ( class_exists('TeamData_LastMatchWidget') ) register_widget( 'TeamData_LastMatchWidget' );
		if ( class_exists('TeamData_NextMatchWidget') ) register_widget( 'TeamData_NextMatchWidget' );
	}

	public static function autoload($className) {
		if ( substr( $className, 0, 8) == 'TeamData' ) {
			include $className . '.php';
		}
	}

	public function debug($debug_output) {
		if ($this->debug_flag) {
			$debug_file = dirname(__FILE__) . '/debug.log';
			$date = new DateTime();
			$date = $date->format('Y-m-d H:i:s');
			$sep = "\n-------------------------------------------------------\n";
			file_put_contents($debug_file, $sep . $date . " Debug output:" . $sep . $debug_output, FILE_APPEND);
		}
	}

	public function debug_returned_output() {
		$this->debug(ob_get_contents());
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
?>