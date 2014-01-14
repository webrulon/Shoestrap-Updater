<?php

class Shoestrap_Updater {
	private $remote_api_url;
	private $item_name;
	private $license;
	private $version;
	private $author;
	private $mode;
	private $title;
	private $field_name;
	private $description;
	private $file;
	private $single_license;


	function __construct( $args = array() ) {
		$args = wp_parse_args( $args, array(
			'remote_api_url' => 'http://shoestrap.org',
			'item_name'      => '',
			'license'        => '',
			'version'        => '',
			'author'         => 'Aristeides Stathopoulos',
			'mode'           => 'plugin',
			'title'          => '',
			'field_name'     => 'field_name',
			'description'    => 'description',
			'file'           => 'file',
			'single_license' => false
		) );
		extract( $args );

		$this->license        = $license;
		$this->item_name      = $item_name;
		$this->version        = $version;
		$this->author         = $author;
		$this->remote_api_url = $remote_api_url;
		$this->mode           = $mode;
		$this->title          = $title;
		$this->field_name     = $field_name;
		$this->description    = $description;
		$this->file           = $file;
		$this->single_license = $single_license;


		add_action( 'admin_init', array( &$this, 'setup_updater' ) );
		add_action( 'admin_init', array( &$this, 'register_option' ) );
		add_action( 'shoestrap_dashboard_form_content', array( &$this, 'license_form' ) );

		add_action( 'admin_init', array( &$this, 'process_license' ) );

	}


	function setup_updater() {
		// Setup the updater

		if ( $this->mode == 'theme' ) {
// 			print_r(
// 								array(
// 					'remote_api_url'  => $this->remote_api_url,
// 					'version'         => $this->version,
// 					'license'         => $this->license,
// 					'item_name'       => $this->item_name,
// 					'author'          => $this->author
// 				)
// );
			$edd_updater = new EDD_SL_Theme_Updater(
				array(
					'remote_api_url'  => $this->remote_api_url,
					'version'         => $this->version,
					'license'         => $this->license,
					'item_name'       => $this->item_name,
					'author'          => $this->author
				)
			);
		} else {
			$edd_updater = new EDD_SL_Plugin_Updater(
				$this->remote_api_url,
				$this->file,
				array(
					'version'   => $this->version,
					'license'   => $this->license,
					'item_name' => $this->item_name,
					'author'    => $this->author
				)
			);
		}
	}


	/*
	 * The license form that is added in the admin page.
	 */
	function license_form() { ?>
		<form method="post" action="options.php">
			<?php $status 	= get_option( $this->field_name . '_status' ); ?>
			<?php $disabled = ( $this->single_license ) ? ' disabled' : ''; ?>
			<?php $license  = $this->license; ?>

			<h2><?php echo $this->title ?></h2>
			<?php settings_fields( $this->field_name ); ?>
			<?php echo $this->description; ?>

			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top"><?php _e( 'License Key', 'shoestrap_updater' ); ?></th>
						<td>
							<input id="<?php echo $this->field_name . '_key'; ?>" name="<?php echo $this->field_name . '_key'; ?>" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>"<?php echo $disabled; ?> />
							<?php if ( !$disabled ) : ?>
								<label class="description" for="<?php echo $this->field_name . '_key'; ?>"><?php _e( 'Enter your license key', 'shoestrap_updater' ); ?></label>
							<?php endif; ?>
						</td>
						<?php if ( !$disabled ) : ?>

							<td>
								<?php wp_nonce_field( $this->field_name . '_nonce', $this->field_name . '_nonce' ); ?>
								<?php submit_button(); ?>
							</td>

							<td>
								<?php if( $status !== false && $status == 'valid' ) { ?>
									<?php wp_nonce_field( $this->field_name . '_nonce', $this->field_name . '_nonce' ); ?>
									<input type="submit" class="button-secondary" name="<?php echo $this->field_name; ?>_deactivate" value="<?php _e('Deactivate License'); ?>"/>
								<?php } else { ?>
									<?php wp_nonce_field( $this->field_name . '_nonce', $this->field_name . '_nonce' ); ?>
									<input type="submit" class="button-secondary" name="<?php echo $this->field_name; ?>_activate" value="<?php _e('Activate License'); ?>"/>
								<?php } ?>
							</td>
						<?php endif; ?>
						<td style="font-size:2em;line-height:0.5em;text-align:right;">
							<?php if( $status !== false && $status == 'valid' ) : ?>
								<span style="color:green;">&#149;</span>
							<?php else : ?>
								<span style="color:red;">&#149;</span>
							<?php endif; ?>
						</td>
					</tr>
				</tbody>
			</table>
		</form>
		<hr>
		<?php
	}


	/*
	 * Register the key setting inthe db
	 */
	function register_option() {
		// creates our settings in the options table
		register_setting( $this->field_name, $this->field_name . '_key', array( &$this, 'license_field_callback' ) );
	}


	/*
	 * When a new key is entered, make sure that the license status is reset
	 */
	function license_field_callback( $new ) {
		$old = get_option( $this->field_name . '_key' );
		if( $old && $old != $new )
			delete_option( $this->field_name . '_status' );

		return $new;
	}


	/*
	 * Process the license
	 */
	function process_license() {
		global $wp_version;
		$process = false;
		$action  = 'activate_license';
		
		if ( $this->single_license ) {
			// This is an auto-activated license so keep processing it.
			// do not process if license is already valid.
			if ( get_option( $this->field_name . '_status' ) != 'valid' )
				$process = true;

		} else {
			// listen for our activate button to be clicked
			if ( isset( $_POST[$this->field_name . '_activate'] ) && check_admin_referer( $this->field_name . '_nonce', $this->field_name . '_nonce' ) )
				$process = true;

			// do not process if license is already valid.
			if ( get_option( $this->field_name . '_status' ) == 'valid' )
				$process = false;

			// If he just hit the de-activate button, keep procesing and set action to 'deactivate_license'.
			if ( isset( $_POST[$this->field_name . '_deactivate'] ) && check_admin_referer( $this->field_name . '_nonce', $this->field_name . '_nonce' ) ) {
				$process = true;
				$action  = 'deactivate_license';
			}
		}


		if ( !$process )
			return;

		// retrieve the license from the database
		$license = trim( get_option( $this->field_name . '_key' ) );

		// data to send in our API request
		$api_params = array(
			'edd_action'=> $action,
			'license' 	=> $license,
			'item_name' => urlencode( $this->item_name )
		);

		// Call the custom API.
		$response = wp_remote_get(
			add_query_arg( $api_params, $this->remote_api_url ),
			array( 'timeout' => 15, 'sslverify' => false )
		);

		// make sure the response came back okay
		if ( is_wp_error( $response ) )
			return false;

		// decode the license data
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		update_option( $this->field_name . '_status', $license_data->license );
	}
}