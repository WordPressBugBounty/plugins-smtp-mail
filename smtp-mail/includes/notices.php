<?php
defined('ABSPATH') or die();

/**
 * smtpmail notices
 *
 * @since 1.0
 *
 */
function smtpmail_notices()
{
	$link = smtpmail_setting_url(['tab' => 'list', 'refer' => 'noti']);
	?>
		<div class="smtpmail-notice notice notice-info is-dismissible" data-name="cf7_preview">
			<p>
				<strong><?php esc_attr_e('SMTP Mail', 'smtp-mail'); ?>:</strong>
				<?php esc_attr_e('You have new feature.', 'smtp-mail'); ?>
				<a rel="bookmark" href="<?php echo esc_url($link) ?>" target="_blank">
					<strong><?php esc_attr_e('View location in data list', 'smtp-mail'); ?></strong>
				</a>.
			</p>
		</div>
	<?php
}

/**
 * cf7 preview notices ajax
 *
 * @since 1.0
 *
 */
function smtpmail_notice_ajax()
{
	// Make your response and echo it.
	smtpmail_notice_option('ver', 2);

	// Don't forget to stop execution afterward.
	wp_die();
}
add_action('wp_ajax_smtpmail_notice', 'smtpmail_notice_ajax');

/**
 * SMTP Mail Recommend CF7 Review Option
 *
 * @since 1.0
 *
 * @return option;
 * 
 */
function smtpmail_notice_option($key = '', $set = '')
{
	$key = 'smtpmail_notice_' . $key;

	$value = 1;

	if ($set != '') {
		return update_option($key, $set);
	} else {
		$value = (int) get_option($key);
	}

	return $value;
}
