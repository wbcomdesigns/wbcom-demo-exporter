<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WBCOM_TDE_Generate_Demo_Data' ) ) :
/**
 * Handles theme demo data generation
 *
 * @class WBCOM_TDE_Generate_Demo_Data
 * @version 1.1.0
 */
class WBCOM_TDE_Generate_Demo_Data {
    /**
     * The single instance of the class.
     *
     * @var WBCOM_TDE_Generate_Demo_Data
     * @since 1.0.0
     */
    protected static $_instance = null;
    
    /**
     * Parent directory
     *
     * @var string
     * @since 1.0.0
     */
    protected static $_parent_dir = 'wbcom-theme-demos';
    
    /**
     * Log of export operations
     *
     * @var array
     * @since 1.1.0
     */
    protected $export_log = array();
    
    /**
     * Main WBCOM_TDE_Generate_Demo_Data Instance.
     *
     * @since 1.0.0
     * @static
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
     * @since 1.0.0
     */
    private function init_hooks() {
        add_action('admin_init', array($this, 'process_export_request'));
    }
    
    /**
     * Process the export request
     * 
     * @since 1.1.0
     */
    public function process_export_request() {
        if (!isset($_POST['wbcom_generate_theme_demo_data'])) {
            return;
        }
        
        // Verify required fields
        if (empty($_POST['demo_slug'])) {
            wp_redirect(admin_url('admin.php?page=wbcom-theme-demo-exporter&export=error&reason=missing_fields'));
            exit;
        }
        
        // Start timing for performance tracking
        $start_time = microtime(true);
        
        try {
            // Log the start of export
            $this->log_message('Starting theme demo export process');
            
            // Generate the demo data
            $this->generate_theme_demo_data();
            
            // Calculate execution time
            $execution_time = microtime(true) - $start_time;
            $this->log_message(sprintf('Export completed in %.2f seconds', $execution_time));
            
            // Save log to file
            $this->save_export_log();
            
            // Redirect with success message
            wp_redirect(admin_url('admin.php?page=wbcom-theme-demo-exporter&export=success'));
            exit;
            
        } catch (Exception $e) {
            $this->log_message('Export error: ' . $e->getMessage(), 'error');
            $this->save_export_log();
            
            wp_redirect(admin_url('admin.php?page=wbcom-theme-demo-exporter&export=error&reason=exception'));
            exit;
        }
    }

    /**
     * Generate theme demo data based on form input
     * 
     * @since 1.0.0
     */
    public function generate_theme_demo_data() {
        $installer_info = array();
        $package_info = array();
        
        // Initial setup for directories
        $this->log_message('Setting up directories');
        $this->initial_directory_setup();
        
        // Process post types export
        $this->log_message('Processing post types export');
        $package_info['post_types'] = $this->make_xml_for_post_types();
        
        // Process database tables export
        $this->log_message('Processing database tables export');
        $package_info['database_tables'] = $this->make_json_for_database_tables();
        
        // Process plugins information
        $this->log_message('Processing plugins information');
        $selected_plugins = isset($_POST['selected_plugins']) ? $_POST['selected_plugins'] : array();
        if (!is_array($selected_plugins)) {
            $selected_plugins = array($selected_plugins);
        }
        
        $_selected_plugins = array();
        $plugins = get_plugins();
        if (!empty($selected_plugins)) {
            foreach ($selected_plugins as $value) {
                $plugin_name = $plugins[$value]['Name'];
                $plugin_slug = explode('/', $value);
                $plugin_slug = $plugin_slug[0];
                $_selected_plugins[$value] = array(
                    'name' => $plugin_name,
                    'slug' => $plugin_slug,
                );
            }
        }
        $package_info['plugins'] = $_selected_plugins;
        
        // Process uploads folders
        $this->log_message('Processing upload folders');
        $selected_upload_folders = isset($_POST['selected_upload_folders']) ? $_POST['selected_upload_folders'] : array();
        if (!is_array($selected_upload_folders)) {
            $selected_upload_folders = array($selected_upload_folders);
        }
        
        $upload_dir_urls = array();
        if (!empty($selected_upload_folders)) {
            $upload = wp_upload_dir();
            $upload_dir = $upload['basedir'] . '/';
            
            foreach ($selected_upload_folders as $selected_parent_folder) {
                $this->log_message("Processing upload folder: {$selected_parent_folder}");
                $locationToCreate = $this->get_theme_demo_location('path');
                $locationToPick = $upload_dir . $selected_parent_folder . '/';
                
                // Create folder in demo package
                $temp_folder_location = $this->get_theme_demo_location('path') . $selected_parent_folder;
                if (!is_dir($temp_folder_location)) {
                    wp_mkdir_p($temp_folder_location);
                }
                
                // Process subfolders
                $files_n_folders = array_diff(scandir($upload_dir . $selected_parent_folder), array('..', '.'));
                foreach ($files_n_folders as $key => $sub_folder) {
                    if (is_dir($upload_dir . $selected_parent_folder . '/' . $sub_folder)) {
                        $sourceFolder = $upload_dir . $selected_parent_folder . '/' . $sub_folder . '/';
                        $destinationFolder = $this->get_theme_demo_location('path') . $selected_parent_folder . '/';
                        
                        $thisFolderIsOver = false;
                        $resultOfPrevOperation = '';
                        $counter = 1;
                        
                        do {
                            $this->log_message("Creating zip for {$sub_folder} (part {$counter})");
                            $resultOfPrevOperation = $this->createZip(
                                $sourceFolder, 
                                $destinationFolder, 
                                $sub_folder . "-break-{$counter}", 
                                $resultOfPrevOperation
                            );
                            
                            if (empty($resultOfPrevOperation)) {
                                $thisFolderIsOver = true;
                            }
                            
                            $upload_dir_url = $this->get_theme_demo_location('url');
                            $upload_dir_url = $upload_dir_url . $selected_parent_folder . "/" . $sub_folder . "-break-{$counter}.zip";
                            $upload_dir_urls[] = $upload_dir_url;
                            
                            $counter++;
                        } while ($thisFolderIsOver == false);
                    }
                }
            }
            $package_info['upload_folders'] = $upload_dir_urls;
        }
        
        // Store additional package info
        $package_info['screenshot'] = isset($_POST['demo_screenshot']) ? $_POST['demo_screenshot'] : '';
        $installer_info['screenshot'] = isset($_POST['demo_screenshot']) ? $_POST['demo_screenshot'] : '';
        $package_info['created_on'] = date("Y-m-d H:i:s");
        $installer_info['created_on'] = date("Y-m-d H:i:s");
        
        // Save package.json file
        $this->log_message('Creating package.json file');
        $args = array(
            'content' => json_encode($package_info, JSON_PRETTY_PRINT),
            'fileName' => 'package',
            'fileExtension' => 'json',
        );
        $this->saveContentToDemoPackage($args, $locationTill = 'demo');
        
        // Create plugins.json file
        $this->log_message('Creating plugins.json file');
        $plugins_info = $this->get_default_plugins_info();
        $args = array(
            'content' => json_encode($plugins_info, JSON_PRETTY_PRINT),
            'fileName' => 'plugins',
            'fileExtension' => 'json',
        );
        $this->saveContentToDemoPackage($args, $locationTill = 'demo');
        
        // Create installer.json file
        $this->log_message('Creating installer.json file');
        $theme_slug = isset($_POST['theme_slug']) ? $_POST['theme_slug'] : '';
        $installer_info['theme_name'] = $theme_slug;
        $theme_slug = sanitize_title($theme_slug);
        $installer_info['theme_slug'] = $theme_slug;
        
        $demo_slug = isset($_POST['demo_slug']) ? $_POST['demo_slug'] : '';
        $installer_info['demo_name'] = $demo_slug;
        $demo_slug = sanitize_title($demo_slug);
        $demo_slug = 'theme_demo'; // to make one folder only
        $installer_info['demo_slug'] = $demo_slug;
        $installer_info['package'] = $this->get_theme_demo_location('url');
        
        $url_to_request = $this->get_theme_demo_location('url', $locationTill = 'parent');
        $url_to_request .= 'installer.json';
        
        $response = wp_remote_get($url_to_request, array('timeout' => 120));
        $retrieved_data = array();
        
        if (!is_wp_error($response)) {
            if (isset($response['response']['code']) && ($response['response']['code'] == 200)) {
                $response = isset($response['body']) ? $response['body'] : '';
                if (!empty($response)) {
                    $retrieved_data = json_decode($response, true);
                }
            }
        }
        
        if (!array_key_exists($theme_slug, $retrieved_data)) {
            $retrieved_data[$theme_slug] = array();
        }
        
        $retrieved_data[$theme_slug][$demo_slug] = $installer_info;
        
        $args = array(
            'content' => json_encode($retrieved_data, JSON_PRETTY_PRINT),
            'fileName' => 'installer',
            'fileExtension' => 'json',
        );
        
        $this->saveContentToDemoPackage($args, $locationTill = 'parent');
        
        $this->log_message('Export process completed successfully');
    }
    
    /**
     * Log message to export log
     *
     * @param string $message Message to log
     * @param string $type Log message type (info, warning, error)
     * @since 1.1.0
     */
    protected function log_message($message, $type = 'info') {
        $this->export_log[] = array(
            'time' => date('Y-m-d H:i:s'),
            'message' => $message,
            'type' => $type
        );
    }
    
    /**
     * Save export log to file
     *
     * @since 1.1.0
     */
    protected function save_export_log() {
        $log_content = json_encode($this->export_log, JSON_PRETTY_PRINT);
        $theme_slug = isset($_POST['theme_slug']) ? sanitize_title($_POST['theme_slug']) : 'theme';
        $demo_slug = 'theme_demo'; // to make one folder only
        
        $args = array(
            'content' => $log_content,
            'fileName' => 'export-log',
            'fileExtension' => 'json',
        );
        
        $this->saveContentToDemoPackage($args, $locationTill = 'demo');
    }
    
    /**
     * Get default plugins info for plugins.json
     *
     * @return array Default plugins information
     * @since 1.1.0
     */
    protected function get_default_plugins_info() {
        return array(
            array(
                "name" => "Wbcom Essential",
                "slug" => "wbcom-essential",
                "required" => true,
                "version" => "1.0.0",
                "force_activation" => false,
                "force_deactivation" => false,
                "external_url" => "",
                "description" => "Wbcom Essential is the required plugin to use the theme to its maximum extent."
            ),
            array(
                "name" => "Buddypress",
                "slug" => "buddypress",
                "required" => true,
                "force_activation" => false,
                "force_deactivation" => false,
                "external_url" => "",
                "description" => "BuddyPress adds community features to WordPress. Member Profiles, Activity Streams, Direct Messaging, Notifications, and more!"
            ),
            array(
                "name" => "bbPress",
                "slug" => "bbpress",
                "required" => true,
                "force_activation" => false,
                "force_deactivation" => false,
                "external_url" => "",
                "description" => "bbPress is forum software with a twist from the creators of WordPress."
            ),
            array(
                "name" => "Elementor",
                "slug" => "elementor",
                "required" => true,
                "force_activation" => false,
                "force_deactivation" => false,
                "external_url" => "",
                "description" => "The most advanced frontend drag & drop page builder. Create high-end, pixel perfect websites at record speeds. Any theme, any page, any design."
            ),
            array(
                "name" => "WooCommerce",
                "slug" => "woocommerce",
                "required" => false,
                "force_activation" => false,
                "force_deactivation" => false,
                "external_url" => "",
                "description" => "An eCommerce toolkit that helps you sell anything. Beautifully."
            )
        );
    }
    
    /**
     * Create ZIP archive for upload folders
     *
     * @param string $sourceFolder Source folder path
     * @param string $destinationFolder Destination folder path
     * @param string $folderToPick Folder name to archive
     * @param string $resultOfPrevOperation Previous operation result
     * @return string Result of current operation
     * @since 1.0.0
     */
    public function createZip($sourceFolder = '', $destinationFolder = '', $folderToPick = '', $resultOfPrevOperation = '') {
        // Get the source folder's last directory name
        $sourceFolderLastFolderName = explode('/', $sourceFolder);
        $sourceFolderLastFolderName = array_filter($sourceFolderLastFolderName);
        $sourceFolderLastFolderName = array_values($sourceFolderLastFolderName);
        $sourceFolderLastFolderName = $sourceFolderLastFolderName[count($sourceFolderLastFolderName) - 2];
        
        $sourceFolder = realpath($sourceFolder);
        $destinationFolder = realpath($destinationFolder);
        
        $wpUploadsFolder = wp_upload_dir();
        $wpUploadsFolder = $wpUploadsFolder['basedir'];
        
        $sourceFolderLastFolderPath = $wpUploadsFolder . '/' . $sourceFolderLastFolderName . '/';
        $wpUploadsFolder = realpath($wpUploadsFolder);
        $sourceFolderLastFolderPath = realpath($sourceFolderLastFolderPath);
        
        $zip = new ZipArchive;
        $totalFileSize = 0;
        $maxSizeInBytes = 6888333; // Maximum size per ZIP file
        $hasMoreData = false;
        $nameAsIdentifier = '';
        $allowToAddToZip = true;
        
        if (!empty($resultOfPrevOperation)) {
            $allowToAddToZip = false;
        }
        
        if ($zip->open($destinationFolder . "/$folderToPick.zip", ZipArchive::CREATE) === TRUE) {
            // Create recursive directory iterator
            /** @var SplFileInfo[] $files */
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($sourceFolder),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($files as $name => $file) {
                if (!empty($resultOfPrevOperation)) {
                    if ($resultOfPrevOperation == $name) {
                        $allowToAddToZip = true;
                    }
                }
                
                if (!$allowToAddToZip) {
                    continue;
                }
                
                // Skip directories (they would be added automatically)
                if (!$file->isDir()) {
                    if ($maxSizeInBytes < $totalFileSize) {
                        $hasMoreData = true;
                        $nameAsIdentifier = $name;
                        break;
                    }
                    
                    $totalFileSize += filesize($file);
                    
                    // Get real and relative path for current file
                    $filePath = $file->getRealPath();
                    $relativePath = substr($filePath, strlen($sourceFolderLastFolderPath) + 1);
                    $zip->addFile($filePath, $relativePath);
                }
            }
            
            // Zip archive will be created only after closing object
            $zip->close();
            
            if ($hasMoreData) {
                return $nameAsIdentifier;
            }
        }
        
        return '';
    }
    
    /**
     * Create JSON files for database tables
     * 
     * @return array Array of JSON file URLs
     * @since 1.0.0
     */
    public function make_json_for_database_tables() {
        $json_urls = array();
        $upload_dir_url = $this->get_theme_demo_location('url');
        
        // Process database tables
        $selected_database_tables = isset($_POST['selected_database_tables']) ? $_POST['selected_database_tables'] : array();
        if (!is_array($selected_database_tables)) {
            $selected_database_tables = array($selected_database_tables);
        }
        
        global $wpdb;
        $startingPoint = 0;
        $limit = 200; // Process 200 rows at a time
        
        foreach ($selected_database_tables as $database_table) {
            $this->log_message("Processing database table: {$database_table}");
            $json_content = '';
            $counter = 1;
            
            do {
                $startingPoint = ($limit * ($counter - 1));
                $sql_query = "SELECT * FROM {$wpdb->prefix}{$database_table} LIMIT {$startingPoint}, {$limit}";
                $json_content = $wpdb->get_results($sql_query, ARRAY_A);
                
                if (empty($json_content)) {
                    break;
                }
                
                if (!empty($json_content) && is_array($json_content)) {
                    if ($database_table == 'options') {
                        $json_content_ = array();
                        
                        foreach ($json_content as $content) {
                            if (isset($content['option_value'])) {
                                $option_value = maybe_unserialize($content['option_value']);
                                
                                if (is_array($option_value) || is_string($option_value)) {
                                    // Replace site URLs with placeholder
                                    if (is_string($option_value)) {
                                        $option_value = str_replace(get_site_url(), '{{*home_url}}', $option_value);
                                        $content['option_value'] = maybe_serialize($option_value);
                                    }
                                }
                            }
                            
                            $json_content_[] = $content;
                        }
                        
                        $json_content = json_encode($json_content_);
                    } else {
                        // Replace URLs in other tables
                        $json_content = array_map(function($value) {
                            if (is_array($value)) {
                                return array_map(function($v) {
                                    return is_string($v) ? str_replace(home_url(), '{{*home_url}}', $v) : $v;
                                }, $value);
                            }
                            return $value;
                        }, $json_content);
                        
                        $json_content = json_encode($json_content);
                    }
                } else {
                    $json_content = '';
                }
                
                $args = array(
                    'content' => $json_content,
                    'fileName' => $database_table . $counter,
                    'fileExtension' => 'json',
                );
                
                $this->saveContentToDemoPackage($args, $locationTill = 'demo');
                $json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
                $counter++;
            } while (!empty($json_content));
        }
        
        // Process theme mods
        $this->log_message("Processing theme mods");
        $theme_mods_data = get_theme_mods();
        $json_content = json_encode($theme_mods_data);
        $database_table = 'theme_mods';
        $counter = 1;
        
        $args = array(
            'content' => $json_content,
            'fileName' => $database_table . $counter,
            'fileExtension' => 'json',
        );
        
        $this->saveContentToDemoPackage($args, $locationTill = 'demo');
        $json_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
        
        return $json_urls;
    }
    
    /**
     * Generate XML files for post types
     * 
     * @return array Array of XML file URLs
     * @since 1.0.0
     */
    public function make_xml_for_post_types() {
        $xml_urls = array();
        $upload_dir_url = $this->get_theme_demo_location('url');
        
        // Process post types
        $selected_post_types = isset($_POST['selected_post_types']) ? $_POST['selected_post_types'] : array();
        if (!is_array($selected_post_types)) {
            $selected_post_types = array($selected_post_types);
        }
        
        require_once('xml-exporter/wbcom-xml-exporter.php');
        
        foreach ($selected_post_types as $post_type_slug) {
            $this->log_message("Processing post type: {$post_type_slug}");
            $args = array('content' => $post_type_slug);
            
            ob_start();
            export_wp($args);
            $xml_content = ob_get_clean();
            
            $args = array(
                'content' => $xml_content,
                'fileName' => $post_type_slug,
                'fileExtension' => 'xml',
            );
            
            $this->saveContentToDemoPackage($args, $locationTill = 'demo');
            $xml_urls[] = $upload_dir_url . $args['fileName'] . "." . $args['fileExtension'];
        }
        
        return $xml_urls;
    }
    
    /**
     * Set up initial directory structure
     * 
     * @return string Demo directory path
     * @since 1.0.0
     */
    public function initial_directory_setup() {
        $theme_slug = isset($_POST['theme_slug']) ? $_POST['theme_slug'] : '';
        $theme_slug = sanitize_title($theme_slug);
        $demo_slug = isset($_POST['demo_slug']) ? $_POST['demo_slug'] : '';
        $demo_slug = sanitize_title($demo_slug);
        $demo_slug = 'theme_demo'; // to make one folder only
        
        $upload = wp_upload_dir();
        $upload_dir = $upload['basedir'];
        $upload_dir = $upload_dir . '/' . self::$_parent_dir;
        
        // Create parent directory if needed
        if (!is_dir($upload_dir)) {
            wp_mkdir_p($upload_dir);
            
            $args = array(
                'content' => '<?php // Silence is golden',
                'fileName' => 'index',
                'fileExtension' => 'php',
            );
            $this->saveContentToDemoPackage($args, $locationTill = 'parent');
            
            $args = array(
                'content' => '{}',
                'fileName' => 'installer',
                'fileExtension' => 'json',
            );
            $this->saveContentToDemoPackage($args, $locationTill = 'parent');
        }
        
        // Create theme directory if needed
        $upload_dir = $upload_dir . '/' . $theme_slug;
        if (!is_dir($upload_dir)) {
            wp_mkdir_p($upload_dir);
            
            $args = array(
                'content' => '<?php // Silence is golden',
                'fileName' => 'index',
                'fileExtension' => 'php',
            );
            $this->saveContentToDemoPackage($args, $locationTill = 'theme');
        }
        
        // Create demo directory (remove existing one first)
        $upload_dir = $upload_dir . '/' . $demo_slug;
        if (is_dir($upload_dir)) {
            $this->recursiveRemoveDirectory($upload_dir . '/');
        }
        
        wp_mkdir_p($upload_dir);
        
        $args = array(
            'content' => '<?php // Silence is golden',
            'fileName' => 'index',
            'fileExtension' => 'php',
        );
        $this->saveContentToDemoPackage($args, $locationTill = 'demo');
        
        return $upload_dir . '/';
    }
    
    /**
     * Recursively remove a directory and its contents
     * 
     * @param string $directory Directory path to remove
     * @since 1.0.0
     */
    public function recursiveRemoveDirectory($directory) {
        foreach (glob("{$directory}/*") as $file) {
            if (is_dir($file)) {
                $this->recursiveRemoveDirectory($file);
            } else {
                unlink($file);
            }
        }
        
        // Don't remove the parent directory, just its contents
        // rmdir($directory);
    }
    
    /**
     * Get the path or URL for the theme demo location
     * 
     * @param string $value 'path' or 'url'
     * @param string $locationTill 'parent', 'theme', or 'demo'
     * @param string $theme_slug Theme slug (optional)
     * @param string $demo_slug Demo slug (optional)
     * @return string Path or URL
     * @since 1.0.0
     */
    public function get_theme_demo_location($value = 'path', $locationTill = 'demo', $theme_slug = '', $demo_slug = '') {
        if (empty($theme_slug)) {
            $theme_slug = isset($_POST['theme_slug']) ? $_POST['theme_slug'] : '';
            $theme_slug = sanitize_title($theme_slug);
        }
        
        if (empty($demo_slug)) {
            $demo_slug = isset($_POST['demo_slug']) ? $_POST['demo_slug'] : '';
            $demo_slug = sanitize_title($demo_slug);
        }
        
        $demo_slug = 'theme_demo'; // to make one folder only
        
        $upload = wp_upload_dir();
        if ($value == 'path') {
            $upload_dir = $upload['basedir'];
        } else if ($value == 'url') {
            $upload_dir = $upload['baseurl'];
        }
        
        $upload_dir = $upload_dir . '/' . self::$_parent_dir;
        
        if ($locationTill == 'parent') {
            return $upload_dir . '/';
        }
        
        $upload_dir = $upload_dir . '/' . $theme_slug;
        
        if ($locationTill == 'theme') {
            return $upload_dir . '/';
        }
        
        $upload_dir = $upload_dir . '/' . $demo_slug;
        return $upload_dir . '/';
    }
    
    /**
     * Save content to demo package
     * 
     * @param array $args Content arguments
     * @param string $locationTill 'parent', 'theme', or 'demo'
     * @since 1.0.0
     */
    public function saveContentToDemoPackage($args = array(), $locationTill = 'demo') {
        $package_path = $this->get_theme_demo_location('path', $locationTill);
        $fp = fopen($package_path . "$args[fileName].$args[fileExtension]", "w");
        fwrite($fp, $args['content']);
        fclose($fp);
    }
}
endif;

/**
 * Main instance of WBCOM_TDE_Generate_Demo_Data.
 * @since 1.0.0
 * @return WBCOM_TDE_Generate_Demo_Data
 */
WBCOM_TDE_Generate_Demo_Data::instance();