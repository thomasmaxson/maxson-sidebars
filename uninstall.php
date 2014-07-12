<?php 
/**
 * Fired when the plugin is uninstalled.
 *
 * @package    WordPress
 * @subpackage Maxson_Sidebar
 * @author     Thomas Maxson
 * @copyright  Copyright (c) 2014, Thomas Maxson
 * @since      1.0
 */

// If uninstall not called from WordPress, then exit
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

delete_option( 'sidebar_version' );
delete_option( 'sidebar_activated' );
delete_option( 'sidebar_admin_notices' );

delete_option( 'sidebar_post_types_available' );
delete_option( 'sidebar_taxonomies_available' );
delete_option( 'sidebar_user_roles_available' );

delete_option( 'sidebar_meta_taxonomy' );

delete_option( 'sidebar_default_404' );
delete_option( 'sidebar_default_attachment' );
delete_option( 'sidebar_default_author' );
delete_option( 'sidebar_default_index' );
delete_option( 'sidebar_default_post' );
delete_option( 'sidebar_default_search' );
delete_option( 'sidebar_default_taxonomy' );

?>