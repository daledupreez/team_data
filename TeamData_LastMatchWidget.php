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
		$get_logos = apply_filters( 'widget_text', ( empty($instance['get_logos']) ) ? '' : $instance['get_logos'], $instance );
		$get_logos = ($get_logos == '1');
		$logo_style = apply_filters( 'widget_text', ( empty($instance['logo_style']) ) ? '' : $instance['logo_style'], $instance );

		$level_id = -1;
		if (isset($instance['level_id']) && (intval($instance['level_id']) > 0)) {
			$level_id = intval($instance['level_id']);
		}

		echo $before_widget;
		if ( !empty( $title ) ) echo $before_title . $title . $after_title;
		echo '<div class="match_widget">';

		$match = TeamData_WidgetUtils::get_last_match($level_id, $get_logos);
		if ($match) {
			echo '<div class="match_widget_date">' . esc_html($match['_date']) . '</div>';
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
				echo '<div class="match_widget_tournament">' . esc_html($match['tourney_name']) . '</div>';
			}
			echo '<div class="match_widget_result">' . esc_html($result) . '</div>';
			if ($match['tourney_name'] === '') {
				$our_logo = '';
				if ($get_logos) $our_logo = TeamData_WidgetUtils::get_our_logo_link();
				$have_logo = ( !empty($our_logo) ) || ( !empty($match['team_logo']) );
?>
				<div class="match_widget_result_details <?php echo ($have_logo ? 'match_widget_with_logos' : ''); ?>">
					<div class="match_widget_pair match_widget_us">
						<div class="match_widget_team match_widget_us"><?php echo esc_html($team_name); ?></div>
<?php
					if ( $have_logo ) {
						echo '<div class="match_widget_logo match_widget_us' . ( empty($our_logo) ? ' match_widget_logo_empty' : '' ) . '">';
						if ( !empty($our_logo) ) echo '<img src="' . esc_attr($our_logo) . '"' . (!empty($logo_style) ? ' style="' . esc_attr($logo_style) . '"' : '') . ' />';
						echo '</div>';
					}
?>
						<div class="match_widget_score match_widget_us"><?php echo esc_html($match['our_score']); ?></div>
					</div>
					<div class="match_widget_vs"><?php echo ( $match['is_home'] ? 'vs' : '@' ); ?></div>
					<div class="match_widget_pair match_widget_them">
						<div class="match_widget_team match_widget_them">
<?php
							if ($match['info_link'] != '') {
								echo '<a class="match_widget_team_info" href="' . esc_attr($match['info_link']) . '">';
							}
							echo esc_html($match['team']);
							if ($match['info_link'] != '') {
								echo '</a>';
							}
?>
						</div>
<?php
						if ( $have_logo ) {
							echo '<div class="match_widget_logo match_widget_them' . ( empty($match['team_logo']) ? ' match_widget_logo_empty' : '' ) . '">';
							if ( !empty($match['team_logo']) ) echo '<img src="' . esc_attr($match['team_logo']) . '"' . (!empty($logo_style) ? ' style="' . esc_attr($logo_style) . '"' : '') . ' />';
							echo '</div>';
						}
?>
						<div class="match_widget_score match_widget_them"><?php echo esc_html($match['opposition_score']); ?></div>
					</div>
				</div>
<?php
			}
			echo '<div class="match_widget_venue">';
			if ( !empty($match['directions_link']) ) {
				echo '<a class="match_widget_directions" href="' . esc_attr($match['directions_link']) . '">';
			}
			echo esc_html($match['venue']);
			if ( !empty($match['directions_link']) ) {
				echo '</a>';
			}
			echo '</div>';
		}
		else {
			echo '<div class="match_widget_empty">' . esc_html(__( 'No match history', 'team_data' )) . '</div>';
		}
		if ( !empty($more_info) ) {
			echo '<div class="match_widget_more_info">' . $more_info . '</div>';
		}
		echo '</div>';
		echo $after_widget;
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['team_name'] = strip_tags( $new_instance['team_name'] );
		$instance['level_id'] = strip_tags( $new_instance['level_id'] );
		$instance['logo_style'] = strip_tags( $new_instance['logo_style'] );
		$instance['get_logos'] = strip_tags( $new_instance['get_logos'] );
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
		$instance = wp_parse_args( (array) $instance, array( 'title' => '', 'team_name' => '', 'more_info' => '', 'level_id' => '', 'logo_style' => '', 'get_logos' => 1 ) );
		$title = strip_tags( $instance['title'] );
		$team_name = strip_tags( $instance['team_name'] );
		$level_id = strip_tags( $instance['level_id'] );
		if ($level_id == '') $level_id = -1;
		$more_info = esc_textarea( $instance['more_info'] );
		$logo_style = strip_tags( $instance['logo_style'] );
		$get_logos = strip_tags( $instance['get_logos'] );

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
		echo '<label for="' . $this->get_field_id('get_logos_checkbox') . '">' . __('Display logos:', 'team_data') . '</label>' . "\n";
		echo '<input type="checkbox" id="' . $this->get_field_id('get_logos_checkbox') . '" ' . ($get_logos ? ' checked="true"' : '') . ' onchange="document.getElementById(\'' . $this->get_field_id('get_logos') . '\').value = ( this.checked ? 1 : 0 );" />';
		echo '<input type="hidden" id="' . $this->get_field_id('get_logos') . '" name="' . $this->get_field_name('get_logos') . '" /><br />';
		echo '<label for="' . $this->get_field_id('logo_style') . '">' . __('Logo style:','team_data') . '</label>' . "\n";
		echo '<textarea class="widefat" rows="3" cols="20" id="' . $this->get_field_id('logo_style') . '" name="' . $this->get_field_name('logo_style') . '">';
		echo $logo_style;
		echo '</textarea>';
	}

}
?>