<?php

/**
 * Fired during plugin activation
 *
 * @link       http://www.wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Wb_Demo_Exporter
 * @subpackage Wb_Demo_Exporter/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wb_Demo_Exporter
 * @subpackage Wb_Demo_Exporter/includes
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Wb_Demo_Exporter_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		$wbde_upload = wp_upload_dir();
		$wbde_upload_dir = $wbde_upload['basedir'];
		$wbde_upload_dir = $wbde_upload_dir . '/wb-demo-exporter/';
		if ( !file_exists( $wbde_upload_dir ) ) {
			mkdir( $wbde_upload_dir, 0755, true );
		}
	}

}
