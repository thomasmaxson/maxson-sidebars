<?php
/**
 * Maxson Sidebar Settings Class
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


class Maxson_Sidebar_Settings 
{ 
	/**
	 * Testimonial setting variables
	 * 
	 * @since 		1.0
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

		add_action( 'admin_init', array( &$this, 'activate_sidebar_settings' ) );
		add_action( 'admin_init', array( &$this, 'register_sidebar_settings' ) );
		add_action( 'admin_menu', array( &$this, 'add_sidebar_settings_menu' ) );
	}


	/**
	 * Return post types to exclude from admin checkboxes
	 *
	 * @since 		1.0
	 *
	 * @return 		array
	 */

	public function get_excluded_post_types()
	{ 
		$excluded_post_types = array( 'sidebar', 'revision', 'nav_menu_item' );

		$output = apply_filters( 'maxson_sidebar_excluded_post_types', $excluded_post_types );

		return $output;
	}


	/**
	 * Return taxonomies to exclude from admin checkboxes
	 *
	 * @since 		1.0
	 *
	 * @return 		array
	 */

	public function get_excluded_taxonomies()
	{ 
		$excluded_taxonomies = array( 'post_format' );

		$output = apply_filters( 'maxson_sidebar_excluded_taxonomies', $excluded_taxonomies );

		return $output;
	}


	/**
	 * Return user roles to exclude from admin checkboxes
	 *
	 * @since 		1.0
	 *
	 * @return 		array
	 */

	public function get_excluded_user_roles()
	{ 
		$remove_buddypress_roles  = array();
		$remove_woocommerce_roles = array();
		
		$active_plugins = get_option( 'active_plugins' );

		if( apply_filters( 'maxson_sidebar_exclude_woocommerce_roles', false ) )
			$remove_woocommerce_roles = array( 'shop_manager', 'customer' );

		if( apply_filters( 'maxson_sidebar_exclude_buddypress_roles', false ) )
			$remove_buddypress_roles = array( 'member', 'moderator' );

		$excluded_user_roles = array_merge( $remove_woocommerce_roles, $remove_buddypress_roles );

		$output = apply_filters( 'maxson_sidebar_excluded_user_roles', $excluded_user_roles );

		return $output;
	}


	/**
	 * Display sidebar select meta fields
	 *
	 * @since 		1.0
	 *
	 * @return 		string
	 */

	public function get_sidebar_select_fields( $type )
	{ 
		global $sidebars;

		$field_name = "sidebar_default_{$type}";

		$sidebar_find_items = array();
		$sidebar_find_all   = $sidebars->get_sidebars_array( false );
		$sidebar_find_meta  = get_sidebar_default( $type, 'find' );

		$sidebar_replace_items = array();
		$sidebar_replace_all   = $sidebars->get_sidebars_array( true );
		$sidebar_replace_meta  = get_sidebar_default( $type, 'replace' );

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


		echo '<div style="display: inline-block;">';
		printf( '<label for="%s_find">%s</label>', $field_name, __( 'Find:', 'maxson' ) );
		printf( '<br><select name="%1$s[find]" id="%1$s_find">%2$s</select>', $field_name, join( "\n", $sidebar_find_items ) );
		echo '</div>';

		echo '<div style="display: inline-block;">';
		printf( '<label for="%s_replace">%s</label>', $field_name, __( 'Replace:', 'maxson' ) );
		printf( '<br><select name="%1$s[replace]" id="%1$s_replace">%2$s</select>', $field_name, join( "\n", $sidebar_replace_items ) );
		echo '</div>';
	}


	/** 
	 * Save the default options on activation
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	public function activate_sidebar_settings()
	{ 
		$sidebar_activated = get_option( 'sidebar_activated', false );

		if( false === $sidebar_activated )
		{ 
			$post_type_options  = get_option( 'sidebar_post_types_available' );
			$taxonomy_options   = get_option( 'sidebar_taxonomies_available' );
			$user_roles_options = get_option( 'sidebar_user_roles_available' );

			// Post Types
			$save_post_types = array();
			$all_post_types  = get_post_types( array( 
				'public'  => true, 
				'show_ui' => true
			), 'names');

			$remove_post_types = $this->get_excluded_post_types();
			$post_types        = array_diff( $all_post_types, $remove_post_types );

			foreach( $post_types as $post_type )
			{ 
				$save_post_types[] = $post_type;

			} // endforeach


			// Taxonomies
			$save_taxonomies = array();
			$all_taxonomies  = get_taxonomies( array( 
				'public'  => true, 
				'show_ui' => true
			), 'names' );

			$remove_taxonomies = $this->get_excluded_taxonomies();
			$taxonomies        = array_diff( $all_taxonomies, $remove_taxonomies );

			foreach( $taxonomies as $taxonomy )
			{ 
				$save_taxonomies[] = $taxonomy;

			} // endforeach


			// User Roles
			$save_user_roles   = array();
			$all_user_roles    = get_editable_roles();

			$remove_user_roles = $this->get_excluded_user_roles();
			$user_roles        = array_diff( array_keys( $all_user_roles ), $remove_user_roles );

			foreach( $user_roles as $user_role )
			{ 
				$save_user_roles[] = $user_role;

			} // endforeach

			update_option( 'sidebar_post_types_available', $save_post_types );
			update_option( 'sidebar_taxonomies_available', $save_taxonomies );
			update_option( 'sidebar_user_roles_available', $save_user_roles );

			update_option( 'sidebar_activated', true );

		} // endif

		update_option( 'sidebar_version', $this->version );
	}


	/**
	 * Add testimonial settings page to menu
	 * 
	 * @since		1.0
	 * 
	 * @return		void
	 */

	function add_sidebar_settings_menu()
	{ 
		add_options_page( __( 'Manage Sidebar Replacement Settings', 'maxson' ), __( 'Sidebar Replacement', 'maxson' ), 'edit_theme_options', 'sidebar_settings', array( &$this, 'add_sidebar_settings_page' ) );
	}


	/**
	 * Testimonial settings page layout
	 * 
	 * @since		1.0
	 * 
	 * @return		void
	 */

	function add_sidebar_settings_page()
	{ ?>
		<div class="wrap">
			<?php screen_icon( 'options-general' ); ?>

			<h2><?php printf( __( '%s Replacement Settings', 'maxson' ), $this->singular ); ?></h2>

			<form id="testimonials_settings" action="options.php" method="post">
				<?php settings_fields( 'sidebar_settings' ); ?>
				<?php do_settings_sections( 'sidebar_settings' ); ?>
				<?php submit_button( __( 'Save Changes', 'maxson' ), 'primary', 'sidebar_settings_submit', true ); ?>
			</form>
		</div><!-- .wrap -->
	<?php }


	/**
	 * Sidebar settings
	 * 
	 * @since		1.0
	 * 
	 * @return		void
	 */

	function register_sidebar_settings()
	{ 
		add_settings_section( 'sidebar_settings_section', '', array( &$this, 'sidebar_option_description' ), 'sidebar_settings' );

		register_setting( 'sidebar_settings', 'sidebar_post_types_available' );
		add_settings_field(	'sidebar_post_types_available', __( 'Post Types', 'maxson' ), array( &$this, 'sidebar_option_post_types_available' ), 'sidebar_settings', 'sidebar_settings_section' );

		register_setting( 'sidebar_settings', 'sidebar_taxonomies_available' );
		add_settings_field(	'sidebar_taxonomies_available', __( 'Taxonomies', 'maxson' ), array( &$this, 'sidebar_option_taxonomies_available' ), 'sidebar_settings', 'sidebar_settings_section' );

		register_setting( 'sidebar_settings', 'sidebar_user_roles_available' );
		add_settings_field(	'sidebar_user_roles_available', __( 'User Roles', 'maxson' ), array( &$this, 'sidebar_option_user_roles_available' ), 'sidebar_settings', 'sidebar_settings_section' );

		add_settings_section( 'sidebar_settings_meta_section', sprintf( __( 'Global %s Overrides', 'maxson' ), $this->singular ), array( &$this, 'sidebar_meta_option_description' ), 'sidebar_settings' );

		register_setting( 'sidebar_settings', 'sidebar_default_404' );

		add_settings_field(	'sidebar_meta_404', __( '404 Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', '404' );

		register_setting( 'sidebar_settings', 'sidebar_default_attachment' );
		add_settings_field(	'sidebar_meta_attachment', __( 'Attachment Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'attachment' );

		register_setting( 'sidebar_settings', 'sidebar_default_category' );
		add_settings_field(	'sidebar_meta_category', __( 'Category Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'cateogry' );

		register_setting( 'sidebar_settings', 'sidebar_default_index' );
		add_settings_field(	'sidebar_meta_index', __( 'Index (Home) Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'index' );

		register_setting( 'sidebar_settings', 'sidebar_default_page' );
		add_settings_field(	'sidebar_meta_page', __( 'Page Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'page' );

		register_setting( 'sidebar_settings', 'sidebar_default_post' );
		add_settings_field(	'sidebar_meta_post', __( 'Post Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'post' );

		register_setting( 'sidebar_settings', 'sidebar_default_search' );
		add_settings_field(	'sidebar_meta_search', __( 'Search Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'search' );

		register_setting( 'sidebar_settings', 'sidebar_default_tag' );
		add_settings_field(	'sidebar_meta_tag', __( 'Tag Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'tag' );

		register_setting( 'sidebar_settings', 'sidebar_default_author' );
		add_settings_field(	'sidebar_meta_author', __( 'User (Author) Template', 'maxson' ), array( &$this, 'sidebar_option_page_override' ), 'sidebar_settings', 'sidebar_settings_meta_section', 'author' );
	}


	/**
	 * Settings description
	 * 
	 * @return		void
	 * 
	 * @since 		1.0
	 */

	public function sidebar_option_description()
	{ 
		_e( 'Select which post types, taxonomies, and users you would like to active sidebar replacements in.', 'maxson' );
	}


	/**
	 * Settings description
	 * 
	 * @return		void
	 * 
	 * @since 		1.0
	 */

	public function sidebar_meta_option_description()
	{ 
		_e( 'Below are default sidebar overrides.', 'maxson' );
	}


	/**
	 * Post type setting callback
	 * 
	 * @since 		1.0
	 * 
	 * @return		void
	 */

	public function sidebar_option_post_types_available()
	{ 
		$all_post_types = get_post_types( array( 
			'public'  => true, 
			'show_ui' => true
		), 'names' );

		if ( empty( $all_post_types ) )
			return;

		$remove_post_types = $this->get_excluded_post_types();
		$post_types        = array_diff( $all_post_types, $remove_post_types );

		$options = get_option( 'sidebar_post_types_available' );

		if( empty( $options ) )
			$options = array();

		foreach( $post_types as $post_type )
		{ 
			$value = in_array( $post_type, $options );
			$checked = checked( $value, true, false );

			$object	= get_post_type_object( $post_type );
			$text = ucwords( $object->labels->name );

			printf( '<input type="checkbox" name="sidebar_post_types_available[]" id="%1$s" value="%1$s"%2$s><label for="%1$s">&nbsp;%3$s</label><br>', esc_attr( $post_type ), $checked, esc_html( $text ) );

		} // endforeach
	}


	/**
	 * Taxonomy setting callback
	 * 
	 * @since 		1.0
	 * 
	 * @return		void
	 */

	public function sidebar_option_taxonomies_available()
	{ 
		$all_taxonomies = get_taxonomies( array(
			'public'  => true, 
			'show_ui' => true, 
		), 'names' ); 

		if ( empty( $all_taxonomies ) )
			return;

		$remove_taxonomies = $this->get_excluded_taxonomies();
		$taxonomies        = array_diff( $all_taxonomies, $remove_taxonomies );

		$options = get_option( 'sidebar_taxonomies_available' );

		if( empty( $options ) )
			$options = array();

		foreach( $taxonomies as $taxonomy )
		{ 
			$value   = in_array( $taxonomy, $options );
			$checked = checked( $value, true, false );

			$object = get_taxonomy( $taxonomy );
			$text   = ucwords( $object->labels->name );

			printf( '<input type="checkbox" name="sidebar_taxonomies_available[]" id="%1$s" value="%1$s"%2$s><label for="%1$s">&nbsp;%3$s</label><br>', esc_attr( $taxonomy ), $checked, esc_html( $text ) );

		} // endforeach
	}


	/**
	 * Users setting callback
	 * 
	 * @since 		1.0
	 * 
	 * @return		void
	 */

	public function sidebar_option_user_roles_available()
	{ 
		$all_user_roles    = get_editable_roles();
		$remove_user_roles = $this->get_excluded_user_roles();
		$user_roles        = array_diff( array_keys( $all_user_roles ), $remove_user_roles );

		$options = get_option( 'sidebar_user_roles_available' );

		if( empty( $options ) )
			$options = array();

		foreach( $user_roles as $user_role )
		{ 
			$value   = in_array( $user_role, $options );
			$checked = checked( $value, 1, false );

			$object = $all_user_roles[$user_role];
			$text = ucwords( $object['name'] );

			printf( '<input type="checkbox" name="sidebar_user_roles_available[]" id="%1$s" value="%1$s"%2$s><label for="%1$s">&nbsp;%3$s</label><br>', esc_attr( $user_role ), $checked, esc_html( $text ) );

		} // endforeach
	}


	/**
	 * Sidebar default override callback
	 * 
	 * @since 		1.0
	 * 
	 * @return		void
	 */

	public function sidebar_option_page_override( $type )
	{ 
		echo $this->get_sidebar_select_fields( $type );
	}

} // endclass

?>