<?php

/**
 * Class DLM_Product
 * The base class for all Download Monitor Extensions
 */
class DLM_Product {

	/**
	 * The store URL
	 */
	const STORE_URL = 'https://www.download-monitor.com/?wc-api=';

	/**
	 * Activation endpoint
	 */
	const ENDPOINT_ACTIVATION = 'wp_plugin_licencing_activation_api';

	/**
	 * Update endpoint
	 */
	const ENDPOINT_UPDATE = 'wp_plugin_licencing_update_api';

	/**
	 * @var String
	 */
	private $product_id;

	/**
	 * @var string
	 */
	private $product_name = "";

	/**
	 * @var String
	 */
	private $plugin_name;

	/**
	 * @var string
	 */
	private $version = false;

	/**
	 * @var DLM_Product_License
	 */
	private $license = null;

	/**
	 * Constructor
	 *
	 * @param String $product_id
	 * @param string|bool $version
	 * @param string $product_name
	 */
	function __construct( $product_id, $version = false, $product_name = "" ) {
		$this->product_id = $product_id;

		// The plugin file name
		$this->plugin_name = $this->product_id . '/' . $this->product_id . '.php';

		// set product name
		$this->product_name = $product_name;

		// BC
		if ( empty( $this->product_name ) ) {
			$this->product_name = $this->product_id;
		}

		// Set plugin version
		$this->version = $version;
	}

	/**
	 * @return String
	 */
	public function get_product_id() {
		return $this->product_id;
	}

	/**
	 * @param String $product_id
	 */
	public function set_product_id( $product_id ) {
		$this->product_id = $product_id;
	}

	/**
	 * @return string
	 */
	public function get_product_name() {
		return $this->product_name;
	}

	/**
	 * @param string $product_name
	 */
	public function set_product_name( $product_name ) {
		$this->product_name = $product_name;
	}

	/**
	 * @return String
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * @param String $plugin_name
	 */
	public function set_plugin_name( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Get the license, license will be automatically loaded if not set yet.
	 *
	 * @return DLM_Product_License
	 */
	public function get_license() {
		if ( null === $this->license ) {
			$this->license = new DLM_Product_License( $this->product_id );
		}

		return $this->license;
	}

	/**
	 * Set the license
	 *
	 * @param DLM_Product_License $license
	 */
	public function set_license( $license ) {
		$this->license = $license;
		$this->license->store();
	}

	/**
	 * Attempt to activate a plugin licence
	 *
	 * @return String
	 */
	public function activate() {

		// Get License
		$license = $this->get_license();

		try {

			// Check License key
			if ( '' === $license->get_key() ) {
				throw new Exception( 'Please enter your license key.' );
			}

			// Check license email
			if ( '' === $license->get_email() ) {
				throw new Exception( 'Please enter the email address associated with your license.' );
			}

			// Do activate request
			$request = wp_remote_get( self::STORE_URL . self::ENDPOINT_ACTIVATION . '&' . http_build_query( array(
					'email'          => $license->get_email(),
					'licence_key'    => $license->get_key(),
					'api_product_id' => $this->product_id,
					'request'        => 'activate',
					'instance'       => site_url()
				), '', '&' ) );

			// Check request
			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				throw new Exception( 'Connection failed to the License Key API server. Try again later.' );
			}

			// Get activation result
			$activate_results = json_decode( wp_remote_retrieve_body( $request ), true );

			// Check if response is correct
			if ( ! empty( $activate_results['activated'] ) ) {

				// Set local activation status to true
				$license->set_status( 'active' );
				$this->set_license( $license );

				// Return Message
				return array(
					'result'  => 'success',
					'message' => __( 'License successfully activated.', 'download-monitor' )
				);

			} elseif ( $activate_results === false ) {
				throw new Exception( 'Connection failed to the License Key API server. Try again later.' );
			} elseif ( isset( $activate_results['error_code'] ) ) {
				throw new Exception( $activate_results['error'] );
			}


		} catch ( Exception $e ) {

			// Set local activation status to false
			$license->set_status( 'inactivate' );
			$this->set_license( $license );

			// Return error message
			return array( 'result' => 'failed', 'message' => $e->getMessage() );
		}
	}

	/**
	 * Attempt to deactivate a licence
	 */
	public function deactivate() {

		// Get License
		$license = $this->get_license();

		try {

			// Check License key
			if ( '' === $license->get_key() ) {
				throw new Exception( "Can't deactivate license without a license key." );
			}

			// The Request
			$request = wp_remote_get( self::STORE_URL . self::ENDPOINT_ACTIVATION . '&' . http_build_query( array(
					'api_product_id' => $this->product_id,
					'licence_key'    => $license->get_key(),
					'request'        => 'deactivate',
					'instance'       => site_url(),
				), '', '&' ) );

			// Check request
			if ( is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) != 200 ) {
				throw new Exception( 'Connection failed to the License Key API server. Try again later.' );
			}

			// Get result
			$result = json_decode( wp_remote_retrieve_body( $request ), true );

			/** @todo check result * */

			// Set new license status
			$license->set_status( 'inactive' );
			$this->set_license( $license );

			return array( 'result' => 'success' );

		} catch ( Exception $e ) {

			// Return error message
			return array( 'result' => 'failed', 'message' => $e->getMessage() );
		}

	}

}