<?php
/*
 * Since 1.3
 */
defined('ABSPATH') or die();

/**
 * https://docs.sendgrid.com/for-developers/sending-email/api-getting-started
 */
function smtpmail_curl_sendgrid($args = array())
{
	extract(shortcode_atts(array(
		'to' 	    => array(),
		'subject'   => '',
		'body' 		=> '',
	), (array) $args));

	$sendgrid_api_key = smtpmail_options('sendgrid_api_key');

	if ($sendgrid_api_key == '') {
		$json = array(
			'code'      => 403,
			'message'   => 'Sendgrid API Key null'
		);
	} else {

		$from_email = smtpmail_options('From');
		$from_name  = smtpmail_options('FromName');

		if (smtpmail_options('IsHTML') == true) {
			$content = [
				"type" => "text/html",
				"value" => wp_kses_post($body)
			];
		} else {
			$content = [
				"type" => "text/plain",
				"value" => wp_strip_all_tags($body)
			];
		}

		// https://docs.sendgrid.com/api-reference/mail-send/mail-send
		$post_data = array(
			"personalizations" => [
				[
					"to" => $to
				]
			],
			"from" => [
				"email" => $from_email,
				"name"  => $from_name,
			],
			"subject" => "=?UTF-8?B?" . base64_encode($subject) . "?=",
			"content" => [$content],
		);

		$json = array(
			'code'      => 400,
			'message'   => 'Data fail'
		);

		$response = wp_remote_post('https://api.sendgrid.com/v3/mail/send', array(
			'timeout' => 120,
			'httpversion' => '1.1',
			'headers' => array(
				'Authorization' => 'Bearer ' . $sendgrid_api_key,
				'Content-Type' => 'application/json',
				'Referer' => home_url()
			),
			'body' => json_encode($post_data, JSON_UNESCAPED_UNICODE)
		));

		$body = wp_remote_retrieve_body($response);
		if ($body != '') {
			$data = json_decode($body, true);

			if (is_array($data) && isset($data['errors'])) {
				$json['message'] = 'Sent fail';
			} else {
				$json = array(
					'code'      => 200,
					'message'   => 'Sent success'
				);
			}
		}
	}

	return $json;
}

function smtpmail_wp_mail_sendgrid($result = null, $atts = array())
{
	if (smtpmail_options('isSMTP') != 2) {
		return $result;
	}

	if (isset($atts['to'])) {
		$to = $atts['to'];
	}

	if (! is_array($to)) {
		$to = explode(',', $to);
	}

	$addresses = [];

	foreach ($to as $i => $address) {
		// Break $recipient into name and address parts if in the format "Foo <bar@baz.com>".
		$recipient_name = '';

		if (preg_match('/(.*)<(.+)>/', $address, $matches)) {
			if (count($matches) == 3) {
				$recipient_name = $matches[1];
				$address        = $matches[2];
			}
		}

		if ($recipient_name == '') {
			$recipient_name = ucwords(explode('@', $address)[0]);
		}

		$addresses[] = [
			"email" => $address,
			"name"  => $recipient_name
		];
	}

	if (isset($atts['subject'])) {
		$subject = $atts['subject'];
	}

	if (isset($atts['message'])) {
		$message = $atts['message'];
	}

	if (isset($atts['headers'])) {
		$headers = $atts['headers'];
	}

	if (isset($atts['attachments'])) {
		$attachments = $atts['attachments'];

		if (! is_array($attachments)) {
			$attachments = explode("\n", str_replace("\r\n", "\n", $attachments));
		}

		if (! empty($attachments)) {
			foreach ($attachments as $filename => $attachment) {
				$filename = is_string($filename) ? $filename : '';
			}
		}
	}

	// setup data before send mail
	if (smtpmail_options('save_data')) {
		$data = wp_unslash($_POST);

		$params = array_merge($data, array(
			'ip' => smtpmail_get_server('SERVER_ADDR'),
			'user_agent' => smtpmail_get_server('HTTP_USER_AGENT'),
		));

		$emails = smtpmail_array_values_by_key($to, 'email');
		$names = smtpmail_array_values_by_key($to, 'name');

		$send_data = array(
			'from_name' => smtpmail_options('FromName'),
			'from_email' => smtpmail_options('From'),
			'to_email' => implode(';', $emails),
			'to_name' => implode(';', $names),
			'message' => $message,
			'subject' => $subject,
			'params' => json_encode($params),
			'created' => current_time('mysql'),
		);

		smtpmail_insert_data($send_data);
	}

	$response = smtpmail_curl_sendgrid(array(
		'to'        => $addresses,
		'subject'   => $subject,
		'body'      => $message,
	));

	$mail_data = compact('to', 'subject', 'message', 'headers', 'attachments');

	if ($response['code'] == 200) {
		do_action('wp_mail_succeeded', $mail_data);

		$result = true;
	} else {
		do_action('wp_mail_failed', new WP_Error('wp_mail_failed', $response['message'], $mail_data));

		$result = false;
	}

	return $result;
}
add_filter('pre_wp_mail', 'smtpmail_wp_mail_sendgrid', 10, 2);
