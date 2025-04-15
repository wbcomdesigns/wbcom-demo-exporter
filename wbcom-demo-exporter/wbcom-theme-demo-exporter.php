<?php
/**
 * Plugin Name: WB Demo Exporter
 * Plugin URI: https://wbcomdesigns.com/
 * Description: Creates theme demo packages from your current WordPress site for easy importing elsewhere.
 * Version: 1.1.0
 * Author: Wbcom Designs
 * Author URI: https://wbcomdesigns.com/
 * Requires at least: 4.0
 * Tested up to: 6.3
 *
 * Text Domain: wbcom-theme-demo-exporter
 * Domain Path: /languages/
 *
 * @package WBCOM_Theme_Demo_Exporter
 * @category Core
 * @author Wbcom Designs
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WBCOM_Theme_Demo_Exporter' ) ) :

/**
 * Main WBCOM_Theme_Demo_Exporter Class.
 *
 * @class WBCOM_Theme_Demo_Exporter
 * @version 1.1.0
 */
class WBCOM_Theme_Demo_Exporter {
    /**
     * WBCOM_Theme_Demo_Exporter version.
     *
     * @var string
     */
    public $version = '1.1.0';

    /**
     * The single instance of the class.
     *
     * @var WBCOM_Theme_Demo_Exporter
     * @since 1.0.0
     */
    protected static $_instance = null;

    /**
     * Main WBCOM_Theme_Demo_Exporter Instance.
     *
     * Ensures only one instance of WBCOM_Theme_Demo_Exporter is loaded or can be loaded.
     *
     * @since 1.0.0
     * @static
     * @see INSTANTIATE_WBCOM_Theme_Demo_Exporter()
     * @return WBCOM_Theme_Demo_Exporter - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * WBCOM_Theme_Demo_Exporter Constructor.
     */
    public function __construct() {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();

        do_action( 'wbcom_theme_demo_exporter_loaded' );
    }

    /**
     * Hook into actions and filters.
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action( 'init', array( $this, 'load_plugin_textdomain' ) );
        add_filter( 'plugin_action_links_' . WBCOM_Theme_Demo_Exporter_PLUGIN_BASENAME, array( $this, 'add_plugin_action_links' ) );
        add_action( 'admin_init', array( $this, 'check_dependencies' ) );
        
        // Create upload directory on activation
        register_activation_hook( __FILE__, array( $this, 'on_activation' ) );
    }

    /**
     * Add plugin action links
     *
     * @param array $links Plugin action links
     * @return array Modified action links
     */
    public function add_plugin_action_links( $links ) {
        $plugin_links = array(
            '<a href="' . admin_url( 'admin.php?page=wbcom-theme-demo-exporter' ) . '">' . __( 'Settings', 'wbcom-theme-demo-exporter' ) . '</a>',
        );
        
        return array_merge( $plugin_links, $links );
    }

    /**
     * Check plugin dependencies
     */
    public function check_dependencies() {
        // Check if ZipArchive is available
        if ( ! class_exists( 'ZipArchive' ) ) {
            add_action( 'admin_notices', function() {
                ?>
                <div class="notice notice-error">
                    <p><?php _e( 'WB Demo Exporter requires the PHP ZipArchive extension to be installed on your server.', 'wbcom-theme-demo-exporter' ); ?></p>
                </div>
                <?php
            });
        }
    }

    /**
     * Run on plugin activation
     */
    public function on_activation() {
        // Create upload directory for demo packages
        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'] . '/wbcom-theme-demos';
        
        if ( ! is_dir( $upload_dir ) ) {
            wp_mkdir_p( $upload_dir );
            
            // Create an index.php file to prevent directory listing
            $index_file = $upload_dir . '/index.php';
            if ( ! file_exists( $index_file ) ) {
                $handle = @fopen( $index_file, 'w' );
                if ( $handle ) {
                    fwrite( $handle, '<?php // Silence is golden' );
                    fclose( $handle );
                }
            }
        }
        
        // Create an empty installer.json file
        $installer_file = $upload_dir . '/installer.json';
        if ( ! file_exists( $installer_file ) ) {
            $handle = @fopen( $installer_file, 'w' );
            if ( $handle ) {
                fwrite( $handle, '{}' );
                fclose( $handle );
            }
        }
    }

    /**
     * Define WBCOM_Theme_Demo_Exporter Constants.
     */
    private function define_constants() {
        $this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_FILE', __FILE__ );
        $this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
        $this->define( 'WBCOM_Theme_Demo_Exporter_VERSION', $this->version );
        $this->define( 'WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN', 'wbcom-theme-demo-exporter' );
        $this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
        $this->define( 'WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL', plugin_dir_url( __FILE__ ) );
    }

    /**
     * Define constant if not already set.
     *
     * @param string $name Constant name
     * @param string|bool $value Constant value
     */
    private function define( $name, $value ) {
        if ( ! defined( $name ) ) {
            define( $name, $value );
        }
    }

    /**
     * Include required core files used in admin and on the frontend.
     */
    public function includes() {
        // Core classes
        include_once 'core/admin-settings.php';
        include_once 'core/importer-request-handler.php';
        include_once 'core/generate-demo-data.php';
        
        // Create required directories
        $this->create_required_directories();
    }

    /**
     * Create required directories for assets
     */
    private function create_required_directories() {
        // Create directories if they don't exist
        $directories = array(
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'assets/css',
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'assets/js',
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'assets/images',
        );
        
        foreach ( $directories as $directory ) {
            if ( ! is_dir( $directory ) ) {
                wp_mkdir_p( $directory );
            }
        }
    }

    /**
     * Load Localisation files.
     */
    public function load_plugin_textdomain() {
        $locale = apply_filters( 'plugin_locale', get_locale(), WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN );
        
        load_textdomain(
            WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN,
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'languages/' . WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN . '-' . $locale . '.mo'
        );
        
        load_plugin_textdomain(
            WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }
}

endif;

/**
 * Main instance of WBCOM_Theme_Demo_Exporter.
 *
 * Returns the main instance of WBCOM_Theme_Demo_Exporter to prevent the need to use globals.
 *
 * @since 1.0.0
 * @return WBCOM_Theme_Demo_Exporter
 */
function instantiate_wbcom_theme_demo_exporter() {
    return WBCOM_Theme_Demo_Exporter::instance();
}

// Global for backwards compatibility.
$GLOBALS['wbcom_theme_demo_exporter'] = instantiate_wbcom_theme_demo_exporter();