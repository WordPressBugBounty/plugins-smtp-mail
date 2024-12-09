<?php
/*
 * SMTP Mail plugin
 */
defined('ABSPATH') or die();

/*
 * Since 1.0.0
 * 
 * Update at 1.3.12
 */
function smtpmail_plugin_actions($actions = [], $plugin_file = '')
{
	if ($plugin_file == plugin_basename(smtpmail_index())) {
		array_unshift(
			$actions,
			sprintf('<a href="%s">%s</a>', smtpmail_setting_url(), __("Settings", 'smtp-mail')),
			sprintf('<a href="%s" target="_blank">%s</a>', smtpmail_pbone_url('contact'), __("Support", 'smtp-mail'))
		);
	}

	return $actions;
}
add_filter("plugin_action_links", "smtpmail_plugin_actions", 10, 2);

/*
 * Since 1.3.1
 */
function smtpmail_admin_plugin_row_meta($plugin_meta = array(), $plugin_file = '')
{
	if ($plugin_file == plugin_basename(smtpmail_index())) {
		$plugin_meta[] = sprintf('<a class="dashicons-before dashicons-awards" href="%s" target="_blank">%s</a>', smtpmail_pbone_url('donate?for=smtp-mail'), __('Donate 1', 'smtp-mail'));
	}

	return $plugin_meta;
}
add_filter('plugin_row_meta', 'smtpmail_admin_plugin_row_meta', 10, 2);

/*
 * Since 1.0.0
 */
function smtpmail_add_options_page()
{
	add_options_page(
		'SMTP Mail Settings',
		'SMTP Mail',
		'manage_options',
		'smtpmail-setting',
		'smtpmail_setting_display'
	);
}
add_action('admin_menu', 'smtpmail_add_options_page');

/*
 * Since 1.0.0
 */
function smtpmail_init_theme_opotion()
{
	// add Setting
	add_settings_section(
		'smtpmail_options_section',
		'SMTP Mail Options',
		'smtpmail_options_section_display',
		'smtpmail-options-section'
	);

	register_setting('smtpmail_settings', 'smtpmail_options');
	
	// Styles
	wp_enqueue_style('smtpmail', smtpmail_assets_url('admin.css'), '', smtpmail_ver());
	wp_enqueue_script('smtpmail', smtpmail_assets_url('admin.min.js'), array('jquery'), smtpmail_ver(), true);
}
add_action('admin_init', 'smtpmail_init_theme_opotion');

/*
 * Since 1.0.0
 */
function smtpmail_setting_display()
{
	$options = smtpmail_options();

	$tab 		= sanitize_text_field(isset($_GET['tab']) ? $_GET['tab'] : '');
	$orderby 	= sanitize_text_field(isset($_GET['orderby']) ? $_GET['orderby'] : '');

	if ($orderby != '') {
		$tab = 'list';
	}

	if (get_option('template') != 'site') {
		$options['time'] = smtpmail_get_new_expires();
	}

	?>
	<h2 class="entry-title"><?php _e('SMTP Mail Settings', 'smtp-mail'); ?></h2>
	<div class="wrap smtpmail_settings clearfix">
		<div class="smtpmail_advanced clearfix">
			<div class="smtpmail_tabmenu clearfix">
				<ul>
					<li class="<?php echo esc_attr($tab == '' ? 'active' : ''); ?>">
						<a href="<?php echo smtpmail_setting_url() ?>"><?php _e('General', 'smtp-mail'); ?></a>
					</li>
					<li class="<?php echo esc_attr($tab == 'test' ? 'active' : ''); ?>">
						<a href="<?php echo smtpmail_setting_url(['tab' => 'test']) ?>"><?php _e('Send test', 'smtp-mail'); ?></a>
					</li>
					<li class="<?php echo esc_attr($tab == 'list' ? 'active' : ''); ?>">
						<a href="<?php echo smtpmail_setting_url(['tab' => 'list']) ?>"><?php _e('Data', 'smtp-mail'); ?></a>
					</li>
					<li class="<?php echo esc_attr($tab == 'more' ? 'active' : ''); ?>">
						<a href="<?php echo smtpmail_setting_url(['tab' => 'more']) ?>"><?php _e('Plugins', 'smtp-mail'); ?></a>
					</li>
				</ul>
			</div>
			<div class="smtpmail_tabitems clearfix">
				<div class="smtpmail_tabitem item-1<?php echo esc_attr($tab == '' ? ' active' : ''); ?>">
					<?php smtpmail_setting_form($options); ?>
				</div>
				<div class="smtpmail_tabitem item-2<?php echo esc_attr($tab == 'test' ? ' active' : ''); ?>">
					<?php smtpmail_sendmail_form($options); ?>
				</div>
				<div class="smtpmail_tabitem item-3<?php echo esc_attr($tab == 'list' ? ' active' : ''); ?>">
					<?php smtpmail_data_list($options); ?>
				</div>
				<div class="smtpmail_tabitem item-4<?php echo esc_attr($tab == 'more' ? ' active' : ''); ?>">
					<?php smtpmail_include('plugins.html'); ?>
				</div>
			</div>
		</div>
		<?php if (smtpmail_show_sidebar_footer($tab) == false) : ?>
			<div class="smtpmail_sidebar clearfix">
				<?php
					smtpmail_help_links();

					smtpmail_donate_text();
				?>
			</div>
		<?php endif; ?>
	</div>
	<?php if (smtpmail_show_sidebar_footer($tab)) : ?>
		<div class="wrap smtpmail_settings smtpmail_settings_footer">
			<?php
				smtpmail_help_links();

				smtpmail_donate_text();
			?>
		</div>
	<?php
	endif;
}

/*
 * Since 1.0.0
 */
function smtpmail_show_sidebar_footer($tab = '')
{
	return in_array($tab, array('list', 'more'));
}

/*
 * Since 1.0.0
 */
function smtpmail_help_links()
{
	?>
	<div class="smtpmail_sidebar_box">
		<h4><?php _e('Do you need help?', 'smtp-mail'); ?></h4>
		<ol>
			<li>
				<a href="https://docs.photoboxone.com/smtp-mail.html" target="_blank" rel="help" title="<?php _e('How to configure an SMTP Mail plugin?', 'smtp-mail'); ?>">
					<?php _e('Documentation', 'smtp-mail'); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo smtpmail_pbone_url('contact'); ?>" target="_blank" rel="help">
					<?php _e('Support', 'smtp-mail'); ?>
				</a>
			</li>
			<li>
				<a href="<?php echo smtpmail_pbone_url(); ?>" target="_blank" rel="author">
					<?php _e('About', 'smtp-mail'); ?>
				</a>
			</li>
		</ol>
	</div>
	<?php
}

/*
 * Since 1.0.1
 */
function smtpmail_donate_text()
{
	?>
	<div class="smtpmail_sidebar_box">
		<h4>
			<?php _e('You can donate to us by visiting our website. Thank you for watching.', 'smtp-mail'); ?>
		</h4>
		<p>
		<div class="smtpmail-icon-click">
			<div class="dashicons dashicons-arrow-right-alt"></div>
		</div>
		<a href="https://docs.photoboxone.com/smtp-mail.html" target="_blank" rel="help">
			<?php _e('How to configure an SMTP Mail plugin?', 'smtp-mail'); ?>
		</a>
		</p>
		<p>
			<?php _e('You can donate by PayPal.', 'smtp-mail'); ?>
		</p>
		<p align=center>
			<a href="<?php echo smtpmail_pbone_url('donate?for=smtp-mail'); ?>" target="_blank" rel="help" class="button button-primary">
				<?php _e('Donate', 'smtp-mail'); ?>
			</a>
		</p>
		<p>
			<?php _e('Thank you for using SMTP Mail.', 'smtp-mail'); ?>
		</p>
	</div>
<?php
}

/*
 * Since 1.0.0
 * 
 * Update at 1.3.2
 */
function smtpmail_setting_form($options = array())
{
	extract($options);

	$types = array(
		__('Mail', 'smtp-mail'),
		__('SMTP', 'smtp-mail'),
		__('SendGrid', 'smtp-mail'),
	);
	?>
	<form action="options.php" method="post">
		<?php settings_fields('smtpmail_settings'); ?>
		<input type="hidden" name="smtpmail_options[time]" value="<?php echo esc_attr($time); ?>" />
		<table class="form-table">
			<tr>
				<th scope="row">
					<label for="smtpmail_options_isSMTP"><?php _e('Mail type', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[isSMTP]" id="smtpmail_options_isSMTP"><?php
						foreach ($types as $i => $value) {
							echo '<option value="' . $i . '" ' . ($isSMTP == $i ? " selected" : "") . '>' . $value . '</option>';
						}
					?></select>
				</td>
			</tr>
			<tr class="sendgrid-setting<?php echo esc_attr($isSMTP != 2 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_sendgrid_api_key"><?php _e('SendGrid API Key', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($sendgrid_api_key); ?>" type="text" name="smtpmail_options[sendgrid_api_key]" id="smtpmail_options_sendgrid_api_key" class="regular-text ltr" />
					<span><?php _e('API key', 'smtp-mail'); ?></span>
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_SMTPSecure"><?php _e('SMTP Secure', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[SMTPSecure]" id="smtpmail_options_SMTPSecure">
						<option value="">None</option>
						<option value="ssl" <?php echo ($SMTPSecure == 'ssl' ? " selected" : ""); ?>>SSL</option>
						<option value="tls" <?php echo ($SMTPSecure == 'tls' ? " selected" : ""); ?>>TLS</option>
					</select>
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_SMTPAuth"><?php _e('SMTP Auth', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[SMTPAuth]" id="smtpmail_options_SMTPAuth">
						<option value="0"><?php _e('No', 'smtp-mail'); ?></option>
						<option value="1" <?php echo ($SMTPAuth ? " selected" : ""); ?>><?php _e('Yes', 'smtp-mail'); ?></option>
					</select>
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_Port"><?php _e('Port', 'smtp-mail'); ?>: (25, 465, 587)</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($Port); ?>" type="number" name="smtpmail_options[Port]" id="smtpmail_options_Port" class="regular-text ltr" placeholder="25, 465, 587" />
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_Host"><?php _e('Host (Server)', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($Host); ?>" type="text" name="smtpmail_options[Host]" id="smtpmail_options_Host" class="regular-text ltr" placeholder="mail.domain.com" />
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_Username"><?php _e('Username', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($Username); ?>" type="text" name="smtpmail_options[Username]" id="smtpmail_options_Username" class="regular-text ltr" placeholder="username or noreply@domain.com" />
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_Password"><?php _e('Password', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($Password); ?>" type="password" name="smtpmail_options[Password]" id="smtpmail_options_Password" class="regular-text ltr" placeholder="pass@2371627" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="smtpmail_options_From"><?php _e('From Email', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($From); ?>" type="email" name="smtpmail_options[From]" id="smtpmail_options_From" class="regular-text ltr" placeholder="noreply@domain.com" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="smtpmail_options_FromName"><?php _e('From Name', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input value="<?php echo esc_attr($FromName); ?>" type="text" name="smtpmail_options[FromName]" id="smtpmail_options_FromName" class="regular-text ltr" placeholder="<?php _e('Site name', 'smtp-mail'); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="smtpmail_options_IsHTML"><?php _e('Use HTML content', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[IsHTML]" id="smtpmail_options_IsHTML">
						<option value="0"><?php _e('No', 'smtp-mail'); ?></option>
						<option value="1" <?php echo ($IsHTML ? " selected" : ""); ?>><?php _e('Yes', 'smtp-mail'); ?></option>
					</select>
				</td>
			</tr>
			<tr class="smtp-setting<?php echo esc_attr($isSMTP != 1 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_SMTPAutoTLS"><?php _e('SMTP Auto TLS', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[SMTPAutoTLS]" id="smtpmail_options_SMTPAutoTLS">
						<option value="0"><?php _e('No', 'smtp-mail'); ?></option>
						<option value="1" <?php echo ($SMTPAutoTLS ? " selected" : ""); ?>><?php _e('Yes', 'smtp-mail'); ?></option>
					</select>
				</td>
			</tr>
			<tr class="unsendgrid-setting<?php echo esc_attr($isSMTP == 2 ? " hidden" : "") ?>">
				<th scope="row">
					<label for="smtpmail_options_SMTPDebug"><?php _e('SMTP Debug', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[SMTPDebug]" id="smtpmail_options_SMTPDebug">
						<option value="0"><?php _e('No', 'smtp-mail'); ?></option>
						<option value="1" <?php echo ($SMTPDebug == 1 ? " selected" : ""); ?>>Errors, Messages</option>
						<option value="2" <?php echo ($SMTPDebug == 2 ? " selected" : ""); ?>>Messages only</option>
					</select>
				</td>
			</tr>
			<tr style="border-top: 1px solid #ddd">
				<th scope="row">
					<label for="smtpmail_options_save_data"><?php _e('Save Data SendMail', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[save_data]" id="smtpmail_options_save_data">
						<option value="0"><?php _e('No', 'smtp-mail'); ?></option>
						<option value="1" <?php echo ($save_data ? " selected" : ""); ?>><?php _e('Yes', 'smtp-mail'); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label for="smtpmail_options_anti_spam_form"><?php _e('Anti-spam forms', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<select name="smtpmail_options[anti_spam_form]" id="smtpmail_options_anti_spam_form">
						<option value="0"><?php _e('No', 'smtp-mail'); ?></option>
						<option value="1" <?php echo ($anti_spam_form ? " selected" : ""); ?>><?php _e('Yes', 'smtp-mail'); ?></option>
					</select>
					<br />
					<em><?php _e('The system will check all forms of the website. When the guest enters at least one value, the system will allow the form to be submitted.', 'smtp-mail'); ?></em>
				</td>
			</tr>
			<tr style="border-top: 1px solid #ddd">
				<th colspan=2>
					<input type="submit" class="button button-primary" value="<?php _e('Save', 'smtp-mail'); ?>" />
				</th>
			</tr>
		</table>
	</form>
<?php
}

function smtpmail_sendmail_form()
{
	$lips = array('Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'Ut fermentum magna quis mauris dictum, in elementum diam maximus.', 'Praesent pulvinar erat in velit tincidunt, quis fermentum mauris maximus.', 'Cras vulputate metus id ornare vehicula.', 'Morbi ultricies neque a rutrum euismod.', 'Sed varius nisi sit amet nunc tincidunt facilisis.', 'Maecenas consequat tellus sit amet massa facilisis tincidunt.', 'Etiam at eros congue, feugiat nisl commodo, interdum metus.', 'Duis iaculis massa sed nisl euismod sollicitudin.', 'Ut vestibulum ex sit amet odio eleifend bibendum.', 'Nam ultrices dolor vel ipsum aliquam venenatis.', 'Fusce vel lacus ac justo sollicitudin vestibulum.', 'Nullam vel lectus quis libero tempus pharetra maximus sed ipsum.', 'Nam non arcu sed dui blandit varius eget ac arcu.', 'Aliquam congue felis in efficitur vulputate.', 'Curabitur venenatis mauris eget tristique iaculis.', 'Donec in lectus interdum, rutrum massa nec, malesuada diam.', 'Mauris tempus odio in ultrices iaculis.', 'Quisque vitae arcu ornare, volutpat eros porttitor, rutrum purus.', 'Integer ac mauris rutrum erat luctus consequat.', 'Sed non nisl nec nibh aliquet dapibus.', 'Morbi sit amet lacus lacinia, pulvinar quam et, hendrerit diam.', 'Nunc dapibus lacus id vehicula tempus.', 'Pellentesque sit amet quam faucibus lacus cursus convallis at sed ipsum.', 'Nam consectetur massa a semper eleifend.', 'Proin fringilla ante ut dui aliquam venenatis.', 'Phasellus accumsan ante sit amet velit imperdiet efficitur.', 'Vivamus posuere arcu non sem cursus commodo.');

	$current_user = wp_get_current_user();
	if (!($current_user instanceof WP_User)) return '';

	$name 		= sanitize_text_field(isset($_POST['name']) ? $_POST['name'] : $current_user->display_name);
	$email 		= sanitize_email(isset($_POST['email']) ? $_POST['email'] : $current_user->user_email);
	$subject 	= sanitize_text_field(isset($_POST['subject']) ? $_POST['subject'] : get_bloginfo('name') . ' test at ' . date('Y-m-d H:i:s'));
	$message 	= sanitize_text_field(isset($_POST['message']) ? $_POST['message'] : $lips[rand(0, count($lips) - 1)]);

?>
	<form action="<?php echo smtpmail_setting_url(['tab' => 'test']) ?>" method="post">
		<table class="form-table">
			<tr>
				<th scope="row">
					<label><?php _e('Name', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input name="name" type="text" value="<?php echo esc_attr($name); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e('Email', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input name="email" type="email" value="<?php echo esc_attr($email); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e('Subject', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<input name="subject" type="text" value="<?php echo esc_attr($subject); ?>" class="regular-text ltr" />
				</td>
			</tr>
			<tr>
				<th scope="row">
					<label><?php _e('Message', 'smtp-mail'); ?>:</label>
				</th>
				<td>
					<textarea name="message" id="message" rows="8" cols="40" class="large-text code"><?php echo esc_attr($message); ?></textarea>
				</td>
			</tr>
			<tr style="border-top: 1px solid #ddd">
				<td colspan=2>
					<input type="submit" name="send_test" id="send_test" class="button button-primary" value="<?php _e('Send', 'smtp-mail'); ?>">
				</td>
			</tr>
		</table>
	</form>
	<?php
}

function smtpmail_data_list($options = array())
{
	if (smtpmail_include('data-list-table.php')) {
		smtpmail_render_customer_list_page($options);
	}
}

function smtpmail_admin_notice()
{
	$pagenow 	= sanitize_text_field(isset($GLOBALS['pagenow']) ? $GLOBALS['pagenow'] : '');
	$send_test 	= sanitize_text_field(isset($_POST['send_test']) ? $_POST['send_test'] : '');

	if ($pagenow != 'options-general.php' || count($_POST) == 0 || $send_test != 'Send') return '';

	$name 		= sanitize_text_field(isset($_POST['name']) ? $_POST['name'] : '');
	$email 		= sanitize_email(isset($_POST['email']) ? $_POST['email'] : '');
	$subject 	= sanitize_text_field(isset($_POST['subject']) ? $_POST['subject'] : '');
	$message 	= wp_kses_post(isset($_POST['message']) ? $_POST['message'] : '');

	if ($email != '') {
		if ($name == '') {
			$name = ucwords(array_shift(explode('@', $email)));
		}
		$headers[] = "From: $name <$email>";
	} else {
		$headers[] = 'From: ' . get_bloginfo('name') . ' <noreply@' . $_SERVER['HTTP_HOST'] . '>';
	}

	if (wp_mail($email, $subject, $message, $headers)) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php _e('Send mail successful!', 'smtp-mail'); ?></p>
		</div>
	<?php else : ?>
		<div class="notice notice-error">
			<p><?php _e('Send mail error!', 'smtp-mail'); ?></p>
		</div>
<?php endif;
}
add_action('admin_notices', 'smtpmail_admin_notice');

global $PBOne;
if( isset($PBOne) && $PBOne && $PBOne->check_plugin_active('contact-form-7/wp-contact-form-7.php') ) {
	smtpmail_include('contact-form-7-extensions.php');
}
