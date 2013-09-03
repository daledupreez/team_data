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
		$this->page_codes['email'] = add_submenu_page('team-data-admin', __('Send Email','team_data'), __('Send Email','team_data'), 'manage_options', 'team-data-email', array($this, 'render_email'));

		add_action('admin_footer-' . $this->page_codes['main'], array($this, 'render_main_js'));
		add_action('admin_footer-' . $this->page_codes['matches'], array($this, 'render_matches_js'));
		add_action('admin_footer-' . $this->page_codes['members'], array($this, 'render_member_js'));
		add_action('admin_footer-' . $this->page_codes['email'], array($this, 'render_email_js'));
	}

	public function render_matches() {	
		$this->render_match_list();

?>
<br/>
<input class="team_data_button" id="team_data_new_match_button" type="button" value="<?php echo __('Create New Match(es)','team_data'); ?>" onclick="team_data.api.match.newMatches();" />
<input class="team_data_button" id="team_data_new_tourney_button" type="button" value="<?php echo __('Create New Tournament','team_data'); ?>" onclick="team_data.api.match.newTournament();" />
<div id="team_data_new_match" class="team_data_admin_section" style="display: none;">
	<form id="team_data_new_match_shared">
		<div class="team_data_inline">
			<label for="team_data_new_match_shared__season" class="team_data_edit_label"><?php echo __('Season','team_data'); ?></label>
			<input id="team_data_new_match_shared__season" class="team_data_season team_data_edit_input" name="shared_season" type="text" size="20" />
		</div>
		<div class="team_data_inline">
			<label for="team_data_new_match_shared__date" class="team_data_edit_label"><?php echo __('Date','team_data'); ?></label>
			<input id="team_data_new_match_shared__date" class="team_data_date team_data_edit_input" name="shared_date" type="text" size="10" />
		</div>
		<div class="team_data_inline">
			<label for="team_data_new_match_shared__venue" class="team_data_edit_label"><?php echo __('Venue','team_data'); ?></label>
			<input id="team_data_new_match_shared__venue" class="team_data_venue team_data_edit_input" name="shared_venue" type="text" size="50" />
		</div>
		<div id="team_data_new_match_shared_opposition_div" class="team_data_inline">
			<label for="team_data_new_match_shared__opposition" class="team_data_edit_label"><?php echo __('Opponent','team_data'); ?></label>
			<input id="team_data_new_match_shared__opposition" class="team_data_team team_data_edit_input" name="shared_opposition" type="text" size="50" />
		</div>
		<div id="team_data_new_match_shared_tourney_div" class="team_data_inline">
			<label for="team_data_new_match_shared__tourney_name" class="team_data_edit_label"><?php echo __('Tournament Name','team_data'); ?></label>
			<input id="team_data_new_match_shared__tourney_name" class="team_data_edit_input" name="shared_tourney_name" type="text" size="50" />
		</div>
		<input id="team_data_new_match_shared__matchCount" type="hidden" value="0" />
	</form>
<?php
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
			echo '<input id="team_data_new_tourney__save" class="team_data_button" type="button" value="' . __('Save Tournament', 'team_data') . '" onclick="team_data.api.match.saveNewMatches(true);" />';
			echo '<input id="team_data_new_match__save" class="team_data_button" type="button" value="' . __('Save Matches', 'team_data') . '" onclick="team_data.api.match.saveNewMatches(false);" />';
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
	
	public function render_email() {
		echo '<h2>' . __('Send Email', 'team_data') . '</h2>';
		echo '<h4>' . __('Send emails to lists', 'team_data') . '</h4>';

		$email_enabled = $this->get_option('email_enabled');
		if (empty($email_enabled)) {
			echo '<span>' . __('Email is not currently enabled.', 'team_data') . '</span>';
			return;
		}
	?>
		<div>
			<form id="team_data_send_email">
				<div>
					<label for="team_data_send_email_subject" class="team_data_edit_label"><?php echo __('Subject', 'team_data'); ?></label>
					<input id="team_data_send_email_subject" type="text" size="40" value="" />
				</div>
				<div>
					<label for="team_data_send_email_message" class="team_data_edit_label"><?php echo __('Message', 'team_data'); ?></label>
					<textarea id="team_data_send_email_message" cols="50" rows="10"></textarea>
				</div>
			</form>
			<input id="team_data_send_email__send" class="team_data_button" type="button" value="<?php echo __('Send email', 'team_data'); ?>" onclick="team_data.api.email.sendEmail();" />
		</div>
	<?php
	}

	public function render_members() {
		echo '<h2>' . __('Member Administration Page', 'team_data') . '</h2>';
		echo '<h4>' . __('Manage the team membership.', 'team_data') . '</h4>'; ?>
<div>
	<form id="team_data_member_search" onsubmit="team_data.api.member_search.search(); return false;">
		<div><?php echo __('Enter the first few characters of the following fields to narrow down the results.','team_data'); ?></div>
		<div class="team_data_inline">
			<label for="team_data_member_search__first_name" class="team_data_edit_label"><?php echo __('First name','team_data'); ?></label>
			<input id="team_data_member_search__first_name" class="team_data_edit_input" name="member_search_first_name" type="text" size="20" />
		</div>
		<div class="team_data_inline">
			<label for="team_data_member_search__last_name" class="team_data_edit_label"><?php echo __('Last name','team_data'); ?></label>
			<input id="team_data_member_search__last_name" class="team_data_edit_input" name="member_search_last_name" type="text" size="20" />
		</div>
		<div class="team_data_inline">
			<label for="team_data_member_search__email" class="team_data_edit_label"><?php echo __('Email','team_data'); ?></label>
			<input id="team_data_member_search__email" class="team_data_edit_input" name="member_search_email" type="text" size="20" />
		</div>
		<div class="team_data_inline">
			<label for="team_data_member_search__active" class="team_data_edit_label"><?php echo __('Active','team_data'); ?></label>
			<input id="team_data_member_search__active" class="team_data_edit_input" name="member_search_active" type="checkbox" checked="1" />
		</div>
		<div class="team_data_inline team_data_checkbox_group">
			<label class="team_data_edit_label"><strong><?php echo __('Email Lists','team_data'); ?></strong></label>
<?php
		$admin_ajax = new TeamDataAdminAjax();
		$lists = $admin_ajax->get_all_lists();
		foreach ($lists as $list) {
			?>
			<input id="team_data_member_search__list_<?php echo $list['id']; ?>" type="checkbox" class="team_data_edit_input" value="<?php echo $list['id']; ?>" name="member_search_lists" />
			<label for="team_data_member_search__list_<?php echo $list['id']; ?>" class="team_data_checkbox_label"><?php echo $list['name']; ?></label>
			<?php
		}
?>
		</div>
		<div class="team_data_buttonDiv">
			<input id="team_data_member_search__submit" class="team_data_button" type="submit" value="<?php echo __('Search', 'team_data'); ?>" />
			<input class="team_data_button" type="button" value="<?php echo __('Reset search','team_data'); ?>" onclick="team_data.api.member_search.clear();" />
		</div>
	</form>
	<div id="team_data_members"></div>
</div>
<?php // end render_members()
	}

	public function render_admin_main() {
		global $wpdb;
		echo '<h2>' . __('Main TeamData Administration Page', 'team_data') . '</h2>';
		echo '<div>' . __('Manage the main infrastructure for the TeamData plugin.','team_data') . '<br/>' . __('Each of the boxes below represents a list of available options to show when entering matches, stats, results or members.', 'team_data') . '</div>';
?>
<div id="team_data_admin_options">
	<div>
		<div class="section_title"><?php echo __('Venues', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php 
				echo __('A list of all the venues where you play matches.','team_data');
				echo ' ' . __('In general, venues are not associated with a specific team, except for your home venue(s).','team_data'); ?>
			</div>
			<div id="team_data_venue_table" class="team_data_simple_table_venue"></div>
			<form id="team_data_venue_edit" class="team_data_admin_section">
				<div class="team_data_inline">
					<label for="team_data_venue_edit__id" class="team_data_edit_label"><?php echo __('ID','team_data'); ?></label>
					<input id="team_data_venue_edit__id" class="team_data_edit_input" name="venue_id" type="text" readonly="readonly" disabled="disabled" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_venue_edit__name" class="team_data_edit_label"><?php echo __('Name','team_data'); ?></label>
					<input id="team_data_venue_edit__name" class="team_data_edit_input" name="venue_name" type="text" size="50" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_venue_edit__abbreviation" class="team_data_edit_label"><?php echo __('Short Name','team_data'); ?></label>
					<input id="team_data_venue_edit__abbreviation" class="team_data_edit_input" name="venue_abbreviation" type="text" size="30" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_venue_edit__is_home" class="team_data_edit_label"><?php echo __('Home Venue','team_data'); ?></label>
					<input id="team_data_venue_edit__is_home" class="team_data_admin_checkbox" name="venue_is_home" type="checkbox" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_venue_edit__info_link" class="team_data_edit_label"><?php echo __('Info Link','team_data'); ?></label>
					<input id="team_data_venue_edit__info_link" class="team_data_edit_input" name="venue_info_link" type="text" size="60" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_venue_edit__directions_link" class="team_data_edit_label"><?php echo __('Directions/Map Link','team_data'); ?></label>
					<input id="team_data_venue_edit__directions_link" class="team_data_edit_input" name="venue_directions_link" type="text" size="60" />
				</div>
			</form>';
			<div class="team_data_buttonDiv">
				<input id="team_data_venue_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.venue.save();" />
				<input class="team_data_button" type="button" value="<?php echo __('Create New Venue','team_data'); ?>" onclick="team_data.api.venue.clearForm();" />
			</div>
		</div>
	</div>
	<div>
		<div class="section_title"><?php echo __('Stats', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php
				echo __('A list of all the statistics which you collect for your matches.','team_data');
				echo ' ' . __('This is fairly flexible, but you should be careful about datatypes.','team_data');
				echo ' ' . __('Unless you are sure you will only have integer or float data, it is likely best to use strings for your statistics.','team_data');
				?>
			</div>
			<div id="team_data_stat_table" class="team_data_simple_table_stat"></div>
			<form id="team_data_stat_edit" class="team_data_admin_section">
				<div class="team_data_inline">
					<label for="team_data_stat_edit__id" class="team_data_edit_label"><?php echo __('ID','team_data'); ?></label>
					<input id="team_data_stat_edit__id" class="team_data_edit_input" name="stat_id" type="text" readonly="readonly" disabled="disabled" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_stat_edit__name" class="team_data_edit_label"><?php echo __('Name','team_data'); ?></label>
					<input id="team_data_stat_edit__name" class="team_data_edit_input" name="stat_name" type="text" size="50" />
				</div>
				<div class="team_data_inline">
					<div class="team_data_edit_radioset">
						<div><?php echo __('Data type for stat', 'team_data'); ?></div>
						<input id="team_data_stat_edit__value_type_integer" class="team_data_edit_input" name="stat_value_type" type="radio" value="0"/>
						<label for="team_data_stat_edit__value_type_integer" class="team_data_edit_label_radioset"><?php echo __('Integer (Whole Number)', 'team_data'); ?></label>
						<input id="team_data_stat_edit__value_type_string" class="team_data_edit_input" name="stat_value_type" type="radio" value="1"/>
						<label for="team_data_stat_edit__value_type_string" class="team_data_edit_label_radioset"><?php echo __('String (Text)', 'team_data'); ?></label>
						<input id="team_data_stat_edit__value_type_float" class="team_data_edit_input" name="stat_value_type" type="radio" value="2"/>
						<label for="team_data_stat_edit__value_type_float" class="team_data_edit_label_radioset"><?php echo __('Float (Number with Decimals)', 'team_data'); ?></label>
						<input id="team_data_stat_edit_value_type" type="hidden" value="0" />
					</div>
				</div>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_stat_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.stat.save();" />
				<input class="team_data_button" type="button" value="<?php echo __('Create New Stat','team_data'); ?>" onclick="team_data.api.stat.clearForm();" />
			</div>
		</div>
	</div>
	<div>
		<div class="section_title"><?php echo __('Levels', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php
					echo __('A list of all the levels/leagues/competitions in which your teams compete.','team_data');
					echo ' ' . __('This is fairly flexible, but becomes important when calculating stats and organising matches.','team_data');
				?>
			</div>
			<div id="team_data_level_table" class="team_data_simple_table_level"></div>
			<form id="team_data_level_edit" class="team_data_admin_section">
				<div class="team_data_inline">
					<label for="team_data_level_edit__id" class="team_data_edit_label"><?php echo __('ID','team_data'); ?></label>
					<input id="team_data_level_edit__id" class="team_data_edit_input" name="level_id" type="text" readonly="readonly" disabled="disabled" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_level_edit__name" class="team_data_edit_label"><?php echo __('Name','team_data'); ?></label>
					<input id="team_data_level_edit__name" class="team_data_edit_input" name="level_name" type="text" size="40" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_level_edit__abbreviation" class="team_data_edit_label"><?php echo __('Short Name','team_data'); ?></label>
					<input id="team_data_level_edit__abbreviation" class="team_data_edit_input" name="level_abbreviation" type="text" size="15" />
				</div>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_level_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.level.save();" />
				<input class="team_data_button" type="button" value="<?php echo __('Create New Level','team_data'); ?>" onclick="team_data.api.level.clearForm();" />
			</div>
		</div>
	</div>
	<div>
		<div class="section_title"><?php echo __('Email Lists', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php echo __('All the email lists that are available for use.','team_data'); ?>
			</div>
			<div id="team_data_list_table" class="team_data_simple_table_list"></div>
			<form id="team_data_list_edit" class="team_data_admin_section">
				<div class="team_data_inline">
					<label for="team_data_list_edit__id" class="team_data_edit_label"><?php echo __('ID','team_data'); ?></label>
					<input id="team_data_list_edit__id" class="team_data_edit_input" name="list_id" type="text" readonly="readonly" disabled="disabled" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_list_edit__name" class="team_data_edit_label"><?php echo __('Name','team_data'); ?></label>
					<input id="team_data_list_edit__name" class="team_data_edit_input" name="list_name" type="text" size="40" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_list_edit__auto_enroll" class="team_data_edit_label"><?php echo __('Enroll New Members By Default','team_data'); ?></label>
					<input id="team_data_list_edit__auto_enroll" class="team_data_admin_checkbox" name="list_auto_enroll" type="checkbox" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_list_edit__display_name" class="team_data_edit_label"><?php echo __('Display Name','team_data'); ?></label>
					<input id="team_data_list_edit__display_name" class="team_data_edit_input" name="list_display_name" type="text" size="40" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_list_edit__admin_only" class="team_data_edit_label"><?php echo __('Membership chosen by admins','team_data'); ?></label>
					<input id="team_data_list_edit__admin_only" class="team_data_admin_checkbox" name="list_admin_only" type="checkbox" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_list_edit__comment" class="team_data_edit_label"><?php echo __('Details','team_data'); ?></label>
					<textarea id="team_data_list_edit__comment" class="team_data_edit_input" name="list_comment" cols="80" rows="3"></textarea>
				</div>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_list_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.list.save();" />
				<input class="team_data_button" type="button" value="<?php echo __('Create New Email List','team_data'); ?>" onclick="team_data.api.list.clearForm();" />
			</div>
		</div>
	</div>
	<div>
		<div class="section_title"><?php echo __('Teams', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php
				echo __('A list of all the teams that you play against.','team_data');
				echo ' ' . __('You must add any team you play against to this list before you can add or edit any matches.','team_data');
			?>
			</div>
			<div id="team_data_team_table" class="team_data_simple_table_team"></div>
			<form id="team_data_team_edit" class="team_data_admin_section">
				<div class="team_data_inline">
					<label for="team_data_team_edit__id" class="team_data_edit_label"><?php echo __('ID','team_data'); ?></label>
					<input id="team_data_team_edit__id" class="team_data_edit_input" name="team_id" type="text" readonly="readonly" disabled="disabled" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_team_edit__name" class="team_data_edit_label"><?php echo __('Name','team_data'); ?></label>
					<input id="team_data_team_edit__name" class="team_data_edit_input" name="team_name" type="text" size="50" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_team_edit__abbreviation" class="team_data_edit_label"><?php echo __('Short Name','team_data'); ?></label>
					<input id="team_data_team_edit__abbreviation" class="team_data_edit_input" name="team_abbreviation" type="text" size="30" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_team_edit__logo_link" class="team_data_edit_label"><?php echo __('Logo Link','team_data'); ?></label>
					<input id="team_data_team_edit__logo_link" class="team_data_edit_input" name="team_logo_link" type="text" size="60" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_team_edit__is_us" class="team_data_edit_label"><?php echo __('Our Team','team_data'); ?></label>
					<input id="team_data_team_edit__is_us" class="team_data_admin_checkbox" name="team_is_us" type="checkbox" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_team_edit__info_link" class="team_data_edit_label"><?php echo __('Website','team_data'); ?></label>
					<input id="team_data_team_edit__info_link" class="team_data_edit_input" name="team_info_link" type="text" size="60" />
				</div>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_team_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.team.save();" />
				<input class="team_data_button" type="button" value="<?php echo __('Create New Team','team_data'); ?>" onclick="team_data.api.team.clearForm();" />
			</div>
		</div>
	</div>
	<div>
		<div class="section_title"><?php echo __('Seasons', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php
				echo __('A list of all available seasons.','team_data');
				echo ' ' . __('You must add any seasons to this list before you can add any matches in that season.','team_data');
			?>
			</div>
			<div id="team_data_season_table" class="team_data_simple_table_season"></div>
			<form id="team_data_season_edit" class="team_data_admin_section">
				<div class="team_data_inline">
					<label for="team_data_season_edit__id" class="team_data_edit_label"><?php echo __('ID','team_data'); ?></label>
					<input id="team_data_season_edit__id" class="team_data_edit_input" name="season_id" type="text" readonly="readonly" disabled="disabled" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_season_edit__year" class="team_data_edit_label"><?php echo __('Year','team_data'); ?></label>
					<input id="team_data_season_edit__year" class="team_data_edit_input" name="season_year" type="text" size="5" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_season_edit__season" class="team_data_edit_label team_data_season"><?php echo __('Season','team_data'); ?></label>
					<input id="team_data_season_edit__season" class="team_data_edit_input" name="season_season" type="text" size="30" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_season_edit__is_current" class="team_data_edit_label"><?php echo __('Is Current Season','team_data'); ?></label>
					<input id="team_data_season_edit__is_current" class="team_data_admin_checkbox" name="season_is_current" type="checkbox" />
				</div>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_season_edit__repeat_season" class="team_data_button" type="button" value="<?php echo __('Same as last Season', 'team_data'); ?>" onclick="team_data.api.season.repeatLastSeason();" />
				<input id="team_data_season_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.season.save();" />
				<input class="team_data_button" type="button" value="<?php echo __('Create New Season','team_data'); ?>" onclick="team_data.api.season.clearForm();" />
			</div>
		</div>
	</div>
	<div>
		<div class="section_title"><?php echo __('General Options', 'team_data'); ?></div>
		<div>
			<div class="team_data_help"><?php
				echo __('General configuration options for the TeamData plugin.','team_data');
				echo ' ' . __('Note that the SMTP Password is never returned in the UI. Save the password as -1 to clear the password.', 'team_data');
			?>
			</div>
			<form id="team_data_options_edit" class="team_data_admin_section">
				<table>
					<tr>
						<td><label for="team_data_options_edit__max_matches"><?php echo __('Max Edit Matches','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__max_matches" class="team_data_edit_input" name="options_max_matches" type="number" size="5" value="<?php $max_matches = $this->get_option('max_matches'); echo $max_matches; ?>" />
							<input id="team_data_options_edit__max_matches_orig" type="hidden" value="<?php echo $max_matches; ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_enabled"><?php echo __('Enable Email','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_enabled" class="team_data_admin_checkbox" name="options_email_enabled" type="checkbox" <?php $email_enabled = ($this->get_option('email_enabled') == '1' ? true : false); if ($email_enabled) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__email_enabled_orig" type="checkbox" style="display: none;" <?php if ($email_enabled) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>

			<?php if ($email_enabled) { ?>
					<tr>
						<td><label for="team_data_options_edit__email_from"><?php echo __('Default From address','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_from" class="team_data_edit_input" name="options_email_from" type="email" size="30" value="<?php $email_from = $this->get_option('email_from'); $email_from = (empty($email_from) ? '' : $email_from); echo $email_from; ?>" />
							<input id="team_data_options_edit__email_from_orig" type="hidden" value="<?php echo $email_from; ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_summary_to"><?php echo __('Summary recipient','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_summary_to" class="team_data_edit_input" name="options_email_summary_to" type="email" size="30" value="<?php $email_summary_to = $this->get_option('email_summary_to'); $email_summary_to = (empty($email_summary_to) ? '' : $email_summary_to); echo $email_summary_to; ?>" />
							<input id="team_data_options_edit__email_summary_to_orig" type="hidden" value="<?php echo $email_summary_to; ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__allow_all_member_mail"><?php echo __('Allow All Member Emails','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__allow_all_member_mail" class="team_data_admin_checkbox" name="options_allow_all_member_mail" type="checkbox" <?php $allow_all_member_mail = ($this->get_option('allow_all_member_mail') == '1' ? true : false); if ($allow_all_member_mail) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__allow_all_member_mail_orig" type="checkbox" style="display: none;" <?php if ($allow_all_member_mail) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__use_smtp" ><?php echo __('Use SMTP','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__use_smtp" class="team_data_admin_checkbox" name="options_use_smtp" type="checkbox" <?php $use_smtp = ($this->get_option('use_smtp') == '1' ? true : false); if ($use_smtp) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__use_smtp_orig" type="checkbox" style="display: none;" <?php if ($use_smtp) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_server"><?php echo __('SMTP Server','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_server" class="team_data_edit_input" name="options_smtp_server" type="text" size="30" value="<?php $smtp_server = $this->get_option('smtp_server'); $smtp_server = (empty($smtp_server) ? '' : $smtp_server); echo $smtp_server; ?>" />
							<input id="team_data_options_edit__smtp_server_orig" type="hidden" value="<?php echo $smtp_server; ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_port"><?php echo __('SMTP Port','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_port" class="team_data_edit_input" name="options_smtp_port" type="number" size="5" value="<?php $smtp_port = $this->get_option('smtp_port'); $smtp_port = (empty($smtp_port) ? '' : $smtp_port); echo $smtp_port; ?>" />
							<input id="team_data_options_edit__smtp_port_orig" type="hidden" value="<?php echo $smtp_port; ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_user"><?php echo __('SMTP User','team_data'); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_user" class="team_data_edit_input" name="options_smtp_user" type="text" size="30" value="<?php $smtp_user = $this->get_option('smtp_user'); $smtp_user = (empty($smtp_user) ? '' : $smtp_user); echo $smtp_user; ?>" />
							<input id="team_data_options_edit__smtp_user_orig" type="hidden" value="<?php echo $smtp_user; ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_password"><?php echo __('SMTP Password','team_data'); ?></label></td>
						<td><input id="team_data_options_edit__smtp_password" class="team_data_edit_input" name="options_smtp_password" type="text" size="30" value="" /></td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="team_data_options_edit__text_footer" class="team_data_edit_label"><?php echo __('Footer text to include in plain text emails','team_data'); ?></label>
							<textarea id="team_data_options_edit__text_footer" class="team_data_edit_input" name="options_text_footer" rows="4" cols="75"><?php $text_footer = $this->get_option('text_footer'); $text_footer = (empty($text_footer) ? '' : $text_footer); echo $text_footer; ?></textarea>
							<textarea id="team_data_options_edit__text_footer_orig" style="display: none;"><?php echo $text_footer; ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="team_data_options_edit__html_template" class="team_data_edit_label"><?php echo __('HTML template to use for outbound emails. Use [[CONTENT]] to specify location of text.','team_data'); ?></label>
							<textarea id="team_data_options_edit__html_template" class="team_data_edit_input" name="options_html_template" rows="20" cols="75"><?php $html_template = $this->get_option('html_template'); $html_template = (empty($html_template) ? '' : $html_template); echo $html_template; ?></textarea>
							<textarea id="team_data_options_edit__html_template_orig" style="display: none;"><?php echo $html_template; ?></textarea>
						</td>
					</tr>
			<?php } ?>	
				</table>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_options_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.options.save();" />
			</div>
		</div>
	</div>
</div>
<?php // end render_admin_main()
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

	public function render_member_js() {
		global $wpdb;

		$admin_ajax_obj = new TeamDataAdminAjax();
		$member_data = $admin_ajax_obj->get_all_member_data();
		$lists = $admin_ajax_obj->get_all_lists();
?>
<script type="text/javascript">
team_data.member_data = { "members": <?php echo json_encode($member_data); ?> };
jQuery(document).ready( function() {
	team_data.ui.apiList = [ 'list' ];
	var listData = <?php echo json_encode($lists); ?>;
	team_data.api.list.updateList(listData);
	team_data.api.member_search.render();
} );
</script>
<?php // end render_member_js()
	}

	public function render_main_js() {
		$this->render_footer_js('main');
	}

	public function render_matches_js() {
		$this->render_footer_js('matches');
	}
	
	public function render_email_js() {
		$this->render_footer_js('email');
	}
	
	private function render_footer_js($page = 'main') {
		$newline = "\n";
		$api_list = array(
			'main' => "'venue', 'level', 'list', 'stat', 'team', 'season'",
			'matches' => "'venue', 'level', 'team', 'season'",
			'members' => "'member', 'list', 'stat'",
			'email' => "",
		);
		echo '<script type="text/javascript">' . $newline;
		echo 'jQuery(document).ready( function() {' . $newline;
		if (isset($api_list[$page])) {
			echo 'team_data.ui.apiList = [ ' . $api_list[$page] . ' ];' . $newline;
		}
		echo 'team_data.ui.updateAllData();' . $newline;
		echo 'team_data.ui.enhanceControls();' . $newline;
		if ($page == 'main') {
			echo 'jQuery("#team_data_admin_options").accordion( { "header": "div.section_title", "active": false, "alwaysOpen": false, "animated": false, "heightStyle": "content", "collapsible": true } );' . $newline;
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

			$match_query = "SELECT m.id, m.season_id, CONCAT(s.year,' ',s.season) AS season, m.date, DATE_FORMAT(m.date,'%M %d, %Y') AS pretty_date, TIME_FORMAT(m.time,'%h:%i %p') AS time, m.venue_id, v.name AS venue_name, m.opposition_id, m.opposition_name, m.tourney_name, m.level_id, l.name AS level_name, m.our_score, m.opposition_score, m.result, m.is_league, m.is_postseason
				FROM 
					( SELECT match.id, match.season_id, match.date, match.time, match.venue_id, match.opposition_id, match.tourney_name, match.level_id, match.our_score, match.opposition_score, match.result, match.is_league, match.is_postseason, IF(match.opposition_id IS NULL, '', team.name) AS opposition_name
						FROM $tables->match AS `match` LEFT OUTER JOIN $tables->team AS team ON match.opposition_id = team.id) m,
						$tables->venue v, $tables->level l, $tables->season s
				WHERE m.venue_id = v.id AND m.level_id = l.id AND m.season_id = s.id
				ORDER BY m.date DESC, m.time ASC" . $limit;
			$this->debug('$match_query = ' . $match_query);

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
				echo '<table class="team_data_table team_data_matches">';
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
					echo '<td>' . ($match->opposition_name != '' ? $match->opposition_name : $match->tourney_name ) . '</td>';
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
					echo "team_data.matchData[$match->id] = { \"date\": \"$match->date\", \"time\": \"$match->time\", \"venue\": \"$match->venue_name\", \"team\": \"$match->opposition_id\", \"tourney_name\": \"$match->tourney_name\", \"level\": \"$match->level_id\", \"our_score\": $our_score, \"opposition_score\": $opposition_score, \"stat\": { ";
					
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