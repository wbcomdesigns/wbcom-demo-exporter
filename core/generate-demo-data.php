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
	public function old_generate_theme_demo_data() {
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
		$upload_dir_urls = array();
		if( !empty( $selected_upload_folders ) ) {
			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'] .'/';
			foreach ( $selected_upload_folders as $upload_folder ) {
				$upload_dir_url = $this->get_theme_demo_location( 'url' );
				$upload_dir_url = $upload_dir_url . $upload_folder . '.zip';
				$upload_dir_urls[] = $upload_dir_url;
				$locationToCreate = $this->get_theme_demo_location( 'path' );
				// $locationToCreate = $locationToCreate . $upload_folder . '/';
				$locationToPick = $upload_dir . $upload_folder . '/';
				$this->add_folder_to_zip_file( $upload_folder, $locationToCreate, $locationToPick, $action = 'CREATE' );
			}
			$package_info['upload_folders'] = $upload_dir_urls;
		}
		/* storing information about selected upload folders :: end */
		$package_info['screenshot'] = isset( $_POST['demo_screenshot'] ) ? $_POST['demo_screenshot'] : '';
		$installer_info['screenshot'] = isset( $_POST['demo_screenshot'] ) ? $_POST['demo_screenshot'] : '';
		$package_info['created_on'] = date( "d-m-Y" );
		$installer_info['created_on'] = date( "d-m-Y" );
		/* making package.json file :: start */
		// $args = array(
		// 	'content'	=>	json_encode( $package_info, JSON_PRETTY_PRINT ),
		// 	'fileName'	=>	'package',
		// 	'fileExtension'	=>	'json',
		// );
		$args = array(
			'content'	=>	json_encode( $package_info ),
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
		// $retrieved_data = file_get_contents( $url_to_request );
		// if( !empty( $retrieved_data ) ) {
		// 	$retrieved_data = json_decode( $retrieved_data, true );
		// 	if( empty( $retrieved_data ) && !is_array( $retrieved_data ) ) {
		// 		$retrieved_data = array();
		// 	}
		// }
		// else {
		// 	$retrieved_data = array();
		// }

		$response = wp_remote_get( $url_to_request, array( 'timeout' => 120 ) );
		$retrieved_data = array();
		if ( !is_wp_error( $response ) ) {
			if ( isset( $response['response']['code'] ) &&  ( $response['response']['code'] == 200 ) ) {
				$response = isset( $response['body'] ) ? $response['body'] : '';
				if( !empty( $response ) ) {
					$retrieved_data = json_decode( $response, true );
				}
			}
		}

		if( !array_key_exists( $theme_slug, $retrieved_data ) ) {
			$retrieved_data[$theme_slug] = array();
		}
		$retrieved_data[$theme_slug][$demo_slug] = $installer_info;
		// $args = array(
		// 	'content'	=>	json_encode( $retrieved_data, JSON_PRETTY_PRINT ),
		// 	'fileName'	=>	'installer',
		// 	'fileExtension'	=>	'json',
		// );
		$args = array(
			'content'	=>	json_encode( $retrieved_data ),
			'fileName'	=>	'installer',
			'fileExtension'	=>	'json',
		);
		$this->saveContentToDemoPackage( $args, $locationTill = 'parent' );
		/* setting up installer.json file :: end */
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
		$upload_dir_urls = array();
		if( !empty( $selected_upload_folders ) ) {
			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'] .'/';
			foreach ( $selected_upload_folders as $selected_parent_folder ) {
				// $upload_dir_url = $this->get_theme_demo_location( 'url' );
				// $upload_dir_url = $upload_dir_url . '/' . $selected_parent_folder . '.zip';
				// $upload_dir_urls[] = $upload_dir_url;
				$locationToCreate = $this->get_theme_demo_location( 'path' );
				// $locationToCreate = $locationToCreate . $selected_parent_folder . '/';
				$locationToPick = $upload_dir . $selected_parent_folder . '/';
				

				// $this->Zip( $folderToCompress = realpath($locationToPick), $whereToGetZipFile = $locationToCreate . './compressed.zip' );
				// var_dump($folderToCompress);

				/*
				* Step 1
				* create folder in demo package
				*/
				$temp_folder_location = $this->get_theme_demo_location( 'path' ) . $selected_parent_folder;
				if ( !is_dir( $temp_folder_location ) ) {
					wp_mkdir_p( $temp_folder_location );
				}

				/*
				* Step 2
				* create folder in demo package
				*/
				$sourceFolder = $locationToPick;
				$destinationFolder = $temp_folder_location;
						
				$files_n_folders = array_diff( scandir( $upload_dir . $selected_parent_folder ), array( '..', '.' ) );
				foreach ( $files_n_folders as $key => $sub_folder ) {
					if( is_dir( $upload_dir . $selected_parent_folder . '/' . $sub_folder ) ) {
						$sourceFolder = $upload_dir . $selected_parent_folder . '/' . $sub_folder . '/';
						$destinationFolder = $this->get_theme_demo_location( 'path' ) . $selected_parent_folder . '/';

						$thisFolderIsOver = false;
						$resultOfPrevOperation = '';
						$counter = 1;
						do {
							$resultOfPrevOperation = $this->createZip( $sourceFolder, $destinationFolder, $sub_folder . "-break-$counter", $resultOfPrevOperation );
							if( empty( $resultOfPrevOperation ) ) {
								$thisFolderIsOver = true;
							}
							$upload_dir_url = $this->get_theme_demo_location( 'url' );
							$upload_dir_url = $upload_dir_url . $selected_parent_folder . "/" . $sub_folder . "-break-$counter.zip";
							$upload_dir_urls[] = $upload_dir_url;
							$counter++;
						}
						while( ( $thisFolderIsOver == false ) );
						
					}
					else {

					}
				}

				// $sourceFolder = $this->get_theme_demo_location( 'path' ) . $selected_parent_folder . '/';
				// $destinationFolder = $this->get_theme_demo_location( 'path' );
				// $sub_folder = $selected_parent_folder;
				// $folderToDeleteAfterZip = $this->get_theme_demo_location( 'path' ) . $selected_parent_folder . '/';
				// $this->createParentZip( $sourceFolder, $destinationFolder, $sub_folder );
				// $this->recursiveRemoveDirectory( $folderToDeleteAfterZip );
			}
			$package_info['upload_folders'] = $upload_dir_urls;
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


		/* making plugins.json file :: start */
		$plugins_info = array(
			array(
				"name" => "Wbcom Essential",
				"slug" => "wbcom-essential",
				"required" => true,
				"version" => "1.0.0",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "https://www.dl.dropboxusercontent.com/s/sc7wpcv7pq5peyg/wbcom-essential.zip?dl=0",
				"description" => "Wbcom Essential is the required plugin to use REIGN theme to its maximum extent."
			),
			array(
				"name" => "Buddypress",
				"slug" => "buddypress",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "BuddyPress adds community features to WordPress. Member Profiles, Activity Streams, Direct Messaging, Notifications, and more!"
			),
			array(
				"name" => "bbPress",
				"slug" => "bbpress",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "bbPress is forum software with a twist from the creators of WordPress."
			),
			array(
				"name" => "BP Create Group Type",
				"slug" => "bp-create-group-type",
				"required" => true,
				"version" => "1.0.4",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "This plugin adds a new feature to BuddyPress, Group Types. This allows an easy categorization of BP Groups."
			),
			array(
				"name" => "BuddyPress Activity Social Share",
				"slug" => "bp-activity-social-share",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "A perfect plugin to make your user activities on your website social-share-friendly, and increase your members social reach dramatically!"
			),
			array(
				"name" => "Elementor",
				"slug" => "elementor",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "The most advanced frontend drag & drop page builder. Create high-end, pixel perfect websites at record speeds. Any theme, any page, any design."
			),
			array(
				"name" => "SVG Support",
				"slug" => "svg-support",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "Allow SVG file uploads using the WordPress Media Library uploader plus the ability to inline SVG files for direct styling/animation of SVG elements using CSS/JS."
			),
			array(
				"name" => "Ninja Forms",
				"slug" => "ninja-forms",
				"required" => true,
				"version" => "3.2.4",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "Ninja Forms is a webform builder with unparalleled ease of use and features."
			),
			array(
				"name" => "rtMedia for WordPress, BuddyPress and bbPress",
				"slug" => "buddypress-media",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "This plugin adds missing media rich features like photos, videos and audio uploading to BuddyPress which are essential if you are building social network, seriously!"
			),
			array(
				"name"	=> "WooCommerce",
				"slug"	=> "woocommerce",
				"required"	=> true,
				"version"	=> "2.3.2.1",
				"force_activation"	=> false,
				"force_deactivation"	=> false,
				"external_url"	=> "",
				"description"	=> "Build any type of community website with member profiles, activity streams, user groups, messaging, and more."
			),
			array(
				"name"	=> "WordPress Social Login",
				"slug"	=> "wordpress-social-login",
				"required"	=> true,
				"version"	=> "2.3.2.1",
				"force_activation"	=> false,
				"force_deactivation"	=> false,
				"external_url"	=> "",
				"description"	=> "Allow your visitors to comment and login with social networks such as Twitter, Facebook, Google, Yahoo and more."
			),
			array(
				"name"	=> "Yoast SEO",
				"slug"	=> "wordpress-seo",
				"required"	=> false,
				"version"	=> "2.3.2.1",
				"force_activation"	=> false,
				"force_deactivation"	=> false,
				"external_url"	=> "",
				"description"	=> "The first true all-in-one SEO solution for WordPress, including on-page content analysis, XML sitemaps and much more."
			),
			array(
				"name"	=> "UpdraftPlus WordPress Backup Plugin",
				"slug"	=> "updraftplus",
				"required"	=> false,
				"version"	=> "2.3.2.1",
				"force_activation"	=> false,
				"force_deactivation"	=> false,
				"external_url"	=> "",
				"description"	=> "UpdraftPlus simplifies backups and restoration. It is the world’s highest ranking and most popular scheduled backup plugin, with over a million currently-active installs."
			),
			array(
				"name"	=> "iThemes Security (formerly Better WP Security)",
				"slug"	=> "better-wp-security",
				"required"	=> false,
				"version"	=> "2.3.2.1",
				"force_activation"	=> false,
				"force_deactivation"	=> false,
				"external_url"	=> "",
				"description"	=> "iThemes Security (formerly Better WP Security) gives you over 30+ ways to secure and protect your WordPress site."
			),
		);
		$args = array(
			'content'	=>	json_encode( $plugins_info, JSON_PRETTY_PRINT ),
			'fileName'	=>	'plugins',
			'fileExtension'	=>	'json',
		);
		$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
		/* making plugins.json file :: end */


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
		// $retrieved_data = file_get_contents( $url_to_request );
		// if( !empty( $retrieved_data ) ) {
		// 	$retrieved_data = json_decode( $retrieved_data, true );
		// 	if( empty( $retrieved_data ) && !is_array( $retrieved_data ) ) {
		// 		$retrieved_data = array();
		// 	}
		// }
		// else {
		// 	$retrieved_data = array();
		// }

		$response = wp_remote_get( $url_to_request, array( 'timeout' => 120 ) );
		$retrieved_data = array();
		if ( !is_wp_error( $response ) ) {
			if ( isset( $response['response']['code'] ) &&  ( $response['response']['code'] == 200 ) ) {
				$response = isset( $response['body'] ) ? $response['body'] : '';
				if( !empty( $response ) ) {
					$retrieved_data = json_decode( $response, true );
				}
			}
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

	public function createParentZip( $sourceFolder = '', $destinationFolder = '', $folderToPick = '' ) {
		$sourceFolder = realpath( $sourceFolder );
		$destinationFolder = realpath( $destinationFolder );

		$wpUploadsFolder = wp_upload_dir();
		$wpUploadsFolder = $wpUploadsFolder['basedir'];
		$wpUploadsFolder = realpath( $wpUploadsFolder );
		
		$zip = new ZipArchive;
		if ( $zip->open( $destinationFolder . "/$folderToPick.zip", ZipArchive::CREATE ) === TRUE ) {
			// Create recursive directory iterator
			/** @var SplFileInfo[] $files */
			$files = new RecursiveIteratorIterator(
			    new RecursiveDirectoryIterator( $sourceFolder ),
			    RecursiveIteratorIterator::LEAVES_ONLY
			);
			foreach ( $files as $name => $file ) {
			    // Skip directories (they would be added automatically)
			    if ( !$file->isDir() ) {
			        // Get real and relative path for current file
			        $filePath = $file->getRealPath();
			        $relativePath = substr( $filePath, strlen( $sourceFolder ) + 1 );
			        $zip->addFile( $filePath, $relativePath );
			    }
			}
			// Zip archive will be created only after closing object
			$zip->close();
		}
	}

	public function createZip( $sourceFolder = '', $destinationFolder = '', $folderToPick = '', $resultOfPrevOperation = '' ) {
		// $sub_folder . "-break-$counter"
		$sourceFolderLastFolderName = explode( '/', $sourceFolder );
		$sourceFolderLastFolderName = array_filter( $sourceFolderLastFolderName );
		$sourceFolderLastFolderName = array_values( $sourceFolderLastFolderName );
		$sourceFolderLastFolderName = $sourceFolderLastFolderName[count( $sourceFolderLastFolderName ) -2 ];

		$sourceFolder = realpath( $sourceFolder );
		$destinationFolder = realpath( $destinationFolder );

		$wpUploadsFolder = wp_upload_dir();
		$wpUploadsFolder = $wpUploadsFolder['basedir'];

		$sourceFolderLastFolderPath = $wpUploadsFolder . '/' . $sourceFolderLastFolderName . '/';
		$wpUploadsFolder = realpath( $wpUploadsFolder );
		$sourceFolderLastFolderPath = realpath( $sourceFolderLastFolderPath );
		
		$zip = new ZipArchive;
		$totalFileSize = 0;
		$maxSizeInBytes = 3888333;
		$hasMoreData = false;
		$nameAsIdentifier = '';
		$allowToAddToZip = true;
		if( !empty( $resultOfPrevOperation ) ) {
			$allowToAddToZip = false;
		}
		if ( $zip->open( $destinationFolder . "/$folderToPick.zip", ZipArchive::CREATE ) === TRUE ) {
			// Create recursive directory iterator
			/** @var SplFileInfo[] $files */
			$files = new RecursiveIteratorIterator(
			    new RecursiveDirectoryIterator( $sourceFolder ),
			    RecursiveIteratorIterator::LEAVES_ONLY
			);

			foreach ( $files as $name => $file ) {
				
				if( !empty( $resultOfPrevOperation ) ) {
					if( $resultOfPrevOperation == $name ) {
						$allowToAddToZip = true;
					}
				}

				if( !$allowToAddToZip ) {
					continue;
				}
			
			    // Skip directories (they would be added automatically)
			    if ( !$file->isDir() ) {
			    	if( $maxSizeInBytes < $totalFileSize ) {
			    		$hasMoreData = true;
			    		$nameAsIdentifier = $name;
			    		break;
			    	}
			    	$totalFileSize += filesize( $file );
			        // Get real and relative path for current file
			        $filePath = $file->getRealPath();
			        $relativePath = substr( $filePath, strlen( $sourceFolderLastFolderPath ) + 1 );
			        $zip->addFile( $filePath, $relativePath );
			    }
			}
			// Zip archive will be created only after closing object
			$zip->close();
			if( $hasMoreData ) {
				return $nameAsIdentifier;
			}
		}
		return '';
	}

	public function add_folder_to_zip_file( $folder_name, $locationToCreate = '', $locationToPick = '', $action = 'CREATE' ) {
		$locationToCreate = realpath( $locationToCreate );
		$rootPath = realpath( $locationToPick );
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = realpath( $upload_dir );

		$zip = new ZipArchive;
		$proceedFurther = false;
		if( $action == 'CREATE' ) {
			if ( $zip->open( $locationToCreate . "/$folder_name.zip", ZipArchive::CREATE ) === TRUE ) {
				$proceedFurther = true;
			}
		}
		else {
			if ( $zip->open( $locationToCreate . "/$folder_name.zip", ZipArchive::OVERWRITE ) === TRUE ) {
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

		global $wpdb;
		$startingPoint = 0;
		$limit = 200;
		foreach ( $selected_database_tables as $database_table ) {
			$json_content = '';
			$counter = 1;
			do {
				$startingPoint = ( $limit * ( $counter -1 ) );
				$sql_query = "SELECT * FROM $wpdb->prefix$database_table LIMIT $startingPoint, $limit";
				$json_content = $wpdb->get_results( $sql_query, ARRAY_A );
				if( empty( $json_content ) ) { break; }
				if( !empty( $json_content ) && is_array( $json_content ) ) {
					$json_content = array_map( function( $value ) { return str_replace( home_url(), '{{*home_url}}', $value ); }, $json_content );
					// $json_content = json_encode( $json_content, JSON_PRETTY_PRINT );
					$json_content = json_encode( $json_content );
				}
				else {
					$json_content = '';
				}
				$args = array(
					'content'	=>	$json_content,
					'fileName'	=>	$database_table.$counter,
					'fileExtension'	=>	'json',
				);
				$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
				$json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
				$counter++;
			}
			while( !empty( $json_content ) );
		}
		/* making json data for database tables :: end */

		/** code added to manage theme mod data :: start ***/
		$theme_mods_data = get_theme_mods();
		$json_content = json_encode( $theme_mods_data );
		$database_table = 'theme_mods';
		$counter = 1;
		$args = array(
			'content'	=>	$json_content,
			'fileName'	=>	$database_table.$counter,
			'fileExtension'	=>	'json',
		);
		$this->saveContentToDemoPackage( $args, $locationTill = 'demo' );
		$json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
		/** code added to manage theme mod data :: end ***/

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
			$args = array(
				'content'	=>	'',
				'fileName'	=>	'installer',
				'fileExtension'	=>	'json',
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
