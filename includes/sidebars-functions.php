<?php
/**
 * Return sidebar option value
 * 
 * @since 		1.0
 * 
 * @param       $type    string         Template part
 * @param       $data    string         Data type to return form option
 * @param       $default string|boolean Default value
 * @return 		string
 */

function get_sidebar_default( $type = '', $data = '', $default = false )
{ 
	if( empty( $type ) || empty( $data ) )
		return false;

	$option = get_option( "sidebar_default_{$type}", array() );
		$value = isset( $option[$data] ) ? $option[$data] : $default;

	return $value;
}


/**
 * Return dynamic sidebar data
 * 
 * @since 		1.1
 * 
 * @return 		void
 */

function get_maxson_sidebar( $id = '' )
{ 
	if( empty( $id ) )
		return false;

	$sidebar = '';

	if( function_exists( 'dynamic_sidebar' ) && function_exists( 'is_active_sidebar' ) )
	{ 
		if( is_active_sidebar( $id ) )
		{
			ob_start();

			dynamic_sidebar( $id );

			$sidebar = ob_get_clean();

		} // endif
	} // endif

	return $sidebar;
}

function maxson_sidebar( $id = '' )
{
	echo get_maxson_sidebar( $id ); 
}


/**
 * Return post meta or default option value
 * 
 * @since 		1.0
 * 
 * @param       $type    string 
 * @param       $data    string 
 * @param       $post_id int    Post id
 * @return 		string
 */

function get_sidebar_post_meta( $type = '', $data = '', $post_id = '' )
{ 
	if( empty( $data ) || empty( $type ) )
		return false;

	if( empty( $post_id ) )
		$post_id = get_the_ID();

	$value = get_post_meta( $post_id, "sidebar_{$type}", true );

	if( empty( $value ) || false === $value )
	{ 
		$value = get_sidebar_default( $data, $type );

	} // endif

	return $value;
}

/**
 * Return taxonomy meta or default option value
 * 
 * @since 		1.0
 * 
 * @param       $type    string 
 * @param       $data    string 
 * @param       $term_id int    Post id
 * @return 		string
 */

function get_sidebar_taxonomy_meta( $type = '', $data = '', $term_id = '' )
{ 
	if( empty( $data ) || empty( $type ) )
		return false;

	if( empty( $term_id ) )
		$term_id = get_the_ID();

	$value = '';

	$taxonomy_meta = get_option( 'sidebar_meta_taxonomy' );


	if( isset( $taxonomy_meta[$term_id] ) )
	{ 
		$value = ( isset( $taxonomy_meta[$term_id][$type] ) ) ? $taxonomy_meta[$term_id][$type] : false;

	} // endif


	if( empty( $value ) || false === $value )
	{ 
		$value = get_sidebar_default( $data, $type );

	} // endif

	return $value;
}


/**
 * Return user meta or default option value
 * 
 * @since 		1.0
 * 
 * @param       $type    string 
 * @param       $data    string 
 * @param       $post_id int    Post id
 * @return 		string
 */

function get_sidebar_user_meta( $type = '', $data = 'author', $user_id = '' )
{ 
	if( empty( $data ) || empty( $type ) )
		return false;

	if( empty( $user_id ) )
		$user_id = get_the_ID();

	$value = get_the_author_meta( "sidebar_{$type}", $user_id );

	if( empty( $value ) || false === $value )
	{ 
		$value = get_sidebar_default( $data, $type );

	} // endif

	return $value;
}

?>