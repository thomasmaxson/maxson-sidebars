<?php
/**
 * Maxson Sidebars Class
 * 
 * @package    WordPress
 * @subpackage Maxson_Sidebars
 * @author     Thomas Maxson
 * @copyright  Copyright (c) 2014, Thomas Maxson
 * @since      1.0
 */

// Exit if accessed directly
if( ! defined( 'ABSPATH' ) )
	exit;


class Maxson_Sidebar 
{ 
	/**
	 * Plugin file paths
	 * 
	 * @since 		1.0
	 */

	public $version;
	public $file;

	public $dir;
	public $basename;
	public $plugin_url;

	public $assets_url;
	public $asset_images;
	public $asset_css;
	public $asset_js;

	public $post_type;
	public $singular;
	public $plural;


	/** 
	 * Constructor
	 * 
	 * @return 		void
	 * @since 		1.0
	 */

	function __construct( $file, $version )
	{ 
		// General Setup
		$this->version    = $version;
		$this->file       = $file;

		$this->dir        = dirname( $this->file );
		$this->dir_path   = plugin_dir_path( $this->dir );
		$this->basename   = plugin_basename( $this->file );

		$this->plugin_url = trailingslashit( plugins_url( '', $this->file ) );

		$this->assets_url = trailingslashit( $this->plugin_url . 'assets' );
			$this->assets_images = trailingslashit( $this->assets_url . 'images' );
			$this->assets_css    = trailingslashit( $this->assets_url . 'css' );
			$this->assets_js     = trailingslashit( $this->assets_url . 'js' );

		// Global data
		$this->post_type = _x( 'sidebar', 'post type slug', 'maxson' );
		$this->singular  = _x( 'Sidebar', 'post type singular name', 'maxson' );
		$this->plural    = _x( 'Sidebars', 'post type plural name', 'maxson' );

		// Run this on activation/deactivation
		register_activation_hook( $this->file, array( &$this, 'do_activation' ) );
		register_deactivation_hook( $this->file, array( &$this, 'do_deactivation' ) );

		// Setup
		add_action( 'admin_init', array( &$this, 'can_deactivate_plugin' ) );
		add_action( 'admin_notices', array( &$this, 'activation_error' ) );
		add_action( 'after_switch_theme', array( &$this, 'check_for_theme_sidebars' ) );

		add_action( 'init', array( &$this, 'load_localization' ) );
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_filter( 'wp', array( &$this, 'sidebar_hijack' ) );

		// plugins.php
		add_filter( "plugin_action_links_{$this->basename}", array( &$this, 'plugin_action_links' ), 10, 2 );
		add_filter( "network_admin_plugin_action_links_{$this->basename}", array( &$this, 'plugin_action_links' ), 10, 2 );

		// widgets.php
		add_action( 'admin_print_scripts-widgets.php', array( &$this, 'widgets_form' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'styles_and_scripts' ) );
		add_action( 'load-widgets.php', array( &$this, 'add_sidebar' ), 100 );
		add_action( 'widgets_init', array( &$this, 'register_sidebar' ), 999 );
		add_action( 'admin_notices', array( &$this, 'widgets_message' ) );

		// TinyMCE (post.php & post-new.php)
		add_action( 'admin_head', array( &$this, 'tinymce_sidebar' ) );

		// AJAX
		add_action( 'wp_ajax_maxson_ajax_add_sidebar', array( &$this, 'ajax_add_sidebar' ) );
		add_action( 'wp_ajax_nopriv_maxson_ajax_add_sidebar', array( &$this, 'ajax_add_sidebar' ) );
		add_action( 'wp_ajax_maxson_ajax_delete_sidebar', array( &$this, 'ajax_delete_sidebar' ) );
		add_action( 'wp_ajax_nopriv_maxson_ajax_delete_sidebar', array( &$this, 'ajax_delete_sidebar' ) );

		// Shortcode
		add_shortcode( 'maxson_sidebar', array( &$this, 'maxson_sidebar_shortcode' ) );
	}


	/**
	 * Run on activation
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	function do_activation()
	{ 
		$this->check_for_theme_sidebars();
		flush_rewrite_rules();
	}


	/**
	 * Run on deactivation
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	function do_deactivation()
	{ 
		flush_rewrite_rules();
	}


	/**
	 * Checks if current theme has any registered sidebars
	 * 
	 * @since 		1.3
	 * 
	 * @return 		void
	 */

	public function check_for_theme_sidebars()
	{ 
		global $wp_registered_sidebars;

		if( ! array_keys( $wp_registered_sidebars ) )
		{ 
			$notices = get_option( 'sidebar_admin_notices', array() );
			$plugin_name = 'Sidebars by Maxson';

		//	$plugin_data = get_plugin_data( dirname( plugin_basename( $this->file ) ) );
		//	$plugin_name = $plugin_data->Name;

			$notices[] = sprintf( __( '<strong>%s has been deactived.</strong> The current theme has no defined sidebars. You must <a href="http://codex.wordpress.org/Function_Reference/register_sidebar" target="_blank" title="WordPress.org function reference">register sidebar(s)</a> before using this plugin.', 'maxson' ), $plugin_name );

			update_option( 'sidebar_admin_notices', $notices );

		} // endif
	}


	/**
	 * Display plugin activation error message
	 * 
	 * @since 		1.3
	 * 
	 * @return 		void
	 */

	public function activation_error()
	{ 
		$notices = get_option( 'sidebar_admin_notices' );

		if( empty( $notices ) )
			return false;

		foreach( $notices as $notice )
		{ 
			printf( '<div class="error"><p>%s</p></div>', $notice );

		} // endforeach

		delete_option( 'sidebar_admin_notices' );
	}


	/**
	 * Programatically deactivates plugin
	 * 
	 * @since 		1.3
	 * 
	 * @return 		void
	 */

	public function can_deactivate_plugin()
	{ 
		$notices = get_option( 'sidebar_admin_notices' );

		if( ! empty( $notices ) && is_plugin_active( $this->basename ) )
		{ 
			deactivate_plugins( $this->basename );

			if( isset( $_GET['activate'] ) )
				unset( $_GET['activate'] );

		} // endif
	}


	/**
	 * Initialize translations
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	function load_localization()
	{ 
		$domain          = 'maxson';
		$abs_rel_path    = false;
		$plugin_rel_path = dirname( plugin_basename( $this->file ) ) . '/languages/';

		load_plugin_textdomain( $domain, $abs_rel_path, $plugin_rel_path );
	}


	/** 
	 * Get plugin global data
	 *
	 * @since 		1.0
	 * 
	 * @return 		string
	 */

	public function get_version()
	{ 
		return $this->version;
	}


	public function get_post_type()
	{ 
		return $this->post_type;
	}


	public function get_name( $plural = false )
	{ 
		$value = ( $plural ) ? $this->plural : $this->singular;

		return $value;
	}


	/**
	 * Get available sidebars
	 * 
	 * @return 		array
	 *
	 * @since 		1.1
	 */

	public function get_sidebars( $args = array() )
	{ 
		$defaults = array( 
			'post_type'   => $this->post_type,
			'post_status' => 'publish'
		);

		$args = wp_parse_args( $args, $defaults );

		$sidebars = get_posts( $args );

		return $sidebars;
	}


	/**
	 * Get array of available sidebars
	 * 
	 * @return 		string
	 *
	 * @since 		1.0
	 */

	public function get_sidebars_array( $show_generated_sidebars = false )
	{ 
		global $wp_registered_sidebars;

		$output = array();

		if ( $wp_registered_sidebars && ! is_wp_error( $wp_registered_sidebars ) )
		{ 
			$output[null] = apply_filters( 'maxson_sidebar_meta_box_default_text', __( 'Choose One', 'maxson' ) );

			$generated_sidebars = array();
			$the_sidebars = $this->get_sidebars();

			if ( ! empty( $the_sidebars ) )
			{ 
				foreach( $the_sidebars as $the_sidebar )
				{ 
					$generated_sidebars[] = $the_sidebar->post_name;

				} // endforeach
			} // endif


			foreach ( $wp_registered_sidebars as $sidebar_lookup )
			{ 
				$sidebar_id   = strtolower( $sidebar_lookup['id'] );
				$sidebar_name = ucwords( $sidebar_lookup['name'] );

				if ( false == $show_generated_sidebars && in_array( $sidebar_id, $generated_sidebars ) )
					continue;

				$output[$sidebar_id] = $sidebar_name;

			} // endforeach
		} // endif

		return (array) $output;
	}


	/**
	 * Hijack sidebar widgets
	 * Replaces the content, but not the design of sidebars during the page display
	 * 
	 * @return 		void
	 *
	 * @since 		1.0
	 */

	function sidebar_hijack( $query )
	{ 
		global $wp_query, $_wp_sidebars_widgets;

		if( is_admin() )
			return $query;

		$sidebar_find_meta    = '';
		$sidebar_replace_meta = '';

		$post_obj = $wp_query->get_queried_object();
		$post_id  = $post_obj->ID;

		if( is_home() )
		{ 
			$sidebar_find_meta    = get_sidebar_default( 'index', 'find' );
			$sidebar_replace_meta = get_sidebar_default( 'index', 'replace' );

		} else if( is_singular() )
		{ 
			$post_types_available = get_option( 'sidebar_post_types_available', array() );

			$post_type = $post_obj->post_type;

			if( isset( $post_type ) && ! empty( $post_types_available ) )
			{ 
				if( array_key_exists( $post_type, $post_types_available ) )
				{ 
					$sidebar_find_meta    = get_sidebar_post_meta( 'find', $post_type, $post_id );
					$sidebar_replace_meta = get_sidebar_post_meta( 'replace', $post_type, $post_id );

				} // endif
			} // endif

		} else if( is_archive() )
		{ 
			if( is_category() || is_tag() )
			{ 
				$taxonomies_available = get_option( 'sidebar_taxonomies_available', array() );

				$taxonomy_type = $post_obj->taxonomy;

				if ( isset( $taxonomy_type ) && ! empty( $taxonomies_available ) )
				{ 
					if( array_key_exists( $taxonomy_type, $taxonomies_available ) )
					{ 
						$taxonomy_id     = $post_obj->term_id;
						$taxonomy_parent = $post_obj->parent;
						$taxonomy_type   = ( is_category() ) ? 'category' : 'tag';

						$taxonomy = get_option( 'sidebar_meta_taxonomy', array() );

						$sidebar_find_meta    = get_sidebar_taxonomy_meta( 'find', $taxonomy_type, $taxonomy_id );
						$sidebar_replace_meta = get_sidebar_taxonomy_meta( 'replace', $taxonomy_type, $taxonomy_id );

						while ( '' == $sidebar_find_meta && $taxonomy_parent > 0 )
						{ 
							$post_obj = get_term( $taxonomy_parent, $post_obj->taxonomy );

							if( isset( $taxonomy[$post_obj->term_id]['find'] ) )
								$sidebar_find_meta = $taxonomy[$post_obj->term_id]['find'];

						} // endwhile
					} // endif
				} // endif

			} else if( is_author() )
			{ 
				$authors_available = get_option( 'sidebar_authors_available', array() );

				$author_id = $post_obj->data->ID;

				if ( isset( $author_id ) && ! empty( $authors_available ) )
				{ 
					if( array_key_exists( $author_id, $authors_available ) )
					{ 
						$sidebar_find_meta    = get_sidebar_user_meta( 'find', 'author', $post_id );
						$sidebar_replace_meta = get_sidebar_user_meta( 'replace', 'author', $post_id );

					} // endif
				} // endif

		// --------------------------------------------------
		// Do people even use date/time templates?
		// If so, let us know and we will activate this!
		// --------------------------------------------------

		//	} else if( is_date() )
		//	{ 
		//		if( is_year() )
		//		{ 
		//			$sidebar_find_meta    = get_sidebar_default( 'year', 'find' );
		//			$sidebar_replace_meta = get_sidebar_default( 'year', 'replace' );

		//		if( is_month() )
		//		{ 
		//			$sidebar_find_meta    = get_sidebar_default( 'month', 'find' );
		//			$sidebar_replace_meta = get_sidebar_default( 'month', 'replace' );

		//		if( is_day() )
		//		{ 
		//			$sidebar_find_meta    = get_sidebar_default( 'day', 'find' );
		//			$sidebar_replace_meta = get_sidebar_default( 'day', 'replace' );

		//		if( is_time() )
		//		{ 
		//			$sidebar_find_meta    = get_sidebar_default( 'time', 'find' );
		//			$sidebar_replace_meta = get_sidebar_default( 'time', 'replace' );

		//		} else 
		//		{ 
		//			$sidebar_find_meta    = get_sidebar_default( 'date', 'find' );
		//			$sidebar_replace_meta = get_sidebar_default( 'date', 'replace' );

		//		} // endif
			} // endif

		} else if( is_search() )
		{ 
			$sidebar_find_meta    = get_sidebar_default( 'search', 'find' );
			$sidebar_replace_meta = get_sidebar_default( 'search', 'replace' );

		} else if( is_404() )
		{ 
			$sidebar_find_meta    = get_sidebar_default( '404', 'find' );
			$sidebar_replace_meta = get_sidebar_default( '404', 'replace' );

		} // endif


		// Process sidebar sorcery
		if ( ! empty( $sidebar_find_meta ) && ! empty( $sidebar_replace_meta ) )
		{
			if ( isset( $_wp_sidebars_widgets[ $sidebar_find_meta ] ) )
			{ 
				$_wp_sidebars_widgets[ $sidebar_find_meta ] = $_wp_sidebars_widgets[ $sidebar_replace_meta ];

			} // endif
		} // endif
	}


	/**
	 * Modify sidebar name
	 * 
	 * @return 		void
	 *
	 * @since 		1.0
	 */

	public function clean_sidebar_name( $name )
	{
		global $wp_registered_sidebars;

		if ( empty( $wp_registered_sidebars ) )
			return $name;

		$taken    = array();
		$sidebars = $this->get_sidebars();

		foreach ( $wp_registered_sidebars as $sidebar )
		{
			$taken[] = $sidebar['name'];

		} // endforeach

		if ( empty( $sidebars ) )
			$sidebars = array();

		$taken = array_merge( $taken, $sidebars );

		if ( in_array( $name, $taken ) )
		{
			$counter  = substr( $name, -1 );
			$new_name = '';

			if ( ! is_numeric( $counter ) )
			{ 
				$new_name = $name . ' 1';

			} else
			{ 
				$new_name = substr( $name, 0, -1 ) . ( (int) $counter + 1 );

			} // endif

			$name = $this->clean_sidebar_name( $new_name );

		} // endif

		return (string) $name;
	}


	/**
	 * Add additional plugin action link(s)
	 *
	 * @since 		1.0
	 *
	 * @return		array
	 */

	public function plugin_action_links( $links, $file )
	{ 
		$links = array_merge( $links, array( 
			'options' => sprintf( '<a href="%s">%s</a>', 
				add_query_arg( 'page', 'sidebar_settings', admin_url( 'options-general.php' ) ), 
				_x( 'Settings', 'Plugin settings link', 'maxson' )
		) ) );

		return (array) $links;
	}


	/**
	 * Register the post type
	 *
	 * @since 		1.0
	 *
	 * @return 		void
	 */

	public function register_post_type()
	{
		$single_slug   = apply_filters( 'maxson_sidebar_single_slug', 'sidebar' );
		$archive_slug  = apply_filters( 'maxson_sidebar_archive_slug', 'sidebars' );
		$menu_position = apply_filters( 'maxson_sidebar_menu_position', '' );
		$supports 	   = apply_filters( 'maxson_sidebar_supports', array( 'title', 'editor' ) );

		$labels = array( 
			'name'               => $this->plural,
			'singular_name'      => $this->singular,
			'add_new'            => _x( 'Add New', $this->singular, 'maxson' ),
			'add_new_item'       => sprintf( __( 'Add New %s', 'maxson' ), $this->singular ),
			'edit_item'          => sprintf( __( 'Edit %s', 'maxson' ), $this->singular ),
			'new_item'           => sprintf( __( 'New %s', 'maxson' ), $this->singular ),
			'all_items'          => sprintf( __( 'All %s', 'maxson' ), $this->plural ),
			'view_item'          => sprintf( __( 'View %s', 'maxson' ), $this->singular ),
			'search_items'       => sprintf( __( 'Search %a', 'maxson' ), $this->plural ),
			'not_found'          => sprintf( __( 'No %s Found', 'maxson' ), $this->plural ),
			'not_found_in_trash' => sprintf( __( 'No %s Found In Trash', 'maxson' ), $this->plural ),
			'parent_item_colon'  => '',
			'menu_name'          => $this->plural
		);

		$args = array( 
			'labels'             => $labels,
			'public'             => true,
			'publicly_queryable' => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'query_var'          => false,
			'rewrite'            => array( 'slug' => $single_slug, 'with_front' => false ),
			'capability_type'    => 'post',
			'has_archive'        => $archive_slug,
			'hierarchical'       => false,
			'supports'           => $supports,
			'menu_position'      => $menu_position,
			'menu_icon'          => esc_url( $this->assets_images . 'admin/icon-sidebars-16.png' )
		);

		$sidebar_args = apply_filters( 'maxson_sidebar_post_type_args', $args );

		register_post_type( $this->post_type, $sidebar_args );
	}


	/**
	 * Register custom sidebars for theme
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	public function register_sidebar()
	{ 
		$args = array( 
			'orderby' => 'menu_order'
		);

		$the_sidebars = $this->get_sidebars( $args );

		$args = array( 
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' 	=> '</div>',
			'before_title' 	=> '<h3 class="widget-title">',
			'after_title' 	=> '</h3>'
		);
		
		if ( ! empty( $the_sidebars ) && count( $the_sidebars ) > 0 )
		{ 
			foreach ( $the_sidebars as $sidebar => $sidebar_data )
			{ 
				$sidebar_id    = $sidebar_data->ID;
				$sidebar_name  = $sidebar_data->post_name;
				$sidebar_title = $sidebar_data->post_title;

				$args['id']   = $sidebar_name;
				$args['name'] = $sidebar_title;

				$args = apply_filters( 'maxson_sidebar_generated_args', $args, $sidebar_name, $sidebar_title );

				if ( is_admin() )
				{ 
					$args['class'] = 'generated';

				} // endif

				register_sidebar( $args );

			} // endforeach
		} // endif
	}


	/**
	 * Enqueue scripts and localization on widgets.php
	 * 
	 * @since 		1.0
	 * 
	 * @return 		string
	 */

	public function styles_and_scripts( $hook )
	{ 
		$css_deps = array();
		$js_deps  = array();

		// Styles
		if( wp_style_is( 'dashicons', 'registered' ) )
		{ 
			wp_enqueue_style( 'dashicons' );
			$css_deps[] = 'dashicons';

		} // endif

		wp_register_style( 'maxson-sidebars-admin', $this->assets_css . 'maxson-sidebars-admin.css', $css_deps, $this->version, 'screen' );

		if( wp_style_is( 'maxson-sidebars-admin', 'registered' ) )
		{ 
			wp_enqueue_style( 'maxson-sidebars-admin' );
			$css_deps[] = 'maxson-sidebars-admin';

		} // endif


		wp_register_script( 'maxson-sidebars-admin', $this->assets_js . 'maxson-sidebars-admin.js', array( 'jquery' ), false, $this->version );

		wp_enqueue_script( 'maxson-sidebars-admin' );


		// Get sidebar data as array
		$sidebar_array = $this->get_sidebars_array( true );
		array_shift( $sidebar_array );


		$args = array( 
			// General
			'show_shortcode_link' => apply_filters( 'maxson_sidebar_shortcode', true ), 

			// Widgets.php
			'delete_text'    => __( 'Delete', 'maxson' ), 
			'shortcode_text' => __( 'Shortcode', 'maxson' ), 
			'confirm'        => sprintf( __( "You are about to permanently delete this %s?\n'Cancel' to stop, 'OK' to delete." ), $this->singular , 'maxson' ), 
			'prompt'         => sprintf( __( '%s shortcode:', 'maxson' ), $this->singular ), 

			// TinyMCE
			'title'        => sprintf( __( 'Insert %s', 'maxson' ), $this->singular ), 
			'all_sidebars' => json_encode( $sidebar_array )
		);

		wp_localize_script( 'maxson-sidebars-admin', 'maxson_sidebar_management', $args );
	}


	/**
	 * Sidebar generator message
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	public function widgets_message()
	{ 
		global $pagenow;

		if ( 'widgets.php' == $pagenow )
		{ 
			if( isset( $_GET['sidebar'] ) && $_GET['sidebar'] == 'added' )
			{ 
				$args = array( 
					'posts_per_page' => 1
				);

				$the_sidebars = $this->get_sidebars( $args );

				if( isset( $latest_sidebar[0] ) )
				{ 
					$latest_sidebar = $the_sidebars[0]->post_name;

					printf( '<div id="message" class="updated"><p>%s <a href="#%s" id="new-sidebar-add-widgets">%s</a></p></div>', __( 'Sidebar added.' ), $latest_sidebar, __( 'Add widgets.' ) );
				} // endif

			} // endif

			printf( '<div id="maxson-sidebar-deleted-message" class="updated hidden"><p>%s</p></div>', __( 'Sidebar moved to the Trash.' ) );

		} // endif
	}


	/**
	 * Form for widgets.php page
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	public function widgets_form()
	{ 
		$title       = apply_filters( 'maxson_sidebar_form_title', __( 'Sidebar Generator', 'maxson' ) );
		$placeholder = apply_filters( 'maxson_sidebar_form_placeholder', __( 'Enter sidebar name here', 'maxson' ) );
		$button      = apply_filters( 'maxson_sidebar_form_button', __( 'Create Sidebar', 'maxson' ) );
	?>
	<script type="text/html" id="maxson-add-sidebar-form">
		<form method="POST" class="maxson-sidebar-form">
			<div class="maxson-sidebar-header">
				<h3 class="maxson-sidebar-title"><?php esc_html_e( $title ); ?></h3>
			</div>
			<div class="maxson-sidebar-body">
				<input type="text" name="maxson-add-sidebar" class="maxson-input-field large-text" placeholder="<?php esc_attr_e( $placeholder ); ?>" required="required" value="">
				<button type="reset" class="maxson-clear-button"></button>
				<input type="submit" class="button button-primary button-large maxson-submit-button" value="<?php esc_attr_e( $button ); ?>">
				<?php wp_nonce_field( 'maxson-delete-sidebar-nonce', 'maxson-delete-sidebar-nonce' ); ?>
			</div>
		</form>
	</script>
	<?php }


	/**
	 * Add custom sidebars
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	public function add_sidebar()
	{ 
		if ( ! empty( $_POST['maxson-add-sidebar'] ) )
		{
			$field = sanitize_text_field( $_POST['maxson-add-sidebar'] );

			$post_title = (string) $this->clean_sidebar_name( $field );
			$post_slug  = sanitize_title( $post_title );
			$post_data  = array( 
				'post_title'  => $post_title,
				'post_status' => 'publish',
				'post_type'   => $this->post_type,
				'post_slug'   => $post_slug
			);

			if( $sidebar_id = wp_insert_post( $post_data ) )
			{ 
				set_post_format( $sidebar_id, 'standard' );

				wp_redirect( add_query_arg( array( 
					'sidebar' => 'added'
				), admin_url( 'widgets.php' ) ) );

				die();

			} // endif
		} // endif
	}


	/**
	 * Add custom sidebars via AJAX
	 * 
	 * @since 		1.3
	 * 
	 * @return 		void
	 */

	public function ajax_add_sidebar()
	{ 
		if ( ! wp_verify_nonce( $_POST['nonce'], 'maxson-add-sidebar-nonce' ) )
		{ 
			wp_send_json_error( array( 
				'message' => sprintf( '<p>%s</p>', __( 'Oops! You do not have permission to do that action.', 'maxson' ) )
			) );

		} else if ( isset( $_POST['name'] ) && ! empty( $_POST['name'] ) )
		{ 
			$field = sanitize_text_field( $_POST['name'] );

			$post_title = (string) $this->clean_sidebar_name( $field );
			$post_slug  = sanitize_title( $post_title );
			$post_data  = array( 
				'post_title'  => $post_title,
				'post_status' => 'publish',
				'post_type'   => $this->post_type,
				'post_slug'   => $post_slug
			);

			if( $sidebar_id = wp_insert_post( $post_data ) )
			{ 
				set_post_format( $sidebar_id, 'standard' );

				wp_send_json_success( array( 
					'sidebar_id'    => $sidebar_id, 
					'sidebar_slug'  => $post_slug, 
					'sidebar_title' => $post_title
				) );
			} // endif
		} // endif
	}


	/**
	 * Delete custom sidebars via AJAX
	 * 
	 * @since 		1.0
	 * 
	 * @return 		void
	 */

	public function ajax_delete_sidebar()
	{ 
		if ( ! wp_verify_nonce( $_POST['nonce'], 'maxson-delete-sidebar-nonce' ) )
		{ 
			wp_send_json_error( array( 
				'message' => sprintf( '<p>%s</p>', __( 'Oops! You do not have permission to do that action.', 'maxson' ) )
			) );

		} else if ( isset( $_POST['slug'] ) && ! empty( $_POST['slug'] ) )
		{ 
			$sidebar_slug = trim( stripslashes( $_POST['slug'] ) );

			$args = array( 
				'name'           => $sidebar_slug,
				'posts_per_page' => 1
			);

			$all_sidebars = $this->get_sidebars( $args );

			if( $all_sidebars )
			{ 
				wp_delete_post( $all_sidebars[0]->ID, true );

				wp_send_json_success( array( 
					'slug'    => $_POST['slug'],
					'message' => __( 'Sidebar deleted.', 'maxson' )
				) );

			} else
			{ 
				wp_send_json_error( array( 
					'slug'    => $_POST['slug'],
					'message' => __( 'Sidebar does not exist.', 'maxson' )
				) );

			} // endif

		} else
		{ 
			wp_send_json_error( array( 
				'message' => __( 'An error has occurred.', 'maxson' )
			) );

		} // endif

		die();
	}


	/**
	 * Display sidebar tinymce
	 * 
	 * @since 		1.2
	 * 
	 * @return 		void
	 */

	public function tinymce_sidebar()
	{ 
		if ( current_user_can( 'edit_posts' ) || current_user_can( 'edit_pages' ) )
		{ 

			if( ! apply_filters( 'maxson_sidebar_shortcode', true ) )
				return false;

			if ( ! apply_filters( 'maxson_sidebar_tinymce_dropdown', true ) )
				return false;

			if( 'true' == get_user_option( 'rich_editing' ) )
			{ 
				$row = apply_filters( 'maxson_sidebar_tinymce_dropdown_row', '2' );

				add_filter( 'mce_external_plugins', array( &$this, 'add_tinymce_script' ) );
				add_filter( "mce_buttons_{$row}", array( &$this, 'add_tinymce_listbox' ) );

			} // endif
		} // endif
	}


	/**
	 * Include TinyMCE sidebar script
	 * 
	 * @since		1.2
	 * 
	 * @return 		array
	 */

	function add_tinymce_script( $plugin_array )
	{ 
		$plugin_array['maxsonsidebar'] = esc_url( $this->assets_js . 'maxson-sidebar-tinymce.js' );

		return $plugin_array;
    }


    /**
	 * Add TinyMCE sidebar listbox
	 * 
	 * @since		1.2
	 * 
	 * @return 		array
	 */

	function add_tinymce_listbox( $buttons )
	{ 
		array_push( $buttons, 'maxsonsidebar' );

		return $buttons;
	}


	/**
	 * Display sidebar shortcode
	 * 
	 * @since 		1.1
	 * 
	 * @return 		void
	 */

	public function maxson_sidebar_shortcode( $atts )
	{ 
		$defaults = array( 
			'id' => ''
		);

		extract( shortcode_atts( $defaults, $atts ) );

		return get_maxson_sidebar( $id );
	}

} // endclass

?>