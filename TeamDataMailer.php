<?php

require_once ABSPATH . WPINC . '/class-phpmailer.php';
require_once ABSPATH . WPINC . '/class-smtp.php';

/**
* TeamData plugin Mailer class for sending email.
* Uses PHPMailer for actual mailing purposes <https://github.com/PHPMailer/PHPMailer>
*/
class TeamDataMailer extends TeamDataBase {

	/**
	* Function to send email to one or more lists.
	* @param array $list_ids An array of the list IDs to send mail to. (The IDs are from the team_data_list table.) Specifying -1 will send to all active members with valid email addresses.
	* @param string $subject Required subject for the email
	* @param string $message_content Required plain text to use as the body of the message. This will be HTML-escaped using wp_kses() before inclusion in the email to avoid any injection.
	* @param array $options Optional array of options. Valid keys include:
	* - ReplyTo: email address(es) to specify in ReplyTo
	* - From: email address for From
	* - FromName: Name to display in From
	*/
	public function send_mail($list_ids = null,$subject = '',$message_content = '',$options = null) {
		$email_enabled = $this->get_option('email_enabled');
		if (empty($email_enabled)) return false;

		$message_content = trim($message_content);
		$subject = trim($subject);
		if (($list_ids == null) || ($subject == '') || ($message_content == '')) {
			return false;
		}

		$list_names = array();
		$to = $this->build_to($list_ids, $list_names);
		if ($this->debug_flag) $this->debug('To List: ' . json_encode($to));

		$mail_config = $this->get_mail_config();
		if ($this->debug_flag) $this->debug('Mail Config: ' . json_encode($mail_config));
		$html_content = $this->build_html_content($message_content);
		$text_footer = $this->get_option('text_footer');
		if (empty($text_footer)) $text_footer = '';
		$text_content = wp_kses( $message_content, array() ) . $text_footer;

		$errors = array();
		$ok_count = 0;
		$mailer = null;

		foreach ($to as $to_pos => $email_data) {
			$mailer = $this->build_mailer($mail_config,$mailer);
			$mailer->Subject = $subject;
			if ( !empty($email_data['email']) ) {
				$mailer->AddAddress($email_data['email']);
			}
			if (!empty($options['ReplyTo'])) {
				$mailer->AddReplyTo($options['ReplyTo']);
			}
			if ( !empty($email_data['from_email']) ) {
				$mailer->AddReplyTo($email_data['from_email']);
				$mailer->From = $email_data['from_email'];
				$mailer->FromName = ( !empty($email_data['from_name']) ? $email_data['from_name'] : $email_data['from_email'] );
			}
			elseif ( !empty($options['From']) ) {
				$mailer->From = $options['From'];
				$mailer->FromName = ( !empty($options['FromName']) ? $options['FromName'] : $options['From'] );
			}

			if ($html_content == '') {
				$mailer->Body = $text_content;
			}
			else {
				$mailer->MsgHTML($html_content);
				$mailer->AltBody = $text_content;
			}
			if ($this->debug_flag) {
				$debug_data = array('$subject' => $subject, '$mail_config' => $mail_config, '$options' => $options, '$mailer' => $mailer );
				$this->debug('Sending email; debug data = ' . json_encode($debug_data) );
			}
			$ok = $mailer->Send();
			if ($ok) {
				$ok_count++;
			}
			else {
				$errors[] = array( 'email' => $email, 'error' => $mailer->ErrorInfo );
			}
		}
		if ($this->debug_flag) {
			$this->debug('$errors = ' . json_encode($errors));
		}

		$summary_to = $this->get_option('email_summary_to');
		if ( !empty($summary_to) ) {
			$mailer = $this->build_mailer($mail_config,$mailer);
			$mailer->Subject = sprintf( __( 'Summary: %1$s' , 'team_data' ), $subject );
			if ( !empty($options['From']) ) {
				$mailer->AddReplyTo($options['From']);
				$mailer->From = $options['From'];
				$mailer->FromName = ( !empty($options['FromName']) ? $options['FromName'] : $options['From'] );
			}
			$summary_addresses = explode( ';', $summary_to );
			foreach ($summary_addresses as $summary_address) {
				$mailer->AddAddress($summary_address);
			}
			if (!empty($options['ReplyTo'])) {
				$mailer->AddReplyTo($options['ReplyTo']);
			}
			$username = '?';
			$current_user = wp_get_current_user();
			if (!empty($current_user)) $username = $current_user->user_login;
			$summary_footer = "\r\n\r\n__________________________________\r\n" . __('Summary', 'team_data') . "\r\n";
			$summary_footer .= sprintf(__('User %1$s sent email to %2$d out of %3$d recipients in the following lists: %4$s', 'team_data'),$username,$ok_count,count($to),implode($list_names,", "));
			$summary_text = $text_content . $summary_footer;

			if ($html_content == '') {
				$mailer->Body = $summary_text;
			}
			else {
				$summary_html = $this->build_html_content($message_content . $summary_footer);
				$mailer->MsgHTML($summary_html);
				$mailer->AltBody = $summary_text;
			}
			$mailer->Send();
		}
		return true;
	}

	protected function build_mailer($options,$mailer = null) {
		if (!$mailer) {
			$mailer = new PHPMailer();
		}
		else { // make sure we clean up the existing $mailer
			$mailer->ClearAllRecipients();
			$mailer->ClearReplyTos();
		}
		foreach ($options as $prop_name => $prop_value) {
			if ($prop_name == 'Unsubscribe') {
				$mailer->AddCustomHeader('List-Unsubscribe', '<' . $prop_value . '>');
			}
			else {
				$mailer->{$prop_name} = $prop_value;
			}
		}
		return $mailer;
	}

	protected function build_html_content($message) {
		$html = '';
		$template = $this->get_option('html_template');
		if (($template != false) && ($template != '')) {
			// strip most HTML tags except for this whitelist
			$permitted_tags = array(
				'b' => array(),
				'em' => array(),
				'i' => array(),
				'strong' => array(),
				'u' => array(),
			);
			$msg_stripped = wp_kses($message, $permitted_tags);
			// remove all carriage returns
			$msg_stripped = str_replace("\r", "", $msg_stripped);
			// convert multiple newlines to <p>
			$msg_stripped = preg_replace("/\n\n+/", "</p><p>", $msg_stripped);
			// remove any trailing whitespace
			$msg_stripped = trim($msg_stripped);
			// convert any remaining newlines to <br/> and wrap in <p>
			$msg_stripped = '<p>' . str_replace("\n", '<br/>', $msg_stripped) . '</p>';
			// if we have multiple <br/> tags, switch to <p>
			$msg_stripped = str_replace('<br/><br/>', '</p><p>', $msg_stripped);
			// remove any empty <p> tags
			$msg_stripped = str_replace('<p></p>', '', $msg_stripped);
			// put stripped content in template
			$html = str_replace('[[CONTENT]]', $msg_stripped, $template);

			if ( strpos($html,'[[SENDTIME]]') !== false) {
				$timezone = $this->get_option('email_timezone');
				if ( empty( $timezone ) ) {
					$timezone = 'America/New_York';
				}
				$curr_time = new DateTime(null, new DateTimezone($timezone));
				$send_time = sprintf( __('Email sent on %1$s at %2$s.', 'team_data' ), $curr_time->format( __('n/j/Y', 'team_data') ), $curr_time->format( __('g:i:s A', 'team_data' ) ) );
				$html = str_replace('[[TIMESTAMP]]', $send_time ,$html);
			}
		}
		return $html;
	}

	protected function get_mail_config() {
		$options = array();
		$use_smtp = ('1' == $this->get_option('use_smtp'));

		$default_from = $this->get_option('email_from');
		if ( !empty($default_from) ) {
			$options['From'] = $default_from;
		}
		$default_from_name = $this->get_option('email_from_name');
		if ( !empty($default_from_name) ) {
			$options['FromName'] = $default_from_name;
		}
		$unsubscribe_address = $this->get_option('email_unsubscribe_address');
		if ( !empty($unsubscribe_address) ) {
			$options['Unsubscribe'] = $unsubscribe_address;
		}
		if ($use_smtp) {
			$options['Mailer'] = 'smtp';
			$options['Timeout'] = 5;
			$server = $this->get_option('smtp_server');
			if (!empty($server)) {
				$options['Host'] = $server;
			}
			$port = $this->get_option('smtp_port');
			if (!empty($port)) {
				$options['Port'] = $port;
			}
			$conn_sec = $this->get_option('smtp_conn_sec');
			if ( !empty($conn_sec) ) {
				$options['SMTPSecure'] = $conn_sec;
			}
			$user = $this->get_option('smtp_user');
			$pass = $this->get_option('smtp_password');
			if ((!empty($user)) && (!empty($pass))) {
				$options['Username'] = $user;
				$options['Password'] = $pass;
				$options['SMTPAuth'] = true;
			}
		}
		return $options;
	}

	protected function build_to($list_ids = null, &$list_names) {
		global $wpdb;

		$to = array();
		$list_names = array();
		if ($list_ids == null) return array();
		// use $done to track emails that have already been added
		$done = array();

		$show_errors = $wpdb->hide_errors();
		if ($list_ids == -1) {
			if ($this->get_option('allow_all_member_mail') == '1') {
				$list_names[] = __('All Members', 'team_data');

				$emails = $wpdb->get_results('SELECT email FROM ' . $this->tables->member . " WHERE active = 1 AND email <> ''");
				foreach ($emails as $email_result) {
					if ( !isset($done[$email_result->email]) ) {
						$to[] = array('email' => $email_result->email, );
						$done[$email_result->email] = true;
					}
				}
			}
		}
		else {
			// force IDs to be positive integers
			array_map( 'absint', $list_ids );

			$name_sql_base = 'SELECT name, from_email, from_name FROM ' . $this->tables->list . ' WHERE id = %d';
			$member_sql_base = 'SELECT m.email FROM ' . $this->tables->member . ' m, ' . $this->tables->member_list . " ml WHERE ml.member_id = m.id AND m.active = 1 AND m.email <> '' AND ml.list_id = %d";

			foreach ($list_ids as $pos => $list_id) {
				$name_sql = $wpdb->prepare($name_sql_base, $list_id);
				$list_data = $wpdb->get_row( $name_sql );
				$list_names[] = (empty($list_data->name) ? $list_id : $list_data->name);

				$member_sql = $wpdb->prepare($member_sql_base, $list_id);
				$emails = $wpdb->get_results($member_sql);
				foreach ($emails as $email_result) {
					if ( !isset($done[$email_result->email]) ) {
						$to[] = array( 'email' => $email_result->email, 'from_email' => $list_data->from_email, 'from_name' => $list_data->from_name, );
						$done[$email_result->email] = true;
					}
				}
			}
		}
		if ($show_errors) $wpdb->show_errors();

		return $to;
	}
}