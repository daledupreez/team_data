<?php

class TeamData_LastMatchWidget extends WP_Widget {

	/**
	 * Create and register widget
	 */
	public function __construct() {
		parent::__construct(
			'TeamData_LastMatchWidget',
			'Last Match Widget',
			array( 'description' => __('Widget to display last match', 'TeamData') )
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
				echo '<div class="match_widget_pair match_widget_us">'; // open our pair
				echo '<div class="match_widget_team match_widget_us">' . $team_name . '</div>';
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
				echo '<div class="match_widget_score match_widget_them">' . $match['opposition_score'] . '</div>';
				echo '</div>'; // close their pair
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
			echo '<div class="match_widget_empty">' . __( 'No match history', 'TeamData' ) . '</div>';
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
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'team_name' => '', 'more_info' => '', '$level_id' => '' ) );
		$title = strip_tags( $instance['title'] );
		$team_name = strip_tags( $instance['team_name'] );
		$level_id = strip_tags( $instance['level_id'] );
		if ($level_id == '') $level_id = -1;
		$more_info = esc_textarea( $instance['more_info'] );

		echo '<p>';
		echo '<label for="' . $this->get_field_id('title') . '">' . __('Title:', 'TeamData') . '</label>' . "\n";
		echo '<input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('title') . '" type="text" value="' . esc_attr($title) . '" />' . "\n";
		echo '</p>';
		echo '<p>';
		echo '<label for="' . $this->get_field_id('team_name') . '">' . __('Your team name:', 'TeamData') . '</label>' . "\n";
		echo '<input class="widefat" id="' . $this->get_field_id('team_name') . '" name="' . $this->get_field_name('team_name') . '" type="text" value="' . esc_attr($team_name) . '" />' . "\n";
		echo '</p>';
		echo '<p>';
		if ( class_exists('TeamDataTables') ) {
			$tables = new TeamDataTables();
			$sql = "SELECT id, name FROM $tables->level";
			$levels = $wpdb->get_results($sql, ARRAY_A);
			
			echo '<label for="' . $this->get_field_id('level_id') . '">' . __('Level:', 'TeamData') . '</label>' . "\n";
			echo '<select class="widefat" id="' . $this->get_field_id('level_id') . '" name="' . $this->get_field_name('level_id') . '" type="text" value="' . esc_attr($level_id) . '">' . "\n";
			$selected = (-1 == $level_id ? ' selected="1"' : '');
			echo '<option value="-1"' . $selected . '>' . esc_html( __('Any level', 'TeamData') ) . '</option>';
			foreach ($levels as $level_key => $level) {
				$selected = ($level['id'] == $level_id ? ' selected="1"' : '');
				echo '<option value="' . esc_attr( $level['id'] ) . '"' . $selected . '>' . esc_html( $level['name'] ) . '</option>';
			}
			echo '</select>';
			echo '</p>';
		}
		echo '<label for="' . $this->get_field_id('more_info') . '">' . __('Post-result content:', 'TeamData') . '</label>' . "\n";
		echo '<textarea class="widefat" rows="5" cols="20" id="' . $this->get_field_id('more_info') . '" name="' . $this->get_field_name('more_info') . '">';
		echo $more_info;
		echo '</textarea>';
	}

	/**
	 * Helper function to get the data for the last match played for the level specified in $level_id.
	 * 
	 * @param integer $level_id ID of level we want to get the match for.
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
				'(venue.is_home = 1) As is_home',
				"IF(venue.abbreviation = '', venue.name, venue.abbreviation) As venue",
				'venue.info_link',
				'venue.directions_link'
			);
			$from = array(
				"( SELECT m.date, m.time, m.tourney_name, m.venue_id, m.level_id, m.result, m.our_score, m.opposition_score, IF(m.opposition_id IS NULL, '', IF(t.abbreviation = '', t.name, t.abbreviation)) AS team_name FROM $tables->match m LEFT OUTER JOIN $tables->team t ON m.opposition_id = t.id ) `match`",
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
}
?>