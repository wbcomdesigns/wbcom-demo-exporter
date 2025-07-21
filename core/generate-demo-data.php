<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WBCOM_TDE_Generate_Demo_Data' ) ) :
/**
 * Demo Data Generator with improved indexing and UI
 * @version 2.0.0
 */
class WBCOM_TDE_Generate_Demo_Data {
	/**
	 * The single instance of the class.
	 */
	protected static $_instance = null;
	protected static $_parent_dir = 'wbcom-theme-demos';
	
	/**
	 * Main Instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->init_hooks();
	}
	
	/**
	 * Hook into actions and filters.
	 */
	private function init_hooks() {
		add_action( 'wp_loaded', array( $this, 'generate_theme_demo_data' ) );
		add_action( 'wp_ajax_wbcom_export_progress', array( $this, 'ajax_export_progress' ) );
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
	}
	
	/**
	 * Show admin notices
	 */
	public function admin_notices() {
		if ( isset( $_GET['export_status'] ) && $_GET['export_status'] === 'success' ) {
			$last_export = get_option( 'wbcom_last_export_time' );
			?>
			<div class="notice notice-success is-dismissible">
				<p><strong><?php _e( 'Export completed successfully!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></strong></p>
				<?php if ( $last_export ) : ?>
					<p><?php printf( __( 'Export completed at: %s', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ), date( 'Y-m-d H:i:s', $last_export ) ); ?></p>
				<?php endif; ?>
			</div>
			<?php
		}
		
		if ( isset( $_GET['export_status'] ) && $_GET['export_status'] === 'error' ) {
			?>
			<div class="notice notice-error is-dismissible">
				<p><strong><?php _e( 'Export failed!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></strong></p>
				<p><?php echo esc_html( get_transient( 'wbcom_export_error' ) ); ?></p>
			</div>
			<?php
			delete_transient( 'wbcom_export_error' );
		}
	}
	
	/**
	 * Main export function
	 */
	public function generate_theme_demo_data() {
		// Debug logging
		error_log( 'WBCOM Export: generate_theme_demo_data called' );
		error_log( 'WBCOM Export: POST data: ' . print_r( $_POST, true ) );
		
		if ( ! isset( $_POST['wbcom_generate_theme_demo_data'] ) ) { 
			error_log( 'WBCOM Export: No POST data found' );
			return; 
		}
		
		error_log( 'WBCOM Export: Starting export process' );
		
		// Security checks
		if ( ! current_user_can( 'manage_options' ) ) {
			error_log( 'WBCOM Export: Permission denied' );
			wp_die( __( 'You do not have sufficient permissions to perform this action.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ) );
		}
		
		// Verify nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wbcom_export_demo_nonce' ) ) {
			error_log( 'WBCOM Export: Nonce verification failed' );
			wp_die( __( 'Security check failed.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ) );
		}
		
		error_log( 'WBCOM Export: Security checks passed' );
		
		// Disable error display during export to prevent issues
		$original_error_reporting = error_reporting();
		$original_display_errors = ini_get('display_errors');
		@error_reporting(E_ERROR | E_PARSE);
		@ini_set('display_errors', 0);
		@ini_set('display_startup_errors', 0);
		
		// Increase limits for large exports
		@set_time_limit( 0 );
		@ini_set( 'memory_limit', '512M' );
		
		// Start output buffering to catch any warnings
		ob_start();
		
		try {
			error_log( 'WBCOM Export: Starting try block' );
			
			// Start export tracking
			update_option( 'wbcom_export_status', 'running' );
			update_option( 'wbcom_export_progress', 0 );
			
			$installer_info = array();
			$package_info = array();
			
			// Create fresh export directory
			error_log( 'WBCOM Export: Creating export directory' );
			$this->initial_directory_setup();
			
			// Export post types
			error_log( 'WBCOM Export: Exporting post types' );
			$package_info['post_types'] = $this->make_xml_for_post_types();
			error_log( 'WBCOM Export: Post types exported: ' . count( $package_info['post_types'] ) );
			
			// Export database tables with proper indexing
			error_log( 'WBCOM Export: Exporting database tables' );
			$package_info['database_tables'] = $this->make_json_for_database_tables();
			error_log( 'WBCOM Export: Database tables exported: ' . count( $package_info['database_tables'] ) );
			
			// Export plugins info
			error_log( 'WBCOM Export: Getting plugins info' );
			$package_info['plugins'] = $this->get_active_plugins_info();
			error_log( 'WBCOM Export: Plugins found: ' . count( $package_info['plugins'] ) );
			
			// Export upload folders
			error_log( 'WBCOM Export: Exporting upload folders' );
			$package_info['upload_folders'] = $this->export_upload_folders();
			error_log( 'WBCOM Export: Upload folders exported: ' . count( $package_info['upload_folders'] ) );
			
			// Set metadata
			$package_info['created_on'] = date( 'Y-m-d H:i:s' );
			$package_info['export_version'] = '2.0';
			$package_info['site_url'] = home_url();
			$package_info['wp_version'] = get_bloginfo( 'version' );
			$package_info['screenshot'] = isset( $_POST['demo_screenshot'] ) ? $_POST['demo_screenshot'] : '';
			
			// Add plugins.json URL to package info
			$upload_dir_url = $this->get_theme_demo_location( 'url' );
			$package_info['plugins_json'] = $upload_dir_url . 'plugins.json';
			
			// Save package.json
			$args = array(
				'content' => json_encode( $package_info, JSON_PRETTY_PRINT ),
				'fileName' => 'package',
				'fileExtension' => 'json',
			);
			$this->saveContentToDemoPackage( $args, 'demo' );
			
			// Save plugins.json with default recommended plugins
			$this->save_plugins_json();
			
			// Update installer.json
			error_log( 'WBCOM Export: Updating installer.json' );
			$this->update_installer_json();
			
			// Save export history
			$this->save_export_history();
			
			// Update last export time
			update_option( 'wbcom_last_export_time', time() );
			update_option( 'wbcom_export_status', 'completed' );
			
			error_log( 'WBCOM Export: Export completed successfully' );
			error_log( 'WBCOM Export: Redirecting to: ' . add_query_arg( 'export_status', 'success', wp_get_referer() ) );
			
			// Clean output buffer
			ob_end_clean();
			
			// Restore original error settings
			@error_reporting($original_error_reporting);
			@ini_set('display_errors', $original_display_errors);
			
			// Redirect with success message
			wp_redirect( add_query_arg( 'export_status', 'success', wp_get_referer() ) );
			exit;
			
		} catch ( Exception $e ) {
			// Handle errors
			error_log( 'WBCOM Export: Error occurred: ' . $e->getMessage() );
			error_log( 'WBCOM Export: Stack trace: ' . $e->getTraceAsString() );
			
			set_transient( 'wbcom_export_error', $e->getMessage(), 300 );
			update_option( 'wbcom_export_status', 'failed' );
			
			// Clean output buffer
			ob_end_clean();
			
			// Restore original error settings
			@error_reporting($original_error_reporting);
			@ini_set('display_errors', $original_display_errors);
			
			// Redirect with error message
			wp_redirect( add_query_arg( 'export_status', 'error', wp_get_referer() ) );
			exit;
		}
	}
	
	/**
	 * Create fresh export directory
	 */
	public function initial_directory_setup() {
		$theme_slug = isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : wp_get_theme()->get( 'Name' );
		$theme_slug = sanitize_title( $theme_slug );
		$demo_slug = 'theme_demo'; // Fixed to maintain compatibility
		
		$upload = wp_upload_dir();
		$upload_dir = $upload['basedir'];
		$upload_dir = $upload_dir . '/' . self::$_parent_dir;
		
		// Create parent directory
		if ( !is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}
		
		// Always ensure index.php exists in parent directory
		if ( !file_exists( $upload_dir . '/index.php' ) ) {
			$args = array(
				'content' => '<?php // Silence is golden',
				'fileName' => 'index',
				'fileExtension' => 'php',
			);
			$this->saveContentToDemoPackage( $args, 'parent' );
		}
		
		// Create empty installer.json if not exists
		if ( !file_exists( $upload_dir . '/installer.json' ) ) {
			$args = array(
				'content' => '{}',
				'fileName' => 'installer',
				'fileExtension' => 'json',
			);
			$this->saveContentToDemoPackage( $args, 'parent' );
		}
		
		// Create theme directory
		$upload_dir = $upload_dir . '/' . $theme_slug;
		if ( !is_dir( $upload_dir ) ) {
			wp_mkdir_p( $upload_dir );
		}
		
		// Always ensure index.php exists in theme directory
		if ( !file_exists( $upload_dir . '/index.php' ) ) {
			$args = array(
				'content' => '<?php // Silence is golden',
				'fileName' => 'index',
				'fileExtension' => 'php',
			);
			$this->saveContentToDemoPackage( $args, 'theme' );
		}
		
		// Create demo directory (always fresh)
		$upload_dir = $upload_dir . '/' . $demo_slug;
		if ( is_dir( $upload_dir ) ) {
			$this->recursiveRemoveDirectory( $upload_dir . '/' );
		}
		wp_mkdir_p( $upload_dir );
		$args = array(
			'content' => '<?php // Silence is golden',
			'fileName' => 'index',
			'fileExtension' => 'php',
		);
		$this->saveContentToDemoPackage( $args, 'demo' );
		
		return $upload_dir . '/';
	}
	
	/**
	 * Export database tables with proper zero-padded indexing
	 */
	public function make_json_for_database_tables() {
		global $wpdb;
		$json_urls = array();
		$upload_dir_url = $this->get_theme_demo_location( 'url' );
		
		// Default tables to export if none selected
		$default_tables = array(
			'posts', 'postmeta', 'terms', 'term_taxonomy', 'term_relationships',
			'options', 'comments', 'commentmeta', 'links', 'termmeta'
		);
		
		$selected_database_tables = isset( $_POST['selected_database_tables'] ) ? $_POST['selected_database_tables'] : $default_tables;
		if( !is_array( $selected_database_tables ) ) {
			$selected_database_tables = array( $selected_database_tables );
		}
		
		// Sanitize table names
		$selected_database_tables = array_map( 'sanitize_key', $selected_database_tables );
		
		$startingPoint = 0;
		$limit = 200;
		
		foreach ( $selected_database_tables as $database_table ) {
			$table_name = $wpdb->prefix . $database_table;
			
			// Check if table exists
			if ( $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) ) != $table_name ) {
				continue;
			}
			
			$json_content = '';
			$counter = 1;
			
			do {
				$startingPoint = ( $limit * ( $counter - 1 ) );
				$sql_query = "SELECT * FROM $table_name LIMIT $startingPoint, $limit";
				$json_content = $wpdb->get_results( $sql_query, ARRAY_A );
				
				if( empty( $json_content ) ) { 
					break; 
				}
				
				// Process the content
				if( !empty( $json_content ) && is_array( $json_content ) ) {
					if ( $database_table == 'options' ) {
						$json_content = $this->process_options_data( $json_content );
					} else {
						$json_content = $this->process_general_data( $json_content );
					}
					$json_content = json_encode( $json_content );
				} else {
					$json_content = '';
				}
				
				// Use zero-padded counter for proper sorting
				$padded_counter = str_pad( $counter, 4, '0', STR_PAD_LEFT );
				$args = array(
					'content' => $json_content,
					'fileName' => $database_table . '_' . $padded_counter,
					'fileExtension' => 'json',
				);
				
				$this->saveContentToDemoPackage( $args, 'demo' );
				$json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
				$counter++;
			}
			while( !empty( $json_content ) );
		}
		
		// Export theme mods
		$theme_mods_data = get_theme_mods();
		$json_content = json_encode( $theme_mods_data );
		$args = array(
			'content' => $json_content,
			'fileName' => 'theme_mods_0001',
			'fileExtension' => 'json',
		);
		$this->saveContentToDemoPackage( $args, 'demo' );
		$json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
		
		return $json_urls;
	}
	
	/**
	 * Process options table data
	 */
	private function process_options_data( $rows ) {
		$processed = array();
		$home_url = untrailingslashit( home_url() );
		$home_url_ssl = str_replace( 'http://', 'https://', $home_url );
		
		// Options to exclude
		$exclude = array(
			'cron', '_transient%', '_site_transient%', 'wbcom_export%',
			'auth_key', 'auth_salt', 'logged_in_key', 'logged_in_salt',
			'nonce_key', 'nonce_salt', 'wbcom_last_export_time'
		);
		
		foreach( $rows as $row ) {
			// Check exclusions
			$skip = false;
			foreach ( $exclude as $pattern ) {
				if ( strpos( $pattern, '%' ) !== false ) {
					$pattern = str_replace( '%', '', $pattern );
					if ( strpos( $row['option_name'], $pattern ) === 0 ) {
						$skip = true;
						break;
					}
				} elseif ( $row['option_name'] == $pattern ) {
					$skip = true;
					break;
				}
			}
			
			if ( $skip ) continue;
			
			// Process value
			if ( isset( $row['option_value'] ) ) {
				$option_value = maybe_unserialize( $row['option_value'] );
				
				// Replace URLs in arrays and strings
				if ( is_array( $option_value ) ) {
					array_walk_recursive( $option_value, function( &$value ) use ( $home_url, $home_url_ssl ) {
						if ( is_string( $value ) ) {
							$value = str_replace( 
								array( $home_url, $home_url_ssl ), 
								'{{*home_url}}', 
								$value 
							);
						}
					});
				} elseif ( is_string( $option_value ) ) {
					$option_value = str_replace( 
						array( $home_url, $home_url_ssl ), 
						'{{*home_url}}', 
						$option_value 
					);
				}
				
				$row['option_value'] = maybe_serialize( $option_value );
			}
			
			$processed[] = $row;
		}
		
		return $processed;
	}
	
	/**
	 * Process general table data
	 */
	private function process_general_data( $rows ) {
		$home_url = untrailingslashit( home_url() );
		$home_url_ssl = str_replace( 'http://', 'https://', $home_url );
		
		foreach ( $rows as &$row ) {
			foreach ( $row as $key => &$value ) {
				if ( is_string( $value ) ) {
					$value = str_replace( 
						array( $home_url, $home_url_ssl ), 
						'{{*home_url}}', 
						$value 
					);
				}
			}
		}
		
		return $rows;
	}
	
	/**
	 * Export post types
	 */
	public function make_xml_for_post_types() {
		$xml_urls = array();
		$upload_dir_url = $this->get_theme_demo_location( 'url' );
		
		// Get selected or all public post types
		$selected_post_types = isset( $_POST['selected_post_types'] ) ? $_POST['selected_post_types'] : array();
		if( empty( $selected_post_types ) ) {
			$selected_post_types = get_post_types( array( 'public' => true ), 'names' );
			unset( $selected_post_types['attachment'] ); // Skip media by default
		}
		
		if( !is_array( $selected_post_types ) ) {
			$selected_post_types = array( $selected_post_types );
		}
		
		require_once( dirname( __FILE__ ) . '/xml-exporter/wbcom-xml-exporter.php' );
		
		foreach ( $selected_post_types as $post_type_slug ) {
			$args = array( 'content' => $post_type_slug );
			ob_start();
			// Suppress any errors during XML export
			@export_wp( $args );
			$xml_content = ob_get_clean();
			
			$args = array(
				'content' => $xml_content,
				'fileName' => $post_type_slug,
				'fileExtension' => 'xml',
			);
			$this->saveContentToDemoPackage( $args, 'demo' );
			$xml_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
		}
		
		return $xml_urls;
	}
	
	/**
	 * Get active plugins info
	 */
	private function get_active_plugins_info() {
		$selected_plugins = isset( $_POST['selected_plugins'] ) ? $_POST['selected_plugins'] : get_option( 'active_plugins', array() );
		
		if( !is_array( $selected_plugins ) ) {
			$selected_plugins = array( $selected_plugins );
		}
		
		$_selected_plugins = array();
		$plugins = get_plugins();
		
		if( !empty( $selected_plugins ) ) {
			foreach ( $selected_plugins as $value ) {
				if ( isset( $plugins[$value] ) ) {
					$plugin_name = $plugins[$value]['Name'];
					$plugin_slug = explode( '/', $value );
					$plugin_slug = $plugin_slug[0];
					$_selected_plugins[$value] = array(
						'name' => $plugin_name,
						'slug' => $plugin_slug,
						'version' => $plugins[$value]['Version'],
					);
				}
			}
		}
		
		return $_selected_plugins;
	}
	
	/**
	 * Export upload folders
	 */
	private function export_upload_folders() {
		$upload_urls = array();
		$selected_upload_folders = isset( $_POST['selected_upload_folders'] ) ? $_POST['selected_upload_folders'] : array();
		
		// If none selected, export year folders by default
		if ( empty( $selected_upload_folders ) ) {
			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'];
			$year_folders = glob( $upload_dir . '/20*', GLOB_ONLYDIR );
			foreach ( $year_folders as $folder ) {
				$selected_upload_folders[] = basename( $folder );
			}
		}
		
		if( !is_array( $selected_upload_folders ) ) {
			$selected_upload_folders = array( $selected_upload_folders );
		}
		
		// Sanitize folder names
		$selected_upload_folders = array_map( 'sanitize_file_name', $selected_upload_folders );
		
		if( !empty( $selected_upload_folders ) ) {
			$upload = wp_upload_dir();
			$upload_dir = $upload['basedir'] . '/';
			
			foreach ( $selected_upload_folders as $selected_parent_folder ) {
				$locationToCreate = $this->get_theme_demo_location( 'path' );
				$locationToPick = $upload_dir . $selected_parent_folder . '/';
				
				// Create temp folder
				$temp_folder_location = $this->get_theme_demo_location( 'path' ) . $selected_parent_folder;
				if ( !is_dir( $temp_folder_location ) ) {
					wp_mkdir_p( $temp_folder_location );
					
					// Add index.php for security
					file_put_contents( $temp_folder_location . '/index.php', '<?php // Silence is golden' );
				}
				
				// Process subfolders
				if ( is_dir( $upload_dir . $selected_parent_folder ) ) {
					$files_n_folders = array_diff( scandir( $upload_dir . $selected_parent_folder ), array( '..', '.' ) );
					
					foreach ( $files_n_folders as $key => $sub_folder ) {
						if( is_dir( $upload_dir . $selected_parent_folder . '/' . $sub_folder ) ) {
							$sourceFolder = $upload_dir . $selected_parent_folder . '/' . $sub_folder . '/';
							$destinationFolder = $this->get_theme_demo_location( 'path' ) . $selected_parent_folder . '/';
							
							$thisFolderIsOver = false;
							$resultOfPrevOperation = '';
							$counter = 1;
							
							do {
								// Use zero-padded counter
								$padded_counter = str_pad( $counter, 3, '0', STR_PAD_LEFT );
								$resultOfPrevOperation = $this->createZip( 
									$sourceFolder, 
									$destinationFolder, 
									$sub_folder . "_" . $padded_counter, 
									$resultOfPrevOperation 
								);
								
								if( empty( $resultOfPrevOperation ) ) {
									$thisFolderIsOver = true;
								}
								
								$upload_dir_url = $this->get_theme_demo_location( 'url' );
								$upload_dir_url = $upload_dir_url . $selected_parent_folder . "/" . $sub_folder . "_" . $padded_counter . ".zip";
								$upload_urls[] = $upload_dir_url;
								$counter++;
							}
							while( ( $thisFolderIsOver == false ) );
						}
					}
				}
			}
		}
		
		return $upload_urls;
	}
	
	/**
	 * Create ZIP with improved chunk handling
	 */
	public function createZip( $sourceFolder = '', $destinationFolder = '', $folderToPick = '', $resultOfPrevOperation = '' ) {
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
		$maxSizeInBytes = 52428800; // 50MB chunks
		$hasMoreData = false;
		$nameAsIdentifier = '';
		$allowToAddToZip = true;
		
		if( !empty( $resultOfPrevOperation ) ) {
			$allowToAddToZip = false;
		}
		
		if ( $zip->open( $destinationFolder . "/$folderToPick.zip", ZipArchive::CREATE ) === TRUE ) {
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
				
				if ( !$file->isDir() ) {
					if( $maxSizeInBytes < $totalFileSize ) {
						$hasMoreData = true;
						$nameAsIdentifier = $name;
						break;
					}
					$totalFileSize += filesize( $file );
					$filePath = $file->getRealPath();
					$relativePath = substr( $filePath, strlen( $sourceFolderLastFolderPath ) + 1 );
					$zip->addFile( $filePath, $relativePath );
				}
			}
			
			$zip->close();
			
			if( $hasMoreData ) {
				return $nameAsIdentifier;
			}
		}
		
		return '';
	}
	
	/**
	 * Save default plugins.json
	 */
	private function save_plugins_json() {
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
				"name" => "Elementor",
				"slug" => "elementor",
				"required" => true,
				"version" => "2.3.2.1",
				"force_activation" => false,
				"force_deactivation" => false,
				"external_url" => "",
				"description" => "The most advanced frontend drag & drop page builder. Create high-end, pixel perfect websites at record speeds. Any theme, any page, any design."
			)
		);
		
		$args = array(
			'content' => json_encode( $plugins_info, JSON_PRETTY_PRINT ),
			'fileName' => 'plugins',
			'fileExtension' => 'json',
		);
		$this->saveContentToDemoPackage( $args, 'demo' );
	}
	
	/**
	 * Update installer.json
	 */
	private function update_installer_json() {
		$theme_slug = isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : wp_get_theme()->get( 'Name' );
		$theme_slug = sanitize_title( $theme_slug );
		$demo_slug = 'theme_demo';
		
		error_log( 'WBCOM Export: Theme slug: ' . $theme_slug );
		
		$installer_info = array(
			'theme_name' => isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : wp_get_theme()->get( 'Name' ),
			'theme_slug' => $theme_slug,
			'demo_name' => isset( $_POST['demo_slug'] ) ? $_POST['demo_slug'] : 'Main Demo',
			'demo_slug' => $demo_slug,
			'package' => $this->get_theme_demo_location( 'url' ),
			'created_on' => date( 'Y-m-d H:i:s' ),
			'screenshot' => isset( $_POST['demo_screenshot'] ) ? $_POST['demo_screenshot'] : '',
		);
		
		// Get existing installer data
		$url_to_request = $this->get_theme_demo_location( 'url', 'parent' ) . 'installer.json';
		$response = wp_remote_get( $url_to_request, array( 'timeout' => 120 ) );
		$retrieved_data = array();
		
		if ( !is_wp_error( $response ) ) {
			if ( isset( $response['response']['code'] ) && ( $response['response']['code'] == 200 ) ) {
				$response = isset( $response['body'] ) ? $response['body'] : '';
				if( !empty( $response ) ) {
					$retrieved_data = json_decode( $response, true );
				}
			}
		}
		
		// Update data
		if( !array_key_exists( $theme_slug, $retrieved_data ) ) {
			$retrieved_data[$theme_slug] = array();
		}
		$retrieved_data[$theme_slug][$demo_slug] = $installer_info;
		
		// Save installer.json
		$args = array(
			'content' => json_encode( $retrieved_data, JSON_PRETTY_PRINT ),
			'fileName' => 'installer',
			'fileExtension' => 'json',
		);
		$this->saveContentToDemoPackage( $args, 'parent' );
		
		error_log( 'WBCOM Export: installer.json saved to: ' . $this->get_theme_demo_location( 'path', 'parent' ) . 'installer.json' );
	}
	
	/**
	 * Save export history
	 */
	private function save_export_history() {
		$history = get_option( 'wbcom_export_history', array() );
		
		// Add new entry
		$history[] = array(
			'date' => date( 'Y-m-d H:i:s' ),
			'status' => 'success',
			'size' => $this->get_directory_size( $this->get_theme_demo_location( 'path' ) ),
		);
		
		// Keep only last 10 entries
		$history = array_slice( $history, -10 );
		
		update_option( 'wbcom_export_history', $history );
	}
	
	/**
	 * Get directory size
	 */
	private function get_directory_size( $path ) {
		$size = 0;
		
		if ( is_dir( $path ) ) {
			$iterator = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator( $path, RecursiveDirectoryIterator::SKIP_DOTS )
			);
			
			foreach ( $iterator as $file ) {
				if ( $file->isFile() ) {
					$size += $file->getSize();
				}
			}
		}
		
		return $size;
	}
	
	/**
	 * AJAX handler for export progress
	 */
	public function ajax_export_progress() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}
		
		$status = get_option( 'wbcom_export_status', 'idle' );
		$progress = get_option( 'wbcom_export_progress', 0 );
		
		wp_send_json_success( array(
			'status' => $status,
			'progress' => $progress,
		) );
	}
	
	/**
	 * Helper functions
	 */
	public function recursiveRemoveDirectory( $directory ) {
		foreach( glob("{$directory}/*" ) as $file ) {
			if( is_dir( $file ) ) {
				$this->recursiveRemoveDirectory( $file );
			} else {
				unlink( $file );
			}
		}
		if ( is_dir( $directory ) ) {
			rmdir( $directory );
		}
	}
	
	public function get_theme_demo_location( $value = 'path', $locationTill = 'demo', $theme_slug = '', $demo_slug = '' ) {
		if( empty( $theme_slug ) ) {
			$theme_slug = isset( $_POST['theme_slug'] ) ? $_POST['theme_slug'] : wp_get_theme()->get( 'Name' );
			$theme_slug = sanitize_title( $theme_slug );
		}
		if( empty( $demo_slug ) ) {
			$demo_slug = isset( $_POST['demo_slug'] ) ? $_POST['demo_slug'] : 'Main Demo';
			$demo_slug = sanitize_title( $demo_slug );
		}
		$demo_slug = 'theme_demo'; // Fixed for compatibility
		
		$upload = wp_upload_dir();
		if( $value == 'path' ) {
			$upload_dir = $upload['basedir'];
		} else if( $value == 'url' ) {
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

// Initialize
WBCOM_TDE_Generate_Demo_Data::instance();