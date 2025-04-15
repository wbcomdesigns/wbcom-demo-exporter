<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WBCOM_TDE_Importer_Request_Handler' ) ) :
/**
 * Handles AJAX requests for theme demo importer
 *
 * @class WBCOM_TDE_Importer_Request_Handler
 * @version 1.1.0
 */
class WBCOM_TDE_Importer_Request_Handler {
    /**
     * The single instance of the class.
     *
     * @var WBCOM_TDE_Importer_Request_Handler
     * @since 1.0.0
     */
    protected static $_instance = null;
    
    /**
     * Parent directory for demos
     *
     * @var string
     * @since 1.0.0
     */
    protected static $_parent_dir = 'wbcom-theme-demos';
    
    /**
     * Main WBCOM_TDE_Importer_Request_Handler Instance.
     *
     * @since 1.0.0
     * @static
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
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'importer_request_handler' ) );
        add_action( 'wp_ajax_wbcom_theme_demo_get_themes', array( $this, 'ajax_get_themes' ) );
        add_action( 'wp_ajax_wbcom_theme_demo_get_demos', array( $this, 'ajax_get_demos' ) );
        add_action( 'wp_ajax_wbcom_theme_demo_get_plugins', array( $this, 'ajax_get_plugins' ) );
    }
    
    /**
     * Handle demo importer requests
     * 
     * @since 1.0.0
     */
    public function importer_request_handler() {
        // Check if this is a demo listing request
        if ( ! isset( $_GET['wbcom_theme_demo_listing'] ) || $_GET['wbcom_theme_demo_listing'] !== 'yes' ) {
            return;
        }
        
        // Handle plugins list request
        if ( isset( $_POST['theme_slug'] ) && isset( $_POST['demo_slug'] ) && isset( $_POST['plugins_list'] ) ) {
            $this->handle_plugins_list_request();
        }
        // Handle theme demos request
        elseif ( isset( $_POST['theme_name'] ) ) {
            $this->handle_theme_demos_request();
        }
        // Handle demo package request
        elseif ( isset( $_POST['theme_slug'] ) && isset( $_POST['demo_slug'] ) ) {
            $this->handle_demo_package_request();
        }
        
        // Always exit after handling the request
        exit;
    }
    
    /**
     * Handle plugins list request
     * 
     * @since 1.1.0
     */
    protected function handle_plugins_list_request() {
        $theme_slug = sanitize_text_field( $_POST['theme_slug'] );
        $demo_slug = sanitize_text_field( $_POST['demo_slug'] );
        
        // Verify input
        if ( empty( $theme_slug ) || empty( $demo_slug ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request parameters' ) );
            exit;
        }
        
        $upload = wp_upload_dir();
        $upload_dir_url = $upload['baseurl'] . '/';
        $upload_dir_url = $upload_dir_url . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/';
        $file_url = $upload_dir_url . '/plugins.json';
        
        $response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
        $retrieved_data = '';
        
        if ( ! is_wp_error( $response ) ) {
            if ( isset( $response['response']['code'] ) && ( $response['response']['code'] == 200 ) ) {
                $retrieved_data = isset( $response['body'] ) ? $response['body'] : '';
            }
        }
        
        echo $retrieved_data;
    }
    
    /**
     * Handle theme demos request
     * 
     * @since 1.1.0
     */
    protected function handle_theme_demos_request() {
        $theme_name = trim( sanitize_text_field( $_POST['theme_name'] ) );
        $theme_name = sanitize_title( $theme_name );
        
        $upload = wp_upload_dir();
        $upload_dir_url = $upload['baseurl'] . '/';
        $upload_dir_url = $upload_dir_url . self::$_parent_dir . '/';
        $file_url = $upload_dir_url . 'installer.json';
        
        $response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
        $retrieved_data = array();
        
        if ( ! is_wp_error( $response ) ) {
            if ( isset( $response['response']['code'] ) && ( $response['response']['code'] == 200 ) ) {
                $response_body = isset( $response['body'] ) ? $response['body'] : '';
                
                if ( ! empty( $response_body ) ) {
                    $parsed_data = json_decode( $response_body, true );
                    
                    if ( ! empty( $parsed_data ) && is_array( $parsed_data ) ) {
                        $retrieved_data = isset( $parsed_data[$theme_name] ) ? $parsed_data[$theme_name] : array();
                    }
                }
            }
        }
        
        echo json_encode( $retrieved_data );
    }
    
    /**
     * Handle demo package request
     * 
     * @since 1.1.0
     */
    protected function handle_demo_package_request() {
        $theme_slug = sanitize_text_field( $_POST['theme_slug'] );
        $demo_slug = sanitize_text_field( $_POST['demo_slug'] );
        
        // Verify input
        if ( empty( $theme_slug ) || empty( $demo_slug ) ) {
            wp_send_json_error( array( 'message' => 'Invalid request parameters' ) );
            exit;
        }
        
        $upload = wp_upload_dir();
        $upload_dir_url = $upload['baseurl'] . '/';
        $upload_dir_url = $upload_dir_url . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/';
        $file_url = $upload_dir_url . '/package.json';
        
        $response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
        $retrieved_data = '';
        
        if ( ! is_wp_error( $response ) ) {
            if ( isset( $response['response']['code'] ) && ( $response['response']['code'] == 200 ) ) {
                $retrieved_data = isset( $response['body'] ) ? $response['body'] : '';
            }
        }
        
        echo $retrieved_data;
    }
    
    /**
     * AJAX handler for getting available themes
     * 
     * @since 1.1.0
     */
    public function ajax_get_themes() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wbcom_theme_demo_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }
        
        $upload = wp_upload_dir();
        $upload_dir_url = $upload['baseurl'] . '/';
        $upload_dir_url = $upload_dir_url . self::$_parent_dir . '/';
        $file_url = $upload_dir_url . 'installer.json';
        
        $response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
        $themes = array();
        
        if ( ! is_wp_error( $response ) && ( $response['response']['code'] == 200 ) ) {
            $response_body = isset( $response['body'] ) ? $response['body'] : '';
            
            if ( ! empty( $response_body ) ) {
                $parsed_data = json_decode( $response_body, true );
                
                if ( ! empty( $parsed_data ) && is_array( $parsed_data ) ) {
                    // Format themes data for the frontend
                    foreach ( $parsed_data as $theme_slug => $demos ) {
                        $themes[] = array(
                            'slug' => $theme_slug,
                            'name' => isset( $demos[ key( $demos ) ]['theme_name'] ) ? $demos[ key( $demos ) ]['theme_name'] : $theme_slug,
                            'demo_count' => count( $demos )
                        );
                    }
                }
            }
        }
        
        wp_send_json_success( array( 'themes' => $themes ) );
    }
    
    /**
     * AJAX handler for getting demos for a specific theme
     * 
     * @since 1.1.0
     */
    public function ajax_get_demos() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wbcom_theme_demo_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }
        
        if ( ! isset( $_POST['theme_slug'] ) ) {
            wp_send_json_error( array( 'message' => 'Theme slug is required' ) );
        }
        
        $theme_slug = sanitize_text_field( $_POST['theme_slug'] );
        
        $upload = wp_upload_dir();
        $upload_dir_url = $upload['baseurl'] . '/';
        $upload_dir_url = $upload_dir_url . self::$_parent_dir . '/';
        $file_url = $upload_dir_url . 'installer.json';
        
        $response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
        $demos = array();
        
        if ( ! is_wp_error( $response ) && ( $response['response']['code'] == 200 ) ) {
            $response_body = isset( $response['body'] ) ? $response['body'] : '';
            
            if ( ! empty( $response_body ) ) {
                $parsed_data = json_decode( $response_body, true );
                
                if ( ! empty( $parsed_data ) && isset( $parsed_data[$theme_slug] ) ) {
                    $demos = $parsed_data[$theme_slug];
                }
            }
        }
        
        wp_send_json_success( array( 'demos' => $demos ) );
    }
    
    /**
     * AJAX handler for getting plugins for a specific demo
     * 
     * @since 1.1.0
     */
    public function ajax_get_plugins() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( $_POST['nonce'], 'wbcom_theme_demo_nonce' ) ) {
            wp_send_json_error( array( 'message' => 'Security check failed' ) );
        }
        
        if ( ! isset( $_POST['theme_slug'] ) || ! isset( $_POST['demo_slug'] ) ) {
            wp_send_json_error( array( 'message' => 'Theme and demo slugs are required' ) );
        }
        
        $theme_slug = sanitize_text_field( $_POST['theme_slug'] );
        $demo_slug = sanitize_text_field( $_POST['demo_slug'] );
        
        $upload = wp_upload_dir();
        $upload_dir_url = $upload['baseurl'] . '/';
        $upload_dir_url = $upload_dir_url . self::$_parent_dir . '/' . $theme_slug . '/'. $demo_slug . '/';
        $file_url = $upload_dir_url . '/plugins.json';
        
        $response = wp_remote_get( $file_url, array( 'timeout' => 120 ) );
        $plugins = array();
        
        if ( ! is_wp_error( $response ) && ( $response['response']['code'] == 200 ) ) {
            $response_body = isset( $response['body'] ) ? $response['body'] : '';
            
            if ( ! empty( $response_body ) ) {
                $plugins = json_decode( $response_body, true );
            }
        }
        
        wp_send_json_success( array( 'plugins' => $plugins ) );
    }
}
endif;

/**
 * Main instance of WBCOM_TDE_Importer_Request_Handler.
 * @since 1.0.0
 * @return WBCOM_TDE_Importer_Request_Handler
 */
WBCOM_TDE_Importer_Request_Handler::instance();