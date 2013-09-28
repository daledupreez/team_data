<?php

class TeamData_LastMatchWidget extends WP_Widget {

	/**
	 * Create and register widget
	 */
	public function __construct() {
		parent::__construct(
			'TeamData_LastMatchWidget',
			'Last Match Widget',
			array( 'description' => __('Widget to display last match', 'team_data') )
		);
	}

	/**
	 *
	 */
	public function widget( $args, $instance ) {
		extract($args);
		$title = apply_filters( 'widget_title', ( empty($instance['title']) ) ? '' : $instance['title'], $instance, $this->id_base );
		$team_name = apply_filters( 'widget_text', ( empty($instance['team_name']) ) ? '' : $instance['team_name'], $instance );
		$more_info = apply_filters( 'widget_text', ( empty($instance['more_info']) ) ? '' : $instance['more_info'], $instance );
		$logo_style = apply_filters( 'widget_text', ( empty($instance['logo_style']) ) ? '' : $instance['logo_style'], $instance );

		$level_id = -1;
		if (isset($instance['level_id']) && (intval($instance['level_id']) > 0)) {
			$level_id = intval($instance['level_id']);
		}

		echo $before_widget;
		if ( !empty( $title ) ) echo $before_title . $title . $after_title;
		echo '<div class="match_widget">';

		$match = $this->get_last_match($level_id);
		if ($match) {
			echo '<div class="match_widget_date">' . $match['_date'] . '</div>';
			$result = 'W';
			if (($match['result'] != null) && ($match['result'] != '')) {
				$result = $match['result'];
			}
			else {
				$our_score = intval($match['our_score']);
				$their_score = intval($match['opposition_score']);
				if ($our_score < $their_score) {
					$result = 'L';
				}
				else if ($our_score == $their_score) {
					$result = 'D';
				}
				// otherwise we can leave as W
			}
			if ($match['tourney_name'] !== '') {
				echo '<div class="match_widget_tournament">' . $match['tourney_name'] . '</div>';
			}
			echo '<div class="match_widget_result">' . $result . '</div>';
			if ($match['tourney_name'] === '') {
				echo '<div class="match_widget_result_details">'; // open details wrapper
				echo '<div class="match_widget_pair match_widget_us">'; // open our pair
				echo '<div class="match_widget_team match_widget_us">' . $team_name . '</div>';
				$our_logo = $this->get_our_logo_link();
				$have_logo = ( !empty($our_logo) ) || ( !empty($match['team_logo']) );
				if ( $have_logo ) {
					echo '<div class="match_widget_logo match_widget_us">';
					if ( !empty($our_logo) ) echo '<img src="' . esc_attr($our_logo) . '"' . (!empty($logo_style) ? ' style="' . esc_attr($logo_style) . '"' : '') . ' />';
					echo '</div>';
				}
				echo '<div class="match_widget_score match_widget_us">' . $match['our_score'] . '</div>';
				echo '</div>'; // close our pair
				echo '<div class="match_widget_vs">' . ($match['is_home'] ? 'vs' : '@') . '</div>';
				echo '<div class="match_widget_pair match_widget_them">'; // open their pair
				echo '<div class="match_widget_team match_widget_them">';
				if ($match['info_link'] != '') {
					echo '<a class="match_widget_team_info" href="' . $match['info_link'] . '">';
				}
				echo $match['team'];
				if ($match['info_link'] != '') {
					echo '</a>';
				}
				echo '</div>';
				if ( $have_logo ) {
					echo '<div class="match_widget_logo match_widget_them">';
					if ( !empty($match['team_logo']) ) echo '<img src="' . $match['team_logo'] . '"' . (!empty($logo_style) ? ' style="' . esc_attr($logo_style) . '"' : '') . ' />';
					echo '</div>';
				}
				echo '<div class="match_widget_score match_widget_them">' . $match['opposition_score'] . '</div>';
				echo '</div>'; // close their pair
				echo '</div>'; // close details wrapper
			}
			echo '<div class="match_widget_venue">';
			if ($match['directions_link'] != '') {
				echo '<a class="match_widget_directions" href="' . $match['directions_link'] . '">';
			}
			echo $match['venue'];
			if ($match['directions_link'] != '') {
				echo '</a>';
			}
			echo '</div>';
		}
		else {
			echo '<div class="match_widget_empty">' . __( 'No match history', 'team_data' ) . '</div>';
		}
		echo '<div class="match_widget_more_info">' . $more_info . '</div>';
		echo '</div>';
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['team_name'] = strip_tags( $new_instance['team_name'] );
		$instance['level_id'] = strip_tags( $new_instance['level_id'] );
		$instance['logo_style'] = strip_tags( $new_instance['logo_style'] );
		if ( current_user_can('unfiltered_html') ) {
			$instance['more_info'] = $new_instance['more_info'];
		}
		else {
			 // Code cribbed from WP_Widget_Text. Comment there is: wp_filter_post_kses() expects slashed
			$instance['more_info'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['more_info']) ) );
		}
		return $instance;
	}

	public function form( $instance ) {
		global $wpdb;
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'team_name' => '', 'more_info' => '', 'level_id' => '', 'logo_style' => '' ) );
		$title = strip_tags( $instance['title'] );
		$team_name = strip_tags( $instance['team_name'] );
		$level_id = strip_tags( $instance['level_id'] );
		if ($level_id == '') $level_id = -1;
		$more_info = esc_textarea( $instance['more_info'] );
		$logo_style = strip_tags( $instance['logo_style'] );

		echo '<p>';
		echo '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'team_data') . '</label>' . "\n";
		echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title) . '" />' . "\n";
		echo '</p>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id('team_name') . '">' . __('Your team name:', 'team_data') . '</label>' . "\n";
		echo '<input class="widefat" id="' . $this->get_field_id('team_name') . '" name="' . $this->get_field_name('team_name') . '" type="text" value="' . esc_attr($team_name) . '" />' . "\n";
		echo '</p>';
		echo '<p>';
		if ( class_exists('TeamDataTables') ) {
			$tables = new TeamDataTables();
			$sql = "SELECT id, name FROM $tables->level";
			$levels = $wpdb->get_results($sql, ARRAY_A);
			
			echo '<label for="' . $this->get_field_id('level_id') . '">' . __('Level:', 'team_data') . '</label>' . "\n";
			echo '<select class="widefat" id="' . $this->get_field_id('level_id') . '" name="' . $this->get_field_name('level_id') . '" type="text" value="' . esc_attr($level_id) . '">' . "\n";
			$selected = (-1 == $level_id ? ' selected="1"' : '');
			echo '<option value="-1"' . $selected . '>' . esc_html( __('Any level', 'team_data') ) . '</option>';
			foreach ($levels as $level_key => $level) {
				$selected = ($level['id'] == $level_id ? ' selected="1"' : '');
				echo '<option value="' . esc_attr( $level['id'] ) . '"' . $selected . '>' . esc_html( $level['name'] ) . '</option>';
			}
			echo '</select>';
			echo '</p>';
		}
		echo '<label for="' . $this->get_field_id('more_info') . '">' . __('Post-result content:', 'team_data') . '</label>' . "\n";
		echo '<textarea class="widefat" rows="5" cols="20" id="' . $this->get_field_id('more_info') . '" name="' . $this->get_field_name('more_info') . '">';
		echo $more_info;
		echo '</textarea>';
		echo '<label for="' . $this->get_field_id('logo_style') . '">' . __('Logo style:','team_data') . '</label>' . "\n";
		echo '<textarea class="widefat" rows="3" cols="20" id="' . $this->get_field_id('logo_style') . '" name="' . $this->get_field_name('logo_style') . '">';
		echo $logo_style;
		echo '</textarea>';
	}

	/**
	 * Helper function to get the data for the last match played for the level specified in $level_id.
	 * 
	 * @param integer $level_id ID of level we want to get the match for.
	 * @return array match data
	 */
	private function get_last_match($level_id = -1) {
		global $wpdb;
		
		$match = null;
		if ( class_exists('TeamDataTables') ) {
			$tables = new TeamDataTables();

			$select = array(
				"DATE_FORMAT(match.date,'%M %D %Y') AS `_date`",
				"DATE_FORMAT(match.time,'%l:%i %p') AS `_time`",
				'match.result',
				'match.our_score',
				'match.opposition_score',
				'match.tourney_name',
				"IF(level.abbreviation = '', level.name, level.abbreviation) As level",
				"match.team_name AS team",
				"match.team_logo",
				'(venue.is_home = 1) As is_home',
				"IF(venue.abbreviation = '', venue.name, venue.abbreviation) As venue",
				'venue.info_link',
				'venue.directions_link'
			);
			$from = array(
				"( SELECT m.date, m.time, m.tourney_name, m.venue_id, m.level_id, m.result, m.our_score, m.opposition_score, IF(m.opposition_id IS NULL, '', IF(t.abbreviation = '', t.name, t.abbreviation)) AS team_name, IF(m.opposition_id IS NULL, '', t.logo_link) AS team_logo FROM $tables->match m LEFT OUTER JOIN $tables->team t ON m.opposition_id = t.id ) `match`",
				"$tables->level As level",
				"$tables->venue As venue",
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
		}
		return $match;
	}

	/**
	 * Helper function to get the logo link for our team.
	 * 
	 * @return string The link for our team.
	 */
	private function get_our_logo_link() {
		global $wpdb;
		
		$logo_link = '';
		if ( class_exists('TeamDataTables') && class_exists('TeamDataBase') ) {
			$tables = new TeamDataTables();
			$team_data = new TeamDataBase();
			
			$our_team = $team_data->get_option('our_team');
			if (!empty($our_team)) {
				$sql = $wpdb->prepare("SELECT logo_link FROM $tables->team WHERE id = %d", $our_team);
				$logo_link = $wpdb->get_var($sql);
			}
		}
		return $logo_link;
	}
}
?>