jQuery(document).ready(function( $ ) {
	'use strict';

	if( wbde_admin_js_object.is_bp_active == 'yes' ) {
		//Selectize groups
		var wbde_group_ids = $('#wbde-export-groups').selectize({
			placeholder		: "Select groups",
			plugins			: ['remove_button'],
		});
		var groups_selectize = wbde_group_ids[0].selectize;
	}

	//Selectize cpts
	var wbde_post_types_names = $('#wbde-export-post-types').selectize({
		placeholder		: "Select post types",
		plugins			: ['remove_button'],
	});
	var pts_selectize = wbde_post_types_names[0].selectize;

	//Selectize taxonomies
	var wbde_taxonomies = $('#wbde-export-taxonomies').selectize({
		placeholder		: "Select taxonomies",
		plugins			: ['remove_button'],
	});
	var taxonomies_selectize = wbde_taxonomies[0].selectize;

	//Selectize plugins
	var wbde_plugins = $('#wbde-required-plugins').selectize({
		placeholder		: "Select plugins",
		plugins			: ['remove_button'],
	});
	var plugins_selectize = wbde_plugins[0].selectize;

	/**
	 * Create your demo export
	 */
	$(document).on('click', '#wbde-export-demo-data', function(){
		var req_plugins		=	$('#wbde-required-plugins').val();

		if( req_plugins == null ) {
			alert("Please provide a set of required plugins for this demo export!");
		} else {
			var btn 			=	$(this);
			var btn_txt 		=	btn.html();
			var groups 			=	null;
			var post_types 		=	$('#wbde-export-post-types').val();
			var taxonomies 		=	$('#wbde-export-taxonomies').val();
			var site_options 	=	$('#wbde-exclude-options').val();
			var title 			=	$('#wbde-demo-data-title').val();


			if( wbde_admin_js_object.is_bp_active == 'yes' ) {
				groups 	=	$('#wbde-export-groups').val();
			}

			var fdata = new FormData();
			var logo = $("#wbde-export-logo").prop("files")[0];

			fdata.append( 'file', logo );
			fdata.append( 'title', title );
			fdata.append( 'action', 'wbde_export_demo_data' );
			fdata.append( 'groups', groups );
			fdata.append( 'post_types', post_types );
			fdata.append( 'taxonomies', taxonomies );
			fdata.append( 'site_options', site_options );
			fdata.append( 'req_plugins', req_plugins );

			btn.html( '<i class="fa fa-refresh fa-spin"></i>  Exporting...' );
			$.ajax({
				dataType: "JSON",
				url: wbde_admin_js_object.ajaxurl,
				type: 'POST',
				data: fdata,
				cache: false,
				contentType: false,
				processData: false,
				success: function( response ) {
					console.log(response['data']['message']);
					btn.html( 'Data Exported  <i class="fa fa-check"></i>' );
					$('#wbde-demo-data-title').val('');
					$('#wbde-exclude-options').val('');
					$("#wbde-export-logo").reset();
				},
			});
		}
	});

	// Select-Unselect all groups
	$(document).on('click', '#wbde-select-all-groups', function(){
		var group_ids = [], i;
		var group_options = groups_selectize.options;
		for( i in group_options ) {
			group_ids.push( group_options[i]['value'] );
		}
		groups_selectize.setValue( group_ids );
	});
	$(document).on('click', '#wbde-unselect-all-groups', function(){
		groups_selectize.setValue( [] );
	});

	// Select-Unselect all post types
	$(document).on('click', '#wbde-select-all-post-types', function(){
		var pt_names = [], i;
		var pt_options = pts_selectize.options;
		for( i in pt_options ) {
			pt_names.push( pt_options[i]['value'] );
		}
		pts_selectize.setValue( pt_names );
	});
	$(document).on('click', '#wbde-unselect-all-post-types', function(){
		pts_selectize.setValue( [] );
	});

	// Select-Unselect all taxonomies
	$(document).on('click', '#wbde-select-all-taxonomies', function(){
		var taxonomies_names = [], i;
		var taxonomies_options = taxonomies_selectize.options;
		for( i in taxonomies_options ) {
			taxonomies_names.push( taxonomies_options[i]['value'] );
		}
		taxonomies_selectize.setValue( taxonomies_names );
	});
	$(document).on('click', '#wbde-unselect-all-taxonomies', function(){
		taxonomies_selectize.setValue( [] );
	});

	// Select-Unselect all plugins
	$(document).on('click', '#wbde-select-all-plugins', function(){
		var plugins_slugs = [], i;
		var plugins_options = plugins_selectize.options;
		for( i in plugins_options ) {
			plugins_slugs.push( plugins_options[i]['value'] );
		}
		plugins_selectize.setValue( plugins_slugs );
	});
	$(document).on('click', '#wbde-unselect-all-plugins', function(){
		plugins_selectize.setValue( [] );
	});

});