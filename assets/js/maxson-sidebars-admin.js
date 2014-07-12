jQuery(function(){ 

	initMaxsonSidebarGenerator();

});

/* ----------------------------------------------------
Init JQuery Sidebar Generator
---------------------------------------------------- */

function initMaxsonSidebarGenerator(){ 

	if ( typeof(maxson_sidebar_management) === 'undefined' )
		return false;


	jQuery('#new-sidebar-add-widgets').on('click', function(event){ 
		event.preventDefault();

		var new_sidebar = jQuery(this).attr('href');

		jQuery('.widget-liquid-right .widgets-holder-wrap:not(.closed)').each(function(index, item){ 
			jQuery(item).addClass('closed');
		});

	//	jQuery(new_sidebar).siblings('.sidebar-name').find('.sidebar-name-arrow').trigger('click');
		jQuery(new_sidebar + ' .sidebar-name .sidebar-name-arrow').trigger('click');
	});


	jQuery('.widget-liquid-right .sidebars-column-1').prepend( jQuery('#maxson-add-sidebar-form').html() );


	// Add Container
	jQuery('#widgets-right').find('.widgets-holder-wrap').append('<div class="maxson-sidebar-links" />');

	// Add Shortcode Button
	if( maxson_sidebar_management.show_shortcode_link )
	{ 
		jQuery('#widgets-right').find('.widgets-holder-wrap .maxson-sidebar-links').append('<a href="#sidebar-shortcode" class="maxson-sidebar maxson-shortcode-sidebar">' + maxson_sidebar_management.shortcode_text + '</a>');
	}

	// Add Delete to Container
	jQuery('#widgets-right').find('.widgets-holder-wrap.sidebar-generated .maxson-sidebar-links').append('<a href="#delete-sidebar" class="maxson-sidebar maxson-delete-sidebar">' + maxson_sidebar_management.delete_text + '</a>');


	jQuery('.widget-liquid-right').on('click', '.maxson-delete-sidebar', function(event){ 
		event.preventDefault();
		event.stopPropagation();

		var confirmation = confirm(maxson_sidebar_management.confirm);

		if (confirmation){ 
			var widget      = jQuery(this).closest('.widgets-holder-wrap'),
				title       = widget.find('.sidebar-name h3'),
				spinner     = title.find('.spinner'),
				widget_name = title.text(),
				widget_id   = widget.find('.widgets-sortables').attr('id');

			var $data = {
				action : 'maxson_ajax_delete_sidebar',
				nonce  : jQuery('.widget-liquid-right').find('input[name="maxson-delete-sidebar-nonce"]').val(),
				name   : widget_name,
				slug   : widget_id
			};

			jQuery.ajax({
				type: 'POST',
				url: window.ajaxurl,
				data: $data,

				beforeSend: function(){
					spinner.addClass('activate_spinner');
				},
				success: function(response){ 
					if(response.success == true){ 
						jQuery('#maxson-sidebar-deleted-message').removeClass('hidden');
						jQuery('#maxson-sidebar-deleted-message').siblings('.updated').remove();

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
	}).on('click', '.maxson-shortcode-sidebar', function(event){ 
		event.preventDefault();
		event.stopPropagation();

		var widget = jQuery(this).closest('.widgets-holder-wrap'),
			widget_id = widget.find('.widgets-sortables').attr('id');

			prompt( maxson_sidebar_management.prompt, '[maxson_sidebar' + ' id="' + widget_id + '"]' );
	});







	jQuery('#sidebar-add-toggle').on('click', function(event){ 
		event.preventDefault();

		jQuery(this).parents('div:first').toggleClass( 'wp-hidden-children' );

		jQuery('#newsidebar').focus();
	} );





	jQuery('#sidebar-add').on('click', '#sidebar-add-submit', function(event){ 
		event.preventDefault();

		var widget_name = jQuery('#newsidebar').val();

		var $data = {
			action : 'maxson_ajax_add_sidebar',
			nonce  : jQuery(this).siblings('input[name="maxson-add-sidebar-nonce"]').val(),
			name   : widget_name
		};

		jQuery.ajax({
			type: 'POST',
			url: window.ajaxurl,
			data: $data,

			success: function(response){ 
				if(response.success == true){ 
					jQuery('#newsidebar').val('');
					jQuery('#sidebar_replace').append( '<option value="' + response.data.sidebar_slug + '">' + response.data.sidebar_title + '</option>' );
				} // endif
			}
		});
	});

}