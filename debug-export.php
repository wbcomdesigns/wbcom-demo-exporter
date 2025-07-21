<?php
/**
 * Debug Export Tool
 * 
 * Place this file in the plugin directory and access it directly to test export
 */

// Load WordPress
require_once( '../../../../wp-load.php' );

// Check if user is admin
if ( ! current_user_can( 'manage_options' ) ) {
    die( 'Access denied' );
}

echo "<h2>WBCOM Export Debug</h2>";
echo "<pre>";

// Check if plugin is active
if ( ! class_exists( 'WBCOM_TDE_Generate_Demo_Data' ) ) {
    echo "ERROR: Export class not found. Plugin may not be active.\n";
    exit;
}

// Check export status
echo "Export Status: " . get_option( 'wbcom_export_status', 'none' ) . "\n";
echo "Last Export: " . ( get_option( 'wbcom_last_export_time' ) ? date( 'Y-m-d H:i:s', get_option( 'wbcom_last_export_time' ) ) : 'Never' ) . "\n";
echo "\n";

// Check export directory
$upload = wp_upload_dir();
$export_dir = $upload['basedir'] . '/wbcom-theme-demos';
echo "Export Directory: " . $export_dir . "\n";
echo "Directory Exists: " . ( is_dir( $export_dir ) ? 'Yes' : 'No' ) . "\n";

if ( is_dir( $export_dir ) ) {
    $files = scandir( $export_dir );
    echo "Files in export directory:\n";
    foreach ( $files as $file ) {
        if ( $file != '.' && $file != '..' ) {
            echo "  - " . $file . "\n";
        }
    }
}

echo "\n";
echo "To check PHP error logs, look for lines starting with 'WBCOM Export:'\n";
echo "</pre>";

// Manual export trigger button
?>
<form method="post" action="<?php echo admin_url( 'admin.php?page=wbcom-theme-demo-exporter' ); ?>">
    <?php wp_nonce_field( 'wbcom_export_demo_nonce', '_wpnonce' ); ?>
    <input type="hidden" name="theme_slug" value="<?php echo esc_attr( wp_get_theme()->get( 'Name' ) ); ?>" />
    <input type="hidden" name="demo_slug" value="Main Demo" />
    <button type="submit" name="wbcom_generate_theme_demo_data" value="1">Test Export</button>
</form>