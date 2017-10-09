<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if( WBDE_IS_BP_ACTIVE && WBDE_IS_BP_GROUPS_COMPONENT_ACTIVE ) {
	/**
	 * Retrieve all groups
	 */
	$groups = BP_Groups_Group::get(
		array(
			'type'=>'alphabetical',
			'per_page'=>-1
		)
	);
}

/**
 * Retreive all post types
 */
$post_types = get_post_types(
	array(),
	'objects'
);

/**
 * Retreive all taxonomies
 */
$taxonomies = get_taxonomies(
	array(),
	'objects'
);

/**
 * Get all plugins list - to select the required plugins
 */
$plugins = get_plugins();
unset( $plugins['wb-demo-exporter/wb-demo-exporter.php'] );
?>
<div class='wbde-export-demo-data-panel'>
	<p><?php _e( 'Create your demo export.', WBDE_TEXT_DOMAIN );?></p>
	<hr />
	<!-- POST TYPES & TAXONOMIES DATA -->
	<h3><?php _e( 'Post Types & Taxonomies (BuiltIn + Custom)', WBDE_TEXT_DOMAIN );?></h3>
	<table class="form-table">
		<tbody>
			<!-- POST TYPES -->
			<tr>
				<th scope="row"><label for="wbde-select-post-types"><?php _e( 'Post Types', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<?php if( !empty( $post_types ) ) {?>
						<p class="wbde-selection-tags">
							<a href="javascript:void(0);" id="wbde-select-all-post-types"><?php _e( 'Select All', WBDE_TEXT_DOMAIN );?></a> / 
							<a href="javascript:void(0);" id="wbde-unselect-all-post-types"><?php _e( 'Unselect All', WBDE_TEXT_DOMAIN );?></a>
						</p>
						<select id="wbde-export-post-types" multiple>
							<?php foreach( $post_types as $pt ) {?>
								<option value="<?php echo $pt->name;?>" selected><?php echo $pt->label;?></option>
							<?php }?>
						</select>
					<?php } else {?>
						<p><?php _e( 'No Post Type Found!', WBDE_TEXT_DOMAIN );?></p>
					<?php }?>
					<p class="description"><?php _e( 'Select the post types to export. All related posts and post meta data will be exported.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>

			<!-- TAXONOMIES -->
			<tr>
				<th scope="row"><label for="wbde-select-taxonomies"><?php _e( 'Taxonomies', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<?php if( !empty( $taxonomies ) ) {?>
						<p class="wbde-selection-tags">
							<a href="javascript:void(0);" id="wbde-select-all-taxonomies"><?php _e( 'Select All', WBDE_TEXT_DOMAIN );?></a> / 
							<a href="javascript:void(0);" id="wbde-unselect-all-taxonomies"><?php _e( 'Unselect All', WBDE_TEXT_DOMAIN );?></a>
						</p>
						<select id="wbde-export-taxonomies" multiple>
							<?php foreach( $taxonomies as $taxonomy ) {?>
								<option value="<?php echo $taxonomy->name;?>" selected><?php echo $taxonomy->label;?></option>
							<?php }?>
						</select>
					<?php } else {?>
						<p><?php _e( 'No Taxonomy Found!', WBDE_TEXT_DOMAIN );?></p>
					<?php }?>
					<p class="description"><?php _e( 'Select the taxonomies to export. All the related terms and terms relationships will be exported.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>
		</tbody>
	</table>

	<h3><?php _e( 'WordPress Data', WBDE_TEXT_DOMAIN );?></h3>
	<table class="form-table">
		<tbody>
			<!-- SITE OPTIONS -->
			<tr>
				<th scope="row"><label for="wbde-exclude-options"><?php _e( 'Site Options', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<textarea rows="3" placeholder="<?php _e( 'Comma separated list of options...', WBDE_TEXT_DOMAIN );?>" id="wbde-exclude-options"></textarea>
					<p class="description"><?php _e( 'Provide a comma separated list of options you wish to exclude. This list will be neglected wile exporting the options from <strong>wp_options</strong> table.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>

			<!-- USERS -->
			<tr>
				<th scope="row"><label for="wbde-users"><?php _e( 'Users', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<p><?php _e( 'The complete list of registered users will be exported.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>
			
			<!-- NAV MENU ITEMS -->
			<tr>
				<th scope="row"><label for="wbde-nav-menu-items"><?php _e( 'Navigation Menus', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<p><?php _e( 'The complete list of navigation menus will be exported.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>
		</tbody>
	</table>

	<!-- BUDDYPRESS DATA -->
	<?php if( WBDE_IS_BP_ACTIVE ) {?>
		<?php if( WBDE_IS_BP_GROUPS_COMPONENT_ACTIVE || WBDE_IS_BP_ACTIVITY_COMPONENT_ACTIVE ) {?>
			<h3><?php _e( 'BuddyPress Data', WBDE_TEXT_DOMAIN );?></h3>
			<table class="form-table">
				<tbody>

					<?php if( WBDE_IS_BP_GROUPS_COMPONENT_ACTIVE ) {?>
						<!-- GROUPS -->
						<tr>
							<th scope="row"><label for="wbde-select-groups"><?php _e( 'Groups', WBDE_TEXT_DOMAIN );?></label></th>
							<td>
								<?php if( !empty( $groups['groups'] ) ) {?>
									<p class="wbde-selection-tags">
										<a href="javascript:void(0);" id="wbde-select-all-groups"><?php _e( 'Select All', WBDE_TEXT_DOMAIN );?></a> / 
										<a href="javascript:void(0);" id="wbde-unselect-all-groups"><?php _e( 'Unselect All', WBDE_TEXT_DOMAIN );?></a>
									</p>
									<select id="wbde-export-groups" multiple>
										<?php foreach( $groups['groups'] as $group ) {?>
											<option value="<?php echo $group->id;?>" selected><?php echo $group->name;?></option>
										<?php }?>
									</select>
									<input type="hidden" id="wbde-selected-groups" value="">
								<?php } else {?>
									<p><?php _e( 'No Group Found!', WBDE_TEXT_DOMAIN );?></p>
								<?php }?>
								<p class="description"><?php _e( 'Select the groups to export.', WBDE_TEXT_DOMAIN );?></p>
							</td>
						</tr>
					<?php }?>

					<?php if( WBDE_IS_BP_ACTIVITY_COMPONENT_ACTIVE ) {?>
						<!-- ACTIVITY -->
						<tr>
							<th scope="row"><label for="wbde-activity-feed"><?php _e( 'Activity', WBDE_TEXT_DOMAIN );?></label></th>
							<td>
								<p><?php _e( 'The complete activity feed will be exported.', WBDE_TEXT_DOMAIN );?></p>
							</td>
						</tr>
					<?php }?>
				</tbody>
			</table>
		<?php }?>
	<?php }?>
	
	<hr />
	<table class="form-table">
		<tbody>
			<!-- REQUIRED PLUGINS FOR THIS DATA - WHEN IMPORT PROCESS WILL BE EXECUTED -->
			<tr>
				<th scope="row">
					<label for="wbde-select-plugins"><?php _e( 'Required Plugins', WBDE_TEXT_DOMAIN );?></label>
					<span class="wbde-required-field">*</span>
				</th>
				<td>
					<p class="wbde-selection-tags">
						<a href="javascript:void(0);" id="wbde-select-all-plugins"><?php _e( 'Select All', WBDE_TEXT_DOMAIN );?></a> / 
						<a href="javascript:void(0);" id="wbde-unselect-all-plugins"><?php _e( 'Unselect All', WBDE_TEXT_DOMAIN );?></a>
					</p>
					<select id="wbde-required-plugins" multiple>
						<?php foreach( $plugins as $plugin_slug => $plugin ) {?>
							<option value="<?php echo $plugin_slug;?>" selected><?php echo $plugin['Name'];?></option>
						<?php }?>
					</select>
					<p class="description"><?php _e( 'Select the required plugins for this dummy data. This list will be served when this dummy data will be imported.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>
			<!-- DEMO DATA TITLE -->
			<tr>
				<th scope="row"><label for="wbde-demo-data-title"><?php _e( 'Title', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<input type="text" class="regular-text" placeholder="<?php _e( 'Demo Data Title', WBDE_TEXT_DOMAIN );?>" id="wbde-demo-data-title">
					<p class="description"><?php _e( 'This title will be visible on the installer screen.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>

			<!-- DEMO DATA LOGO -->
			<tr>
				<th scope="row"><label for="wbde-demo-data-logo"><?php _e( 'Logo', WBDE_TEXT_DOMAIN );?></label></th>
				<td>
					<input type="file" id="wbde-export-logo">
					<p class="description"><?php _e( 'This logo will be visible on the installer screen.', WBDE_TEXT_DOMAIN );?></p>
				</td>
			</tr>
		</tbody>
	</table>
	<p class="submit"><button id="wbde-export-demo-data" class="button button-primary"><?php _e('Export', WBDE_TEXT_DOMAIN); ?></button></p>
</div>