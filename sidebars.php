<?php
/**
 * Plugin Name: Sidebars by Maxson
 * Description: Allows you to create new sidebars, and override sidebars per post/taxonomy
 * Version: 1.3
 * Author: Thomas Maxson
 * Author URI: http://thomasmaxson.com/
 * License: GPLv2 or later
 * 
 * This program is free software; you can redistribute it and/or modify 
 * it under the terms of the GNU General Public License version 2, as 
 * published by the Free Software Foundation. 
 * 
 * You may NOT assume that you can use any other version of the GPL.
 *
 * This program is distributed in the hope that it will be useful, 
 * but WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 * 
 * 
 * @package    WordPress
 * @subpackage Maxson_Sidebars
 * @author     Thomas Maxson
 * @copyright  Copyright (c) 2014, Thomas Maxson
 * @since      1.0
 */


/**
 * Access forbidden, exit if accessed directly
 */

if( ! defined( 'ABSPATH' ) )
{ 
	header( 'Status: 403 Forbidden' );
	header( 'HTTP/1.1 403 Forbidden' );
	exit();
}


/**
 * Setup plugin
 *
 * @since 		1.0
 * @return 		void
 */

require_once( 'includes/sidebars-functions.php' );
require_once( 'includes/sidebars-class-main.php' );

global $sidebars;

$sidebars = new Maxson_Sidebar( __FILE__, '1.3' );


/**
 * Setup plugin settings
 *
 * @since 		1.0
 * @return 		void
 */

require_once( 'includes/sidebars-class-settings.php' );

global $sidebars_settings;

$sidebars_settings = new Maxson_Sidebar_Settings();


/**
 * Setup plugin meta boxes
 *
 * @since 		1.0
 * @return 		void
 */

require_once( 'includes/sidebars-class-meta-boxes.php' );

global $sidebars_meta;

$sidebars_meta = new Maxson_Sidebar_Meta_Boxes();

?>