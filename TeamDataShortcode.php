<?php

/**
* TeamData plugin Shortcode handler/API.
*/
class TeamDataShortcode extends TeamDataBase {

	private $shortcodes;

	private $script_registry;

	public function __construct() {
		$this->script_registry = array(
			"register" => array( "jquery" ),
		);
		$this->shortcodes = array();
	}

	public function add_actions() {
		if ( $this->actions_added ) return;

		add_shortcode( 'team_data_register', array( $this, 'handle_register' ) );
		add_action('wp_print_footer_scripts', array( $this, 'check_scripts') );
		$this->actions_added = true;
	}

	public function handle_register( $atts ) {
		global $wpdb;
		$this->shortcodes[ 'register' ] = true;

		$default_options = array(
			'address2' => false,
			'tel_home' => false,
			'tel_work' => false,
			'height' => true,
			'weight' => true,
			'date_of_birth' => true,
			'college_or_school' => true,
			'position' => true,
			'past_clubs' => true,
		);
		$att_options = array(
			'form_id' => 'register_form',
			'title' => __( 'Register', 'team_data' ),
			'button_title' => __( 'Submit', 'team_data' ),
			'check_for_spam' => true, // flag to implement a basic check for spam using an input we don't actually submit
			'use_province' => false, // use 'Province' caption instead of 'State'
			'use_postal_code' => false, // use 'Postal Code' caption instead of 'Zip'
		);
		foreach ($default_options as $option => $default_value) {
			$att_options[ 'include_' . $option ] = $default_value;
		}
		$atts = shortcode_atts( $att_options, $atts );

		$form_id = esc_attr( $atts['form_id'] );
		$form = array();
		$form[] = '<div id="' . $form_id . '" class="team_data_register_form">';
		if ($atts['title'] != '') {
			$form[] = '<h2 class="team_data_header">' . esc_attr( $atts['title'] ) . '</h2>';
		}
		$form[] = '<input id="' . $form_id . '__nonce" name="team_data_nonce" type="hidden" value="' . esc_attr( wp_create_nonce( 'team_data_nonce' ) ) . '" />';
		$fields = array(
			'first_name' => array(
				'label' => __( 'First name', 'team_data' ),
				'type' => 'text',
			),
			'last_name' => array(
				'label' => __( 'Last name', 'team_data' ),
				'type' => 'text',
			),
			'email' => array(
				'label' => __( 'Email', 'team_data' ),
				'type' => 'email'
			),
			'address1' => array(
				'label' => __( 'Address', 'team_data' ),
				'type' => 'text'
			),
			'address2' => array(
				'label' => __( 'Address 2', 'team_data' ),
				'type' => 'text'
			),
			'city' => array(
				'label' => __( 'City', 'team_data' ),
				'type' => 'text'
			),
			'state' => array(
				'label' => ( $atts[ 'use_province' ] ? __( 'Province', 'team_data') : __( 'State', 'team_data' ) ),
				'type' => 'text'
			),
			'postal_code' => array(
				'label' => ( $atts[ 'use_postal_code' ] ? __( 'Postal Code', 'team_data') : __( 'Zip', 'team_data' ) ),
				'type' => 'text'
			),
			'cell' => array(
				'label' => __( 'Cell', 'team_data' ),
				'type' => 'tel'
			),
			'tel_home' => array(
				'label' => __( 'Home', 'team_data' ),
				'type' => 'tel'
			),
			'tel_work' => array(
				'label' => __( 'Work', 'team_data' ),
				'type' => 'tel'
			),
			'date_of_birth' => array(
				'label' => __( 'Date of Birth', 'team_data' ),
				'type' => 'date'
			),
			'height' => array(
				'label' => __( 'Height', 'team_data' ),
				'type' => 'text'
			),
			'weight' => array(
				'label' => __( 'Weight', 'team_data' ),
				'type' => 'text'
			),
			'college_or_school' => array(
				'label' => __( 'School/Year', 'team_data' ),
				'type' => 'text'
			),
			'position' => array(
				'label' => __( 'Position(s)', 'team_data' ),
				'type' => 'text'
			),
			'past_clubs' => array(
				'label' => __( 'Past Club(s)', 'team_data' ),
				'type' => 'text'
			),
		);
		foreach ($default_options as $opt => $opt_val) {
				$include_option = 'include_' . $opt;
				if (!$atts[ $include_option ]) {
					unset( $fields[ $opt ] );
				}
		}
		foreach ( $fields as $name => $control ) {
			$control_id = $form_id . '__' . $name;
			$form[] = '<div class="team_data_register_pair">';
			$form[] = '<label for="' . $control_id . '">' . esc_attr( $control['label'] ) . '</label>';
			$form[] = '<input id="' . $control_id . '" type="' . $control['type'] . '" name="team_data_' . $name . '" />';
			$form[] = '</div>';
		}
		// Add email lists
		$form[] = '<div id="' . $form_id . '__email_lists" class="team_data_register_email_lists">';
		$list_table = $wpdb->prefix . 'team_data_list';
		$lists = $wpdb->get_results( "SELECT IF(registration_name <> '', registration_name, name) AS name, auto_enroll FROM $list_table WHERE show_in_registration = 1" );
		foreach ($lists as $list_pos => $list) {
			$list_name = esc_attr( $list->name );
			$control_id = $form_id . '__email_list_' . $list_pos;
			$form[] = '<div class="team_data_register_email_pair">';
			$form[] = '<label for="' . $control_id . '">' . $list_name . '</label>';
			$form[] = '<input id="' . $control_id . '" type="checkbox" name="team_data_list__' . $list_name . '"' . ($list->auto_enroll == '1' ? ' checked="checked"' : '') . ' listname="' . $list_name . '" />';
			$form[] = '</div>';
		}
		$form[] = '</div>';

		$form[] = '<div class="team_data_register_pair">';
		$form[] = '<label for="' . $form_id . '__comments">' . __( 'Comments', 'team_data' ) . '</label>';
		$form[] = '<textarea id="' . $form_id . '__comments" name="team_data_comments" />';
		$form[] = '</div>';
		$form[] = '<input type="button" class="team_data_button" onclick="window._team_data_register_js.register(\'' . esc_js( $form_id ) . '\');" value="' . esc_attr( $atts['button_title'] ) . '" />';
		if ($atts['check_for_spam']) {
			$form[] = '<input type="text" style="display: none;" id="' . $form_id . '__more_comments" name="team_data_more_comments" value="" />';
		}
		$form[] = '</div>';
		return implode('', $form);
	}

	public function check_scripts() {
		global $wp_scripts;
		foreach ($this->shortcodes as $shortcode => $processed) {
			if ($processed && isset($this->script_registry[$shortcode])) {
				$scripts = $this->script_registry[$shortcode];
				foreach ($scripts as $script) {
					wp_enqueue_script( $script );
					// check whether script has already been done or is scheduled for footer
					if ((!in_array($script, $wp_scripts->done)) && (!in_array($script, $wp_scripts->in_footer))) {
						$wp_scripts->in_footer[] = $script;
					}
				}
			}
		}
	}

	protected function get_register_js($form_id)
	{
		$js = array();
		$js[] = '<script type="text/javascript">' . "\n";
		$js[] = 'if (!window._team_data_register_js) { ';
		$js[] = 'window._team_data_register_js = {';
		$js[] = '"ajax_url": "' . esc_js( admin_url( 'admin-ajax.php' ) ) . '",';
		$js[] = '"register": function(divID) {';
		$js[] = 'if ((!divID) || (typeof window.jQuery != "function")) { return false; }';
		$js[] = 'var formDiv = document.getElementById(divID);';
		$js[] = 'if (!formDiv) { return false; }';
		$js[] = 'var check_field = document.getElementById(divID + "__more_comments");';
		$js[] = 'if (check_field && (check_field.value != "")) { return false; }';
		$js[] = 'var postData = { "action": "wp_ajax_nopriv_team_data_register_member", "listNames": {} };';
		$js[] = 'var divIDLen = divID.length;';
		$js[] = 'var controls = formDiv.getElementsByTagName("input");';
		$js[] = 'for (var i = 0; i < controls.length; i++) {';
		// use item() as this is not actually an array
		$js[] = 'var control = controls.item(i);';
		$js[] = 'if (control) {';
		$js[] = 'var controlID = String(control.id);';
		// exclude other input elements, and the list checkboxes
		$js[] = 'if ((controlID.substring(0,divIDLen) == divID) && (controlID.substring(divIDLen,6) != "_list__")) {';
		// get name after leading "team_data_" characters
		$js[] = 'var controlName = String(control.getAttribute("name")).substring(10);';
		$js[] = 'if (controlName != "") { postData[controlName] = control.value; }';
		$js[] = '}'; // end substring if
		$js[] = '}'; // end (control) if
		$js[] = '}'; // end for loop over controls
		$js[] = 'var listDiv = document.getElementById(divID + "__email_lists");';
		$js[] = 'if (listDiv) {';
		$js[] = 'var listControls = listDiv.getElementsByTagName("input");';
		$js[] = 'for (var i = 0; i < listControls.length; i++) {';
		$js[] = 'var checkbox = listControls.item(i);';
		$js[] = 'if (checkbox && (checkbox.getAttribute("listname") != "")) {';
		$js[] = 'postData[listNames][checkbox.getAttribute("listname")] = (checkbox.checked ? "1" : "0");';
		$js[] = '}'; // end if (checkbox && (checkbox.getAttribute("listname") != ""))
		$js[] = '}'; // end for loop over listControls
		$js[] = '}'; // end if (listDiv)
		// Prepare callbackForm for use in a closure
		$js[] = 'var callbackDivID = divID;';
		$js[] = 'jQuery.post(_team_data_register_js.ajax_url, postData, function(registerResult) { _team_data_register_js.registerHandler(registerResult,callbackDivID); });';
		$js[] = '},'; // end register()
		$js[] = '"registerHandler": function(response,divID) {';
		$js[] = 'if ((!response) || (!divID)) { return; }';
		$js[] = 'var div = document.getElementById(divID);';
		$js[] = 'if (div) {';
		$js[] = 'var html = [];';
		$js[] = 'if (response.result == "error") {';
		$js[] = "html.push('<span class=\"team_data_error\">" . esc_js(esc_html(__( 'There was an error processing your request.', 'team_data' ))) . "</span><br/>');";
		$js[] = 'if (response.error_message) {';
		$js[] = "html.push('<span class=\"team_data_error\">" . esc_js(esc_html(__( 'If you cannot resolve the problem, please report the following error to the webmaster:'))) . "</span><br/>');";
		$js[] = "html.push('<span class=\"team_data_error\">' + response.error_message + '</span>');";
		$js[] = '}'; // end if (response.error_message)
		$js[] = '}'; // end if (response.result == 'error')
		$js[] = 'else {';
		// check if we have a registration landing page
		$registration_target = $get_option('team_data_registration_target', '');
		if ($registration_target != '') {
			$js[] = 'document.location = "' . esc_js($registration_target) . '";';
			$js[] = 'return;';
		}
		else {
			$registration_message = $get_option('team_data_registration_message', '');
			if ($registration_message == '') {
				$registration_message = __('Thank you for registering with us! We will be in touch soon.', 'team_data');
			}
			$registration_message = esc_js(esc_html($registration_message));
			$js[] = "html.push('<span class=\"team_data_success\">" . $registration_message . "</span>');";
		}
		$js[] = "}"; // end else (response.result == 'error')
		$js[] = 'div.innerHTML = html.join("");';
		$js[] = '}'; // end if (div)
		$js[] = '}'; // end registerHandler()
		$js[] = '}'; // end _team_data_register_js object definition
		$js[] = '}'; // end if (!window._team_data_register_js)
		$js[] = "\n" . '</script>';
		return implode('', $js);
	}
}
?>