<?php
/*
 * SMTP Mail plugin
 */
defined('ABSPATH') or die();

// Stop wp
function smtpmail_shutdown()
{
	// This is our shutdown function, in 
	// here we can do any last operations
	// before the script is complete.

	// echo 'Script executed with success', PHP_EOL;

	// Check wp has send mail 
	if (isset($_SERVER['SMTPMAIL_WP_MAIL_SENDING']) && $_SERVER['SMTPMAIL_WP_MAIL_SENDING']) {
		$_SERVER['SMTPMAIL_WP_MAIL_SENDING'] = false;

		smtpmail_update_data(array(
			'status' => 1,
			'modified' => current_time('mysql'),
		));
	}
}
register_shutdown_function('smtpmail_shutdown');

/**
 * WP Check
 *
 * @since 1.1.8
 * 
 * @update 1.3.6
 *
 */
function smtpmail_wp_check()
{
	if (smtpmail_is_guest() == 0) return;
}
add_action('wp', 'smtpmail_wp_check');

/**
 * Scripts
 *
 * @since 1.2.13
 *
 * @update 1.3.9
 *
 */
function smtpmail_enqueue_scripts()
{
	$anti_spam_form = (int) smtpmail_options('anti_spam_form');

	// Scripts
	if ($anti_spam_form > 0) {
		wp_enqueue_script('security', smtpmail_assets_url('security.js'),  array('jquery'), '1.2.13', true);
		wp_localize_script('security', 'wp_security', ['anti_spam_form' => $anti_spam_form]);
	}
}
add_action('wp_enqueue_scripts', 'smtpmail_enqueue_scripts', 40);
