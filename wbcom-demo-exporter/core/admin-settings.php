<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WBCOM_TDE_ADMIN_SETTINGS' ) ) :
/**
 * Admin settings class for Theme Demo Exporter
 *
 * @class WBCOM_TDE_ADMIN_SETTINGS
 * @version 1.1.0
 */
class WBCOM_TDE_ADMIN_SETTINGS {
    /**
     * The single instance of the class.
     *
     * @var WBCOM_TDE_ADMIN_SETTINGS
     * @since 1.0.0
     */
    protected static $_instance = null;
    
    /**
     * Plugin slug
     *
     * @var string
     * @since 1.0.0
     */
    protected static $_slug = 'wbcom-theme-demo-exporter';
    
    /**
     * Parent directory
     *
     * @var string
     * @since 1.0.0
     */
    protected static $_parent_dir = 'wbcom-theme-demos';

    /**
     * Main WBCOM_TDE_ADMIN_SETTINGS Instance.
     *
     * @since 1.0.0
     * @static
     * @return WBCOM_TDE_ADMIN_SETTINGS - Main instance.
     */
    public static function instance() {
        if ( is_null( self::$_instance ) ) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    /**
     * WBCOM_TDE_ADMIN_SETTINGS Constructor.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Hook into actions and filters.
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 100 );
        add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
    }

    /**
     * Register admin menu
     *
     * @since 1.0.0
     */
    public function add_admin_menu() {
        add_menu_page(
            __( 'Theme Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
            __( 'Theme Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
            'manage_options',
            self::$_slug,
            array( $this, 'render_admin_page' ),
            'dashicons-database-export',
            80
        );
    }

    /**
     * Display admin notices for operations
     *
     * @since 1.1.0
     */
    public function display_admin_notices() {
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::$_slug ) {
            return;
        }

        // Handle export success message
        if ( isset( $_GET['export'] ) && $_GET['export'] === 'success' ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><?php _e( 'Theme demo package has been successfully generated!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
            </div>
            <?php
        }

        // Handle export error message
        if ( isset( $_GET['export'] ) && $_GET['export'] === 'error' ) {
            ?>
            <div class="notice notice-error is-dismissible">
                <p><?php _e( 'There was an error generating the theme demo package. Please check the logs for more information.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
            </div>
            <?php
        }
    }

    /**
     * Render the admin page
     *
     * @since 1.0.0
     */
    public function render_admin_page() {
        // Get theme information
        $theme_info = wp_get_theme();
        $reflection = new ReflectionClass( $theme_info );
        $property = $reflection->getProperty( 'headers' );
        $property->setAccessible(true);
        $theme_info = $property->getValue( $theme_info );
        
        ?>
        <div class="wrap wbcom-theme-exporter-wrap">
            <h1><?php _e( 'Theme Demo Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h1>
            
            <div class="wbcom-exporter-description">
                <p><?php _e( 'Generate a complete package of your current site configuration to create a demo that can be imported into another WordPress installation.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
            </div>

            <div class="wbcom-exporter-card">
                <h2><?php _e( 'Create Demo Package', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h2>
                
                <form method="post" id="wbcom-exporter-form">
                    <div class="wbcom-form-fields">
                        <div class="wbcom-form-field">
                            <label for="theme_slug"><?php _e( 'Theme Name', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></label>
                            <input type="text" name="theme_slug" id="theme_slug" value="<?php echo esc_attr( $theme_info['Name'] ); ?>" readonly />
                            <p class="description"><?php _e( 'Current active theme name (read-only)', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                        </div>
                        
                        <div class="wbcom-form-field">
                            <label for="demo_slug"><?php _e( 'Demo Name', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?> <span class="required">*</span></label>
                            <input type="text" name="demo_slug" id="demo_slug" value="" required />
                            <p class="description"><?php _e( 'Enter a name for this demo (e.g. Business, Shop, Blog)', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                        </div>
                        
                        <div class="wbcom-form-field">
                            <label for="demo_screenshot"><?php _e( 'Demo Screenshot', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></label>
                            <div class="screenshot-upload-container">
                                <?php
                                $image_inline_style = 'width:150px;height:150px;display:none;';
                                $remove_inline_style = 'display:none;';
                                ?>
                                <input class="wbcom_demo_exporter_img_url" type="hidden" name="demo_screenshot" id="demo_screenshot" value="" />
                                <img class="wbcom_demo_exporter_img" src="" style="<?php echo $image_inline_style; ?>" />
                                <div class="screenshot-buttons">
                                    <input type="button" class="wbcom_demo_exporter-upload-button button" value="<?php _e( 'Upload Image', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>" />
                                    <a href="#" class="wbcom_demo_exporter-remove-file-button" style="<?php echo $remove_inline_style; ?>"><?php _e( 'Remove Image', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></a>
                                </div>
                                <p class="description"><?php _e( 'Upload a screenshot image for this demo (recommended size: 1200x900px)', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                            </div>
                        </div>
                        
                        <div class="wbcom-form-field">
                            <label for="selected_post_types"><?php _e( 'Select Post Types', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></label>
                            <?php
                            $allPostTypes = get_post_types(
                                array(),
                                'object',
                                'and'
                            );
                            $post_types = array();
                            if ( !empty( $allPostTypes ) && is_array( $allPostTypes ) ) {
                                foreach ( $allPostTypes as $post_type ) {
                                    $post_types[$post_type->name] = $post_type->label;
                                }
                            }
                            ?>
                            <select name="selected_post_types[]" id="selected_post_types" class="wbcom-demo-exporter-select2" multiple>
                                <?php foreach ( $post_types as $key => $value ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Select the post types to include in the demo package', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                        </div>
                        
                        <div class="wbcom-form-field">
                            <label for="selected_database_tables"><?php _e( 'Select Database Tables', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></label>
                            <?php
                            global $wpdb;
                            $sql = defined( 'DB_NAME' ) ? "SHOW TABLES FROM " . DB_NAME : "SHOW TABLES LIKE '%'";
                            $results = $wpdb->get_results( $sql );
                            $db_tables = array();
                            foreach ( $results as $key => $result ) {
                                foreach ( $result as $table_name ) {
                                    $table_name = str_replace( $wpdb->prefix, '', $table_name );
                                    $db_tables[$table_name] = $table_name;
                                }
                            }
                            ?>
                            <select name="selected_database_tables[]" id="selected_database_tables" class="wbcom-demo-exporter-select2" multiple>
                                <?php foreach ( $db_tables as $db_table ) : ?>
                                    <option value="<?php echo esc_attr( $db_table ); ?>"><?php echo esc_html( $db_table ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Select the database tables to include in the demo package', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                        </div>
                        
                        <div class="wbcom-form-field">
                            <label for="selected_plugins"><?php _e( 'Select Plugins', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></label>
                            <?php $plugins = get_plugins(); ?>
                            <select name="selected_plugins[]" id="selected_plugins" class="wbcom-demo-exporter-select2" multiple>
                                <?php foreach ( $plugins as $key => $value ) : ?>
                                    <option value="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value['Name'] ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Select the plugins that should be included in the demo package', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                        </div>
                        
                        <div class="wbcom-form-field">
                            <label for="selected_upload_folders"><?php _e( 'Select Uploads Folders', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></label>
                            <?php
                            $upload = wp_upload_dir();
                            $upload_dir = $upload['basedir'];
                            $folders = array_diff( scandir( $upload_dir ), array( '..', '.' ) );
                            ?>
                            <select name="selected_upload_folders[]" id="selected_upload_folders" class="wbcom-demo-exporter-select2" multiple>
                                <?php foreach ( $folders as $folder ) : ?>
                                    <option value="<?php echo esc_attr( $folder ); ?>"><?php echo esc_html( $folder ); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description"><?php _e( 'Select upload folders to include in the demo package', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
                        </div>
                    </div>
                    
                    <div class="wbcom-export-actions">
                        <input type="submit" name="wbcom_generate_theme_demo_data" id="wbcom_generate_theme_demo_data" value="<?php _e( 'Generate Demo Package', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>" class="button button-primary" />
                        <div id="wbcom-export-progress" class="hidden">
                            <span class="spinner is-active"></span>
                            <span class="progress-text"><?php _e( 'Generating demo package...', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></span>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="wbcom-exporter-tips">
                <h3><?php _e( 'Export Tips', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h3>
                <ul>
                    <li><?php _e( 'For large sites, export may take several minutes to complete.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
                    <li><?php _e( 'Select only the essential plugins needed for your demo.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
                    <li><?php _e( 'Large media files will increase the package size significantly.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
                    <li><?php _e( 'Generated packages are stored in your wp-content/uploads/wbcom-theme-demos folder.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }

    /**
     * Enqueue scripts and styles for the admin page
     *
     * @since 1.0.0
     */
    public function admin_enqueue_scripts() {
        $screen = get_current_screen();
        if ( $screen->id !== 'toplevel_page_' . self::$_slug ) {
            return;
        }

        // Enqueue media scripts for image upload
        wp_enqueue_media();

        // Enqueue Select2 for dropdowns
        wp_register_script(
            'wbcom_theme_demo_exporter_select2_js',
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/js/select2.min.js',
            array( 'jquery' ),
            WBCOM_Theme_Demo_Exporter_VERSION,
            true
        );
        wp_enqueue_script( 'wbcom_theme_demo_exporter_select2_js' );

        // Enqueue Select2 CSS
        wp_register_style(
            'wbcom_theme_demo_exporter_select2_css',
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/css/select2.min.css',
            array(),
            WBCOM_Theme_Demo_Exporter_VERSION,
            'all'
        );
        wp_enqueue_style( 'wbcom_theme_demo_exporter_select2_css' );

        // Enqueue custom admin JS
        wp_register_script(
            'wbcom_theme_demo_exporter_admin_js',
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/js/admin.js',
            array( 'jquery', 'wbcom_theme_demo_exporter_select2_js' ),
            WBCOM_Theme_Demo_Exporter_VERSION,
            true
        );
        
        // Pass data to JavaScript
        wp_localize_script(
            'wbcom_theme_demo_exporter_admin_js',
            'wbcom_theme_demo_exporter_params',
            array(
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'export_nonce' => wp_create_nonce( 'wbcom_theme_demo_export' ),
                'i18n' => array(
                    'generating' => __( 'Generating demo package...', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
                    'please_wait' => __( 'Please wait, this may take several minutes...', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
                    'success' => __( 'Demo package created successfully!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
                    'error' => __( 'Error creating demo package. Please try again.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
                    'validation_error' => __( 'Please fill in all required fields.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN )
                )
            )
        );
        wp_enqueue_script( 'wbcom_theme_demo_exporter_admin_js' );

        // Enqueue admin styles
        wp_register_style(
            'wbcom_theme_demo_exporter_admin_css',
            WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/css/admin.css',
            array(),
            WBCOM_Theme_Demo_Exporter_VERSION,
            'all'
        );
        wp_enqueue_style( 'wbcom_theme_demo_exporter_admin_css' );
    }
}
endif;

/**
 * Main instance of WBCOM_TDE_ADMIN_SETTINGS.
 * 
 * @since 1.0.0
 * @return WBCOM_TDE_ADMIN_SETTINGS
 */
WBCOM_TDE_ADMIN_SETTINGS::instance();