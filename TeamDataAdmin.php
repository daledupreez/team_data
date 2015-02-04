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

		$menu_items = array(
			'main' => array(
				'menu_title' => __('Team Data Admin','team_data'),
				'page_title' => __('Admin','team_data'),
				'capability' => 'team_data_manage_options',
				'slug' => 'team-data-admin',
				'render_method' => 'render_admin_main',
				'main_menu' => true,
			),
			'matches' => array(
				'page_title' => __('Matches and Results','team_data'),
				'capability' => 'team_data_manage_matches',
			),
			'members' => array(
				'page_title' => __('Members','team_data'),
				'capability' => 'team_data_manage_members',
			),
			'email' => array(
				'page_title' => __('Send Email','team_data'),
				'capability' => 'team_data_send_mail',
			),
		);
		foreach ($menu_items as $menu => $data) {
			$menu_title = ( isset( $data['menu_title'] ) ? $data['menu_title'] : $data['page_title'] );
			$menu_slug = ( isset( $data['slug'] ) ? $data['slug'] : 'team-data-' . $menu );
			$render_method = ( isset( $data['render_method'] ) ? $data['render_method'] : 'render_' . $menu );
			$is_menu = ( isset( $data['main_menu'] ) && $data['main_menu'] );
			if ( $is_menu ) {
				$this->page_codes[$menu] = add_menu_page( $data['page_title'], $menu_title, $data['capability'], $menu_slug, array( $this, $render_method ) );
			}
			else {
				$this->page_codes[$menu] = add_submenu_page( 'team-data-admin', $data['page_title'], $menu_title, $data['capability'], $menu_slug, array( $this, $render_method ) );
			}
			add_action( 'admin_footer-' . $this->page_codes[$menu], array( $this, 'render_' . $menu . '_js' ) );
		}
	}

	public function render_matches() {
		if ( !current_user_can( 'team_data_manage_matches' ) ) {
			return;
		}
		echo '<div class="team_data_content">';
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
		echo '</div>'; // End team_data_content div
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
		if ( !current_user_can( 'team_data_send_email' ) ) {
			return;
		}
		global $wpdb;

		echo '<div class="team_data_content">';
		echo '<h2>' . __('Send Email', 'team_data') . '</h2>';
		echo '<h4>' . __('Send email to configured TeamData lists', 'team_data') . '</h4>';

		$email_enabled = $this->get_option('email_enabled');
		if (empty($email_enabled)) {
			echo '<span>' . __('Email is not currently enabled.', 'team_data') . '</span>';
			echo '</div>';
			return;
		}
		$email_prefix = $this->get_option('email_prefix');
	?>
		<div>
			<form id="team_data_send_email">
				<div class="team_data_edit_radioset">
					<div><?php echo __('Select mailing list', 'team_data'); ?></div>
<?php
					echo '<input id="team_data_send_email_list_id" type="hidden" value="" />';
					$onchange = "document.getElementById('team_data_send_email_list_id').value = this.value;";
					if ( $this->get_option('allow_all_member_mail') == '1' ) {
						echo '<input id="team_data_email_list_all" type="radio" class="team_data_edit_input" name="email_list" value="-1" onchange="' . $onchange . '" />';
						echo '<label for="team_data_email_list_all" class="team_data_edit_label_radioset">' . __('All Members', 'team_data') . '</label>';
					}
					$list_sql = "SELECT id, name FROM " . $this->tables->list;
					$lists = $wpdb->get_results($list_sql);
					foreach ($lists as $list_pos => $list_data) {
						echo '<input id="team_data_email_list_' . esc_attr($list_data->id) . '" type="radio" name="email_list" class="team_data_edit_input" value="' . esc_attr($list_data->id) . '" onchange="' . $onchange . '" />';
						echo '<label for="team_data_email_list_' . esc_attr($list_data->id) . '" class="team_data_edit_label_radioset">' . esc_html($list_data->name) . '</label>';
					}
?>
				</div>
				<div>
					<label for="team_data_send_email_subject" class="team_data_edit_label"><?php echo __('Subject', 'team_data'); ?></label>
					<input id="team_data_send_email_subject" type="text" size="60" value="<?php echo ( !empty($email_prefix) ? esc_attr($email_prefix) . ' ' : ''); ?>" />
				</div>
				<div>
					<label for="team_data_send_email_replyto" class="team_data_edit_label"><?php echo __('Reply To Address', 'team_data'); ?></label>
					<input id="team_data_send_email_replyto" type="email" size="60" value="" />
				</div>
				<div>
					<label for="team_data_send_email_message" class="team_data_edit_label"><?php echo __('Message', 'team_data'); ?></label>
					<textarea id="team_data_send_email_message" class="widefat" cols="60" rows="10"></textarea>
					<br />
					<span><?php printf( esc_html( __('Note that the following HTML formatting sequences are supported: %1$s, %2$s, %3$s, %4$s, %5$s', 'team_data') ), sprintf( '&lt;b&gt;<b>%s</b>&lt;/b&gt;', esc_html( __('bold', 'team_data') ) ), sprintf( '&lt;em&gt;<em>%s</em>&lt;/em&gt;', esc_html( __('emphasis', 'team_data') ) ), sprintf( '&lt;i&gt;<i>%s</i>&lt;/i&gt;', esc_html( __('italic', 'team_data') ) ), sprintf('&lt;strong&gt;<strong>%s</strong>&lt;/strong&gt;', esc_html( __('strong', 'team_data') ) ), sprintf('&lt;u&gt;<u>%s</u>&lt;/u&gt;', esc_html( __('underline', 'team_data') ) ) ); ?></span>
					<span><?php printf( esc_html( __('The following tags and attributes are also supported: %1$s, %2$s, %3$s, %4$s', 'team_data') ), sprintf( '&lt;img src="" alt="" style="" class="" /&gt;' ), sprintf( '&lt;ul style="" /&gt;' ), sprintf( '&lt;ol style="" /&gt;' ), sprintf ( '&lt;li /&gt;' ) ); ?></span>
				</div>
			</form>
			<input id="team_data_send_email__send" class="team_data_button" type="button" value="<?php echo __('Send email', 'team_data'); ?>" onclick="team_data.api.email.sendEmail();" />
			<div id="team_data_send_email_dialog" title="<?php echo esc_attr(__('Sending Email...', 'team_data')); ?>">
				<div><?php echo esc_html( __('Your email is being sent...', 'team_data') ); ?></div>
				<div id="team_data_send_email_progress"></div>
				<div id="team_data_send_email_done" style="display: none; margin-top: 10px; text-align: center;">
					<div id="team_data_send_email_success" style="display: none;"><?php echo esc_html( __('Email sent successfully!', 'team_data' ) ); ?></div>
					<div id="team_data_send_email_error" style="display: none;">
						<div><?php echo esc_html( __('Error sending email!', 'team_data' ) ); ?></div>
						<div id="team_data_send_email_error_message"></div>
					</div>
					<div style="margin-top: 10px;"><button id="team_data_send_email_dialog_button" onclick="team_data.api.email.closeDialog();"><?php echo esc_html( __('OK', 'team_data') ); ?></button></div>
				</div>
			</div>
		</div>
	</div> <!-- end TeamData -->
	<?php
	}

	public function render_members() {
		if ( !current_user_can( 'team_data_manage_members' ) ) {
			return;
		}
		echo '<div class="team_data_content">';
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
</div> <!-- End team_data_content -->
<?php // end render_members()
	}

	public function render_admin_main() {
		if ( !current_user_can( 'team_data_manage_options' ) ) {
			return;
		}
		global $wpdb;
		echo '<div class="team_data_content">';
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
			</form>
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
				<div class="team_data_inline">
					<label for="team_data_level_edit__display_group" class="team_data_edit_label"><?php echo __('Display Group','team_data'); ?></label>
					<input id="team_data_level_edit__display_group" class="team_data_edit_input" name="level_display_group" type="number" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_level_edit__display_rank" class="team_data_edit_label"><?php echo __('Rank within Display Group','team_data'); ?></label>
					<input id="team_data_level_edit__display_rank" class="team_data_edit_input" name="level_display_rank" type="number" />
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
					<label for="team_data_list_edit__from_email" class="team_data_edit_label"><?php echo __('From email address','team_data'); ?></label>
					<input id="team_data_list_edit__from_email" class="team_data_edit_input" name="list_from_email" type="email" size="50" />
				</div>
				<div class="team_data_inline">
					<label for="team_data_list_edit__from_name" class="team_data_edit_label"><?php echo __('From Name','team_data'); ?></label>
					<input id="team_data_list_edit__from_name" class="team_data_edit_input" name="list_from_name" type="text" size="50" />
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
					<input class="team_data_select_image_button" type="button" value="<?php echo __('Select or Upload Image','team_data'); ?>" target-id="team_data_team_edit__logo_link" data-frame_title="<?php echo __('Choose or upload a team image','team_data'); ?>" data-button_text="<?php echo __('Select Image','team_data'); ?>" />
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
		<div class="section_title"><?php echo esc_html(__('General Options', 'team_data')); ?></div>
		<div>
			<div class="team_data_help"><?php echo esc_html(__('General configuration options for the TeamData plugin.','team_data')); ?></div>
			<form id="team_data_options_edit" class="team_data_admin_section">
				<table>
					<tr>
						<td><label for="team_data_options_edit__max_matches"><?php echo esc_html(__('Max Edit Matches','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__max_matches" class="team_data_edit_input" name="options_max_matches" type="number" size="5" value="<?php $max_matches = $this->get_option('max_matches'); echo esc_attr($max_matches); ?>" />
							<input id="team_data_options_edit__max_matches_orig" type="hidden" value="<?php echo esc_attr($max_matches); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_enabled"><?php echo esc_html(__('Enable Email','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_enabled" class="team_data_admin_checkbox" name="options_email_enabled" type="checkbox" <?php $email_enabled = ($this->get_option('email_enabled') == '1' ? true : false); if ($email_enabled) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__email_enabled_orig" type="checkbox" style="display: none;" <?php if ($email_enabled) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__debug_mode"><?php echo esc_html(__('Enable Debugging','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__debug_mode" class="team_data_admin_checkbox" name="options_debug_mode" type="checkbox" <?php $debug_mode = ($this->get_option('debug_mode') == '1' ? true : false); if ($debug_mode) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__debug_mode_orig" type="checkbox" style="display: none;" <?php if ($debug_mode) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
				</table>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_options_edit__save" class="team_data_button" type="button" value="<?php echo __('Save Changes', 'team_data'); ?>" onclick="team_data.api.options.save();" />
			</div>
		</div>
	</div>
<?php if ($email_enabled) { ?>
	<div>
		<div class="section_title"><?php echo esc_html(__('Email Options', 'team_data')); ?></div>
		<div>
			<div class="team_data_help"><?php
				echo esc_html(__('Email configuration options for the TeamData plugin.','team_data'));
				echo '<br />' . esc_html(__('NOTE: the SMTP Password is never returned in the UI. Save the password as -1 to clear the password.', 'team_data'));
			?>
			</div>
			<form id="team_data_options_edit__email" class="team_data_admin_section">
				<table>
					<tr>
						<td><label for="team_data_options_edit__email_from"><?php echo esc_html(__('Default From address','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_from" class="team_data_edit_input" name="options_email_from" type="email" size="50" value="<?php $email_from = $this->get_option('email_from'); $email_from = (empty($email_from) ? '' : $email_from); echo esc_attr($email_from); ?>" />
							<input id="team_data_options_edit__email_from_orig" type="hidden" value="<?php echo esc_attr($email_from); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_from_name"><?php echo esc_html(__('Default From display name','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_from_name" class="team_data_edit_input" name="options_email_from_name" type="text" size="50" value="<?php $email_from_name = $this->get_option('email_from_name'); $email_from_name = (empty($email_from_name) ? '' : $email_from_name); echo esc_attr($email_from_name); ?>" />
							<input id="team_data_options_edit__email_from_name_orig" type="hidden" value="<?php echo esc_attr($email_from_name); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_unsubscribe_address"><?php echo esc_html( __('Unsubscribe email address', 'team_data') ); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_unsubscribe_address" class="team_data_edit_input" name="options_email_unsubscribe_address" type="email" size="50" value="<?php $email_unsubscribe_address = $this->get_option('email_unsubscribe_address'); $email_unsubscribe_address = (empty($email_unsubscribe_address) ? '' : $email_unsubscribe_address); echo esc_attr($email_unsubscribe_address); ?>" />
							<input id="team_data_options_edit__email_unsubscribe_address_orig" type="hidden" value="<?php echo esc_attr($email_unsubscribe_address); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_prefix"><?php echo esc_html(__('Default subject prefix','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_prefix" class="team_data_edit_input" name="options_email_prefix" type="text" size="50" value="<?php $email_prefix = $this->get_option('email_prefix'); $email_prefix = (empty($email_prefix) ? '' : $email_prefix); echo esc_attr($email_prefix); ?>" />
							<input id="team_data_options_edit__email_prefix_orig" type="hidden" value="<?php echo esc_attr($email_prefix); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_summary_to"><?php echo esc_html(__('Summary recipient(s)','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_summary_to" class="team_data_edit_input" name="options_email_summary_to" type="email" size="50" value="<?php $email_summary_to = $this->get_option('email_summary_to'); $email_summary_to = (empty($email_summary_to) ? '' : $email_summary_to); echo esc_attr($email_summary_to); ?>" />
							<input id="team_data_options_edit__email_summary_to_orig" type="hidden" value="<?php echo esc_attr($email_summary_to); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_new_member_to"><?php echo esc_html(__('New registration recipient(s)','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_new_member_to" class="team_data_edit_input" name="options_email_new_member_to" type="email" size="50" value="<?php $email_new_member_to = $this->get_option('email_new_member_to'); $email_new_member_to = (empty($email_new_member_to) ? '' : $email_new_member_to); echo esc_attr($email_new_member_to); ?>" />
							<input id="team_data_options_edit__email_new_member_to_orig" type="hidden" value="<?php echo esc_attr($email_new_member_to); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_new_member_include_empty_fields"><?php echo esc_html(__('Include empty fields in new registration email','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_new_member_include_empty_fields" class="team_data_admin_checkbox" name="options_email_new_member_include_empty_fields" type="checkbox" <?php $email_new_member_include_empty_fields = ($this->get_option('email_new_member_include_empty_fields') == '1' ? true : false); if ($email_new_member_include_empty_fields) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__email_new_member_include_empty_fields_orig" type="checkbox" style="display: none;" <?php if ($email_new_member_include_empty_fields) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__email_timezone"><?php echo esc_html(__('Time zone to use for email [[SENDTIME]] shortcut','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__email_timezone" class="team_data_edit_input" name="options_email_timezone" type="text" size="50" value="<?php $email_timezone = $this->get_option('email_timezone'); $email_timezone = (empty($email_timezone) ? '' : $email_timezone); echo esc_attr($email_timezone); ?>" />
							<input id="team_data_options_edit__email_timezone_orig" type="hidden" value="<?php echo esc_attr($email_timezone); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__allow_all_member_mail"><?php echo esc_html(__('Allow All Member Emails','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__allow_all_member_mail" class="team_data_admin_checkbox" name="options_allow_all_member_mail" type="checkbox" <?php $allow_all_member_mail = ($this->get_option('allow_all_member_mail') == '1' ? true : false); if ($allow_all_member_mail) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__allow_all_member_mail_orig" type="checkbox" style="display: none;" <?php if ($allow_all_member_mail) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__use_smtp" ><?php echo esc_html(__('Use SMTP','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__use_smtp" class="team_data_admin_checkbox" name="options_use_smtp" type="checkbox" <?php $use_smtp = ($this->get_option('use_smtp') == '1' ? true : false); if ($use_smtp) { echo 'checked="checked"'; } ?> />
							<input id="team_data_options_edit__use_smtp_orig" type="checkbox" style="display: none;" <?php if ($use_smtp) { echo 'checked="checked"'; } ?> />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_server"><?php echo esc_html(__('SMTP Server','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_server" class="team_data_edit_input" name="options_smtp_server" type="text" size="50" value="<?php $smtp_server = $this->get_option('smtp_server'); $smtp_server = (empty($smtp_server) ? '' : $smtp_server); echo esc_attr($smtp_server); ?>" />
							<input id="team_data_options_edit__smtp_server_orig" type="hidden" value="<?php echo esc_attr($smtp_server); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_port"><?php echo esc_html(__('SMTP Port','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_port" class="team_data_edit_input" name="options_smtp_port" type="number" size="5" value="<?php $smtp_port = $this->get_option('smtp_port'); $smtp_port = (empty($smtp_port) ? '' : $smtp_port); echo esc_attr($smtp_port); ?>" />
							<input id="team_data_options_edit__smtp_port_orig" type="hidden" value="<?php echo esc_attr($smtp_port); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_conn_sec"><?php echo esc_html(__('SMTP Connection Security','team_data')); ?></label></td>
						<td>
							<?php $smtp_conn_sec = $this->get_option('smtp_conn_sec'); $smtp_conn_sec = ( empty($smtp_conn_sec) ? '' : $smtp_conn_sec); ?>
							<select id="team_data_options_edit__smtp_conn_sec" class="team_data_edit_input" name="options_smtp_conn_sec">
								<option value="" <?php echo ($smtp_conn_sec == '' ? 'selected="true"' : ''); ?>><?php echo esc_html(__('None', 'team_data')); ?></option>
								<option value="tls" <?php echo ($smtp_conn_sec == 'tls' ? 'selected="true"' : ''); ?>><?php echo esc_html(__('STARTTLS', 'team_data')); ?></option>
								<option value="ssl" <?php echo ($smtp_conn_sec == 'ssl' ? 'selected="true"' : ''); ?>><?php echo esc_html(__('SSL', 'team_data')); ?></option>
							</select>
							<input id="team_data_options_edit__smtp_conn_sec_orig" type="hidden" value="<?php echo esc_attr($smtp_conn_sec); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_user"><?php echo esc_html(__('SMTP User','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_user" class="team_data_edit_input" name="options_smtp_user" type="text" size="50" value="<?php $smtp_user = $this->get_option('smtp_user'); $smtp_user = (empty($smtp_user) ? '' : $smtp_user); echo esc_attr($smtp_user); ?>" />
							<input id="team_data_options_edit__smtp_user_orig" type="hidden" value="<?php echo esc_attr($smtp_user); ?>" />
						</td>
					</tr>
					<tr>
						<td><label for="team_data_options_edit__smtp_password"><?php echo esc_html(__('SMTP Password','team_data')); ?></label></td>
						<td>
							<input id="team_data_options_edit__smtp_password" class="team_data_edit_input" name="options_smtp_password" type="text" size="50" value="" />
							<input id="team_data_options_edit__smtp_password_orig" type="hidden" value="" />
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="team_data_options_edit__text_footer" class="team_data_edit_label"><?php echo esc_html(__('Footer text to include in plain text emails','team_data')); ?></label>
							<textarea id="team_data_options_edit__text_footer" class="team_data_edit_input widefat" name="options_text_footer" rows="4" cols="75"><?php $text_footer = $this->get_option('text_footer'); $text_footer = (empty($text_footer) ? '' : $text_footer); echo $text_footer; ?></textarea>
							<textarea id="team_data_options_edit__text_footer_orig" style="display: none;"><?php echo $text_footer; ?></textarea>
						</td>
					</tr>
					<tr>
						<td colspan="2">
							<label for="team_data_options_edit__html_template" class="team_data_edit_label"><?php echo esc_html(__('HTML template to use for outbound emails. Use [[CONTENT]] to specify location of text. Use [[SENDTIME]] to specify the send time.','team_data')); ?></label>
							<textarea id="team_data_options_edit__html_template" class="team_data_edit_input widefat" name="options_html_template" rows="20" cols="75"><?php $html_template = $this->get_option('html_template'); $html_template = (empty($html_template) ? '' : $html_template); echo $html_template; ?></textarea>
							<textarea id="team_data_options_edit__html_template_orig" style="display: none;"><?php echo $html_template; ?></textarea>
						</td>
					</tr>
				</table>
			</form>
			<div class="team_data_buttonDiv">
				<input id="team_data_options_edit__email__save" class="team_data_button" type="button" value="<?php echo esc_attr(__('Save Changes', 'team_data')); ?>" onclick="team_data.api.options.save();" />
			</div>
		</div>
	</div>
<?php } // end if $email_enabled ?>

</div>
</div> <!-- end team_data_content -->
<?php // end render_admin_main()
	}

	public function queue_admin_js($hook) {
		if (false === in_array($hook,$this->page_codes)) return;

		$jquery_ui_dir = get_bloginfo('url') . '/wp-includes/js/jquery/ui/';
		wp_enqueue_media();
		wp_enqueue_script('jquery');
		wp_enqueue_script('jquery-ui-core');
		wp_enqueue_script('jquery-ui-datepicker', $jquery_ui_dir . 'jquery.ui.datepicker.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-autocomplete', $jquery_ui_dir . 'jquery.ui.autocomplete.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-accordion', $jquery_ui_dir . 'jquery.ui.accordion.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-button', $jquery_ui_dir . 'jquery.ui.button.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-dialog', $jquery_ui_dir . 'jquery.ui.dialog.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('jquery-ui-progressbar', $jquery_ui_dir . 'jquery.ui.progressbar.min.js', array('jquery', 'jquery-ui-core') );
		wp_enqueue_script('team-data', plugins_url('js/team_data.js', __FILE__ ));
		wp_enqueue_style('jquery.ui.theme', plugins_url('css/redmond/jquery-ui-1.10.4.custom.css', __FILE__ ));
		wp_enqueue_style('team-data-css', plugins_url('css/team_data.css',__FILE__));
		wp_localize_script('team-data', 'team_data_ajax', array( 'nonce' => wp_create_nonce('team_data_nonce') ) );

	}

	public function render_loc_js($hook) {
		//$this->debug('loc_hook=' . $hook);
	}

	public function render_members_js() {
		if ( !current_user_can( 'team_data_manage_members' ) ) {
			return;
		}
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
<?php // end render_members_js()
	}

	public function render_main_js() {
		if ( current_user_can( 'team_data_manage_options' ) ) {
			$this->render_footer_js('main');
		}
	}

	public function render_matches_js() {
		if ( current_user_can( 'team_data_manage_matches' ) ) {
			$this->render_footer_js('matches');
		}
	}
	
	public function render_email_js() {
		if ( current_user_can( 'team_data_send_email' ) ) {
			$this->render_footer_js('email');
		}
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
?>
var team_data_image_frame;
jQuery('.team_data_select_image_button').live('click', function( event ){
	event.preventDefault();
 	
	if ( team_data_image_frame ) {
		team_data_image_frame.open();
		team_data_image_frame.team_data_control = this;
		return;
	}
 
	team_data_image_frame = wp.media.frames.file_frame = wp.media({
		title: jQuery( this ).data( 'frame_title' ),
		button: {
			text: jQuery( this ).data( 'button_text' ),
		},
		multiple: false
	});
 
	team_data_image_frame.on( 'select', function() {
		var attachment = team_data_image_frame.state().get('selection').first().toJSON();

		if (attachment && team_data_image_frame.team_data_control) {
			var target_id = team_data_image_frame.team_data_control.getAttribute('target-id');
			if (target_id) {
				var target_control = document.getElementById(target_id);
				if (target_control) {
					target_control.value = attachment.url;
				}
			}
		}
	});
 
	team_data_image_frame.open();
	team_data_image_frame.team_data_control = this;
});
<?php		
		}
		else if ($page == 'email') {
?>
jQuery('#team_data_send_email_dialog').dialog( { dialogClass: "no-close", autoOpen: false, closeOnEscape: false, draggable: false, modal: true, resizable: false } );

jQuery('#team_data_send_email_dialog_button').button();
<?php
		}
		echo '} );' . $newline;
		echo '</script>' . $newline;
	}

	private function render_match_list() {
		global $wpdb;
		$tables = $this->tables;

		echo '<h2>' . esc_html(__('Available Fixtures', 'team_data')) . '</h2>';

		$season_id = '';
		if ( isset($_GET['seasonID'] ) ) {
			$season_id = intval( $_GET['seasonID'] );
		}
		else {
			$season_id = $this->get_option('current_season');
		}

		$helper = new TeamDataAjax();
		$seasons = $helper->get_all_seasons();
		$seasons = array_reverse($seasons,true);

		$count_sql = "SELECT COUNT(*) AS matchCount FROM $tables->match";
		if ($season_id > 0) {
			$count_sql = $wpdb->prepare($count_sql . ' WHERE season_id = %d', $season_id);
		}
		$matchCount = $wpdb->get_var($count_sql);

		$pageNum = 0;
		$pageSize = 20;
		if ($matchCount > 0) {
			$maxPage = ceil($matchCount/$pageSize) - 1;
			if (isset($_GET['fixturePage'])) {
				$pageNum = intval( $_GET['fixturePage'] );
			}
			$limit = ' LIMIT ' . ($pageNum * $pageSize) . ', ' . $pageSize;
			echo '<script type="text/javascript">';
			echo "team_data.paging.pageSize = $pageSize;";
			echo "team_data.paging.resultCount = $matchCount;";
			echo '</script>';

			$season_where = ($season_id === 0 ? '' : "AND m.season_id = $season_id");
			$match_query = "SELECT m.id, m.season_id, CONCAT(s.year,' ',s.season) AS season, m.date, DATE_FORMAT(m.date,'%M %d, %Y') AS pretty_date, TIME_FORMAT(m.time,'%h:%i %p') AS time, m.venue_id, v.name AS venue_name, m.opposition_id, m.opposition_name, m.tourney_name, m.level_id, l.name AS level_name, m.our_score, m.opposition_score, m.result, m.comment, m.is_league, m.is_postseason
				FROM 
					( SELECT match.id, match.season_id, match.date, match.time, match.venue_id, match.opposition_id, match.tourney_name, match.level_id, match.our_score, match.opposition_score, match.result, match.comment, match.is_league, match.is_postseason, IF(match.opposition_id IS NULL, '', team.name) AS opposition_name
						FROM $tables->match AS `match` LEFT OUTER JOIN $tables->team AS team ON match.opposition_id = team.id) m,
						$tables->venue v, $tables->level l, $tables->season s
				WHERE m.venue_id = v.id AND m.level_id = l.id AND m.season_id = s.id $season_where
				ORDER BY m.date DESC, m.time ASC" . $limit;
			$this->debug('$match_query = ' . $match_query);

			$matches = $wpdb->get_results($match_query);
			$pageCount = count($matches);
			if ($pageCount > 0) {
				$back_disabled = ($pageNum == 0 ? 'disabled="disabled" ' : '');
				$next_disabled = ($pageNum == $maxPage ? 'disabled="disabled" ' : '');
				echo '<input class="team_data_button" id="team_data_start" type="button" value="' . esc_attr(__('First Page', 'team_data')) . '" ' . $back_disabled . 'onclick="team_data.fn.changePage(false,true);" />';
				echo '<input class="team_data_button" id="team_data_prev" type="button" value="' . esc_attr(__('Previous', 'team_data')) . '" ' . $back_disabled . 'onclick="team_data.fn.changePage(false,false);" />';
				echo '<input class="team_data_button" id="team_data_next" type="button" value="' . esc_attr(__('Next', 'team_data')) . '" ' . $next_disabled . 'onclick="team_data.fn.changePage(true,false);" />';
				echo '<input class="team_data_button" id="team_data_end" type="button" value="' . esc_attr(__('Last Page', 'team_data')) . '" ' . $next_disabled . 'onclick="team_data.fn.changePage(true,true);" />';
			}
			else {
				$matchCount = 0;
			}
		}
		// render season selection irrespective of whether we have any matches
		$all_selected = ($season_id === 0 ? 'selected="selected"' : '');
		?><div class="team_data_select">
			<label for="team_data_season_select"><?php echo esc_html(__('Season', 'team_data')); ?></label>
			<select id="team_data_season_select" class="team_data_select" onchange="team_data.fn.selectSeason(this.value);">
				<option value="0" <?php echo $all_selected; ?>><?php echo esc_html(__('All','team_data')); ?></option>
		<?php
		foreach ($seasons as $season) {
			$selected = ($season_id == $season['id'] ? ' selected="selected"' : '');
			echo '<option value="' . $season['id'] . '"' . $selected . '>' . esc_html($season['name']) . '</option>' . "\n";
		}
		?></select>
		</div>
		<?php
		if ($matchCount < 1) {
			echo esc_html(__('No results found', 'team_data'));
		}
		else {
			echo '<div id="team_data_page_info">';
			printf(esc_html(__('Displaying page %1$s of %2$s')), $pageNum + 1, ceil($matchCount/$pageSize));
			echo '</div>';
			echo '<table class="team_data_table team_data_matches">';
			echo '<tr>';
			echo '<th>' . esc_html(__('Season', 'team_data')) . '</th>';
			echo '<th>' . esc_html(__('Date', 'team_data')) . '</th>';
			echo '<th>' . esc_html(__('Time', 'team_data')) . '</th>';
			echo '<th>' . esc_html(__('Opponent', 'team_data')) . '</th>';
			echo '<th>' . esc_html(__('Level', 'team_data')) . '</th>';
			echo '<th>' . esc_html(__('Venue', 'team_data')) . '</th>';
			echo '<th>' . esc_html(__('League','team_data')) . '/<br/>' . __('Playoffs', 'team_data') . '</th>';
			echo '<th>' . esc_html(__('Score/Result', 'team_data')) . '</th>';
			echo '<th>&nbsp;</th>';
			echo '</tr>';
			foreach ($matches as $match) {
				echo '<tr id="team_data_match_row_' . $match->id . '">';
				echo '<td>' . esc_html($match->season) . '</td>';
				echo '<td>' . esc_html($match->pretty_date) . '</td>';
				echo '<td>' . esc_html($match->time) . '</td>';
				echo '<td>' . esc_html(( $match->opposition_name != '' ? $match->opposition_name : $match->tourney_name )) . ($match->comment != '' ? ' (' . esc_html($match->comment) . ')' : '') . '</td>';
				echo '<td>' . esc_html($match->level_name) . '</td>';
				echo '<td>' . esc_html($match->venue_name) . '</td>';
				echo '<td>' . ($match->is_league == '1' ? 'L' : ($match->is_postseason == '1' ? 'P' : '&nbsp;')) . '</td>';
				
				$match_result = $this->get_match_result_string($match->our_score,$match->opposition_score,$match->result);
				echo '<td>';
					echo '<div id="team_data_edit__score_display_' . $match->id . '">';
						echo (($match_result == '') ? '&nbsp;-&nbsp;' : esc_html($match_result));
					echo '</div>';
					echo '<form id="team_data_edit__score_edit_' . $match->id . '" style="display: none;">';
						echo '<input id="team_data_edit__score_edit_' . $match->id . '_our" class="team_data_input" placeholder="' . esc_attr(__('ours','team_data')) . '" name="score_our_score" type="text" size="3" value="' . (($match->our_score == null) || ($match->our_score == '') ? '' : esc_attr($match->our_score)) . '"/>';
						echo '&nbsp;';
						echo '<input id="team_data_edit__score_edit_' . $match->id . '_opposition" class="team_data_input" placeholder="' . esc_attr(__('theirs','team_data')) . '" name="score_opposition_score" type="text" size="3" value="' . (($match->opposition_score == null) || ($match->opposition_score == '') ? '' : esc_attr($match->opposition_score)) . '"/>';
						echo '<input id="team_data_edit_score_edit_' . $match->id . '_result" class="team_data_input" placeholder="' . esc_attr(__('result','team_data')) . '" name="score_result" type="text" size="1" value="' . (($match->result == null) || ($match->result == '') ? '' : esc_attr($match->result)) . '"/>';
					echo '</form>';
				echo '</td>';
				echo '<td>';
					echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_score_' . $match->id . '" onclick="team_data.api.match.toggleScoreControls(' . $match->id . ',true);" value="' . esc_attr(__('Edit Score', 'team_data')) . '" />';
					echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_score_save_' . $match->id . '" style="display: none;" onclick="team_data.api.match.editScore(' . $match->id . ');" value="' . esc_attr(__('Save Score', 'team_data')) . '" />';
					echo '&nbsp;&nbsp;';
					echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_' . $match->id . '" onclick="team_data.api.match.editMatch(' . $match->id . ');" value="' . esc_attr(__('Edit', 'team_data')) . '" />';
				echo '</td>';
				echo '</tr>';
			}
			echo '</table>';
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
			echo '<div style="font-weight: bold; font-size: 1.2em; padding: 5px;">' . esc_attr(__('Update an existing match or result:','team_data')) . '</div>';
			echo '<form id="team_data_match_edit">';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__id" class="team_data_edit_label">' . esc_attr(__('ID','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__id" class="team_data_edit_input" name="match_id" type="text" size="5" readonly="readonly" disabled="disabled" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__season" class="team_data_edit_label">' . esc_attr(__('Season','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__season" class="team_data_edit_input team_data_season" name="match_season" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__date" class="team_data_edit_label">' . esc_attr(__('Date','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__date" class="team_data_edit_input team_data_date" name="match_date" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__time" class="team_data_edit_label">' . esc_attr(__('Time','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__time" class="team_data_edit_input team_data_time" name="match_time" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__opposition" class="team_data_edit_label">' . esc_attr(__('Opponent','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__opposition" class="team_data_edit_input team_data_team" name="match_opposition" type="text" size="50" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__tourney_name" class="team_data_edit_label">' . esc_html(__('Tournament/Competition Name','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__tourney_name" class="team_data_edit_input" name="match_tourney_name" type="text" size="20" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__level" class="team_data_edit_label">' . esc_attr(__('Level','team_data')) . '</label>';
					echo '<select id="team_data_match_edit__level" name="match_level">';
					foreach(array_keys($levels) as $level_id) {
						echo '<option value="' . esc_attr($level_id) . '">' . esc_html($levels[$level_id]) . '</option>';
					}
					echo '</select>';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__venue" class="team_data_edit_label">' . esc_html(__('Venue','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__venue" class="team_data_edit_input team_data_venue" name="match_venue" type="text" size="50" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__our_score" class="team_data_edit_label">' . esc_html(__('Us','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__our_score" class="team_data_edit_input" name="match_our_score" type="text" size="3" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__opposition_score" class="team_data_edit_label">' . esc_html(__('Them','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__opposition_score" class="team_data_edit_input" name="match_opposition_score" type="text" size="3" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__result" class="team_data_edit_label">' . esc_html(__('Result','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__result" class="team_data_edit_input" name="match_result" type="text" size="10" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__is_league" class="team_data_edit_label">' . esc_html(__('League?','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__is_league" class="team_data_admin_checkbox" name="match_is_league" type="checkbox" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__is_postseason" class="team_data_edit_label">' . esc_html(__('Playoffs?','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__is_postseason" class="team_data_admin_checkbox" name="match_is_postseason" type="checkbox" />';
				echo '</div>';
				echo '<div class="team_data_inline">';
					echo '<label for="team_data_match_edit__comment" class="team_data_edit_label">' . esc_html(__('Comment','team_data')) . '</label>';
					echo '<input id="team_data_match_edit__comment" class="team_data_edit_input" name="match_comment" type="text" size="20" />';
				echo '</div>';
			echo '</form>';
			echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_save" onclick="team_data.api.match.saveMatch();" value="' . esc_attr(__('Save', 'team_data')) . '" />';
			echo '<input class="team_data_edit_button" type="button" id="team_data_edit_match_delete" onclick="team_data.api.match.deleteMatch();" value="' . esc_attr(__('Delete', 'team_data')) . '" />';
		echo '</div>';
	}
}
?>