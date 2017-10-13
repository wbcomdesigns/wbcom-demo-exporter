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
	}

	public function importer_request_handler() {
		if( isset( $_GET['wbcom_theme_demo_listing'] ) && ( $_GET['wbcom_theme_demo_listing'] == 'yes' ) ) {
			if( isset( $_POST['theme_name'] ) ) {
				$theme_name = trim( $_POST['theme_name'] );
				$theme_name = sanitize_title( $theme_name );
				$upload = wp_upload_dir();
				$upload_dir_url = $upload['baseurl'] . '/';
				$upload_dir_url = $upload_dir_url . self::$_parent_dir . '/';
				$file_url = $upload_dir_url . '/installer.json';
				$retrieved_data = file_get_contents( $file_url );
				if( !empty( $retrieved_data ) ) {
					$retrieved_data = json_decode( $retrieved_data , true );
					$retrieved_data = isset( $retrieved_data[$theme_name] ) ? $retrieved_data[$theme_name] : array();
				}
				else {
					$retrieved_data = '';
				}
				echo json_encode( $retrieved_data );
			}
			if( isset( $_POST['theme_slug'] ) && isset( $_POST['demo_slug'] ) ) {
				$theme_slug = $_POST['theme_slug'];
				$demo_slug = $_POST['demo_slug'];
				$upload = wp_upload_dir();
				$upload_dir_url = $upload['baseurl'] . '/';
				$upload_dir_url = $upload_dir_url . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/';
				$file_url = $upload_dir_url . '/package.json';
				$retrieved_data = file_get_contents( $file_url );
				echo $retrieved_data;
			}
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