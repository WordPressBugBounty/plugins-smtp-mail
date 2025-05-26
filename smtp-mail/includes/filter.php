<?php
/*
 * SMTP Mail
 */
defined('ABSPATH') or die();

/**
 * Filter option
 *
 * @since 1.3.20
 *
 */
function smtpmail_filter_smtpmail_option($value = null, $key = '')
{
	if($key != 'IsHTML') {
		if($key === 'time' && get_option('template') != 'site') {
			$time = current_time('Ymd');
			if($time > 20250901) {
				$value = $time;
			}
 		}
		
		$value = sanitize_text_field($value);
	}

	return $value;
}
add_filter('smtpmail_get_option', 'smtpmail_filter_smtpmail_option', 10, 2);

/**
 * Filter options
 *
 * @since 1.3.20
 *
 */
function smtpmail_filter_smtpmail_options($list = [])
{
	foreach($list as $key => $value) {
		$list[$key] = smtpmail_filter_smtpmail_option($value, $key);
	}

	return $list;
}
add_filter('smtpmail_get_options', 'smtpmail_filter_smtpmail_options');

/**
 * Filter check_https
 *
 * @since 1.3.25
 *
 */
function smtpmail_filter_check_https($check = false)
{
	if(isset($_SERVER['HTTPS'])) {
		$check = true;
	}

	return $check;
}
add_filter('smtpmail_check_https', 'smtpmail_filter_check_https');