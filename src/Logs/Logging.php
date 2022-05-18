<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * DLM_Logging class.
 */
class DLM_Logging {

	/**
	 * Holds the class object.
	 *
	 * @since 4.6.0
	 *
	 * @var object
	 */
	public static $instance;

	/**
	 * Class constructor
	 * 
	 * @since 4.6.0
	 */
	public function __construct() {

	}

	/**
	 * Returns the singleton instance of the class.
	 *
	 * @return object The DLM_Logging object.
	 * @since 4.6.0
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof DLM_Logging ) ) {
			self::$instance = new DLM_Logging();
		}

		return self::$instance;

	}

	/**
	 * Check if logging is enabled.
	 * Modified in version 4.5.0
	 *
	 * @return bool
	 *
	 * @since 4.5.0
	 */
	public static function is_logging_enabled() {

		return apply_filters( 'dlm_enable_reports', true );
	}

	/**
	 * Get the type of IP logging that is configured in settings
	 *
	 * @return string
	 */
	public function get_ip_logging_type() {
		$type = get_option( 'dlm_logging_ip_type', 'full' );
		if ( empty( $type ) ) {
			$type = 'full';
		}

		return $type;
	}

	public function is_ua_logging_enabled() {
		return (1==get_option('dlm_logging_ua', 1));
	}

	/**
	 * Check if 'dlm_count_unique_ips' is enabled
	 *
	 * @return bool
	 */
	public function is_count_unique_ips_only() {
		return ( '1' == get_option( 'dlm_count_unique_ips', 0 ) );
	}

	/**
	 * Check if visitor has downloaded version
	 *
	 * @param DLM_Download_Version $version
	 *
	 * @return bool
	 */
	public function has_ip_downloaded_version( $version ) {
		global $wpdb;

		return ( absint( $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(ID) FROM {$wpdb->download_log} WHERE `version_id` = %d AND `user_ip` = %s", $version->get_id(), DLM_Utils::get_visitor_ip() ) ) ) > 0 );
	}


	/**
	 * Create a log if logging is enabled
	 *
	 * @param string $type
	 * @param string $status
	 * @param string $message
	 * @param DLM_Download $download
	 * @param DLM_Download_Version $version
	 * @param $cookie bool 
	 */
	public function log( $download, $version, $status = 'completed', $cookie = true ) {

		// Check if logging is enabled.
		if ( ! DLM_Logging::is_logging_enabled() ) return;
		// setup new log item object
		if( ! DLM_Cookie_Manager::exists( $download ) ) {

			$log_item = new DLM_Log_Item();
			$log_item->set_user_id( absint( get_current_user_id() ) );
			$log_item->set_download_id( absint( $download->get_id() ) );
			$log_item->set_user_ip( DLM_Utils::get_visitor_ip() );
			$log_item->set_user_uuid( DLM_Utils::get_visitor_ip() );
			$log_item->set_user_agent( DLM_Utils::get_visitor_ua() );
			$log_item->set_version_id( absint( $version->get_id() ) );
			$log_item->set_version( $version->get_version() );
			$log_item->set_download_status( $status );
			$log_item->increase_download_count();
			
			if ( $cookie ) {
				DLM_Cookie_Manager::set_cookie( $download );
			}
		
			// persist log item.
			download_monitor()->service( 'log_item_repository' )->persist( $log_item );
		}
	}
}

