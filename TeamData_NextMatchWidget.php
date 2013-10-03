<?php

class TeamData_NextMatchWidget extends WP_Widget {

	/**
	 * Create and register widget
	 */
	public function __construct() {
		parent::__construct(
			'TeamData_NextMatchWidget',
			'Next Match Widget',
			array( 'description' => __('Widget to display next match', 'team_data') )
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
		$pos_values = array( 'before', 'after', 'none' );
		$date_pos = apply_filters( 'widget_text', ( empty($instance['date_pos']) ) ? 'before' : $instance['date_pos'], $instance );
		if ( !in_array($date_pos, $pos_values) ) {
			$date_pos = 'before';
		}
		$time_pos = apply_filters( 'widget_text', ( empty($instance['time_pos']) ) ? 'before' : $instance['time_pos'], $instance );
		if ( !in_array($time_pos, $pos_values) ) {
			$time_pos = 'before';
		}
		$get_logos = apply_filters( 'widget_text', ( empty($instance['get_logos']) ) ? '0' : $instance['get_logos'], $instance );
		$get_logos = ($get_logos == '1');
		$logo_style = apply_filters( 'widget_text', ( empty($instance['logo_style']) ) ? '' : $instance['logo_style'], $instance );

		$level_id = -1;
		if (isset($instance['level_id']) && (intval($instance['level_id']) > 0)) {
			$level_id = intval($instance['level_id']);
		}

		echo $before_widget;
		if ( !empty( $title ) ) echo $before_title . $title . $after_title;
		echo '<div class="match_widget">';

		$match = TeamData_WidgetUtils::get_next_match($level_id, $get_logos);
		if ($match) {
			if ( $date_pos == 'before' ) {
				echo '<div class="match_widget_date">' . esc_html($match['_date']) . '</div>';
			}
			if ( $time_pos == 'before' ) {
				echo '<div class="match_widget_time">' . esc_html($match['_time']) . '</div>';
			}
			if ($match['tourney_name'] !== '') {
				echo '<div class="match_widget_tournament">' . esc_html($match['tourney_name']) . '</div>';
			}
			else {
				$our_logo = '';
				if ( $get_logos ) $our_logo = TeamData_WidgetUtils::get_our_logo_link();
				$have_logo = ( !empty($our_logo) ) || ( !empty($match['team_logo']) );
?>
				<div class="match_widget_next_wrapper<?php echo ($have_logo ? ' match_widget_with_logos' : ''); ?>">
					<div class="match_widget_next_details match_widget_us">
						<div class="match_widget_next match_widget_us"><?php echo esc_html($team_name); ?></div>
						<?php if ($have_logo) {
							echo '<div class="match_widget_logo match_widget_us">';
							if ( !empty($our_logo) ) echo '<img src="' . esc_attr($our_logo) . '"' . (!empty($logo_style) ? ' style="' . esc_attr($logo_style) . '"' : '') . ' />';
							echo '</div>';
						} ?>
					</div>
					<div class="match_widget_vs"><?php echo ($match['is_home'] ? 'vs' : '@'); ?></div>
					<div class="match_widget_next_details match_widget_them">
						<div class="match_widget_next match_widget_them"><?php
						if ( !empty($match['info_link']) ) {
							echo '<a class="match_widget_team_info" href="' . esc_attr($match['info_link']) . '">';
						}
						echo $match['team'];
						if ( !empty($match['info_link']) ) {
							echo '</a>';
						}
?>						</div>
<?php
						if ($have_logo) {
							echo '<div class="match_widget_logo match_widget_them">';
							if ( !empty($match['team_logo']) ) echo '<img src="' . esc_attr($match['team_logo']) . '"' . (!empty($logo_style) ? ' style="' . esc_attr($logo_style) . '"' : '') . ' />';
							echo '</div>';
						}
?>
					</div>
				</div>
<?php
			} 
			if ( $date_pos == 'after' ) {
				echo '<div class="match_widget_date">' . esc_html($match['_date']) . '</div>';
			}
			if ( $time_pos == 'after' ) {
				echo '<div class="match_widget_time">' . esc_html($match['_time']) . '</div>';
			}
			echo '<div class="match_widget_venue">';
			if ( !empty($match['directions_link']) ) {
				echo '<a class="match_widget_directions" href="' . esc_attr($match['directions_link']) . '">';
			}
			echo esc_html($match['venue']);
			if ( !empty($match['directions_link']) ) {
				echo '</a>';
			}
			echo '</div>'; // close .match_widget_venue
		}
		else { // if NOT $match
			echo '<div class="match_widget_empty">' . esc_html(__( 'No matches scheduled', 'team_data' )) . '</div>';
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
		$instance['date_pos'] = strip_tags( $new_instance['date_pos'] );
		$instance['time_pos'] = strip_tags( $new_instance['time_pos'] );
		$instance['logo_style'] = strip_tags( $new_instance['logo_style'] );
		$instance['get_logos'] = strip_tags( $new_instance['get_logos'] );
		if ( current_user_can('unfiltered_html') ) {
			$instance['more_info'] = $new_instance['more_info'];
		}
		else {
			 // Code cribbed from WP_Widget_Text. Comment there is: wp_filter_post_kses() expects slashed
			$instance['more_info'] = stripslashes( wp_filter_post_kses( addslashes($new_instance['more_info']) ) );
		}
		if ( isset($new_instance['level_id']) && ( intval($new_instance['level_id']) > 0) ) {
			$instance['level_id'] = intval($new_instance['level_id']);
		}
		else {
			$instance['level_id'] = -1;
		}
		return $instance;
	}

	public function form( $instance ) {
		global $wpdb;

		$valid_fields = array(
			'title' => '',
			'team_name' => '',
			'level_id' => '',
			'more_info' => '',
			'logo_style' => '',
			'get_logos' => '1',
			'date_pos' => 'before',
			'time_pos' => 'before',
		);
		$instance = wp_parse_args( (array) $instance, $valid_fields );
		$title = strip_tags( $instance['title'] );
		$team_name = strip_tags( $instance['team_name'] );
		$more_info = esc_textarea( $instance['more_info'] );
		$date_pos = strip_tags( $instance['date_pos'] );
		$time_pos = strip_tags( $instance['time_pos'] );
		$get_logos = strip_tags( $instance['get_logos'] );
		$logo_style = strip_tags( $instance['logo_style'] );
		$level_id = strip_tags( $instance['level_id'] );
		if ($level_id == '') $level_id = -1;
		$position_options = array(
			'before' => esc_html(__('Before Teams','team_data')),
			'after' => esc_html(__('After Teams','team_data')),
			'none' => esc_html(__("Don't Display",'team_data')),
		);
?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php echo esc_html(__('Title:', 'team_data')); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('team_name'); ?>"><?php echo esc_html(__('Your team name:', 'team_data')); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('team_name'); ?>" name="<?php echo $this->get_field_name('team_name'); ?>" type="text" value="<?php echo esc_attr($team_name); ?>" />
		</p>
<?php
		if ( class_exists('TeamDataTables') ) {
			$tables = new TeamDataTables();
			$sql = "SELECT id, name FROM $tables->level";
			$levels = $wpdb->get_results($sql, ARRAY_A);
			
			echo '<p>';
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
?>
		<p>
			<label for="<?php echo $this->get_field_id('date_pos'); ?>"><?php echo esc_html(__('Date position:','team_data')); ?></label>
			<select id="<?php echo $this->get_field_id('date_pos'); ?>" name="<?php echo $this->get_field_name('date_pos'); ?>">
<?php
			foreach ($position_options as $pos_option => $pos_display) {
				echo '<option value="' . esc_attr($pos_option) . '" ' . ($date_pos == $pos_option ? 'selected="true"' : '') . '>' . $pos_display . '</option>'; 
			}
?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('time_pos'); ?>"><?php echo esc_html(__('Time position:','team_data')); ?></label>
			<select id="<?php echo $this->get_field_id('time_pos'); ?>" name="<?php echo $this->get_field_name('time_pos'); ?>">
<?php
			foreach ($position_options as $pos_option => $pos_display) {
				echo '<option value="' . esc_attr($pos_option) . '" ' . ($time_pos == $pos_option ? 'selected="true"' : '') . '>' . $pos_display . '</option>'; 
			}
?>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('more_info'); ?>"><?php echo esc_html(__('Post-result content:', 'team_data')); ?></label>
			<textarea class="widefat" rows="5" cols="20" id="<?php echo $this->get_field_id('more_info'); ?>" name="<?php echo $this->get_field_name('more_info'); ?>"><?php echo $more_info; ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('get_logos_checkbox'); ?>"><?php echo esc_html(__('Display logos:', 'team_data')); ?></label>
			<input type="checkbox" id="<?php echo $this->get_field_id('get_logos_checkbox'); ?>" <?php echo ($get_logos ? ' checked="true"' : ''); ?> onchange="document.getElementById('<?php echo $this->get_field_id('get_logos'); ?>').value = ( this.checked ? 1 : 0 );" />
			<input type="hidden" id="<?php echo $this->get_field_id('get_logos'); ?>" name="<?php echo $this->get_field_name('get_logos'); ?>" value="<?php echo ( $get_logos ? '1' : '0' ); ?>"/><br />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('logo_style'); ?>"><?php echo esc_html(__('Logo style:','team_data')); ?></label>
			<textarea class="widefat" rows="3" cols="20" id="<?php echo $this->get_field_id('logo_style'); ?>" name="<?php echo $this->get_field_name('logo_style'); ?>"><?php echo $logo_style; ?></textarea>
		</p>
<?php
	}

}
?>