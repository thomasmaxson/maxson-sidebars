jQuery(function(){ 

	initMaxsonSidebarGenerator();
	initMaxsonSidebarMetabox();

});


/* ----------------------------------------------------
Init JQuery Sidebar Generator
---------------------------------------------------- */

function initMaxsonSidebarGenerator(){ 

	var maxson_data = maxson_sidebar_management;

	if ( typeof(maxson_data) === 'undefined' )
		return false;

	var widgetsWrapper = jQuery('.widget-liquid-right'), 
		link_shortcode = '<a href="#sidebar-shortcode" class="maxson-sidebar-shortcode">' + maxson_data.shortcode_text + '</a>',
		link_delete    = '<a href="#delete-sidebar" class="maxson-sidebar-delete">' + maxson_data.delete_text + '</a>';


	// Add form to widget column
	widgetsWrapper.find('.sidebars-column-1').prepend( jQuery('#maxson-sidebar-generator-form').html() );


	// Toggle all widgets, open newest widget added
	jQuery('#new-sidebar-message').on('click', 'a', function(event){ 
		event.preventDefault();

		var new_sidebar    = jQuery(this).attr('href');


		widgetsWrapper.find('.widgets-holder-wrap:not(.closed)').each(function(index, item){ 
			jQuery(item).addClass('closed');
		});

		jQuery(new_sidebar + ' .sidebar-name .sidebar-name-arrow').trigger('click');
	});


	// Add Links Container
	jQuery('#widgets-right').find('.widgets-holder-wrap').append('<div class="maxson-sidebar-links" />');

	// Add "shortcode" button to links container
	if( maxson_data.show_shortcode_link )
	{ 

		jQuery('#widgets-right').find('.widgets-holder-wrap .maxson-sidebar-links').append( link_shortcode );
	}

	// Add "delete" button to links container
	jQuery('#widgets-right').find('.widgets-holder-wrap.sidebar-generated .maxson-sidebar-links').append( link_delete );


	// Delete generated sidebar
	widgetsWrapper.on('click', '.maxson-sidebar-delete', function(event){ 
		event.preventDefault();
		event.stopPropagation();

		var confirmation = confirm(maxson_data.confirm);

		if (confirmation){ 
			var widget      = jQuery(this).closest('.widgets-holder-wrap'),
				title       = widget.find('.sidebar-name h3'),
				spinner     = title.find('.spinner'),
				widget_name = title.text(),
				widget_id   = widget.find('.widgets-sortables').attr('id');

			var $ajax_data = {
				action : 'maxson_ajax_delete_sidebar',
				nonce  : jQuery('.widget-liquid-right').find('input[name="maxson-delete-sidebar-nonce"]').val(),
				name   : widget_name,
				slug   : widget_id
			};

			jQuery.ajax({
				type: 'POST',
				url: window.ajaxurl,
				data: $ajax_data,

				beforeSend: function(){
					spinner.addClass('activate_spinner');
				},
				success: function(response){ 
					if(response.success == true){ 
						jQuery('#maxson-sidebar-deleted-message').removeClass('hidden').siblings('.updated').remove();

						widget.slideUp(200, function(){ 
							// Delete all widgets inside
							jQuery('.widget-control-remove', widget).trigger('click');

							widget.remove();
						});

					}else{
						spinner.removeClass('activate_spinner');

					}
				}
			});
		}

	// Alert generated sidebar shortcode
	}).on('click', '.maxson-sidebar-shortcode', function(event){ 
		event.preventDefault();
		event.stopPropagation();

		var widget = jQuery(this).closest('.widgets-holder-wrap'),
			widget_id = widget.find('.widgets-sortables').attr('id');

			prompt( maxson_sidebar_management.prompt, '[maxson_sidebar' + ' id="' + widget_id + '"]' );
	});
}


/* ----------------------------------------------------
Init JQuery Sidebar Generator
---------------------------------------------------- */

function initMaxsonSidebarMetabox(){ 

	// Toggle "add new sidebar" form
	jQuery('#sidebar-add-toggle').on('click', function(event){ 
		event.preventDefault();

		jQuery(this).parents('div:first').toggleClass( 'wp-hidden-children' );

		jQuery('#maxson-sidebar-metabox-title').focus();
	} );


	// AJAX submit new sidebar
	jQuery('#sidebar-add').on('click', '#sidebar-add-submit', function(event){ 
		event.preventDefault();

		var widget_title = jQuery('#maxson-sidebar-metabox-title'), 
			widget_desc  = jQuery('#maxson-sidebar-metabox-desc');

		var $data = { 
			action      : 'maxson_ajax_add_sidebar',
			nonce       : jQuery(this).siblings('input[name="maxson-add-sidebar-nonce"]').val(),
			title       : widget_title.val(),
			description : widget_desc.val()
		};

		jQuery.ajax({
			type: 'POST',
			url: window.ajaxurl,
			data: $data,

			success: function(response){ 
				if(response.success == true){ 
					widget_title.val('');
					widget_desc.val('');
					jQuery('#sidebar_replace').append( '<option value="' + response.data.slug + '">' + response.data.title + '</option>' );
				} // endif
			}
		});
	});

}