<?php

/**
* TeamData plugin Admin page rendering class.
*/
class TeamDataAdmin extends TeamDataBase {

	public $page_codes;

	private $js_data;
	
	public function __construct() {
		parent::__construct();
		$this->page_codes = array();
		$this->js_data = array(
			'main'
		);
	}

	public function add_actions() {
		if ( $this->actions_added ) return;
		add_action('admin_menu', array($this, 'add_admin_menu'));
		add_action('admin_enqueue_scripts', array($this, 'queue_admin_js'));
		add_action('admin_enqueue_scripts', array($this, 'render_loc_js'));
		$this->actions_added = true;
	}

	public function add_admin_menu() {
		$this->page_codes['main'] = add_menu_page( __('Admin','team_data'), __('Team Data Admin','team_data'), 'manage_options', 'team-data-admin', array($this, 'render_admin_main') );
		$this->page_codes['matches'] = add_submenu_page('team-data-admin', __('Matches and Results','team_data'), __('Matches and Results','team_data'), 'manage_options', 'team-data-matches', array($this, 'render_matches'));
		$this->page_codes['members'] = add_submenu_page('team-data-admin', __('Members','team_data'), __('Members','team_data'), 'manage_options', 'team-data-members', array($this, 'render_members'));

		add_action('admin_footer-' . $this->page_codes['main'], array($this, 'render_main_js'));
		add_action('admin_footer-' . $this->page_codes['matches'], array($this, 'render_matches_js'));
	}

	public function render_matches() {	
		$this->render_match_list();

		echo '<br/>';
		echo '<input class="team_data_button" id="team_data_new_match_button" type="button" value="' . __('Create New Match(es)','team_data') . '" onclick="team_data.api.match.newMatches();" />';
		echo '<div id="team_data_new_match" class="team_data_admin_section" style="display: none;">';
			echo '<form id="team_data_new_match_shared">';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_new_match_shared__season" class="team_data_edit_label">' . __('Season','team_data') . '</label>';
					echo '<input id="team_data_new_match_shared__season" class="team_data_season team_data_edit_input" name="shared_season" type="text" size="20" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_new_match_shared__date" class="team_data_edit_label">' . __('Date','team_data') . '</label>';
					echo '<input id="team_data_new_match_shared__date" class="team_data_date team_data_edit_input" name="shared_date" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_new_match_shared__venue" class="team_data_edit_label">' . __('Venue','team_data') . '</label>';
					echo '<input id="team_data_new_match_shared__venue" class="team_data_venue team_data_edit_input" name="shared_venue" type="text" size="50" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_new_match_shared__opposition" class="team_data_edit_label">' . __('Opponent','team_data') . '</label>';
					echo '<input id="team_data_new_match_shared__opposition" class="team_data_opposition team_data_edit_input" name="shared_opposition" type="text" size="50" />';
				echo '</div>';
				echo '<input id="team_data_new_match_shared__matchCount" type="hidden" value="0" />';
			echo '</form>';
			$max_matches = intval($this->get_option('max_matches'));
			if ($max_matches < 1) $max_matches = 3;
			$levels = $this->get_levels();
			for ($i = 1; $i <= $max_matches; $i++) {
				echo '<form id="team_data_new_match_' . $i . '" class="team_data_admin_section" style="display: none;">';
					echo '<div class="team_data_inline">';
						echo '<label for="team_data_new_match_' . $i . '__time" class="team_data_edit_label">' . __('Time','team_data') . '</label>';
						echo '<input id="team_data_new_match_' . $i . '__time" class="team_data_time team_data_edit_input" name="match_time" type="text" size="10" />';
					echo '</div>';
					echo '<div class="team_data_inline">';
						echo '<label for="team_data_new_match_' . $i . '__level" class="team_data_edit_label">' . __('Level','team_data') . '</label>';
						echo '<select id="team_data_new_match_' . $i . '__level" name="match_level">';
						$count = 0;
						foreach(array_keys($levels) as $level_id) {
							$count = $count + 1;
							echo '<option value="' . $level_id . '" ' . ($count == $i ? 'selected="selected"' : '') . '>' . $levels[$level_id] . '</option>';
						}
						echo '</select>';
					echo '</div>';
					echo '<div class="team_data_inline">';
						echo '<label for="team_data_new_match_' . $i . '__is_league" class="team_data_edit_label">' . __('League?','team_data') . '</label>';
						echo '<input id="team_data_new_match_' . $i . '__is_league" class="team_data_admin_checkbox" name="match_is_league" type="checkbox" />';
					echo '</div>';
					echo '<div class="team_data_inline">';
						echo '<label for="team_data_new_match_' . $i . '__is_postseason" class="team_data_edit_label">' . __('Playoffs?','team_data') . '</label>';
						echo '<input id="team_data_new_match_' . $i . '__is_postseason" class="team_data_admin_checkbox" name="match_is_postseason" type="checkbox" />';
					echo '</div>';
				echo '</form>';
			}
			echo '<input id="team_data_new_match__save" class="team_data_button" type="button" value="' . __('Save Matches', 'team_data') . '" onclick="team_data.api.match.saveNewMatches();" />';
		echo '</div>';
	}
	
	private function get_levels() {
		global $wpdb;

		$level_table = $this->tables->level;
		$level_data = array();
		$levels = $wpdb->get_results("SELECT id, name FROM $level_table ORDER BY name ASC");
		foreach($levels as $level) {
			if ($level->id) {
				$level_data[$level->id] = $level->name;
			}
		}
		return $level_data;
	}
	
	public function render_members() {
		global $wpdb;

		echo '<h2>' . __('Member Administration Page', 'team_data') . '</h2>';
		echo 'members';
	}
	
	public function render_admin_main() {
		global $wpdb;
		echo '<h2>' . __('Main TeamData Administration Page', 'team_data') . '</h2>';
		echo '<div>' . __('Manage the main infrastructure for the TeamData plugin.','team_data') . __('Each of the boxes below represents a list of available options to show when entering matches, stats, results or members.', 'team_data') . '</div>';
		echo '<div id="team_data_admin_options">';
			echo '<div>';
				echo '<div class="section_title">' . __('Venues', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('A list of all the venues where you play matches.','team_data');
						echo ' ' . __('In general, venues are not associated with a specific team, except for your home venue(s).','team_data');
					echo '</div>';
					echo '<div id="team_data_venue_table" class="team_data_simple_table_venue"></div>';
					echo '<form id="team_data_venue_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_venue_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
							echo '<input id="team_data_venue_edit__id" class="team_data_edit_input" name="venue_id" type="text" readonly="readonly" disabled="disabled" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_venue_edit__name" class="team_data_edit_label">' . __('Name','team_data') . '</label>';
							echo '<input id="team_data_venue_edit__name" class="team_data_edit_input" name="venue_name" type="text" size="50" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_venue_edit__abbreviation" class="team_data_edit_label">' . __('Short Name','team_data') . '</label>';
							echo '<input id="team_data_venue_edit__abbreviation" class="team_data_edit_input" name="venue_abbreviation" type="text" size="30" />';
						echo '</div>';	
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_venue_edit__is_home" class="team_data_edit_label">' . __('Home Venue','team_data') . '</label>';
							echo '<input id="team_data_venue_edit__is_home" class="team_data_admin_checkbox" name="venue_is_home" type="checkbox" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_venue_edit__info_link" class="team_data_edit_label">' . __('Info Link','team_data') . '</label>';
							echo '<input id="team_data_venue_edit__info_link" class="team_data_edit_input" name="venue_info_link" type="text" size="60" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_venue_edit__directions_link" class="team_data_edit_label">' . __('Directions/Map Link','team_data') . '</label>';
							echo '<input id="team_data_venue_edit__directions_link" class="team_data_edit_input" name="venue_directions_link" type="text" size="60" />';
						echo '</div>';	
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_venue_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.venue.save();" />';
						echo '<input class="team_data_button" type="button" value="' . __('Create New Venue','team_data') . '" onclick="team_data.api.venue.clearForm();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div>';
				echo '<div class="section_title">' . __('Stats', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('A list of all the statistics which you collect for your matches.','team_data');
						echo ' ' . __('This is fairly flexible, but you should be careful about datatypes.','team_data');
						echo ' ' . __('Unless you are sure you will only have integer or float data, it is likely best to use strings for your statistics.','team_data');
					echo '</div>';
					echo '<div id="team_data_stat_table" class="team_data_simple_table_stat"></div>';
					echo '<form id="team_data_stat_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_stat_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
							echo '<input id="team_data_stat_edit__id" class="team_data_edit_input" name="stat_id" type="text" readonly="readonly" disabled="disabled" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_stat_edit__name" class="team_data_edit_label">' . __('Name','team_data') . '</label>';
							echo '<input id="team_data_stat_edit__name" class="team_data_edit_input" name="stat_name" type="text" size="50" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<div class="team_data_edit_radioset">';
								echo '<div>' . __('Data type for stat', 'team_data') . '</div>';
								echo '<input id="team_data_stat_edit__value_type_integer" class="team_data_edit_input" name="stat_value_type" type="radio" value="0"/>';
								echo '<label for="team_data_stat_edit__value_type_integer" class="team_data_edit_label_radioset">' . __('Integer (Whole Number)', 'team_data') . '</label>';
								echo '<input id="team_data_stat_edit__value_type_string" class="team_data_edit_input" name="stat_value_type" type="radio" value="1"/>';
								echo '<label for="team_data_stat_edit__value_type_string" class="team_data_edit_label_radioset">' . __('String (Text)', 'team_data') . '</label>';
								echo '<input id="team_data_stat_edit__value_type_float" class="team_data_edit_input" name="stat_value_type" type="radio" value="2"/>';
								echo '<label for="team_data_stat_edit__value_type_float" class="team_data_edit_label_radioset">' . __('Float (Number with Decimals)', 'team_data') . '</label>';
								echo '<input id="team_data_stat_edit_value_type" type="hidden" value="0" />';
							echo '</div>';	
						echo '</div>';
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_stat_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.stat.save();" />';
						echo '<input class="team_data_button" type="button" value="' . __('Create New Stat','team_data') . '" onclick="team_data.api.stat.clearForm();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div>';
				echo '<div class="section_title">' . __('Levels', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('A list of all the levels/leagues/competitions in which your teams compete.','team_data');
						echo ' ' . __('This is fairly flexible, but becomes important when calculating stats and organising matches.','team_data');
					echo '</div>';
					echo '<div id="team_data_level_table" class="team_data_simple_table_level"></div>';
					echo '<form id="team_data_level_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_level_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
							echo '<input id="team_data_level_edit__id" class="team_data_edit_input" name="level_id" type="text" readonly="readonly" disabled="disabled" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_level_edit__name" class="team_data_edit_label">' . __('Name','team_data') . '</label>';
							echo '<input id="team_data_level_edit__name" class="team_data_edit_input" name="level_name" type="text" size="40" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_level_edit__abbreviation" class="team_data_edit_label">' . __('Short Name','team_data') . '</label>';
							echo '<input id="team_data_level_edit__abbreviation" class="team_data_edit_input" name="level_abbreviation" type="text" size="15" />';
						echo '</div>';
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_level_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.level.save();" />';
						echo '<input class="team_data_button" type="button" value="' . __('Create New Level','team_data') . '" onclick="team_data.api.level.clearForm();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div>';
				echo '<div class="section_title">' . __('Roles', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('A list of all the roles that you can assign to members.','team_data');
						echo ' ' . __('You can also think of this as specific groups of members.','team_data');
						echo ' ' . __('In either case, you must define the values here so they are available elsewhere.','team_data');
					echo '</div>';
					echo '<div id="team_data_role_table" class="team_data_simple_table_role"></div>';
					echo '<form id="team_data_role_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_role_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
							echo '<input id="team_data_role_edit__id" class="team_data_edit_input" name="role_id" type="text" readonly="readonly" disabled="disabled" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_role_edit__name" class="team_data_edit_label">' . __('Name','team_data') . '</label>';
							echo '<input id="team_data_role_edit__name" class="team_data_edit_input" name="role_name" type="text" size="40" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_role_edit__comment" class="team_data_edit_label">' . __('Details','team_data') . '</label>';
							echo '<textarea id="team_data_role_edit__comment" class="team_data_edit_input" name="role_comment" cols="80" rows="3"></textarea>';
						echo '</div>';
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_role_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.role.save();" />';
						echo '<input class="team_data_button" type="button" value="' . __('Create New Role','team_data') . '" onclick="team_data.api.role.clearForm();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div>';
				echo '<div class="section_title">' . __('Teams', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('A list of all the teams that you play against.','team_data');
						echo ' ' . __('You must add any team you play against to this list before you can add or edit any matches.','team_data');
					echo '</div>';
					echo '<div id="team_data_team_table" class="team_data_simple_table_team"></div>';
					echo '<form id="team_data_team_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_team_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
							echo '<input id="team_data_team_edit__id" class="team_data_edit_input" name="team_id" type="text" readonly="readonly" disabled="disabled" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_team_edit__name" class="team_data_edit_label">' . __('Name','team_data') . '</label>';
							echo '<input id="team_data_team_edit__name" class="team_data_edit_input" name="team_name" type="text" size="50" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_team_edit__abbreviation" class="team_data_edit_label">' . __('Short Name','team_data') . '</label>';
							echo '<input id="team_data_team_edit__abbreviation" class="team_data_edit_input" name="team_abbreviation" type="text" size="30" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_team_edit__logo_link" class="team_data_edit_label">' . __('Logo Link','team_data') . '</label>';
							echo '<input id="team_data_team_edit__logo_link" class="team_data_edit_input" name="team_logo_link" type="text" size="60" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_team_edit__is_us" class="team_data_edit_label">' . __('Our Team','team_data') . '</label>';
							echo '<input id="team_data_team_edit__is_us" class="team_data_admin_checkbox" name="team_is_us" type="checkbox" />';
						echo '</div>';
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_team_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.team.save();" />';
						echo '<input class="team_data_button" type="button" value="' . __('Create New Team','team_data') . '" onclick="team_data.api.team.clearForm();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div>';
				echo '<div class="section_title">' . __('Seasons', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('A list of all available seasons.','team_data');
						echo ' ' . __('You must add any seasons to this list before you can add any matches in that season.','team_data');
					echo '</div>';
					echo '<div id="team_data_season_table" class="team_data_simple_table_season"></div>';
					echo '<form id="team_data_season_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_season_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
							echo '<input id="team_data_season_edit__id" class="team_data_edit_input" name="season_id" type="text" readonly="readonly" disabled="disabled" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_season_edit__year" class="team_data_edit_label">' . __('Year','team_data') . '</label>';
							echo '<input id="team_data_season_edit__year" class="team_data_edit_input" name="season_year" type="text" size="5" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_season_edit__season" class="team_data_edit_label team_data_season">' . __('Season','team_data') . '</label>';
							echo '<input id="team_data_season_edit__season" class="team_data_edit_input" name="season_season" type="text" size="30" />';
						echo '</div>';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_season_edit__is_current" class="team_data_edit_label">' . __('Is Current Season','team_data') . '</label>';
							echo '<input id="team_data_season_edit__is_current" class="team_data_admin_checkbox" name="season_is_current" type="checkbox" />';
						echo '</div>';
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_season_edit__repeat_season" class="team_data_button" type="button" value="' . __('Same as last Season', 'team_data') . '" onclick="team_data.api.season.repeatLastSeason();" />';
						echo '<input id="team_data_season_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.season.save();" />';
						echo '<input class="team_data_button" type="button" value="' . __('Create New Season','team_data') . '" onclick="team_data.api.season.clearForm();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<div>';
				echo '<div class="section_title">' . __('General Options', 'team_data') . '</div>';
				echo '<div>';
					echo '<div class="team_data_help">';
						echo __('General configuration options for the TeamData plugin.','team_data');
					echo '</div>';
					echo '<form id="team_data_options_edit" class="team_data_admin_section">';
						echo '<div class="team_data_inline">';
							echo '<label for="team_data_options_edit__max_matches">' . __('Max Edit Matches','team_data') . '</label>';
							echo '<input id="team_data_options_edit__max_matches" class="team_data_edit_input" name="options_max_matches" type="text" size="5" value="' . $this->get_option('max_matches') . '" />';
							echo '<input id="team_data_options_edit__max_matches_orig" type="hidden" value="' . $this->get_option('max_matches') . '" />';
						echo '</div>';
					echo '</form>';
					echo '<div style="padding: 5px;">';
						echo '<input id="team_data_options_edit__save" class="team_data_button" type="button" value="' . __('Save Changes', 'team_data') . '" onclick="team_data.api.options.save();" />';
					echo '</div>';
				echo '</div>';
			echo '</div>';
		
		echo '</div>';
	}

	public function queue_admin_js($hook) {
		if (false === in_array($hook,$this->page_codes)) return;

		$jquery_ui_dir = get_bloginfo('url') . '/wp-includes/js/jquery/ui/';
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker', $jquery_ui_dir . 'jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-autocomplete', $jquery_ui_dir . 'jquery.ui.autocomplete.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-accordion', $jquery_ui_dir . 'jquery.ui.accordion.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('team-data', plugins_url('js/team_data.js', __FILE__ ));
		wp_enqueue_style('jquery.ui.theme', plugins_url('css/redmond/jquery-ui-1.8.23.custom.css', __FILE__ ));
		wp_enqueue_style('team-data-css', plugins_url('css/team_data.css',__FILE__));
		wp_localize_script('team-data', 'team_data_ajax', array( 'nonce' => wp_create_nonce('team_data_nonce') ) );
	}
	
	public function render_loc_js($hook) {
		//$this->debug('loc_hook=' . $hook);
	}
	
	public function render_main_js() {
		$this->render_footer_js('main');
	}

	public function render_matches_js() {
		$this->render_footer_js('matches');
	}
	
	
	private function render_footer_js($page = 'main') {
		$newline = "\n";
		$api_list = array(
			'main' => "'venue', 'level', 'role', 'stat', 'team', 'season'",
			'matches' => "'venue', 'level', 'team', 'season'",
			'members' => "'member', 'role', 'stat'",
		);
		echo '<script type="text/javascript">' . $newline;
		echo 'jQuery(document).ready( function() {' . $newline;
		if (isset($api_list[$page])) {
			echo 'team_data.ui.apiList = [ ' . $api_list[$page] . ' ];' . $newline;
		}
		echo 'team_data.ui.updateAllData();' . $newline;
		echo 'team_data.ui.enhanceControls();' . $newline;
		if ($page == 'main') {
			echo 'jQuery("#team_data_admin_options").accordion( { "header": "div.section_title", "active": false, "alwaysOpen": false, "animated": false, "autoHeight": false, "collapsible": true } );' . $newline;
		}
		echo '} );' . $newline;
		echo '</script>' . $newline;
	}

	private function render_match_list() {
		global $wpdb;
		$tables = $this->tables;
		
		echo '<h2>' . __('Available Fixtures', 'team_data') . '</h2>';

		$matchCount = $wpdb->get_var("SELECT COUNT(*) As matchCount FROM $tables->match");
		
		if ($matchCount < 1) {
			echo __('No results found', 'team_data');
		}
		else {
			$pageNum = 0;
			$pageSize = 20;
			$maxPage = ceil($matchCount/$pageSize) - 1;
			if (isset($_GET['fixturePage'])) {
				$pageNum = $_GET['fixturePage'];
			}
			$limit = ' LIMIT ' . ($pageNum * $pageSize) . ', ' . $pageSize;
			echo '<script type="text/javascript">';
			echo "team_data.paging.pageSize = $pageSize;";
			echo "team_data.paging.resultCount = $matchCount;";
			echo '</script>';

			$match_query = "SELECT m.id, m.season_id, CONCAT(s.year,' ',s.season) as season, m.date, DATE_FORMAT(m.date,'%M %d, %Y') As pretty_date, TIME_FORMAT(m.time,'%h:%i %p') as time, m.venue_id, v.name as venue_name, m.opposition_id, t.name as opposition_name, m.level_id, l.name as level_name, m.our_score, m.opposition_score, m.result, m.is_league, m.is_postseason
				FROM $tables->match m, $tables->venue v, $tables->team t, $tables->level l, $tables->season s
				WHERE m.opposition_id = t.id AND m.venue_id = v.id AND m.level_id = l.id AND m.season_id = s.id
				ORDER BY m.date DESC, m.time ASC" . $limit;

			$matches = $wpdb->get_results($match_query);
			if (count($matches) < 1) {
				echo __('No results found', 'team_data');
			}
			else {
				$back_disabled = ($pageNum == 0 ? 'disabled="disabled" ' : '');
				$next_disabled = ($pageNum == $maxPage ? 'disabled="disabled" ' : '');
				echo '<input class="team_data_button" id="team_data_start" type="button" value="' .__('First Page', 'team_data') . '" ' . $back_disabled . 'onclick="team_data.fn.changePage(false,true);" />';
				echo '<input class="team_data_button" id="team_data_prev" type="button" value="' .__('Previous', 'team_data') . '" ' . $back_disabled . 'onclick="team_data.fn.changePage(false,false);" />';
				echo '<input class="team_data_button" id="team_data_next" type="button" value="' .__('Next', 'team_data') . '" ' . $next_disabled . 'onclick="team_data.fn.changePage(true,false);" />';
				echo '<input class="team_data_button" id="team_data_end" type="button" value="' .__('Last Page', 'team_data') . '" ' . $next_disabled . 'onclick="team_data.fn.changePage(true,true);" />';
				echo '<div id="team_data_page_info">';
				printf(__('Displaying page %1$s of %2$s'), $pageNum + 1, ceil($matchCount/$pageSize));
				//echo 'Displaying page ' . ($pageNum + 1) . ' of ' . (ceil($matchCount/$pageSize));
				echo '</div>';
				echo '<table class="team_data_matches">';
				echo '<tr>';
				echo '<th>' . __('Season', 'team_data') . '</th>';
				echo '<th>' . __('Date', 'team_data') . '</th>';
				echo '<th>' . __('Time', 'team_data') . '</th>';
				echo '<th>' . __('Opponent', 'team_data') . '</th>';
				echo '<th>' . __('Level', 'team_data') . '</th>';
				echo '<th>' . __('Venue', 'team_data') . '</th>';
				echo '<th>' . __('League','team_data') . '/<br/>' . __('Playoffs', 'team_data') . '</th>';
				echo '<th>' . __('Score', 'team_data') . '</th>';
				echo '<th>&nbsp;</th>';
				echo '</tr>';
				foreach ($matches as $match) {
					echo '<tr id="team_data_match_row_' . $match->id . '">';
					echo '<td>' . $match->season . '</td>';
					echo '<td>' . $match->pretty_date . '</td>';
					echo '<td>' . $match->time . '</td>';
					echo '<td>' . $match->opposition_name . '</td>';
					echo '<td>' . $match->level_name . '</td>';
					echo '<td>' . $match->venue_name . '</td>';
					echo '<td>' . ($match->is_league == '1' ? 'L' : ($match->is_postseason == '1' ? 'P' : '&nbsp;')) . '</td>';
					
					$match_result = $this->get_match_result_string($match->our_score,$match->opposition_score,$match->result);
					echo '<td>';
						echo '<div id="team_data_edit__score_display_' . $match->id . '">';
							echo (($match_result == '') ? '&nbsp;-&nbsp;' : $match_result);
						echo '</div>';
						echo '<form id="team_data_edit__score_edit_' . $match->id . '" style="display: none;">';
							echo '<input id="team_data_edit__score_edit_' . $match->id . '_our" class="team_data_input" placeholder="' . __('ours','team_data') . '" name="score_our_score" type="text" size="3" value="' . (($match->our_score == null) || ($match->our_score == '') ? '' : $match->our_score) . '"/>';
							echo '&nbsp;';
							echo '<input id="team_data_edit__score_edit_' . $match->id . '_opposition" class="team_data_input" placeholder="' . __('theirs','team_data') . '" name="score_opposition_score" type="text" size="3" value="' . (($match->opposition_score == null) || ($match->opposition_score == '') ? '' : $match->opposition_score) . '"/>';
							echo '<input id="team_data_edit_score_edit_' . $match->id . '_result" class="team_data_input" placeholder="' . __('result','team_data') . '" name="score_result" type="text" size="1" value="' . (($match->result == null) || ($match->result == '') ? '' : $match->result) . '"/>';
						echo '</form>';
					echo '</td>';
					echo '<td>';
						echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_score_' . $match->id . '" onclick="team_data.api.match.toggleScoreControls(' . $match->id . ',true);" value="' . __('Edit Score', 'team_data') . '" />';
						echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_score_save_' . $match->id . '" style="display: none;" onclick="team_data.api.match.editScore(' . $match->id . ');" value="' . __('Save Score', 'team_data') . '" />';
						echo '&nbsp;&nbsp;';
						echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_' . $match->id . '" onclick="team_data.api.match.editMatch(' . $match->id . ');" value="' . __('Edit', 'team_data') . '" />';
					echo '</td>';
					echo '</tr>';
				}
				echo '</table>';
				echo '';
				echo '<script type="text/javascript">' . "\n";
				foreach ($matches as $match) {
					$our_score = ($match->our_score == null ? 'null' : $match->our_score);
					$opposition_score = ($match->opposition_score == null ? 'null' : $match->opposition_score);
					echo "team_data.matchData[$match->id] = { \"date\": \"$match->date\", \"time\": \"$match->time\", \"venue\": \"$match->venue_name\", \"team\": \"$match->opposition_id\", \"level\": \"$match->level_id\", \"our_score\": $our_score, \"opposition_score\": $opposition_score, \"stat\": { ";
					
					$stats = $wpdb->get_results("SELECT ms.id, ms.member_id, ms.stat_id, s.name as stat_name, CASE s.value_type WHEN '0' THEN ms.stat_intvalue WHEN '1' THEN ms.stat_stringvalue ELSE NULL END AS stat_value FROM $tables->stat s, $tables->match_stat ms WHERE ms.match_id = $match->id AND ms.stat_id = s.id");
					$first_stat = true;
					foreach ($stats as $stat) {
						if ($first_stat) {
							$first_stat = false;
						}
						else {
							echo ",\n";
						}
						
						/* TODO
						echo "\"$stat->id\": { \"member\": $stat->member_id, \"tries\": $scorer->tries, \"conversions\": $scorer->conversions, \"penalties\": $scorer->penalties, \"drop_goals\": $scorer->drop_goals }";
						*/
					}
					echo " } };\n";
				}
				echo '</script>';
			}
		}
		$this->render_match_edit_div($wpdb,false);
	}

	private function get_match_result_string($our_score,$opposition_score,$stored_result = '') {
		if ($stored_result !== '') return $stored_result;
		if (($our_score == null) || ($opposition_score == null)) return '';
		$result = 'W';
		if ($our_score < $opposition_score) {
			$result = 'L';
		}
		else if ($our_score == $opposition_score) {
			$result = 'D';
		}
		return $result . '&nbsp;' . $our_score . '&nbsp;-&nbsp;' . $opposition_score;
	}

	private function render_match_edit_div($wpdb, $display) {
		$tables = $this->tables;
		$levels = $this->get_levels();
		echo '<div id="team_data_match_edit_div" class="team_data_admin_section"' . (!$display ? ' style="display: none;"' : '') . '>';
			echo '<div style="font-weight: bold; font-size: 1.2em; padding: 5px;">' . __('Update an existing match or result:','team_data') . '</div>';
			echo '<form id="team_data_match_edit">';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__id" class="team_data_edit_label">' . __('ID','team_data') . '</label>';
					echo '<input id="team_data_match_edit__id" class="team_data_edit_input" name="match_id" type="text" size="5" readonly="readonly" disabled="disabled" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__season" class="team_data_edit_label">' . __('Season','team_data') . '</label>';
					echo '<input id="team_data_match_edit__season" class="team_data_edit_input team_data_season" name="match_season" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__date" class="team_data_edit_label">' . __('Date','team_data') . '</label>';
					echo '<input id="team_data_match_edit__date" class="team_data_edit_input team_data_date" name="match_date" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__time" class="team_data_edit_label">' . __('Time','team_data') . '</label>';
					echo '<input id="team_data_match_edit__time" class="team_data_edit_input team_data_time" name="match_time" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__opposition" class="team_data_edit_label">' . __('Opponent','team_data') . '</label>';
					echo '<input id="team_data_match_edit__opposition" class="team_data_edit_input team_data_opposition" name="match_opposition" type="text" size="50" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__level" class="team_data_edit_label">' . __('Level','team_data') . '</label>';
					echo '<select id="team_data_match_edit__level" name="match_level">';
					foreach(array_keys($levels) as $level_id) {
						echo '<option value="' . $level_id . '">' . $levels[$level_id] . '</option>';
					}
					echo '</select>';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__venue" class="team_data_edit_label">' . __('Venue','team_data') . '</label>';
					echo '<input id="team_data_match_edit__venue" class="team_data_edit_input team_data_venue" name="match_venue" type="text" size="50" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__our_score" class="team_data_edit_label">' . __('Us','team_data') . '</label>';
					echo '<input id="team_data_match_edit__our_score" class="team_data_edit_input" name="match_our_score" type="text" size="3" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__opposition_score" class="team_data_edit_label">' . __('Them','team_data') . '</label>';
					echo '<input id="team_data_match_edit__opposition_score" class="team_data_edit_input" name="match_opposition_score" type="text" size="3" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__is_league" class="team_data_edit_label">' . __('League?','team_data') . '</label>';
					echo '<input id="team_data_match_edit__is_league" class="team_data_admin_checkbox" name="match_is_league" type="checkbox" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__is_postseason" class="team_data_edit_label">' . __('Playoffs?','team_data') . '</label>';
					echo '<input id="team_data_match_edit__is_postseason" class="team_data_admin_checkbox" name="match_is_postseason" type="checkbox" />';
				echo '</div>';
			echo '</form>';
			echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_save" onclick="team_data.api.match.saveMatch();" value="' . __('Save', 'team_data') . '" />';
		echo '</div>';
	}
}
?>