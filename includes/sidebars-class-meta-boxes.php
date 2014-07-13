<?php
/**
 * Maxson Sidebar Meta Boxes Class
 * 
 * @package    WordPress
 * @subpackage Maxson_Sidebar
 * @author     Thomas Maxson
 * @copyright  Copyright (c) 2014, Thomas Maxson
 * @since      1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) )
	exit;


class Maxson_Sidebar_Meta_Boxes 
{ 
	/**
	 * Testimonial setting variables
	 * 
	 * @since 		1.0
	 * 
	 * @return		void
	 */

	public $version;

	public $post_type;
	public $singular;
	public $plural;


	/**
	 * Constructor function
	 * 
	 * @since   	1.0
	 * 
	 * @return		void
	 */

	function __construct()
	{ 
		global $sidebars;

		$this->version = $sidebars->get_version();

		$this->post_type = $sidebars->get_post_type();
		$this->singular  = $sidebars->get_name( false );
		$this->plural    = $sidebars->get_name( true );

		$sidebar_post_types = get_option( 'sidebar_post_types_available', array() );
		$sidebar_taxonomies = get_option( 'sidebar_taxonomies_available', array() );

		if( ! empty( $sidebar_post_types ) )
		{ 
			foreach ( $sidebar_post_types as $sidebar_post_type )
			{ 
				if( 'attachment' == $sidebar_post_type )
				{ 
					add_action( 'admin_init', array( &$this, 'add_attachment_meta_box' ) );

				} else 
				{ 
					add_action( "add_meta_boxes_{$sidebar_post_type}", array( &$this, 'add_post_meta_box' ) );
					add_action( 'save_post', array( &$this, 'save_post_meta_box' ) );

				} // endif
			} // endforeach
		} // endif


		if( ! empty( $sidebar_taxonomies ) )
		{ 
			foreach ( $sidebar_taxonomies as $sidebar_taxonomy )
			{ 
				// Add form fields
				add_action( "{$sidebar_taxonomy}_add_form_fields", array( &$this, 'add_taxonomy_meta_field' ), 10, 2 );
				add_action( "{$sidebar_taxonomy}_edit_form_fields", array( &$this, 'edit_taxonomy_meta_field' ), 10, 2 );

				// Save form fields
				add_action( "edited_{$sidebar_taxonomy}", array( &$this, 'save_taxonomy_meta' ), 10, 2 );
				add_action( "create_{$sidebar_taxonomy}", array( &$this, 'save_taxonomy_meta' ), 10, 2 );

				// Delete field meta
				add_action( "delete_{$sidebar_taxonomy}", array( &$this, 'delete_taxonomy_meta' ), 10, 2 );

			} // endforeach
		} // endif


		// Profile.php
		add_action( 'show_user_profile', array( &$this, 'add_author_meta' ) );
		add_action( 'personal_options_update', array( &$this, 'save_author_meta' ) );

		// User-Edit.php
		add_action( 'edit_user_profile', array( &$this, 'add_author_meta' ) );
		add_action( 'edit_user_profile_update', array( &$this, 'save_author_meta' ) );

		// User-New.php
		add_action( 'user_new_form', array( &$this, 'add_author_meta' ) );
		add_action( 'user_register', array( &$this, 'save_author_meta' ) );
	}


	/**
	 * 
	 *
	 * @since 		1.0
	 *
	 * @return 		void
	 */

	public function build_meta_box_field( $id = '', $before = '<div class="maxson-sidebar-meta-field">', $after = '</div>' )
	{ 
		global $sidebars;

		$sidebar_find_items = array();
		$sidebar_find_all   = $sidebars->get_sidebars_array( false );

		$sidebar_replace_items = array();
		$sidebar_replace_all   = $sidebars->get_sidebars_array( true );

		if( empty( $id ) )
		{ 
			$sidebar_find_meta    = '';
			$sidebar_replace_meta = '';

		} else
		{ 
			$screen = get_current_screen();

			if( 'attachment' == $screen->post_type )
			{ 
				$sidebar_find_meta    = get_sidebar_post_meta( 'find', 'attachment', $id );
				$sidebar_replace_meta = get_sidebar_post_meta( 'replace', 'attachment', $id );

			} else if( 'page' == $screen->post_type )
			{ 
				$sidebar_find_meta    = get_sidebar_post_meta( 'find', 'page', $id );
				$sidebar_replace_meta = get_sidebar_post_meta( 'replace', 'page', $id );

			} else if( in_array( $screen->base, array( 'profile', 'user-edit' ) ) )
			{ 
				$sidebar_find_meta    = get_sidebar_user_meta( 'find', 'author', $id );
				$sidebar_replace_meta = get_sidebar_user_meta( 'replace', 'author', $id );

			} else
			{ 
				$sidebar_find_meta    = get_sidebar_post_meta( 'find', 'post', $id );
				$sidebar_replace_meta = get_sidebar_post_meta( 'replace', 'post', $id );

			} // endif
		} // endif


		foreach( $sidebar_find_all as $key => $value )
		{ 
			$selected = selected( $sidebar_find_meta, $key, false );
			$sidebar_find_items[] = sprintf( '<option value="%1$s"%3$s>%2$s</option>', $key, $value, $selected );

		} // endforeach


		foreach( $sidebar_replace_all as $key => $value )
		{ 
			$selected = selected( $sidebar_replace_meta, $key, false );
			$sidebar_replace_items[] = sprintf( '<option value="%1$s"%3$s>%2$s</option>', $key, $value, $selected );

		} // endforeach


		echo $before;
		printf( '<label for="sidebar_find">%s</label>', __( 'Find:', 'maxson' ) );
		printf( '<select name="sidebar_find" id="sidebar_find" class="widefat">%s</select>', join( "\n", $sidebar_find_items ) );
		echo $after;

		echo $before;
		printf( '<label for="sidebar_replace">%s</label>', __( 'Replace:', 'maxson' ) );
		printf( '<select name="sidebar_replace" id="sidebar_replace" class="widefat">%s</select>', join( "\n", $sidebar_replace_items ) );
		echo $after;
	}


	/**
	 * Init post and page sidebar meta boxes
	 *
	 * @since 		1.0
	 *
	 * @return 		void
	 */

	public function add_post_meta_box( $post_obj )
	{ 
		$post_type = $post_obj->post_type;

		$post_title    = apply_filters( 'sidebar_post_meta_box_title', __( 'Sidebar Replacement', 'maxson' ), $post_type );
		$post_context  = apply_filters( 'sidebar_post_meta_box_context', 'side', $post_type );
		$post_priority = apply_filters( 'sidebar_post_meta_box_priority', 'default', $post_type );

		add_meta_box( 'post_sidebar_replacement', $post_title, array( &$this, 'post_meta_box_field' ), $post_type, $post_context, $post_priority );
	}


	/**
	 * Init attachment sidebar meta box
	 *
	 * @since 		1.0
	 *
	 * @return 		void
	 */

	public function add_attachment_meta_box()
	{ 
		$attachment_title    = apply_filters( 'sidebar_attachment_meta_box_title', __( 'Sidebar Replacement', 'maxson' ) );
		$attachment_context  = apply_filters( 'sidebar_attachment_meta_box_context', 'side' );
		$attachment_priority = apply_filters( 'sidebar_attachment_meta_box_priority', 'default' );

		add_meta_box( 'attachment_sidebar_replacement', $attachment_title, array( &$this, 'attachment_meta_box_field' ), 'attachment', $attachment_context, $attachment_priority, array( 'post_type' => 'attachment' ) );
	
	}


	/**
	 * 
	 *
	 * @since 		1.0
	 * 
	 * @param 		$data array
	 * @return 		void
	 */

	public function post_meta_box_field( $data ) 
	{ 
		$add_new_label = sprintf( __( 'Add New %s', 'maxson' ), ucwords( $this->post_type ) );
		$add_new_value = sprintf( __( 'New %s Name', 'maxson' ), ucwords( $this->post_type ) );

		$this->build_meta_box_field( get_the_ID() );

		echo '<div id="sidebar-adder" class="wp-hidden-children">';
			printf( '<h4><a id="sidebar-add-toggle" href="#sidebar-add" class="hide-if-no-js">+ %s</a></h4>', $add_new_label );

			echo '<p id="sidebar-add" class="sidebar-add wp-hidden-child">';
				printf( '<label class="screen-reader-text" for="newsidebar">%s</label>', $add_new_label );
				echo'<input type="text" name="newsidebar" id="newsidebar" class="form-required form-input-tip" value="" aria-required="true">';
				printf( '<input type="button" id="sidebar-add-submit" class="button sidebar-add-submit" value="%s">', $add_new_label );
				wp_nonce_field( 'maxson-add-sidebar-nonce', 'maxson-add-sidebar-nonce' );
			echo '</p>';
		echo '</div>';
	}


	/**
	 * 
	 *
	 * @since 		1.0
	 * 
	 * @param 		$data array
	 * @return 		void
	 */

	public function attachment_meta_box_field( $data ) 
	{ 
		$this->build_meta_box_field( get_the_ID() );
	}


	/**
	 * 
	 *
	 * @since 		1.0
	 *
	 * @return 		void
	 */

	public function save_post_meta_box( $post_id )
	{ 
		if ( wp_is_post_autosave( $post_id ) && wp_is_post_revision( $post_id ) )
			return $post_id;

		$screen = get_current_screen();

		$find_old = get_post_meta( $post_id, 'sidebar_find', true );
		$find_new = isset( $_POST['sidebar_find'] ) ? $_POST['sidebar_find'] : '';

		$replace_old = get_post_meta( $post_id, 'sidebar_replace', true );
		$replace_new = isset( $_POST['sidebar_replace'] ) ? $_POST['sidebar_replace'] : '';

		if( isset( $screen->post_type ) )
		{ 
			if( 'page' == $screen->post_type )
			{ 
				$sidebar_find_option    = get_sidebar_post_meta( 'find', 'page', $post_id );
				$sidebar_replace_option = get_sidebar_post_meta( 'replace', 'page', $post_id );

			} else 
			{ 
				$sidebar_find_option    = get_sidebar_post_meta( 'find', 'post', $post_id );
				$sidebar_replace_option = get_sidebar_post_meta( 'replace', 'post', $post_id );

			} // endif
		} // endif


		if ( $find_new != $find_old && $find_new != $sidebar_find_option )
		{ 
			update_post_meta( $post_id, 'sidebar_find', $find_new );

		} else
		{ 
			delete_post_meta( $post_id, 'sidebar_find' );

		} // endif

		if ( $replace_new != $replace_old && $replace_new != $sidebar_replace_option )
		{ 
			update_post_meta( $post_id, 'sidebar_replace', $replace_new );

		} else
		{ 
			delete_post_meta( $post_id, 'sidebar_replace' );

		} // endif
	}


	/**
	 * Add taxonomy sidebar meta fields
	 *
	 * @since 		1.0
	 *
	 * @param 		object $term
	 * @param 		object $taxonomy
	 * @return 		void
	 */

	public function add_taxonomy_meta_field( $term )
	{ 
		global $sidebars;

		$sidebar_find_items = array();
		$sidebar_find_all   = $sidebars->get_sidebars_array( false );

		$sidebar_replace_items = array();
		$sidebar_replace_all   = $sidebars->get_sidebars_array( true );

		foreach( $sidebar_find_all as $key => $value )
		{ 
			$sidebar_find_items[] = sprintf( '<option value="%s">%s</option>', $key, $value );

		} // endforeach


		foreach( $sidebar_replace_all as $key => $value )
		{ 
			$sidebar_replace_items[] = sprintf( '<option value="%s">%s</option>', $key, $value );

		} // endforeach

	?>
		<div class="form-field">
			<label for="sidebar_find"><?php _e( 'Find Sidebar', 'maxson' ); ?></label>
			<?php printf( '<select name="%s[find]" id="sidebar_find" class="widefat">%s</select>', 'sidebar_meta_taxonomy', join( "\n", $sidebar_find_items ) ); ?>

			<br><br>

			<label for="sidebar_replace"><?php _e( 'Replace Sidebar', 'maxson' ); ?></label>
			<?php printf( '<select name="%s[replace]" id="sidebar_replace" class="widefat">%s</select>', 'sidebar_meta_taxonomy', join( "\n", $sidebar_replace_items ) ); ?>
		</div>
	<?php }


	/**
	 * Edit taxonomy sidebar meta fields
	 *
	 * @since 		1.0
	 *
	 * @param 		object $term
	 * @param 		object $taxonomy
	 * @return 		void
	 */

	public function edit_taxonomy_meta_field( $term, $taxonomy )
	{ 
		global $sidebars;

		$taxonomy_id   = $term->term_id;
		$taxonomy_type = $term->taxonomy;

		$sidebar_find_items = array();
		$sidebar_find_all   = $sidebars->get_sidebars_array( false );

		$sidebar_replace_items = array();
		$sidebar_replace_all   = $sidebars->get_sidebars_array( true );

		$sidebar_find_meta    = get_sidebar_taxonomy_meta( 'find', $taxonomy_type, $taxonomy_id );
		$sidebar_replace_meta = get_sidebar_taxonomy_meta( 'replace', $taxonomy_type, $taxonomy_id );


		foreach( $sidebar_find_all as $key => $value )
		{ 
			$selected = selected( $sidebar_find_meta, $key, false );

			$sidebar_find_items[] = sprintf( '<option value="%s"%s>%s</option>', $key, $selected, $value );

		} // endforeach


		foreach( $sidebar_replace_all as $key => $value )
		{ 
			$selected = selected( $sidebar_replace_meta, $key, false );

			$sidebar_replace_items[] = sprintf( '<option value="%s"%s>%s</option>', $key, $selected, $value );

		} // endforeach 
	?>
		<tr class="form-field maxson-sidebar-meta">
			<th scope="row" valign="top">
				<label><?php _e( 'Sidebar', 'maxson' ); ?></label>
			</th>
			<td>
				<div class="maxson-sidebar-meta-field">
					<label for="sidebar_find"><?php _e( 'Find:', 'maxson' ); ?></label>
					<?php printf( '<select name="%s[find]" id="sidebar_find" class="widefat">%s</select>', 'sidebar_meta_taxonomy', join( "\n", $sidebar_find_items ) ); ?>
				</div>
				<div class="maxson-sidebar-meta-field">
					<label for="sidebar_replace"><?php _e( 'Replace:', 'maxson' ); ?></label>
					<?php printf( '<select name="%s[replace]" id="sidebar_replace" class="widefat">%s</select>', 'sidebar_meta_taxonomy', join( "\n", $sidebar_replace_items ) ); ?>
				</div>
			</td>
		</tr>

	<?php }


	/**
	 * Save taxonomy sidebar meta fields
	 *
	 * @since 		1.0
	 *
	 * @param 		integer $term_id
	 * @param 		integer $tt_id
	 * @return 		void
	 */

	public function save_taxonomy_meta( $term_id, $tt_id )
	{ 
		if ( ! $term_id )
			return;

		if ( isset( $_POST['sidebar_meta_taxonomy'] ) )
		{ 
			$sidebars = get_option( 'sidebar_meta_taxonomy' );

			if ( empty( $_POST['sidebar_meta_taxonomy'] ) )
			{ 
				unset( $sidebars[$term_id] );

			} else 
			{ 
				$sidebars[$term_id] = $_POST['sidebar_meta_taxonomy'];

			} // endif


			if ( empty( $sidebars ) )
			{ 
				delete_option( 'sidebar_meta_taxonomy' );

			} else 
			{ 
				update_option( 'sidebar_meta_taxonomy', $sidebars );

			} // endif
		} // endif
	}


	/**
	 * Delete taxonomy sidebar meta values
	 * 
	 * @since 		1.0
	 *
	 * @param 		integer $term_id
	 * @param 		integer $tt_id
	 * @return 		void
	 */

	public function delete_taxonomy_meta( $term_id, $tt_id )
	{ 
		$taxonomies = get_option( 'sidebar_meta_taxonomy' );

		if ( isset( $taxonomies[$term_id] ) )
		{ 
			unset( $taxonomies[$term_id] );

			update_option( 'sidebar_meta_taxonomy', $taxonomies );

		} // endif
	}


	/**
	 * Add author sidebar meta fields
	 *
	 * @since 		1.0
	 *
	 * @param 		integer $user
	 * @return 		void
	 */

	public function add_author_meta( $user )
	{ 
		$user_id = ( isset( $user->ID ) && ! empty( $user->ID ) ) ? $user->ID : false;
		$user_roles = array();

		if( $user_id )
		{ 
			$user = get_userdata( $user_id );
			$user_roles = $user->roles;

		} // endif

		$sidebar_authors = get_option( 'sidebar_user_roles_available', array() );

		$role_intersect = array_intersect( $user_roles, $sidebar_authors );

		if( count( $role_intersect ) > 0 )
		{ ?>
		<table class="form-table">
			<tr>
				<th><label><?php _e( 'Sidebar', 'maxson' ); ?></label></th>
				<td>
					<?php $this->build_meta_box_field( $user_id ); ?>
				</td>
			</tr>
		</table>
		<?php } // endif
	}


	/**
	 * Save author sidebar meta fields
	 *
	 * @since 		1.0
	 *
	 * @param 		integer $user_id
	 * @return 		void
	 */

	function save_author_meta( $user_id )
	{ 
		if( ! current_user_can( 'edit_user', $user_id ) )
			return false;

		if( isset( $_POST['sidebar_find'] ) )
			update_user_meta( $user_id, 'sidebar_find', $_POST['sidebar_find'] );

		if( isset( $_POST['sidebar_replace'] ) )
			update_user_meta( $user_id, 'sidebar_replace', $_POST['sidebar_replace'] );
	}


} // endclass

?>