<?php
/*
Plugin Name: Shoestrap Dashboard
Plugin URI: http://wpmu.io
Description: Licenses and updates for Shoestrap themes and plugins.
Version: 1.0
Author: Aristeides Stathopoulos
Author URI:  http://aristeides.com
Software Licensing: a0abaffea901e14bf9b20eada692f9fe
Software Licensing URL: http://shoestrap.org
Software Licensing Description: The Shoestrap Updater plugin provides updating functions to all other Shoestrap themes and plugins. Free plugins have a license key auto-populated and auto-activated. This makes the field non-editable as there is no need to enter your own license key. If you have purchased a premium plugin or theme, enter your license key in the provided field and it will be activated as soon as you hit the "Save" button.
*/


/*
 * Retrieve our custom headers.
 * These will be used by the updater later on to detect plugin properties.
 */
function shoestrap_licensing_file_header_license_info( $headers ) {
    $headers[] = 'Software Licensing';
	$headers[] = 'Software Licensing Description';
	$headers[] = 'Software Licensing URL';

	return $headers;
}
add_filter( 'extra_plugin_headers', 'shoestrap_licensing_file_header_license_info' );

// Load the plugin updater class
// This class is provided by Easy Digital Downloads Software Licensing.
// More info can be found here: https://easydigitaldownloads.com/extensions/software-licensing/
if( !class_exists( 'EDD_SL_Plugin_Updater' ) )
	include_once( dirname( __FILE__ ) . '/includes/classes/EDD_SL_Plugin_Updater.php' );

// Load the theme updater class
// This class is provided by Easy Digital Downloads Software Licensing.
// More info can be found here: https://easydigitaldownloads.com/extensions/software-licensing/
if( !class_exists( 'EDD_SL_Theme_Updater' ) )
	include_once( dirname( __FILE__ ) . '/includes/classes/EDD_SL_Theme_Updater.php' );

// Load our custom, global updater class
// This is simply a wrapper for the EDD Software Licensing classes, with some additional functionality.
if( !class_exists( 'Shoestrap_Updater' ) )
	include_once( dirname( __FILE__ ) . '/includes/classes/Shoestrap_Updater.php' );

/*
 * Add the menu item on the dashboard
 */
if ( !function_exists( 'shoestrap_dashboard_license_menu' ) ) :
	function shoestrap_dashboard_license_menu() {
		add_plugins_page( 'Shoestrap Dashboard', 'Shoestrap Dashboard', 'manage_options', 'shoestrap-dashboard', 'shoestrap_dashboard_license_page' );
	}
endif;
add_action('admin_menu', 'shoestrap_dashboard_license_menu');

/*
 * Add the wrapper for the admin page.
 * Other plugins and themes can add their content in this page
 * using the 'shoestrap_dashboard_form_content' action.
 */
function shoestrap_dashboard_license_page() { ?>
	<div class="wrap">
		<?php do_action( 'shoestrap_dashboard_form_content' ); ?>
	</div>
	<?php
}


// Add any actions needed here.
do_action( 'shoestrap_updater' );


/*
 * Detects any plugins that have the appropriate header
 * then initializes an updater for each plugin that needs it.
 * Uses the header information defined in shoestrap_licensing_file_header_license_info()
 */
function shoestrap_updater_detect_plugins() {
	// make sure that the 'get_plugins' function exists
	if ( !function_exists( 'get_plugins' ) )
		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	// Get an array of all the plugins
	$plugins = get_plugins();

	foreach ( $plugins as $plugin => $headers ) {
		$field_name = preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $headers['Name'] ) ) );
		$license_option = get_option( $field_name . '_key' );

		// Process the updater if the "Software Licensing" header is NOT empty
		if ( !empty( $headers['Software Licensing'] ) ) {
			$description    = ( $headers['Software Licensing Description'] ) ? $headers['Software Licensing Description'] : '';

			// If 'Software Licensing' is set to something other than 'true',
			// assume that a default license is specified.
			$single  = ( $headers['Software Licensing'] != 'true' ) ? true : false;
			if ( $single && $license_option != $headers['Software Licensing'] ) {
				update_option( $field_name . '_key', $headers['Software Licensing'] );
				$license_option = $headers['Software Licensing'];
			}

			$remote_api_url = !empty( $headers['Software Licensing URL'] ) ? $headers['Software Licensing URL'] : false;

			$license = ( isset( $license_option ) ) ? trim( $license_option ) : '';
			$author  = sanitize_text_field( $headers['Author'] );

			// Populate the updater arguments
			$args = array(
				'file'           => WP_PLUGIN_DIR . '/' . $plugin,
				'item_name'      => $headers['Name'],
				'license'        => $license,
				'version'        => $headers['Version'],
				'author'         => $author,
				'mode'           => 'plugin',
				'title'          => $headers['Name'],
				'field_name'     => $field_name,
				'description'    => $description,
				'single_license' => $single
			);

			// If an API URL has been specified, override the default here.
			if ( $remote_api_url )
				$args['remote_api_url'] = $remote_api_url;

			// Run the updater
			$updater = new Shoestrap_Updater( $args );
		}
	}
}
add_action( 'plugins_loaded', 'shoestrap_updater_detect_plugins' );


/*
 * Detects any plugins that have the appropriate header
 * then initializes an updater for each plugin that needs it.
 * Uses the header information defined in shoestrap_licensing_file_header_license_info()
 */
function shoestrap_updater_detect_themes() {
	// make sure that the 'get_plugins' function exists
	if ( !function_exists( 'wp_get_themes' ) )
		require_once( ABSPATH . 'wp-admin/includes/theme.php' );

	// Get an array of all the plugins
	$themes      = wp_get_themes();
	$themes_root = get_theme_root();
	// print_r($themes);

	// The list of custom headers we're using
	$extra_headers = array(
		'Software Licensing'             => 'Software Licensing',
		'Software Licensing Description' => 'Software Licensing Description',
		'Software Licensing URL'         => 'Software Licensing URL',
	);

	foreach ( $themes as $theme => $headers ) {
		$theme_file     = $themes_root . '/' . $theme . '/style.css';
		$file_headers   = get_file_data( $theme_file, $extra_headers );
		$field_name     = preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $headers['Name'] ) ) );
		$license_option = get_option( $field_name . '_key' );

		// Process the updater if the "Software Licensing" header is NOT empty
		if ( !empty( $file_headers['Software Licensing'] ) ) {
			$description = ( !empty( $file_headers['Software Licensing Description'] ) ) ? $file_headers['Software Licensing Description'] : '';

			// If 'Software Licensing' is set to something other than 'true',
			// assume that a default license is specified.
			$single  = ( trim( $file_headers['Software Licensing'] ) != 'true' ) ? true : false;
			if ( $single && $license_option != $file_headers['Software Licensing'] ) {
				update_option( $field_name . '_key', $file_headers['Software Licensing'] );
				$license_option = $file_headers['Software Licensing'];
			}

			$remote_api_url = !empty( $file_headers['Software Licensing URL'] ) ? $file_headers['Software Licensing URL'] : false;

			$license = ( isset( $license_option ) ) ? trim( $license_option ) : $license_option;
			// Sanitize the authors fiels (get rid of the link to the site).
			$author  = sanitize_text_field( $headers['Author'] );

			// Populate the updater arguments
			$args = array(
				'file'           => $theme_file,
				'item_name'      => $headers['Name'],
				'license'        => $license,
				'version'        => $headers['Version'],
				'author'         => $author,
				'mode'           => 'theme',
				'title'          => $headers['Name'],
				'field_name'     => $field_name,
				'description'    => $description,
				'single_license' => $single
			);

			// If an API URL has been specified, override the default here.
			if ( $remote_api_url )
				$args['remote_api_url'] = $remote_api_url;

			// Run the updater
			$updater = new Shoestrap_Updater( $args );
		}
	}
}
add_action( 'plugins_loaded', 'shoestrap_updater_detect_themes' );