/**
 * Theme Demo Exporter Admin JavaScript
 */
(function($) {
    'use strict';
    
    // Initialize on document ready
    $(document).ready(function() {
        // Initialize Select2
        $('.wbcom-demo-exporter-select2').select2({
            width: '100%',
            placeholder: 'Select items...',
            allowClear: true
        });
        
        // Media uploader for demo screenshot
        var wbcom_demo_exporter_mediaUploader;
        var wbcom_demo_exporter_thisRef;
        
        $('.wbcom_demo_exporter-upload-button').click(function(event) {
            event.preventDefault();
            wbcom_demo_exporter_thisRef = $(this);
            
            // If the uploader object has already been created, reopen the dialog
            if (wbcom_demo_exporter_mediaUploader) {
                wbcom_demo_exporter_mediaUploader.open();
                return;
            }
            
            // Extend the wp.media object
            wbcom_demo_exporter_mediaUploader = wp.media.frames.file_frame = wp.media({
                title: 'Choose Demo Screenshot',
                button: {
                    text: 'Select Image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });
            
            // When a file is selected, grab the URL and set it as the text field's value
            wbcom_demo_exporter_mediaUploader.on('select', function() {
                var attachment = wbcom_demo_exporter_mediaUploader.state().get('selection').first().toJSON();
                
                wbcom_demo_exporter_thisRef.closest('.screenshot-upload-container').find('.wbcom_demo_exporter_img_url').val(attachment.url);
                wbcom_demo_exporter_thisRef.closest('.screenshot-upload-container').find('.wbcom_demo_exporter_img').attr('src', attachment.url).show();
                wbcom_demo_exporter_thisRef.closest('.screenshot-upload-container').find('.wbcom_demo_exporter-remove-file-button').show();
            });
            
            // Open the uploader dialog
            wbcom_demo_exporter_mediaUploader.open();
        });
        
        // Remove image button
        $('.wbcom_demo_exporter-remove-file-button').click(function(event) {
            event.preventDefault();
            
            $(this).closest('.screenshot-upload-container').find('.wbcom_demo_exporter_img_url').val('');
            $(this).closest('.screenshot-upload-container').find('.wbcom_demo_exporter_img').attr('src', '').hide();
            $(this).hide();
        });
        
        // Form validation and submission
        $('#wbcom-exporter-form').on('submit', function(e) {
            // Check required fields
            var $requiredFields = $(this).find('[required]');
            var isValid = true;
            
            $requiredFields.each(function() {
                if (!$(this).val()) {
                    isValid = false;
                    $(this).addClass('validation-error');
                } else {
                    $(this).removeClass('validation-error');
                }
            });
            
            if (!isValid) {
                e.preventDefault();
                alert(wbcom_theme_demo_exporter_params.i18n.validation_error);
                return false;
            }
            
            // Show progress indicator
            $('#wbcom-export-progress').removeClass('hidden');
            $('#wbcom_generate_theme_demo_data').prop('disabled', true);
            
            // Add a notification for long running process
            setTimeout(function() {
                $('.progress-text').text(wbcom_theme_demo_exporter_params.i18n.please_wait);
            }, 3000);
            
            return true;
        });
        
        // Pre-select commonly used options
        function preSelectCommonOptions() {
            // Common post types
            const commonPostTypes = ['post', 'page', 'nav_menu_item'];
            
            // Common database tables
            const commonDbTables = ['options', 'posts', 'postmeta', 'terms', 'termmeta', 
                                    'term_taxonomy', 'term_relationships', 'comments', 'commentmeta'];
            
            // Set selections if nothing is already selected
            if (!$('#selected_post_types').val()) {
                $('#selected_post_types').val(commonPostTypes).trigger('change');
            }
            
            if (!$('#selected_database_tables').val()) {
                $('#selected_database_tables').val(commonDbTables).trigger('change');
            }
        }
        
        // Run pre-selection after a small delay to ensure Select2 is initialized
        setTimeout(preSelectCommonOptions, 500);
    });
    
})(jQuery);