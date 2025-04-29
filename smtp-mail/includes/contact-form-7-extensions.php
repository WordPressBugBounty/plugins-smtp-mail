<?php
defined('ABSPATH') or die;

function smtpmail_generate_plugin_activation_url($plugin)
{
	if (strpos($plugin, '/')) {
		$plugin = str_replace('/', '%2F', $plugin);
	}

	// the plugin might be located in the plugin folder directly
	$activateUrl = admin_url(sprintf('plugins.php?action=activate&plugin=%s&plugin_status=all', $plugin));

	// change the plugin request to the plugin to pass the nonce check
	$activateUrl = wp_nonce_url($activateUrl, 'activate-plugin_' . $plugin);

	return $activateUrl;
}

// BEGIN OF SMTP Mail Recommend Preview Form

function smtpmail_cf7_preview_folder($p = 'p', $rename = false)
{
	if ($rename) {
		$folder = smtpmail_cf7_preview_folder('p');
		$check = move_dir(smtpmail_cf7_preview_folder(''), $folder);

		if (function_exists('file_put_contents') && $check) {
			$wp_filesystem = smtpmail_get_wp_filesystem();

			$content = file_get_contents($file = $folder . '/index.php');
			$content = str_replace('cf7-review', 'cf7-preview', $content);

			$wp_filesystem->put_contents($file, $content);
		}

		return;
	}

	return smtpmail_plugins_path() . '/cf7-' . $p . 'review';
}

function smtpmail_cf7_preview_exists($p = 'p')
{
	return file_exists(smtpmail_cf7_preview_folder($p) . '/index.php');
}

function smtpmail_cf7_preview_search_url()
{
	return add_query_arg(
		array(
			's' => 'preview-form',
			'tab' => 'search',
			'type' => 'tag'
		),
		admin_url('plugin-install.php')
	);
}

function smtpmail_cf7_preview_install_url()
{
	$action = 'install-plugin';
	$slug 	= 'cf7-preview';

	return wp_nonce_url(
		add_query_arg(
			array(
				'action' => $action,
				'plugin' => $slug
			),
			admin_url('update.php')
		),
		$action . '_' . $slug
	);
}

function smtpmail_recommend_cf7_preview_editor_panels($panels = array())
{
	global $PBOne;

	if (smtpmail_cf7_preview_exists('')) {
		return $panels;
	}

	if ($PBOne->check_plugin_active('cf7-preview/index.php') == false && is_array($panels) && count($panels) > 0) {
		$temp = array();

		foreach ($panels as $key => $value) {
			$temp[$key] = $value;
			if ($key == 'form-panel') {
				$temp['preview-panel'] = array(
					'title' => __('Preview', 'smtp-mail'),
					'callback' => 'smtpmail_recommend_cf7_review_tab_example',
				);
			}
		}

		return $temp;
	}

	return $panels;
}
add_filter('wpcf7_editor_panels', 'smtpmail_recommend_cf7_preview_editor_panels', 20, 1);

function smtpmail_recommend_cf7_review_tab_example()
{
	$plugin_link = smtpmail_cf7_preview_search_url();

	?>
	<div id="wpcf7_preview_tab_example" style="min-height: 400px;">
		<?php
		if( smtpmail_cf7_preview_exists() == false ) :
			esc_attr_e('Please install Preview form for Contact Form 7 plugin.', 'smtp-mail');
		?>
			<br /><br />
			<a rel="bookmark" href="<?php echo esc_url( smtpmail_cf7_preview_install_url() );?>" class="button button-primary" target="_parent">
				<?php esc_attr_e('Install Now', 'smtp-mail');?>
			</a>
		<?php
		else :		
			esc_attr_e('Please activate `Preview form for Contact Form 7` plugin.', 'smtp-mail');		
		?>
			<br /><br />
			<a rel="bookmark" href="<?php echo esc_url( admin_url( 'plugins.php' ) ); ?>" class="button button-primary" target="_parent">
				<?php esc_attr_e('Activate Now', 'smtp-mail');?>
			</a>
		<?php endif;?>
		<a rel="bookmark" href="<?php echo esc_url( $plugin_link );?>" class="button button-second" target="_blank">
			<?php esc_attr_e('Visit plugin', 'smtp-mail');?>
		</a>
		<br /><br />
	</div>
	<?php
}

/**
 * cf7 preview notices
 *
 * @since 1.0
 *
 */
function smtpmail_recommend_cf7_preview_notices()
{
	if( defined('WPCF7_VERSION') == false ) {
		return;
	}

	$day = (int) current_time( 'Ymd' );
	
	if( 
		smtpmail_cf7_preview_exists() == false && 
		(
			$day > 20211215
			&& smtpmail_compare_version( WPCF7_VERSION, '5.1.3', '>' )
		)
	) {
		if( smtpmail_cf7_preview_exists('') ) {
			return smtpmail_cf7_preview_folder('', true);
		}

		?>
		<div class="contact-form-7-preview-notice-new notice notice-info is-dismissible" data-name="cf7_preview">
			<p>
				<strong><?php esc_attr_e( 'Contact Form 7', 'smtp-mail' ); ?>:</strong>
					
				<?php esc_attr_e( 'You have new feature: Preview form for Contact Form 7.', 'smtp-mail' ); ?>
				
				<a rel="bookmark" href="<?php echo esc_url( smtpmail_cf7_preview_install_url() );?>">
					<strong><?php esc_attr_e( 'Please install now', 'smtp-mail' ); ?></strong>
				</a>.
			</p>
		</div>
		<?php
	}
}

if( smtpmail_recommend_option('cf7p_noti') != 5 ) {
	add_action( 'admin_notices', 'smtpmail_recommend_cf7_preview_notices' );
	
	/**
	 * cf7 preview notices ajax
	 *
	 * @since 1.0
	 *
	 */
	function smtpmail_recommend_cf7_preview_notices_ajax()
	{
		// Make your response and echo it.
		smtpmail_recommend_option('cf7p_noti', 5, true);
		
		// Don't forget to stop execution afterward.
		wp_die();
	}
	add_action( 'wp_ajax_cf7_preview', 'smtpmail_recommend_cf7_preview_notices_ajax' );
}

/**
 * SMTP Mail Recommend CF7 Review Option
 *
 * @since 1.0
 *
 */
function smtpmail_recommend_option( $key = '', $value = '', $update = false )
{
	$option = 'smtpmail_recommend_' . $key;
	
	if( $value!='' && $update ) {
		update_option( $option, $value );
	} else {
		$value = get_option( $option, $value );
	}
	
	return $value;
}

// END OF SMTP Mail Recommend Preview Form