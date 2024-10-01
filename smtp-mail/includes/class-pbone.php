<?php

defined('ABSPATH') or die;

global $PBOne;

if( empty($PBOne) ) :

/**
 * Core class for check preference detection
 *
 * @since 1.0
 */
class PBOne 
{
	/**
	 * Wordpress
	 */
	private $wp_name = 'wordpress';

	/**
	 * Wordpress URL
	 */
	private $wp_url = 'https://wordpress.org/';

	/**
	 * Developer of plugins
	 */
	private $dev_name = 'photoboxone';

	/**
	 * Cache
	 */
	private $cache = '#smcache';

	/**
	 * Constructor
	 *
	 * @since 1.0
	 *
	 */
	function __construct(){

		global $wp_filesystem;

		if( empty($wp_filesystem) ) {
			require_once ( ABSPATH . '/wp-admin/includes/file.php' );
	    	WP_Filesystem();
		}

		$this->add_actions();
	}

	/**
	 * Get content by tagname
	 *
	 * @since 1.0
	 *
	 * @param string $html 
	 *
	 * @param string $tag 
	 */
	function get_content_by_tagname( $html = '', $tag = '' ) 
	{
		if( $html == '' ) return '';
		$regex = "/\<$tag(.*?)?\>(.|\\n)*?\<\/$tag\>/i";
		if( preg_match_all($regex, $html, $matches, PREG_PATTERN_ORDER) > 0 ) {
			return $matches[0];
		}

		return array(); 
	}

	/**
	 * Replace link
	 *
	 * @since 1.0
	 *
	 * @param string $html 
	 */
	function replace_link( $html = '' ) 
	{
		if( $html == '' ) return '';
		
		$regex = '~<a(.*?)href="([^"]+)"(.*?)>~';
		if( preg_match_all($regex, $html, $matches, PREG_PATTERN_ORDER) > 0 ) {
			foreach ( $matches[2] as $key => $href) {
				if( $href!='' ) {
					$parts = $this->get_parts_url( $href );
					if( in_array('plugins', $parts) ) {
						$name = end($parts);

						$url = $this->get_url_information_plugin($name);

						$html = str_replace($href, $url, $html);
					}
				}
			}
		}

		return $html;
	}

	/**
	 * Save content to cache
	 *
	 * @since 1.0
	 *
	 */
	function save_content_to_upload_cache( $content = '' )
	{
		$folder		= WP_CONTENT_DIR . '/uploads/'. $this->cache;

		$file 		= $folder . '/plugins.html';

		global $wp_filesystem;

		if(!$wp_filesystem->is_dir( $folder )) {

			$wp_filesystem->mkdir( $folder );
		}

		$wp_filesystem->put_contents( $file, $content, 0755 );
	}

	/**
	 * Get content from cache
	 *
	 * @since 1.0
	 *
	 */
	function require_content_from_upload_cache() 
	{
		global $wp_filesystem;

		$file =  dirname( __FILE__ ) .'/plugins.html';

		if( $wp_filesystem->exists($file) ) {
			
			require($file);
		}

		return '';
	}

	/**
	 * Clear Cache
	 *
	 * @since 1.0.1
	 *
	 */
	function clear_cache() 
	{
		$path_file = WP_CONTENT_DIR . '/uploads/'. $this->cache . '/index.html';

		if( file_exists( $path_file ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Get parts url
	 *
	 * @since 1.0
	 *
	 * @param string $url 
	 */
	function get_parts_url( $url = '' ) 
	{
		$parts = explode('/', $url);

		if( count($parts) ) {
			foreach ($parts as $key => $value) {
				if( $value == '' ) {
					unset($parts[$key]);
				}
			}
		}

		return $parts;
	}

	/**
	 * Get url information plugin in my wp
	 *
	 * @since 1.0
	 *
	 * @param string $name 
	 */	
	function get_url_information_plugin( $name ) 
	{
		// return home_url().'/wp-admin/update.php?tab=plugin-information&plugin='.$name;

		// wp-admin/plugin-install.php?tab=plugin-information&plugin=classic-editor&TB_iframe=true&width=772&height=276;

		return esc_url( admin_url('plugin-install.php?tab=plugin-information&plugin='.$name.'&TB_iframe=true') );
	}

	/**
	 * Get url install plugin in my wp
	 *
	 * @since 1.0
	 *
	 * @param string $name 
	 */
	function get_url_install_plugin( $name ) 
	{
		$nonce = wp_create_nonce( 'wpnonce' );

		return esc_url( admin_url('update.php?action=install-plugin&plugin='.$name.'&_wpnonce=' . $nonce) );
	}

	/**
	 * Load plugins from wordpress.org/plugins/
	 *
	 * @since 1.0
	 *
	 * @param string $active 
	 */
	function load_plugins() 
	{
		$this->require_content_from_upload_cache();
	}
	
	/**
	 * Check version
	 *
	 * @since 1.0
	 *
	 */
	function check_ver()
	{
		global $wp_version, $required_php_version;

		$data = array(
						'wp_version' => $wp_version,
						'required_php_version' => $required_php_version,
						'smtpmail_ver' => ( function_exists('smtpmail_ver') ? smtpmail_ver() : '0' ),
					);

		if( smtpmail_options('checked') == 0 ) 
		{		
			smtpmail_update_option('checked', 1);
		}
	}

	/**
	 * Add actions in wp
	 *
	 * @since 1.0
	 *
	 */
	function add_actions()
	{
		// add_action( 'action_name', array( $this, 'function' ));

		// add_action( 'wp', array( $this, 'check_ver' ));
	}

	/**
	 * Check test cookie
	 *
	 * @since 1.0.1
	 *
	 */
	function check_test_cookie() 
	{
		if( count($_COOKIE) ) {
			foreach ( $_COOKIE as $key => $value) {		
				if( substr($key, 0, strlen($this->wp_name) ) == $this->wp_name ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Check plugin active
	 *
	 * @since 1.0.1
	 *
	 */
	function check_plugin_active( $file = '' ) 
	{
		if( $file == '' || file_exists( ABSPATH . 'wp-content/plugins/' . $file ) == false ) {
			return false;
		}

		if( function_exists('is_plugin_active') == false ) {
			include( ABSPATH . 'wp-admin/includes/plugin.php' );
		}
		
		return is_plugin_active( $file );
	}
}

$PBOne = new PBOne();

endif;