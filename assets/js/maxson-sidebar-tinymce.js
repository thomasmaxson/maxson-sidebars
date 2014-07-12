/*global tinyMCE, tinymce*/
;(function(){
"use strict";   
 
	tinymce.init({
		selector: ".wp-editor-area",
		toolbar: "shortcodes",

		setup: function(editor){
			tinymce.PluginManager.add( 'maxsonsidebar', function(editor, url){

				var values = [],
					sidebarData = maxson_sidebar_management.all_sidebars.replace( new RegExp( "&quot;", "g" ), String.fromCharCode( 34 ) );

				sidebarData = jQuery.parseJSON(sidebarData);

				for(var key in sidebarData){
					values.push({
						text: sidebarData[key],
						value: key, 
					});
				}

				editor.addButton( 'maxsonsidebar', {
					type       : 'listbox',
					text       : maxson_sidebar_management.title,
					icon       : false,
					fixedWidth : true,
					values     : values,
					onselect   : function(e){
						var text  = this.text(),
							value = this.value();

					//	tinymce.execCommand( 'mceInsertContent', false, '[maxson_sidebar id="' + value + '"]' );
						editor.insertContent( '[maxson_sidebar' + ' id="' + value + '"]' );

						return false;
					}, onPostRender : function(){ 
						this.addClass('maxson-sidebar');
					}

				});
			});
		}
	});
})();