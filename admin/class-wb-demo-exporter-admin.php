<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.wbcomdesigns.com
 * @since      1.0.0
 *
 * @package    Wb_Demo_Exporter
 * @subpackage Wb_Demo_Exporter/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wb_Demo_Exporter
 * @subpackage Wb_Demo_Exporter/admin
 * @author     Wbcom Designs <admin@wbcomdesigns.com>
 */
class Wb_Demo_Exporter_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		if( stripos( $_SERVER['REQUEST_URI'], $this->plugin_name ) ) {
			wp_enqueue_style( $this->plugin_name.'-font-awesome', WBDE_PLUGIN_URL . 'admin/css/font-awesome.min.css' );
			wp_enqueue_style( $this->plugin_name.'-selectize', WBDE_PLUGIN_URL . 'admin/css/selectize.css' );
			wp_enqueue_style( $this->plugin_name, WBDE_PLUGIN_URL . 'admin/css/wb-demo-exporter-admin.css' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if( stripos( $_SERVER['REQUEST_URI'], $this->plugin_name ) ) {
			wp_enqueue_script( $this->plugin_name.'-selectize', WBDE_PLUGIN_URL . 'admin/js/selectize.min.js', array( 'jquery' ) );
			wp_enqueue_script( $this->plugin_name, WBDE_PLUGIN_URL . 'admin/js/wb-demo-exporter-admin.js', array( 'jquery' ) );
			
			$is_bp_active = 'no';
			if( WBDE_IS_BP_ACTIVE ){
				$is_bp_active = 'yes';
			}

			wp_localize_script(
				$this->plugin_name,
				'wbde_admin_js_object',
				array(
					'ajaxurl'		=>	admin_url('admin-ajax.php'),
					'is_bp_active'	=>	$is_bp_active
				)
			);
		}
	}

	/**
	 * Actions performed to add an admin options page
	 */
	public function wbde_demo_exporter_page() {
		
		add_options_page( __( 'Export Demo Data', WBDE_TEXT_DOMAIN ), __( 'WB Demo Exporter', WBDE_TEXT_DOMAIN ), 'manage_options', $this->plugin_name, array( $this, 'wbde_exporter_page_content' ) );
	}

	/**
	 * Exporter Page Content
	 */
	public function wbde_exporter_page_content(){
		?>
		<div class="wrap">
			<div class="wbde-header">
				<h2 class="wbde-plugin-heading"><?php _e( 'WB Demo Exporter', WBDE_TEXT_DOMAIN );?></h2>
				<?php self::wbde_plugin_extra_actions();?>
			</div>
			<?php
			if ( file_exists( WBDE_PLUGIN_PATH . 'admin/includes/wbde-exporter.php' ) ) {
				require_once( WBDE_PLUGIN_PATH . 'admin/includes/wbde-exporter.php' );
			}
			?>
		</div> 
		<?php
	}

	/**
	 * Actions performed to add plugin extra actions - support links
	 */
	public function wbde_plugin_extra_actions() {
		?>
		<div class="wbde-extra-actions">
			<button class="button button-secondary" onclick="window.open('https://wbcomdesigns.com/contact/', '_blank');"><i class="fa fa-envelope" aria-hidden="true"></i> <?php _e( 'Email Support', WBDE_TEXT_DOMAIN )?></button>
			<button disabled class="button button-secondary" onclick="window.open('', '_blank');"><i class="fa fa-file" aria-hidden="true"></i> <?php _e( 'User Manual', WBDE_TEXT_DOMAIN )?></button>
			<button disabled class="button button-secondary" onclick="window.open('', '_blank');"><i class="fa fa-star" aria-hidden="true"></i> <?php _e( 'Rate Us on WordPress.org', WBDE_TEXT_DOMAIN )?></button>
		</div>
		<?php 
	}

	/** 
	 * AJAX served to export the demo data
	 */
	public function wbde_export_demo_data() {
		if( isset( $_POST['action'] ) && $_POST['action'] === 'wbde_export_demo_data' ) {

			$req_plugins = array();
			if( $_POST['req_plugins'] != '' ) {
				$req_plugins = explode( ',', sanitize_text_field( $_POST['req_plugins'] ) );
			}
			$all_plugins = get_plugins();
			
			foreach( $req_plugins as $req_plugin ) {
				$required_plugins[] = array(
					'plugin_slug' => $req_plugin,
					'plugin_name' => $all_plugins[$req_plugin]['Name'],
					'plugin_version' => $all_plugins[$req_plugin]['Version']
				);
			}
			
			$groups = array();
			if( isset( $_POST['groups'] ) && $_POST['groups'] != 'null' ) {
				$groups = explode( ',', sanitize_text_field( $_POST['groups'] ) );
			}

			$post_types = array();
			if( isset( $_POST['post_types'] ) && $_POST['post_types'] != '' ) {
				$post_types = explode( ',', sanitize_text_field( $_POST['post_types'] ) );
			}

			$taxonomies = array();
			if( isset( $_POST['taxonomies'] ) && $_POST['taxonomies'] != '' ) {
				$taxonomies = explode( ',', sanitize_text_field( $_POST['taxonomies'] ) );
			}

			$site_options_to_exclude = array();
			if( isset( $_POST['site_options'] ) && $_POST['site_options'] != '' ) {
				$site_options_to_exclude = explode( ',', sanitize_text_field( $_POST['site_options'] ) );
			}
			
			//Demo data title
			$title = sanitize_text_field( $_POST['title'] );
			$demo_data_title = ( $title == '' ) ? 'Sample Demo Data '.time() : $title;

			//Demo data logo
			if( !empty( $_FILES ) ) {
				$target_file = WBDE_UPLOADS_PATH . basename( $_FILES["file"]["name"] );
				move_uploaded_file( $_FILES["file"]["tmp_name"], $target_file );
				$demo_data_logo = WBDE_UPLOADS_URL . basename( $_FILES["file"]["name"] );
			} else {
				$demo_data_logo = WBDE_PLUGIN_URL.'admin/images/demo-data.png';
			}
			
			$export_data = array(
				'title'			=>	$demo_data_title,
				'logo'			=>	$demo_data_logo,
				'req_plugins'	=>	serialize( $required_plugins ),
				'users'			=>	self::wbde_handle_users(),
				'site_options'	=>	self::wbde_handle_site_options( $site_options_to_exclude ),
				'groups'		=>	self::wbde_handle_groups( $groups ),
				'activity'		=>	self::wbde_handle_site_wide_activity(),
				'taxonomies'	=>	self::wbde_handle_taxonomies( $taxonomies ),
				'post_types'	=>	self::wbde_handle_post_types( $post_types, $taxonomies ),
				'home_url'		=>	get_home_url()
			);
			
			$filename = str_replace( ' ', '-', strtolower( $demo_data_title ) ).'.json';
			$final_export_data = json_encode( $export_data ); 
			$local_file = WBDE_UPLOADS_PATH . $filename;
			
			//Create a export file at local server
			$handle = fopen( $local_file, "w" ) or die( 'Failed to create file.' );
			fwrite( $handle, $final_export_data );
			fclose( $handle );
			$response = array(
				'message'	=>	__( 'Export File Created !' )
			);
			wp_send_json_success( $response );
			die;
		}
	}

	/**
	 * Handle Taxonomies Export
	 */
	public static function wbde_handle_taxonomies( $taxonomies ) {
		if( !empty( $taxonomies ) ) {
			$taxonomies_export = array();

			foreach( $taxonomies as $taxonomy ) {
				set_time_limit( 30 );
				//Get all parent terms first
				$terms_arr = array();
				$parent_terms = get_terms( $taxonomy, array(
					'hide_empty' => false,
					'parent' => 0
				) );
				foreach( $parent_terms as $p_term ) {
					$terms_arr[] = array(
						'name' => $p_term->name,
						'slug' => $p_term->slug,
						'taxonomy' => $p_term->taxonomy,
						'description' => $p_term->description,
						'parent' => ''
					);
				}

				//Get the child categories
				foreach( $parent_terms as $p_term ) {
					$child_terms = get_term_children( $p_term->term_id, $taxonomy );
					if( !empty( $child_terms ) ) {
						foreach( $child_terms as $c_term_id ) {
							$c_term = get_term_by( 'id', $c_term_id, $taxonomy );
							$parent_term = get_term_by( 'id', $c_term->parent, $taxonomy );
							$terms_arr[] = array(
								'name' => $c_term->name,
								'slug' => $c_term->slug,
								'taxonomy' => $c_term->taxonomy,
								'description' => $c_term->description,
								'parent' => $parent_term->name
							);
						}
					}
				}
				$taxonomies_export[$taxonomy] = serialize( $terms_arr );
			}
		} else {
			$taxonomies_export = $taxonomies;
		}
		return $taxonomies_export;
	}

	/**
	 * Handle Post Types Export
	 */
	public static function wbde_handle_post_types( $post_types, $taxonomies ) {
		if( !empty( $post_types ) ) {
			$post_types_export = array();
			foreach( $post_types as $pt ) {
				set_time_limit( 60 );
				$posts = get_posts( array(
					'post_type' => $pt,
					'post_status' => 'any',
					'posts_per_page' => -1
				) );

				if( !empty( $posts ) ) {
					foreach( $posts as $post ) {
						$author = get_userdata( $post->post_author );
						//Attachment
						$attachment_id = get_post_thumbnail_id( $post->ID );
						$attachment_url = '';
						if( $attachment_id != '' ) {
							$attachment_url = wp_get_attachment_url( $attachment_id );
						}
						$post_types_export[$pt][] = array(
							'ID' => $post->ID,
							'data' => serialize( $post ),
							'meta_data' => serialize( get_post_meta( $post->ID ) ),
							'terms' => serialize( wp_get_object_terms( $post->ID, $taxonomies ) ),
							'author_email' => $author->data->user_email,
							'attachment_url' => $attachment_url
						);
					}
				}
			}
		} else {
			$post_types_export = $post_types;
		}
		return $post_types_export;
	}

	/**
	 * Handle Site Options Export
	 */
	public static function wbde_handle_site_options( $exclude_options_arr ) {
		$all_options = wp_load_alloptions();

		//Default options to be excluded
		$default_options = array( 'siteurl', 'home', 'blogname', 'blogdescription', 'users_can_register', 'admin_email', 'start_of_week', 'use_balanceTags', 'use_smilies', 'require_name_email', 'comments_notify', 'posts_per_rss', 'rss_use_excerpt', 'mailserver_url', 'mailserver_login', 'mailserver_pass', 'mailserver_port', 'default_category', 'default_comment_status', 'default_ping_status', 'default_pingback_flag', 'posts_per_page', 'date_format', 'time_format', 'links_updated_date_format', 'comment_moderation', 'moderation_notify', 'permalink_structure', 'rewrite_rules', 'hack_file', 'blog_charset', 'active_plugins', 'category_base', 'ping_sites', 'comment_max_links', 'gmt_offset', 'default_email_category', 'template', 'stylesheet', 'comment_whitelist', 'comment_registration', 'html_type', 'use_trackback', 'default_role', 'db_version', 'uploads_use_yearmonth_folders', 'upload_path', 'blog_public', 'default_link_category', 'show_on_front', 'tag_base', 'show_avatars', 'avatar_rating', 'upload_url_path', 'thumbnail_size_w', 'thumbnail_size_h', 'thumbnail_crop', 'medium_size_w', 'medium_size_h', 'avatar_default', 'large_size_w', 'large_size_h', 'image_default_link_type', 'image_default_size', 'image_default_align', 'close_comments_for_old_posts', 'close_comments_days_old', 'thread_comments', 'thread_comments_depth', 'page_comments', 'comments_per_page', 'default_comments_page', 'comment_order', 'sticky_posts', 'widget_categories', 'widget_text', 'widget_rss', 'timezone_string', 'page_for_posts', 'page_on_front', 'default_post_format', 'link_manager_enabled', 'finished_splitting_shared_terms', 'site_icon', 'medium_large_size_w', 'medium_large_size_h', 'initial_db_version', 'wp_user_roles', 'fresh_site', 'widget_search', 'widget_recent-posts', 'widget_recent-comments', 'widget_archives', 'widget_meta', 'sidebars_widgets', 'widget_pages', 'widget_calendar', 'widget_media_audio', 'widget_media_image', 'widget_media_video', 'widget_tag_cloud', 'widget_nav_menu', 'widget_custom_html', 'cron' );

		$exclude_options = array_merge( $exclude_options_arr, $default_options );
		foreach( $exclude_options as $option ) {
			unset( $all_options[$option] );
		}
		return serialize( $all_options );
	}

	/**
	 * Handle Groups Export
	 */
	public static function wbde_handle_groups( $groups ) {
		if( !empty( $groups ) ) {
			$groups_export = array();
			foreach( $groups as $groupid ) {
				$group = groups_get_group( $groupid );
				$group_data = serialize( $group );
				$group_meta_data = serialize( groups_get_groupmeta( $groupid ) );
				$creator = get_userdata( $group->creator_id );
				$groups_export[] = array(
					'ID' => $groupid,
					'data' => $group_data,
					'meta_data' => $group_meta_data,
					'creator_email' => $creator->data->user_email
				);
			}
		} else {
			$groups_export = $groups;
		}
		return $groups_export;
	}

	/**
	 * Handle Site Wide Activity Export
	 */
	public static function wbde_handle_site_wide_activity() {
		if( ! ( WBDE_IS_BP_ACTIVE && WBDE_IS_BP_ACTIVITY_COMPONENT_ACTIVE ) ) {
			return array();
		} else {
			$activity_export = array();
			$activities = bp_activity_get();
			if( !empty( $activities['activities'] ) ) {
				foreach( $activities['activities'] as $activity ) {
					$user = get_userdata( $activity->user_id );
					$activity_export[] = array(
						'ID' => $activity->id,
						'data' => serialize( $activity ),
						'meta_data' => serialize( bp_activity_get_meta( $activity->id ) ),
						'user_email' => $user->data->user_email
					);
				}
				return $activity_export;
			} else {
				return array();
			}
		}
	}

	/**
	 * Handle Users Export
	 */
	public static function wbde_handle_users() {
		$users = get_users();
		foreach( $users as $user ) {
			$users_export[] = array(
				'data' => serialize( $user ),
				'meta_data' => serialize( get_user_meta( $user->ID ) )
			);
		}
		return $users_export;
	}
}