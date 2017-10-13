<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WBCOM_TDE_Generate_Demo_Data' ) ) :

/**
 * @class WBCOM_TDE_Generate_Demo_Data
 * @version	1.0.0
 */
class WBCOM_TDE_Generate_Demo_Data {
	
	/**
	 * The single instance of the class.
	 *
	 * @var WBCOM_TDE_Generate_Demo_Data
	 * @since 1.0.0
	 */
	protected static $_instance = null;
	protected static $_parent_dir = 'wbcom-theme-demos';
	
	/**
	 * Main WBCOM_TDE_Generate_Demo_Data Instance.
	 *
	 * Ensures only one instance of WBCOM_TDE_Generate_Demo_Data is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WBCOM_TDE_Generate_Demo_Data()
	 * @return WBCOM_TDE_Generate_Demo_Data - Main instance.
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}

	
	/**
	 * WBCOM_TDE_Generate_Demo_Data Constructor.
	 */
	public function __construct() {
		$this->init_hooks();
	}

	/**
	 * Hook into actions and filters.
	 * @since  1.0.0
	 */
	private function init_hooks() {
		add_action( 'wp_loaded', array( $this, 'generate_theme_demo_data' ) );
	}

	public function generate_theme_demo_data() {

		if( !isset( $_POST['wbcom_generate_theme_demo_data'] ) ) { return; }

		$installer_info = array();
		$package_info = array();

		/* demo directory setup :: first call to "get_theme_demo_location" function */
		$this->initial_directory_setup();
		
		/* making xml data for post types :: start */
		$package_info['post_types'] = $this->make_xml_for_post_types();
		/* making xml data for post types :: end */

		/* making json data for database tables :: start */
		$package_info['database_tables'] = $this->make_json_for_database_tables();
		/* making json data for database tables :: end */

		/* storing information about selected plugins :: start */
		$selected_plugins = isset( $_POST['selected_plugins'] ) ? $_POST['selected_plugins'] : array();
		if( !is_array( $selected_plugins ) ) {
			$selected_plugins = array( $selected_plugins );
		}
		$_selected_plugins = array();
		$plugins = get_plugins();
		if( !empty( $selected_plugins ) ) {
			foreach ( $selected_plugins as $value ) {
				$plugin_name = $plugins[$value]['Name'];
				$plugin_slug = explode( '/', $value );
				$plugin_slug = $plugin_slug[0];
				$_selected_plugins[$value] = array(
					'name'	=>	$plugin_name,
					'slug'	=>	$plugin_slug,
				);
			}
		}
		$package_info['plugins'] = $_selected_plugins;
		/* storing information about selected plugins :: end */

		/* storing information about selected upload folders :: start */
		$selected_upload_folders = isset( $_POST['selected_upload_folders'] ) ? $_POST['selected_upload_folders'] : array();
		if( !is_array( $selected_upload_folders ) ) {
			$selected_upload_folders = array( $selected_upload_folders );
		}
		if( !empty( $selected_upload_folders ) ) {
			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'] .'/';
			foreach ( $selected_upload_folders as $upload_folder ) {
				$locationToCreate = $this->get_theme_demo_location( 'path' );
				$locationToPick = $upload_dir . $upload_folder . '/';
				$this->add_folder_to_zip_file( $locationToCreate, $locationToPick, $action = 'CREATE' );
			}
			$upload_dir_url = $this->get_theme_demo_location( 'url' );
			$upload_dir_url .= '/uploads.zip';
			$package_info['upload_folders'] = $upload_dir_url;
		}
		/* storing information about selected upload folders :: end */

		$package_info['screenshot'] = isset( $_POST['demo_screenshot'] ) ? $_POST['demo_screenshot'] : '';
		$installer_info['screenshot'] = isset( $_POST['demo_screenshot'] ) ? $_POST['demo_screenshot'] : '';

		$package_info['created_on'] = date( "d-m-Y" );
		$installer_info['created_on'] = date( "d-m-Y" );

		/* making package.json file :: start */
		$args = array(
			'content'	=>	json_encode( $package_info, JSON_PRETTY_PRINT ),
			'fileName'	=>	'package',
			'fileExtension'	=>	'json',
		);
		$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
		/* making package.json file :: end */


		/* setting up installer.json file :: start */
		$theme_slug = isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : '';
		$installer_info['theme_name'] = $theme_slug;
		$theme_slug = sanitize_title( $theme_slug );
		$installer_info['theme_slug'] = $theme_slug;
		$demo_slug = isset( $_POST['demo_slug'] ) ? $_POST['demo_slug'] : '';
		$installer_info['demo_name'] = $demo_slug;
		$demo_slug = sanitize_title( $demo_slug );
		$demo_slug = 'theme_demo'; // to make one folder only
		$installer_info['demo_slug'] = $demo_slug;
		$installer_info['package'] = $this->get_theme_demo_location( 'url' );
		$url_to_request = $this->get_theme_demo_location( 'url', $locationTill = 'parent' );
		$url_to_request .= 'installer.json';
		$retrieved_data = file_get_contents( $url_to_request );
		if( !empty( $retrieved_data ) ) {
			$retrieved_data = json_decode( $retrieved_data, true );
			if( empty( $retrieved_data ) && !is_array( $retrieved_data ) ) {
				$retrieved_data = array();
			}
		}
		else {
			$retrieved_data = array();
		}
		if( !array_key_exists( $theme_slug, $retrieved_data ) ) {
			$retrieved_data[$theme_slug] = array();
		}
		$retrieved_data[$theme_slug][$demo_slug] = $installer_info;
		$args = array(
			'content'	=>	json_encode( $retrieved_data, JSON_PRETTY_PRINT ),
			'fileName'	=>	'installer',
			'fileExtension'	=>	'json',
		);
		$this->saveContentToDemoPackage( $args, $locationTill = 'parent' );
		/* setting up installer.json file :: end */
		
	}

	public function add_folder_to_zip_file( $locationToCreate = '', $locationToPick = '', $action = 'CREATE' ) {
		$locationToCreate = realpath( $locationToCreate );
		$rootPath = realpath( $locationToPick );
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = realpath( $upload_dir );

		$zip = new ZipArchive;
		$proceedFurther = false;
		if( $action == 'CREATE' ) {
			if ( $zip->open( $locationToCreate . '/uploads.zip', ZipArchive::CREATE ) === TRUE ) {
				$proceedFurther = true;
			}
		}
		else {
			if ( $zip->open( $locationToCreate . '/uploads.zip', ZipArchive::OVERWRITE ) === TRUE ) {
				$proceedFurther = true;
			}
		}
		if( $proceedFurther )  {
			// Create recursive directory iterator
			/** @var SplFileInfo[] $files */
			$files = new RecursiveIteratorIterator(
			    new RecursiveDirectoryIterator( $rootPath ),
			    RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ( $files as $name => $file ) {
			    // Skip directories (they would be added automatically)
			    if ( !$file->isDir() ) {
			        // Get real and relative path for current file
			        $filePath = $file->getRealPath();
			        $relativePath = substr( $filePath, strlen( $upload_dir ) + 1 );
			        $zip->addFile( $filePath, $relativePath );
			    }
			}
			// Zip archive will be created only after closing object
			$zip->close();
		}
	}

	public function make_json_for_database_tables() {
		$json_urls = array();
		$upload_dir_url = $this->get_theme_demo_location( 'url' );
		/* making json data for database tables :: start */
		$selected_database_tables = isset( $_POST['selected_database_tables'] ) ? $_POST['selected_database_tables'] : array();
		if( !is_array( $selected_database_tables ) ) {
			$selected_database_tables = array( $selected_database_tables );
		}
		foreach ( $selected_database_tables as $database_table ) {
			global $wpdb;
			$json_content = $wpdb->get_results( "SELECT * FROM $wpdb->prefix$database_table", ARRAY_A );
			if( !empty( $json_content ) && is_array( $json_content ) ) {
				$json_content = array_map( function( $value ) { return str_replace( home_url(), '{{*home_url}}', $value ); }, $json_content );
				$json_content = json_encode( $json_content, JSON_PRETTY_PRINT );
			}
			else {
				$json_content = '';
			}
			$args = array(
				'content'	=>	$json_content,
				'fileName'	=>	$database_table,
				'fileExtension'	=>	'json',
			);
			$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
			$json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
		}
		/* making json data for database tables :: end */
		return $json_urls;
	}

	public function make_xml_for_post_types() {
		$xml_urls = array();
		$upload_dir_url = $this->get_theme_demo_location( 'url' );
		
		/* making xml data for post types :: start */
		$selected_post_types = isset( $_POST['selected_post_types'] ) ? $_POST['selected_post_types'] : array();
		if( !is_array( $selected_post_types ) ) {
			$selected_post_types = array( $selected_post_types );
		}
		require_once( 'xml-exporter/wbcom-xml-exporter.php' );
		foreach ( $selected_post_types as $post_type_slug ) {
			$args = array( 'content' => $post_type_slug );
			ob_start();
			export_wp( $args );
			$xml_content = ob_get_clean();
			$args = array(
				'content'	=>	$xml_content,
				'fileName'	=>	$post_type_slug,
				'fileExtension'	=>	'xml',
			);
			$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
			$xml_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
		}
		/* making xml data for post types :: end */
		return $xml_urls;
	}

	public function initial_directory_setup() {
		$theme_slug = isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : '';
		$theme_slug = sanitize_title( $theme_slug );
		$demo_slug = isset( $_POST['demo_slug'] ) ? $_POST['demo_slug'] : '';
		$demo_slug = sanitize_title( $demo_slug );
		$demo_slug = 'theme_demo'; // to make one folder only
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/' . self::$_parent_dir;
		if ( !is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
			$args = array(
				'content'	=>	'',
				'fileName'	=>	'index',
				'fileExtension'	=>	'php',
			);
			$this->saveContentToDemoPackage( $args, $locationTill = 'parent' );
		}
		$upload_dir = $upload_dir . '/' . $theme_slug;
		if ( !is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
			$args = array(
				'content'	=>	'',
				'fileName'	=>	'index',
				'fileExtension'	=>	'php',
			);
			$this->saveContentToDemoPackage( $args, $locationTill = 'theme' );
		}
		$upload_dir = $upload_dir . '/' . $demo_slug;
		if ( !is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
			$args = array(
				'content'	=>	'',
				'fileName'	=>	'index',
				'fileExtension'	=>	'php',
			);
			$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
		}
		else {
			$this->recursiveRemoveDirectory( $upload_dir . '/' );
		}
		return $upload_dir . '/';
	}

	public function recursiveRemoveDirectory( $directory ) {
		foreach( glob("{$directory}/*" ) as $file ) {
			if( is_dir( $file ) ) {
				$this->recursiveRemoveDirectory( $file );
			}
			else {
				unlink( $file );
			}
		}
		// rmdir( $directory );
	}

	public function get_theme_demo_location( $value = 'path', $locationTill = 'demo', $theme_slug = '', $demo_slug = '' ) {
		if( empty( $theme_slug ) ) {
			$theme_slug = isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : '';
			$theme_slug = sanitize_title( $theme_slug );
		}
		if( empty( $demo_slug ) ) {
			$demo_slug = isset( $_POST['demo_slug'] ) ? $_POST['demo_slug'] : '';
			$demo_slug = sanitize_title( $demo_slug );
		}
		$demo_slug = 'theme_demo'; // to make one folder only
		$upload = wp_upload_dir();
		if( $value == 'path' ) {
			$upload_dir = $upload['basedir'];
		}
		else if( $value == 'url' ) {
			$upload_dir = $upload['baseurl'];
		}
		$upload_dir = $upload_dir . '/' . self::$_parent_dir;
		if( $locationTill == 'parent' ) {
			return $upload_dir . '/';
		}
		$upload_dir = $upload_dir . '/' . $theme_slug;
		if( $locationTill == 'theme' ) {
			return $upload_dir . '/';
		}
		$upload_dir = $upload_dir . '/' . $demo_slug;
		return $upload_dir . '/';
	}

	public function saveContentToDemoPackage( $args = array(), $locationTill = 'demo' ) {
		$package_path = $this->get_theme_demo_location( 'path', $locationTill );
		$fp = fopen( $package_path . "$args[fileName].$args[fileExtension]", "w" );
		fwrite( $fp, $args['content'] );
		fclose( $fp );
	}
	
}

endif;

/**
 * Main instance of WBCOM_TDE_Generate_Demo_Data.
 * @since  1.0.0
 * @return WBCOM_TDE_Generate_Demo_Data
 */
WBCOM_TDE_Generate_Demo_Data::instance();
?>