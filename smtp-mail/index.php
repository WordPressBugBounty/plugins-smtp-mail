<?php
/*
Plugin Name: SMTP Mail
Plugin URI: https://docs.photoboxone.com/smtp-mail.html
Description: SMTP settings, mail function, send test, save submitted data. It is very easy to configure and fast.
Author: Photoboxone
Author URI: http://photoboxone.com/donate/?developer=photoboxone
Version: 1.3.35
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 6.2
Requires PHP: 7.4
*/

defined('ABSPATH') or die();

function smtpmail_index()
{
	return __FILE__;
}

require_once __DIR__ . '/includes/functions.php';

if( is_admin() ) {
	
	require_once __DIR__ . '/includes/setting.php';

	require_once __DIR__ . '/includes/notices.php';
	
} else {
	
	require_once __DIR__ . '/includes/site.php';

}

/*
 * Since 1.3.0
 */
require_once __DIR__ . '/includes/sendgrid.php';

/*
 * Since 1.3.20
 */
require_once __DIR__ . '/includes/filter.php';