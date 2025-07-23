<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WBCOM_TDE_ADMIN_SETTINGS' ) ) :
/**
 * Admin Settings with Professional UI
 * @version 2.0.0
 */
class WBCOM_TDE_ADMIN_SETTINGS {
	/**
	 * The single instance of the class.
	 */
	protected static $_instance = null;
	protected static $_slug = 'wbcom-theme-demo-exporter';
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
		add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 10 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 100 );
		add_action( 'wp_ajax_wbcom_clear_export_history', array( $this, 'ajax_clear_history' ) );
		add_action( 'wp_ajax_wbcom_delete_export_folders', array( $this, 'ajax_delete_export_folders' ) );
	}
	
	/**
	 * Add admin menu
	 */
	public function add_admin_menu() {
		add_menu_page(
			__( 'Demo Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
			__( 'Demo Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ),
			'manage_options',
			self::$_slug,
			array( $this, 'render_page_for_added_menu' ),
			'dashicons-download',
			80
		);
	}
	
	/**
	 * Render admin page
	 */
	public function render_page_for_added_menu() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'You do not have sufficient permissions to access this page.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ) );
		}
		
		$api_key = get_option( 'wbcom_exporter_api_key', 'demo-export-2024' );
		$last_export = get_option( 'wbcom_last_export_time' );
		$export_history = get_option( 'wbcom_export_history', array() );
		$site_url = home_url();
		$theme_info = wp_get_theme();
		?>
		<div class="wrap wbcom-demo-exporter-wrap">
			<h1><?php _e( 'Demo Content Exporter', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h1>
			
			<?php 
			// Show export status messages
			if ( isset( $_GET['export_status'] ) ) {
				if ( $_GET['export_status'] === 'success' ) {
					echo '<div class="notice notice-success is-dismissible"><p><strong>' . __( 'Export completed successfully!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ) . '</strong></p></div>';
				} elseif ( $_GET['export_status'] === 'error' ) {
					$error = get_transient( 'wbcom_export_error' );
					echo '<div class="notice notice-error is-dismissible"><p><strong>' . __( 'Export failed!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ) . '</strong> ' . esc_html( $error ) . '</p></div>';
				}
			}
			?>
			
			<div class="wbcom-exporter-container">
				
				<!-- One-Click Export Card -->
				<div class="card">
					<h2><?php _e( 'Export Demo Content', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h2>
					
					<?php if ( $last_export ) : ?>
						<div class="export-status-info">
							<p>
								<span class="dashicons dashicons-yes-alt"></span>
								<strong><?php _e( 'Last Export:', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></strong> 
								<?php echo esc_html( human_time_diff( $last_export, current_time( 'timestamp' ) ) . ' ' . __( 'ago', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ) ); ?>
							</p>
						</div>
					<?php endif; ?>
					
					<p class="description">
						<?php _e( 'Click the button below to export all demo content. This will create a fresh export package with all necessary data.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>
					</p>
					
					<form method="post" id="export-form" action="">
						<?php wp_nonce_field( 'wbcom_export_demo_nonce', '_wpnonce' ); ?>
						
						<!-- Hidden fields for theme info -->
						<input type="hidden" name="theme_slug" value="<?php echo esc_attr( $theme_info->get( 'Name' ) ); ?>" />
						<input type="hidden" name="demo_slug" value="Main Demo" />
						<input type="hidden" name="wbcom_generate_theme_demo_data" value="1" />
						
						<div class="export-info">
							<h4><?php _e( 'What will be exported:', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h4>
							<ul class="export-items">
								<li><span class="dashicons dashicons-database"></span> <?php _e( 'All database content (posts, pages, menus, etc.)', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
								<li><span class="dashicons dashicons-admin-plugins"></span> <?php _e( 'Active plugins information', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
								<li><span class="dashicons dashicons-format-image"></span> <?php _e( 'Media files and uploads', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
								<li><span class="dashicons dashicons-admin-appearance"></span> <?php _e( 'Theme settings and customizations', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></li>
							</ul>
						</div>
						
						<p class="submit">
							<button type="submit" class="button button-primary button-hero" id="export-button">
								<span class="dashicons dashicons-download"></span> 
								<?php _e( 'Export Demo Content', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>
							</button>
							<button type="button" class="button button-secondary button-hero" id="delete-export-folders">
								<span class="dashicons dashicons-trash"></span> 
								<?php _e( 'Delete Export Folders', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>
							</button>
						</p>
					</form>
					
					<!-- Progress Bar (hidden by default) -->
					<div id="export-progress" style="display: none;">
						<h4><?php _e( 'Export Progress', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></h4>
						<div class="progress-bar">
							<div class="progress-bar-fill" style="width: 0%;"></div>
						</div>
						<p class="progress-message"><?php _e( 'Starting export...', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?></p>
					</div>
				</div>
			</div>
		</div>
		
		<style>
			.wbcom-demo-exporter-wrap {
				max-width: 1200px;
				margin: 20px auto;
			}
			
			.wbcom-exporter-container {
				margin-top: 20px;
			}
			
			.wbcom-demo-exporter-wrap .card {
				max-width: none;
				margin-bottom: 20px;
				padding: 20px;
				box-shadow: 0 1px 3px rgba(0,0,0,0.1);
			}
			
			.wbcom-demo-exporter-wrap .card h2 {
				margin-top: 0;
				border-bottom: 1px solid #e1e1e1;
				padding-bottom: 10px;
			}
			
			.wbcom-code {
				background: #f4f4f4;
				padding: 8px 12px;
				border-radius: 3px;
				font-family: monospace;
				display: inline-block;
				margin-right: 10px;
			}
			
			.copy-to-clipboard {
				vertical-align: middle;
			}
			
			.export-status-info {
				background: #f0f8ff;
				border-left: 4px solid #0073aa;
				padding: 15px;
				margin: 15px 0;
			}
			
			.export-status-info .dashicons-yes-alt {
				color: #46b450;
				font-size: 20px;
				vertical-align: middle;
				margin-right: 5px;
			}
			
			.no-export-yet {
				background: #fff3cd;
				border-left: 4px solid #ffb900;
				padding: 15px;
				margin: 15px 0;
			}
			
			.no-export-yet .dashicons {
				color: #ffb900;
				vertical-align: middle;
				margin-right: 5px;
			}
			
			.export-info {
				background: #f9f9f9;
				border: 1px solid #e1e1e1;
				border-radius: 3px;
				padding: 20px;
				margin: 20px 0;
			}
			
			.export-info h4 {
				margin-top: 0;
			}
			
			.export-items {
				list-style: none;
				padding-left: 0;
			}
			
			.export-items li {
				padding: 5px 0;
			}
			
			.export-items .dashicons {
				color: #0073aa;
				margin-right: 10px;
				vertical-align: middle;
			}
			
			.button-hero {
				height: 48px !important;
				padding: 0 36px !important;
				font-size: 16px !important;
			}
			
			.button-hero .dashicons {
				font-size: 26px;
				vertical-align: middle;
				margin-right: 5px;
			}
			
			.progress-bar {
				width: 100%;
				height: 30px;
				background: #f0f0f0;
				border-radius: 3px;
				overflow: hidden;
				margin: 10px 0;
			}
			
			.progress-bar-fill {
				height: 100%;
				background: #0073aa;
				transition: width 0.3s ease;
				text-align: center;
				line-height: 30px;
				color: white;
				font-weight: bold;
			}
			
			.progress-message {
				text-align: center;
				color: #666;
				font-style: italic;
			}
			
			#clear-export-history .dashicons {
				vertical-align: middle;
				margin-right: 3px;
			}
			
			.description {
				color: #666;
			}
			
			#delete-export-folders {
				margin-left: 10px;
			}
			
			.dashicons.spin {
				animation: spin 2s linear infinite;
			}
			
			@keyframes spin {
				0% { transform: rotate(0deg); }
				100% { transform: rotate(360deg); }
			}
		</style>
		
		<script>
		jQuery(document).ready(function($) {
			// Export form handling - simple progress display
			$('#export-form').submit(function(e) {
				var $button = $('#export-button');
				$button.prop('disabled', true).text('<?php _e( 'Exporting...', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>');
			});
			
			// Delete export folders
			$('#delete-export-folders').click(function(e) {
				e.preventDefault();
				
				if (!confirm('<?php _e( 'Are you sure you want to delete all export folders? This action cannot be undone.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>')) {
					return;
				}
				
				var $button = $(this);
				$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php _e( 'Deleting...', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>');
				
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'wbcom_delete_export_folders',
						_wpnonce: '<?php echo wp_create_nonce( 'wbcom_delete_folders' ); ?>'
					},
					success: function(response) {
						if (response.success) {
							alert(response.data || '<?php _e( 'Export folders deleted successfully!', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>');
							location.reload();
						} else {
							alert('<?php _e( 'Error deleting folders:', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?> ' + (response.data || 'Unknown error'));
						}
					},
					error: function(xhr, status, error) {
						alert('<?php _e( 'Failed to delete export folders. Please try again.', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>');
					},
					complete: function() {
						$button.prop('disabled', false).html('<span class="dashicons dashicons-trash"></span> <?php _e( 'Delete Export Folders', WBCOM_Theme_Demo_Exporter_TEXT_DOMAIN ); ?>');
					}
				});
			});
		});
		</script>
		<?php
	}
	
	/**
	 * Enqueue admin scripts and styles
	 */
	public function admin_enqueue_scripts() {
		$screen = get_current_screen();
		if ( $screen->id != 'toplevel_page_' . self::$_slug ) {
			return;
		}
		
		// Enqueue WordPress admin styles
		wp_enqueue_style( 'dashicons' );
		
		// Enqueue jQuery
		wp_enqueue_script( 'jquery' );
		
		// Enqueue media if needed
		wp_enqueue_media();
		
		// Enqueue Select2 if exists
		if ( file_exists( WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'assets/js/select2.min.js' ) ) {
			wp_enqueue_script( 
				'wbcom_theme_demo_exporter_select2_js',
				WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/js/select2.min.js',
				array( 'jquery' ),
				false,
				true
			);
			
			wp_localize_script(
				'wbcom_theme_demo_exporter_select2_js',
				'wbcom_theme_demo_exporter_select2_params',
				array(
					'ajax_url' => admin_url( 'admin-ajax.php' )
				)
			);
		}
		
		// Enqueue Select2 CSS if exists
		if ( file_exists( WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'assets/css/select2.min.css' ) ) {
			wp_enqueue_style( 
				'wbcom_theme_demo_exporter_select2_css',
				WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/css/select2.min.css'
			);
		}
		
		// Enqueue custom style if exists
		if ( file_exists( WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_PATH . 'assets/css/exporter_style.css' ) ) {
			wp_enqueue_style( 
				'wbcom_theme_demo_exporter_style_css',
				WBCOM_Theme_Demo_Exporter_PLUGIN_DIR_URL . 'assets/css/exporter_style.css'
			);
		}
	}
	
	/**
	 * AJAX handler to clear export history
	 */
	public function ajax_clear_history() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die();
		}
		
		if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wbcom_clear_history' ) ) {
			wp_die();
		}
		
		update_option( 'wbcom_export_history', array() );
		
		wp_send_json_success();
	}
	
	/**
	 * AJAX handler to delete export folders
	 */
	public function ajax_delete_export_folders() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions' );
			return;
		}
		
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wbcom_delete_folders' ) ) {
			wp_send_json_error( 'Security check failed' );
			return;
		}
		
		try {
			$upload = wp_upload_dir();
			$export_dir = $upload['basedir'] . '/' . self::$_parent_dir;
			
			if ( is_dir( $export_dir ) ) {
				$this->recursiveRemoveDirectory( $export_dir );
				
				// Clear export history and last export time
				update_option( 'wbcom_export_history', array() );
				delete_option( 'wbcom_last_export_time' );
				
				wp_send_json_success( 'Export folders deleted successfully' );
			} else {
				wp_send_json_success( 'No export folders found' );
			}
		} catch ( Exception $e ) {
			wp_send_json_error( 'Error: ' . $e->getMessage() );
		}
	}
	
	/**
	 * Recursively remove directory
	 */
	private function recursiveRemoveDirectory( $directory ) {
		if ( ! is_dir( $directory ) ) {
			return;
		}
		
		$files = glob( $directory . '/*' );
		if ( $files === false ) {
			return;
		}
		
		foreach( $files as $file ) {
			if( is_dir( $file ) ) {
				$this->recursiveRemoveDirectory( $file );
			} else {
				@unlink( $file );
			}
		}
		
		// Also handle hidden files
		$hidden_files = glob( $directory . '/.*' );
		if ( $hidden_files !== false ) {
			foreach( $hidden_files as $file ) {
				if ( basename( $file ) != '.' && basename( $file ) != '..' ) {
					if( is_dir( $file ) ) {
						$this->recursiveRemoveDirectory( $file );
					} else {
						@unlink( $file );
					}
				}
			}
		}
		
		@rmdir( $directory );
	}
}
endif;

// Initialize
WBCOM_TDE_ADMIN_SETTINGS::instance();