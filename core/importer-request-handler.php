<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'WBCOM_TDE_Importer_Request_Handler' ) ) :
/**
 * @class WBCOM_TDE_Importer_Request_Handler
 * @version	1.0.0
 */
class WBCOM_TDE_Importer_Request_Handler {
	/**
	 * The single instance of the class.
	 *
	 * @var WBCOM_TDE_Importer_Request_Handler
	 * @since 1.0.0
	 */
	protected static $_instance = null;
	protected static $_parent_dir = 'wbcom-theme-demos';
	/**
	 * Main WBCOM_TDE_Importer_Request_Handler Instance.
	 *
	 * Ensures only one instance of WBCOM_TDE_Importer_Request_Handler is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WBCOM_TDE_Importer_Request_Handler()
	 * @return WBCOM_TDE_Importer_Request_Handler - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	/**
	 * WBCOM_TDE_Importer_Request_Handler Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}
	/**
	 * Hook into actions and filters.
	 * @since  1.0.0
	 */
	private function init_hooks() {
		add_action( 'init', array( $this, 'importer_request_handler' ) );
		
		// Add early hook to prevent any output when serving demo data
		add_action( 'plugins_loaded', array( $this, 'prevent_output_on_demo_requests' ), 1 );
	}
	
	/**
	 * Prevent any output on demo data requests
	 */
	public function prevent_output_on_demo_requests() {
		if ( isset( $_GET['wbcom_theme_demo_listing'] ) && $_GET['wbcom_theme_demo_listing'] === 'yes' ) {
			// Remove actions that might output content
			remove_action( 'shutdown', 'wp_ob_end_flush_all', 1 );
			add_filter( 'wp_die_handler', array( $this, 'custom_wp_die_handler' ) );
		}
	}
	
	/**
	 * Custom wp_die handler for JSON responses
	 */
	public function custom_wp_die_handler() {
		return array( $this, 'json_wp_die' );
	}
	
	/**
	 * JSON-friendly wp_die
	 */
	public function json_wp_die( $message, $title = '', $args = array() ) {
		$response = array(
			'error' => true,
			'message' => $message,
		);
		
		ob_end_clean();
		header( 'Content-Type: application/json' );
		echo json_encode( $response );
		die();
	}
	public function importer_request_handler() {
		// For internal use - basic authentication check
		if( isset( $_GET['wbcom_theme_demo_listing'] ) && ( $_GET['wbcom_theme_demo_listing'] == 'yes' ) ) {
			
			// Disable error display to prevent corrupting JSON output
			@error_reporting(0);
			@ini_set('display_errors', 0);
			@ini_set('display_startup_errors', 0);
			
			// Start output buffering to catch any warnings
			ob_start();
			
			// Simple API key check for internal use
			$api_key = isset( $_GET['api_key'] ) ? $_GET['api_key'] : '';
			$valid_api_key = get_option( 'wbcom_exporter_api_key', 'demo-export-2024' ); // Default for internal use
			
			if ( $api_key !== $valid_api_key ) {
				// Clean any buffered output before dying
				ob_end_clean();
				wp_die( 'Invalid API key', 'Unauthorized', array( 'response' => 401 ) );
			}

			if( isset( $_POST['theme_slug'] ) && isset( $_POST['demo_slug'] ) && isset( $_POST['plugins_list'] ) ) {
				$theme_slug = $_POST['theme_slug'];
				$demo_slug = $_POST['demo_slug'];
				$upload = wp_upload_dir();
				$upload_dir_url = $upload['baseurl'] . '/';
				$upload_dir_url = $upload_dir_url . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/';
				$file_url = $upload_dir_url . '/plugins.json';
				// $retrieved_data = file_get_contents( $file_url );

				$response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
				$retrieved_data = array();
				if ( !is_wp_error( $response ) ) {
					if ( isset( $response['response']['code'] ) &&  ( $response['response']['code'] == 200 ) ) {
						$response = isset( $response['body'] ) ? $response['body'] : '';
						if( !empty( $response ) ) {
							$retrieved_data = $response;
						}
					}
				}

				// Clean buffer and output JSON
				ob_end_clean();
				header('Content-Type: application/json');
				echo $retrieved_data;
				die();
			}

			if( isset( $_POST['theme_name'] ) ) {
				$theme_name = trim( $_POST['theme_name'] );
				$theme_name = sanitize_title( $theme_name );
				$upload = wp_upload_dir();
				$upload_dir_url = $upload['baseurl'] . '/';
				$upload_dir_url = $upload_dir_url . self::$_parent_dir . '/';
				$file_url = $upload_dir_url . 'installer.json';
				
				$response = wp_remote_post( $file_url );
				if ( !is_wp_error( $response ) ) {
					if ( isset( $response['response']['code'] ) &&  ( $response['response']['code'] == 200 ) ) {
						$response = isset( $response['body'] ) ? $response['body'] : '';
						if( !empty( $response ) ) {
							$retrieved_data = json_decode( $response , true );
							if( !empty( $retrieved_data ) ) {
								$retrieved_data = isset( $retrieved_data[$theme_name] ) ? $retrieved_data[$theme_name] : array();
							}
							else {
								$retrieved_data = '';
							}
						}
					}
				}
				
				// Clean buffer and output JSON
				ob_end_clean();
				header('Content-Type: application/json');
				echo json_encode( $retrieved_data );
				die();
			}
			
			if( isset( $_POST['theme_slug'] ) && isset( $_POST['demo_slug'] ) ) {
				$theme_slug = $_POST['theme_slug'];
				$demo_slug = $_POST['demo_slug'];
				$upload = wp_upload_dir();
				$upload_dir_url = $upload['baseurl'] . '/';
				$upload_dir_url = $upload_dir_url . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/';
				$file_url = $upload_dir_url . '/package.json';

				// Check if file exists locally first
				$upload_dir_path = $upload['basedir'] . '/' . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/package.json';
				
				if ( file_exists( $upload_dir_path ) ) {
					// Read file directly for better performance
					$retrieved_data = @file_get_contents( $upload_dir_path );
					if ( $retrieved_data === false ) {
						$retrieved_data = json_encode( array( 'error' => 'Could not read package.json' ) );
					}
				} else {
					// Fallback to remote request
					$response = wp_remote_post( $file_url );
					$retrieved_data = '';
					if ( !is_wp_error( $response ) ) {
						if ( isset( $response['response']['code'] ) &&  ( $response['response']['code'] == 200 ) ) {
							$retrieved_data = isset( $response['body'] ) ? $response['body'] : '';
						}
					}
					
					// If still empty, return error JSON
					if ( empty( $retrieved_data ) ) {
						$retrieved_data = json_encode( array( 'error' => 'Package not found' ) );
					}
				}
				
				// Clean buffer and output JSON
				ob_end_clean();
				header('Content-Type: application/json');
				echo $retrieved_data;
				die();
			}
			
			// Clean buffer and die
			ob_end_clean();
			die();
		}
	}
}
endif;
/**
 * Main instance of WBCOM_TDE_Importer_Request_Handler.
 * @since  1.0.0
 * @return WBCOM_TDE_Importer_Request_Handler
 */
WBCOM_TDE_Importer_Request_Handler::instance();
?>