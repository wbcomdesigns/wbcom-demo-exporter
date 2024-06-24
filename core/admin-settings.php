<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'WBCOM_TDE_ADMIN_SETTINGS' ) ) :
/**
 * @class WBCOM_TDE_ADMIN_SETTINGS
 * @version	1.0.0
 */
class WBCOM_TDE_ADMIN_SETTINGS {
	/**
	 * The single instance of the class.
	 *
	 * @var WBCOM_TDE_ADMIN_SETTINGS
	 * @since 1.0.0
	 */
	protected static $_instance = null;
	protected static $_slug = 'wbcom-theme-demo-exporter';
	protected static $_parent_dir = 'wbcom-theme-demos';
	/**
	 * Main WBCOM_TDE_ADMIN_SETTINGS Instance.
	 *
	 * Ensures only one instance of WBCOM_TDE_ADMIN_SETTINGS is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WBCOM_TDE_ADMIN_SETTINGS()
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
	 * @since  1.0.0
	 */
	private function init_hooks() {
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 100 );
	}
	public function add_admin_menu() {
		add_menu_page(
			$page_title	=	__( 'Theme Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
			$menu_title	=	__( 'Theme Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
			$capability	=	'manage_options',
			$menu_slug	=	self::$_slug,
			$function	=	array( $this, 'render_page_for_added_menu' ),
			$icon_url	=	'',
			$position	=	null
		);
	}
	public function render_page_for_added_menu() {
		$theme_info = wp_get_theme();
		$reflection = new ReflectionClass( $theme_info );
		$property = $reflection->getProperty( 'headers' );
		$property->setAccessible(true);
		$theme_info = $property->getValue( $theme_info );
		$pre_selected = false;
		if( $pre_selected ) {
			$pre_selected = 'selected="selected"';
		}
		else {
			$pre_selected = '';
		}
		echo "<div class='wrap reign-theme-exporter'>";
			echo "<form method='post'>";
				echo "<div class='wp-list-table widefat fixed striped'>";
					echo "<div class='select-folder'>";
						echo "<h3><label>" . __( 'Select Uploads Folder', 'ASDF' ) . "</label></h3>";
						echo "<div class='selected-folder'>";
							$pre_selected = 'selected="selected"';
							$upload = wp_upload_dir();
							$upload_dir = $upload['basedir'];
							$folders = array_diff( scandir( $upload_dir ), array( '..', '.' ) );
							echo "<select name='selected_upload_folders[]' class='wbcom-demo-exporter-select2' multiple>";
							foreach ( $folders as $folder ) {
								echo "<option value='$folder' " . $pre_selected . ">" . $folder . "</option>";
							}
							echo "</select>";
						echo "</div>";
					echo "</div>";
				echo "</div>";
				echo "<input type='submit' name='wbcom_generate_theme_demo_data' value='". __( 'Generate', 'ASDF' ) ."' class='button button-primary' />";
			echo "</form>";
		echo "</div>";
	}
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( $screen->id != 'toplevel_page_wbcom-theme-demo-exporter' ) { return; }
		wp_enqueue_media();
		wp_register_script(
			$handle		=	'wbcom_theme_demo_exporter_select2_js',
			$src		=	WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/js/select2.min.js',
			$deps		=	array( 'jquery' ),
			$ver		=	false,
			$in_footer	=	true
		);
		wp_localize_script(
			'wbcom_theme_demo_exporter_select2_js',
			'wbcom_theme_demo_exporter_select2_params',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' )
			)
		);
		wp_enqueue_script( 'wbcom_theme_demo_exporter_select2_js' );
		wp_register_style(
			$handle		=	'wbcom_theme_demo_exporter_select2_css',
			$src		=	WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/css/select2.min.css',
			$deps		=	array(),
			$ver		=	false,
			$media		=	'all'
		);
		wp_enqueue_style( 'wbcom_theme_demo_exporter_select2_css' );

		wp_enqueue_script( 'wbcom_theme_demo_exporter_select2_js' );
		wp_register_style(
			$handle		=	'wbcom_theme_demo_exporter_style_css',
			$src		=	WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/css/exporter_style.css',
			$deps		=	array(),
			$ver		=	false,
			$media		=	'all'
		);
		wp_enqueue_style( 'wbcom_theme_demo_exporter_style_css' );
	}
}
endif;
/**
 * Main instance of WBCOM_TDE_ADMIN_SETTINGS.
 * @since  1.0.0
 * @return WBCOM_TDE_ADMIN_SETTINGS
 */
WBCOM_TDE_ADMIN_SETTINGS::instance();
?>