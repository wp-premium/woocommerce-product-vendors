<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Vendor Admin Class.
 *
 * General admin class to handle all things vendor side for store.
 *
 * @category Admin
 * @package  WooCommerce Product Vendors/Vendor Admin
 * @version  2.0.0
 */
class WC_Product_Vendors_Vendor_Admin {
	public $order_notes;

	/**
	 * Initialize
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function init() {
		$self = new self();

		// add a vendor switcher to the admin bar
		add_action( 'admin_bar_menu', array( $self, 'add_vendor_switcher' ) );

		// remove new from admin bar
		add_action( 'wp_before_admin_bar_render', array( $self, 'remove_new' ) );

		// remove help tab
		add_action( 'admin_bar_menu', array( $self, 'remove_help_tab' ) );

		// add class to admin pages for vendors
		add_filter( 'admin_body_class', array( $self, 'add_admin_body_class' ) );

		// enqueues scripts and styles
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_scripts_styles' ), 11 );

		// adds the screen ids to WooCommerce so WooCommerce scripts and styles will load
		add_filter( 'woocommerce_screen_ids', array( $self, 'add_screen_ids_to_wc' ) );

		// shows the dashboard sales widget if capable
		add_filter( 'woocommerce_dashboard_status_widget_sales_query', array( $self, 'render_dashboard_sales_widget' ) );

		// restrict some columns for vendors
		add_filter( 'manage_product_posts_columns', array( $self, 'restrict_product_columns' ), 11 );

		// adding attachments
		add_action( 'add_attachment', array( $self, 'process_attachment' ) );

		// editing attachments
		add_action( 'edit_attachment', array( $self, 'process_attachment' ) );

		// restrict products to only vendor's taxonomy and caps
		add_filter( 'parse_query', array( $self, 'restrict_products' ), 11 );

		// restrict attachments only to vendor
		add_filter( 'ajax_query_attachments_args', array( $self, 'restrict_attachments_ajax' ) );

		// filter product list category page
		add_filter( 'wc_product_dropdown_categories_get_terms_args', array( $self, 'filter_product_dropdown_categories' ) );

		// modify product filters
		add_filter( 'woocommerce_product_filters', array( $self, 'product_filters' ), 11 );

		// modify the product status views
		add_filter( 'views_edit-product', array( $self, 'product_status_views' ), 11 );

		// modify product months filter
		add_filter( 'months_dropdown_results', array( $self, 'product_months_filter' ), 11, 2 );

		// registers vendor menus
		add_action( 'admin_menu', array( $self, 'register_vendor_menus' ), 99 );

		// remove product meta boxes
		add_action( 'add_meta_boxes', array( $self, 'remove_product_meta_boxes' ), 99 );

		// remove product visibility option
		add_filter( 'woocommerce_product_visibility_options', array( $self, 'remove_product_visibility_option' ) );

		// remove product data tabs
		add_filter( 'woocommerce_product_data_tabs', array( $self, 'remove_product_data_tabs' ) );

		// remove product types for vendors
		add_action( 'product_type_selector', array( $self, 'remove_product_types' ) );

		// set the screen option
		add_filter( 'set-screen-option', array( $self, 'set_screen_option' ), 99, 3 );

		// restricts products for vendors
		add_filter( 'woocommerce_json_search_found_products', array( $self, 'restrict_ajax_searched_products' ) );

		// perform tasks on product save
		add_action( 'save_post', array( $self, 'save_post' ) );

		// add a commission field to the product general tab
		add_action( 'woocommerce_product_options_general_product_data', array( $self, 'add_product_commission_field_general' ) );

		// add a commission field for the product variation
		add_action( 'woocommerce_product_after_variable_attributes', array( $self, 'add_product_commission_field_variation' ), 10, 3 );
		
		// add ajax to handle vendor switching
		add_action( 'wp_ajax_wc_product_vendors_switch', array( $self, 'vendor_switch_ajax' ) );

		// vendor support form
		add_action( 'wp_ajax_wc_product_vendors_vendor_support', array( $self, 'vendor_support_ajax' ) );

		// displays count bubble on unfulfilled products
		add_filter( 'add_menu_classes', array( $self, 'unfulfilled_products_count_bubble' ) );

		// re-set the vendor cookie
		add_action( 'set_logged_in_cookie', array( $self, 'reset_vendor_cookie' ), 10, 4 );

		$self->order_notes = new WC_Product_Vendors_Vendor_Order_Notes();

    	return true;
	}

	/**
	 * Adds vendor switcher function to the admin bar
	 *
	 * @access public
	 * @since 2.0.9
	 * @version 2.0.9
	 * @param string $logged_in_cookie
	 * @param int $expire
	 * @param int $expiration
	 * @param int $user_id
	 * @return bool
	 */
	public function reset_vendor_cookie( $logged_in_cookie, $expire, $expiration, $user_id ) {
		if ( WC_Product_Vendors_Utils::is_vendor( $user_id ) ) {
			$authenticate = new WC_Product_Vendors_Authentication();
			
			$vendor = WC_Product_Vendors_Utils::get_vendor_data_from_user();

			if ( ! empty( $vendor ) ) {
				$authenticate->set_cookie( $user_id, $vendor['term_id'] );
			}
		}

		return true;
	}

	/**
	 * Adds vendor switcher function to the admin bar
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $bar
	 * @return bool
	 */
	public function add_vendor_switcher( $bar ) {
		// get all vendors this logged in user can manage
		$vendors = WC_Product_Vendors_Utils::get_all_vendor_data();

		// if user can only manage one vendor then don't add switcher
		if ( count( $vendors ) <= 1 ) {
			return;
		}

		$current_vendor_id = WC_Product_Vendors_Utils::get_logged_in_vendor();
		$current_active = '';

		if ( ! empty( $vendors ) ) {
			// loop through each vendor and build admin bar menu
			foreach( $vendors as $vendor_id => $vendor_data ) {
				$active = $vendor_id === absint( $current_vendor_id ) ? ' ( ' . __( 'Current', 'woocommerce-product-vendors' ) . ' )' : '';

				if ( ! empty( $active ) ) {
					$current_active = $vendor_data['name'];
				}

				$args = array(
					'id'     => 'wcpv_vendor_' . $vendor_id,
					'title'  => esc_attr( $vendor_data['name'] . $active ),
					'parent' => 'wcpv_vendor_switcher',
					'href'   => '#',
					'meta'   => array(
						'class' => 'wcpv-vendor-switch',
						'html'  => '<input type="hidden" class="wcpv-vendor" value="' . esc_attr( $vendor_id ) . '" />' . wp_nonce_field( 'wcpv_switch_vendor', 'wcpv_vendor_switch_nonce', true, false ),
					),
				);

				// add the menu
				$bar->add_node( $args );
			}

			// add items to the toolbar
			$args = array(
				'id'    => 'wcpv_vendor_switcher',
				'title' => '<span class="wcpv-admin-bar-icon"></span>' . sprintf( esc_html__( 'Vendor Switcher (%s)', 'woocommerce-product-vendors' ), esc_html( $current_active ) ),
			);

			$bar->add_menu( $args );
		}

		return true;
	}

	/**
	 * Remove new function from the admin bar
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function remove_new() {
		global $wp_admin_bar;

		$wp_admin_bar->remove_menu( 'new-content' );

		return true;
	}

	/**
	 * Remove the help tab from admin toolbar
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function remove_help_tab() {
		get_current_screen()->remove_help_tabs();

		return true;
	}

	/**
	 * Handles the switching of vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function vendor_switch_ajax() {
		if ( ! wp_verify_nonce( $_POST['switch_vendor_nonce'], 'wcpv_switch_vendor' ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( empty( $_POST['vendor'] ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		$vendor = sanitize_text_field( $_POST['vendor'] );

		// if current vendor matches clicked vendor do nothing
		if ( $vendor === WC_Product_Vendors_Utils::get_logged_in_vendor() ) {
			echo 'done';
			exit;
		}

		include_once( WC_PRODUCT_VENDORS_PATH . '/includes/class-wc-product-vendors-authentication.php' );

		$authenticate = new WC_Product_Vendors_Authentication();

		$user = wp_get_current_user();

		WC_Product_Vendors_Utils::clear_reports_transients();
		$authenticate->expire_cookie();
		$authenticate->set_cookie( $user->ID, $vendor );

		echo 'switched';
		exit;
	}

	/**
	 * Handles the vendor support form submission
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function vendor_support_ajax() {
		global $errors;

		if ( ! is_array( $_POST['form_items'] ) ) {
			parse_str( $_POST['form_items'], $form_items );
		} else {
			$form_items = $_POST['form_items'];
		}

		$form_items = array_map( 'sanitize_text_field', $form_items );

		if ( ! isset( $form_items ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( ! wp_verify_nonce( $_POST['ajaxVendorSupportNonce'], '_wc_product_vendors_vendor_support_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		// handle form submission/validation
		if ( ! empty( $form_items ) ) {
			$errors = array();

			if ( empty( $form_items['vendor_question'] ) ) {
				$errors[] = __( 'Please provide a question.', 'woocommerce-product-vendors' );
			}

			if ( ! WC_Product_Vendors_Utils::auth_vendor_user() ) {
				$errors[] = __( 'You must be an authorize vendor user to submit a support question.', 'woocommerce-product-vendors' );
			}

			do_action( 'wcpv_vendor_support_form_validation', $errors );

			// no errors, lets process the form
			if ( empty( $errors ) ) {
				$this->vendor_support_form_process( $form_items );
				
			} else {
				wp_send_json( array( 'errors' => $errors ) );
			}
		}
	}

	/**
	 * Process the vendor support form
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $form_items
	 * @return bool
	 */
	public function vendor_support_form_process( $form_items ) {
		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( WC_Product_Vendors_Utils::get_logged_in_vendor() );

		$current_user   = wp_get_current_user();
		$user_firstname = get_user_meta( $current_user->ID, 'user_firstname', true );
		$user_lastname  = get_user_meta( $current_user->ID, 'user_lastname', true );

		$message = sprintf( __( 'Vendor: %s', 'woocommerce-product-vendors' ), esc_html( $vendor_data['name'] ) ) . PHP_EOL;

		$message .= sprintf( __( 'First Name: %s', 'woocommerce-product-vendors' ), esc_html( $user_firstname ) ) . PHP_EOL;

		$message .= sprintf( __( 'Last Name: %s', 'woocommerce-product-vendors' ), esc_html( $user_lastname ) ) . PHP_EOL;

		$message .= sprintf( __( 'Email: %s', 'woocommerce-product-vendors' ), esc_html( $current_user->user_email ) ) . PHP_EOL . PHP_EOL;

		$message .= __( 'Question:', 'woocommerce-product-vendors' ) . PHP_EOL;

		$message .= $form_items['vendor_question'];

		$subject = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) . ' ' . __( 'Vendor Support Question', 'woocommerce-product-vendors' );

		if ( wp_mail( get_bloginfo( 'admin_email' ), $subject, $message ) ) {
			echo 'success';
		} else {
			echo 'errors';
		}

		exit;
	}

	/**
	 * Adds a class to page body for easier targeting of styles
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $classes
	 * @return bool
	 */
	public function add_admin_body_class( $classes ) {
		$classes .= ' vendor';

		return $classes;
	}

	/**
	 * Remove meta boxes from products for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function remove_product_meta_boxes() {
		// remove product vendors taxonomy meta box
		remove_meta_box( 'wcpv_product_vendorsdiv', 'product', 'side' );

		// remove comments meta box from products
		remove_meta_box( 'commentsdiv', 'product', 'normal' );

		// remove custom meta field box
		remove_meta_box( 'postcustom', 'product', 'normal' );

		return true;
	}

	/**
	 * Remove product types from vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $types
	 * @return bool
	 */
	public function remove_product_types( $types ) {
		unset( $types['grouped'], $types['external'] );

		return $types;
	}

	/**
	 * Remove product visibility options
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $options
	 * @return array $options
	 */
	public function remove_product_visibility_option( $options ) {
		return array();
	}

	/**
	 * Remove product data tabs
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $tabs
	 * @return array $tabs
	 */
	public function remove_product_data_tabs( $tabs ) {
		unset( $tabs['advanced'] );

		return $tabs;
	}

	/**
	 * Adds a message for pending vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_pending_vendor_message() {
		_e( 'Thanks for registering to become a vendor.  Your application is being reviewed at this time.', 'woocommerce-product-vendors' );

		return true;
	}

	/**
	 * Remove screen help tabs
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function remove_help_tabs( $old_help, $screen_id, $screen ) {
		$screen->remove_help_tabs();

		return;
	}

	/**
	 * Gets the screen ids that needs styles or scripts
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function get_screen_ids() {
		return apply_filters( 'wcpv_vendor_admin_screen_ids', array(
			'toplevel_page_wcpv-vendor-settings',
			'toplevel_page_wcpv-vendor-orders',
			'product',
			'profile',
		) );
	}

	/**
	 * Enqueue scripts and styles
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function enqueue_scripts_styles() {
		$current_screen = get_current_screen();

		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_style( 'wcpv-admin-styles', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/css/wcpv-admin-styles.css' );

		wp_register_script( 'wcpv-vendor-admin-scripts', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/js/wcpv-vendor-admin-scripts' . $suffix . '.js', array( 'jquery' ), WC_PRODUCT_VENDORS_VERSION, true );

		if ( 'toplevel_page_wcpv-vendor-reports' === $current_screen->id ) {
			$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

			wp_enqueue_script( 'jquery-ui-datepicker' );
		
			wp_enqueue_style( 'jquery-ui-style', '//code.jquery.com/ui/' . $jquery_version . '/themes/smoothness/jquery-ui.css', array(), $jquery_version );

			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_style( 'woocommerce_admin_print_reports_styles' );

			wp_enqueue_script( 'woocommerce_admin' );
			
			wp_enqueue_script( 'wc-reports', WC()->plugin_url() . '/assets/js/admin/reports' . $suffix . '.js', array( 'jquery', 'jquery-ui-datepicker' ), WC_VERSION );

			wp_enqueue_script( 'flot' );
			wp_enqueue_script( 'flot-resize' );
			wp_enqueue_script( 'flot-time' );
			wp_enqueue_script( 'flot-pie' );
			wp_enqueue_script( 'flot-stack' );
		}

		wp_enqueue_style( 'wcpv-admin-styles' );

		wp_enqueue_script( 'wcpv-vendor-admin-scripts' );

		wp_localize_script( 'wcpv-vendor-admin-scripts', 'wcpv_vendor_admin_local', array(
			'ajaxurl'                  => admin_url( 'admin-ajax.php' ),
			'isPendingVendor'          => current_user_can( 'wc_product_vendors_pending_vendor' ) ? true : false,
			'pending_vendor_message'   => __( 'Thanks for registering to become a vendor.  Your application is being reviewed at this time.', 'woocommerce-product-vendors' ),
			'modalLogoTitle'           => __( 'Add Logo', 'woocommerce-product-vendors' ),
			'buttonLogoText'           => __( 'Add Logo', 'woocommerce-product-vendors' ),
			'currentScreen'            => $current_screen->id,
			'ajaxVendorSupportNonce'   => wp_create_nonce( '_wc_product_vendors_vendor_support_nonce' ),
			'ajaxAddOrderNoteNonce'    => wp_create_nonce( '_wc_product_vendors_vendor_add_order_note_nonce' ),
			'vendorSupportSuccess'     => __( 'Your question has been submitted.  You will be contacted shortly.', 'woocommerce-product-vendors' ),
		) );
		
		// vendor settings page
		if ( 'toplevel_page_wcpv-vendor-settings' === $current_screen->id ) {
			wp_enqueue_script( 'wc-users', WC()->plugin_url() . '/assets/js/admin/users' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select' ), WC_VERSION, true );

			wp_localize_script(
				'wc-users',
				'wc_users_params',
				array(
					'countries'              => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
					'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'woocommerce-product-vendors' ),
				)
			);
		}

		// vendor support page
		if ( 'toplevel_page_wcpv-vendor-support' === $current_screen->id ) {
			wp_enqueue_script( 'jquery-blockui' );
		}
		
		// vendor order detail page
		if ( 'admin_page_wcpv-vendor-order' === $current_screen->id ) {
			wp_enqueue_style( 'woocommerce_admin_styles' );
			wp_enqueue_script( 'jquery-blockui' );
			wp_enqueue_script( 'woocommerce_admin' );
		}

		return true;
	}

	/**
	 * Adds our screen ids to WC so scripts can load
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $screen_ids
	 */
	public function add_screen_ids_to_wc( $screen_ids ) {
		$screen_ids[] = 'toplevel_page_wcpv-vendor-settings';

		return $screen_ids;
	}

	/**
	 * Conditionally show sales widget in dashboard for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return object $query
	 */
	public function render_dashboard_sales_widget( $query ) {
		if ( current_user_can( 'view_vendor_sales_widget' ) && WC_Product_Vendors_Utils::auth_vendor_user() ) {

			return $query;
		}

		return $query;
	}

	/**
	 * Filters the product category dropdown
	 *
	 * @access public
	 * @since 2.0.9
	 * @version 2.0.9
	 * @return array $columns modified columns
	 */
	public function filter_product_dropdown_categories( $args ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			// remove the post count per category
			unset( $args['show_count'] );
		}

		return $args;
	}

	/**
	 * Restricts some of the product columns from vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $columns modified columns
	 */
	public function restrict_product_columns( $columns ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			unset( $columns['taxonomy-wcpv_product_vendors'] );
			unset( $columns['featured'] );
		}

		return $columns;
	}

	/**
	 * Restrict products only the vendor has managed access to
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $query original query object
	 * @return bool
	 */
	public function restrict_products( $query ) {
		global $typenow, $current_screen;

		if ( 'product' === $typenow && WC_Product_Vendors_Utils::auth_vendor_user() ) {
			if ( 'edit-product' === $current_screen->id ) {

				$query->query_vars['tax_query'][] = array(
					'taxonomy' => WC_PRODUCT_VENDORS_TAXONOMY,
					'field'    => 'id',
					'terms'    => array( WC_Product_Vendors_Utils::get_logged_in_vendor() ),
				);
			}

			if ( 'product' === $current_screen->id && 'add' !== $current_screen->action && 'edit' !== $current_screen->action ) {
				// prevent vendor from trying to edit posts/products without permission
				if ( ! WC_Product_Vendors_Utils::can_user_manage_product() ) {

					wp_die( __( 'You are not allowed to edit this item.', 'woocommerce-product-vendors' ) );
				}
			}
		}

		return $query;
	}

	/**
	 * Perform tasks on save post
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function save_post( $post_id ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			// don't continue if it is bulk/quick edit
			if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) || ! empty( $_REQUEST['woocommerce_bulk_edit'] ) ) {
				return;
			}

			// check post type to be product
			if ( 'product' === get_post_type( $post_id ) ) {

				// automatically set the vendor term for this product
				wp_set_object_terms( $post_id, WC_Product_Vendors_Utils::get_logged_in_vendor(), WC_PRODUCT_VENDORS_TAXONOMY );

				// set visibility to catalog/search
				update_post_meta( $post_id, '_visibility', 'visible' );
			}
		}	

		return true;
	}

	/**
	 * Add meta when adding attachments
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function process_attachment( $post_id ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			update_post_meta( $post_id, '_wcpv_vendor', WC_Product_Vendors_Utils::get_logged_in_vendor() );
		}

		return true;
	}

	/**
	 * Restrict attachments only the vendor has managed access to
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $query original query object
	 * @return bool
	 */
	public function restrict_attachments_ajax( $query ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			$query['meta_key'] = '_wcpv_vendor';
			$query['meta_value'] = WC_Product_Vendors_Utils::get_logged_in_vendor();
		}

		return $query;
	}

	/**
	 * Restrict ajax searched products only the vendor has managed access to
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $found_products
	 * @return bool
	 */
	public function restrict_ajax_searched_products( $found_products ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			$vendor_product_ids = WC_Product_Vendors_Utils::get_vendor_product_ids();

			if ( ! empty( $vendor_product_ids ) ) {
				$vendor_product_ids = array_combine( $vendor_product_ids, $vendor_product_ids );

				$found_products = array_intersect_key( $found_products, $vendor_product_ids );
			}
		}

		return $found_products;
	}

	/**
	 * Modified product filters to fit for vendor
	 * This is a copy of the original WC product_filters() method to manipulate
	 * the count of each product types
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return mix $output
	 */
	public function product_filters( $output ) {
		global $wp_query;

		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			// Type filtering
			$terms   = get_terms( 'product_type' );
			$output  = '<select name="product_type" id="dropdown_product_type">';
			$output .= '<option value="">' . esc_html__( 'Show all product types', 'woocommerce-product-vendors' ) . '</option>';

			foreach ( $terms as $term ) {
				// remove grouped product
				if ( 'grouped' === $term->name ) {
					continue;
				}

				$output .= '<option value="' . sanitize_title( $term->name ) . '" ';

				if ( isset( $wp_query->query['product_type'] ) ) {
					$output .= selected( $term->slug, $wp_query->query['product_type'], false );
				}

				$output .= '>';

				switch ( $term->name ) {
					case 'grouped' :
						$output .= __( 'Grouped product', 'woocommerce-product-vendors' );
						break;
					case 'external' :
						$output .= __( 'External/Affiliate product', 'woocommerce-product-vendors' );
						break;
					case 'variable' :
						$output .= __( 'Variable product', 'woocommerce-product-vendors' );
						break;
					case 'simple' :
						$output .= __( 'Simple product', 'woocommerce-product-vendors' );
						break;
					default :
						// Assuming that we have other types in future
						$output .= ucfirst( $term->name );
						break;
				}

				$output .= "</option>";

				if ( 'simple' === $term->name ) {

					$output .= '<option value="downloadable" ';

					if ( isset( $wp_query->query['product_type'] ) ) {
						$output .= selected( 'downloadable', $wp_query->query['product_type'], false );
					}

					$output .= '> &rarr; ' . esc_html__( 'Downloadable', 'woocommerce-product-vendors' ) . '</option>';

					$output .= '<option value="virtual" ';

					if ( isset( $wp_query->query['product_type'] ) ) {
						$output .= selected( 'virtual', $wp_query->query['product_type'], false );
					}

					$output .= '> &rarr;  ' . esc_html__( 'Virtual', 'woocommerce-product-vendors' ) . '</option>';
				}
			}

			$output .= '</select>';
		}
		
		return $output;
	}

	/**
	 * Removes the count from product statuses
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return mix $views
	 */
	public function product_status_views( $views ) {
		global $typenow;

		if ( WC_Product_Vendors_Utils::auth_vendor_user() && 'product' === $typenow ) {
			$new_views = array();

			// remove the count from each status
			foreach( $views as $k => $v ) {
				$new_views[$k] = preg_replace( '/\(\d+\)/', '', $v );
			}

			$views = $new_views;

			// remove trash status
			unset( $views['trash'] );
		}

		return $views;
	}

	/**
	 * Modify the product months filter to only show the months where the
	 * product belongs to the current vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $month
	 * @param sting $post_type
	 * @return array $months
	 */
	public function product_months_filter( $months, $post_type ) {
		global $wpdb;

		if ( WC_Product_Vendors_Utils::auth_vendor_user() && 'product' === $post_type ) {
			$product_ids = implode( ',', WC_Product_Vendors_Utils::get_vendor_product_ids() );

			$months = $wpdb->get_results( $wpdb->prepare( "
				SELECT DISTINCT YEAR( post_date ) AS year, MONTH( post_date ) AS month
				FROM $wpdb->posts
				WHERE post_type = %s
				AND ID IN (%s)
				ORDER BY post_date DESC
			", $post_type, $product_ids ) );			
		}

		return $months;
	}

	/**
	 * Adds vendor menus
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_vendor_menus() {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {

			$hook = add_menu_page( __( 'Orders', 'woocommerce-product-vendors' ), __( 'Orders', 'woocommerce-product-vendors' ), 'manage_product', 'wcpv-vendor-orders', array( $this, 'render_orders_page' ), 'dashicons-store', 6.77 );

			add_action( "load-$hook", array( $this, 'add_orders_screen_options' ) );

			add_submenu_page( NULL, __( 'Order', 'woocommerce-product-vendors' ), NULL, 'manage_product', 'wcpv-vendor-order', array( $this, 'render_order_page' ) );

			if ( WC_Product_Vendors_Utils::is_admin_vendor() ) {
				add_menu_page( __( 'Reports', 'woocommerce-product-vendors' ), __( 'Reports', 'woocommerce-product-vendors' ), 'manage_product', 'wcpv-vendor-reports', array( $this, 'render_reports_page' ), 'dashicons-chart-bar', 7.77 );

				add_menu_page( __( 'Store Settings', 'woocommerce-product-vendors' ), __( 'Store Settings', 'woocommerce-product-vendors' ), 'manage_product', 'wcpv-vendor-settings', array( $this, 'render_settings_page' ), 'dashicons-admin-settings', 60.77 );

				add_menu_page( __( 'Support', 'woocommerce-product-vendors' ), __( 'Support', 'woocommerce-product-vendors' ), 'manage_product', 'wcpv-vendor-support', array( $this, 'render_support_page' ), 'dashicons-info', 61.77 );
			}

		}

		// remove menu pages if logged in user without vendor
		if ( WC_Product_Vendors_Utils::is_admin_vendor() || WC_Product_Vendors_Utils::is_manager_vendor() ) {
			remove_menu_page( 'edit.php' );
			remove_menu_page( 'tools.php' );
			remove_menu_page( 'edit-comments.php' );

			if ( ! WC_Product_Vendors_Utils::auth_vendor_user() ) {
				remove_menu_page( 'wc-reports' );
				remove_submenu_page( 'woocommerce', 'wc-reports' );
				remove_menu_page( 'upload.php' );
				remove_menu_page( 'index.php' );
				remove_menu_page( 'edit.php?post_type=product' );
			}
		}

		return true;
	}

	/**
	 * Adds screen options for this page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_orders_screen_options() {
		$option = 'per_page';

		$args = array(
			'label'   => __( 'Orders', 'woocommerce-product-vendors' ),
			'default' => apply_filters( 'wcpv_orders_list_default_item_per_page', 20 ),
			'option'  => 'orders_per_page',
		);

		add_screen_option( $option, $args );

		return true;
	}

	/**
	 * Sets screen options for this page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return mixed
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'orders_per_page' === $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Renders the vendor orders page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_orders_page() {
		$orders_list = new WC_Product_Vendors_Vendor_Orders_List();

		$orders_list->prepare_items();

		include_once( 'views/html-vendor-orders-page.php' );

		return true;
	}

	/**
	 * Renders the vendor order page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_order_page() {
		global $post;

		$order_id = absint( $_GET['id'] );

		$post = get_post( $order_id );

		$theorder = wc_get_order( $order_id );

		$order = $theorder;

		if ( WC()->payment_gateways() ) {
			$payment_gateways = WC()->payment_gateways->payment_gateways();
		} else {
			$payment_gateways = array();
		}

		$payment_method = ! empty( $order->payment_method ) ? $order->payment_method : '';

		$order_list = new WC_Product_Vendors_Vendor_Order_Detail_List();

		$order_list->prepare_items();

		include_once( 'views/html-vendor-order-page.php' );
	}

	/**
	 * Renders the vendor sales report page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_reports_page() {
		WC_Product_Vendors_Vendor_Reports::output();

		return true;
	}

	/**
	 * Add a commission field to the product general tab
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_product_commission_field_general() {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			global $post;
			
			$vendor_id        = WC_Product_Vendors_Utils::get_vendor_id_from_product( $post->ID );
			$vendor_data      = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );
			$commission_data  = WC_Product_Vendors_Utils::get_product_commission( $post->ID, $vendor_data );

			$commission_placeholder = $commission_data['commission'];

			if ( 'percentage' === $commission_data['type'] && ! empty( $commission_placeholder ) ) {
				$commission_placeholder = $commission_placeholder . '%';
			}

			echo '<div class="options_group show_if_simple show_if_variable show_if_booking">';

			woocommerce_wp_text_input( array( 'id' => '_wcpv_product_commission', 'label' => __( 'Commission', 'woocommerce-product-vendors' ), 'custom_attributes' => array( 'disabled' => 'disabled' ), 'placeholder' => $commission_placeholder ) );

			echo '</div>';
		}

		return true;
	}

	/**
	 * Add a commission field to the product variation
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $loop
	 * @return bool
	 */
	public function add_product_commission_field_variation( $loop, $variation_data, $variation ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			$commission = get_post_meta( $variation->ID, '_wcpv_product_commission', true );

			global $post;
			
			$vendor_id        = WC_Product_Vendors_Utils::get_vendor_id_from_product( $post->ID );
			$vendor_data      = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );
			$commission_data  = WC_Product_Vendors_Utils::get_product_commission( $post->ID, $vendor_data );

			$commission_placeholder = $commission_data['commission'];

			if ( 'percentage' === $commission_data['type'] && ! empty( $commission_placeholder ) ) {
				$commission_placeholder = $commission_placeholder . '%';
			}

			echo '<div class="options_group show_if_variable show_if_booking">';
			?>
			<p class="wcpv-commission form-row form-row-first">
				<label><?php esc_html_e( 'Commission', 'woocommerce-product-vendors' ); ?>:</label>

				<input type="text" name="" value="<?php echo esc_attr( $commission ); ?>" disabled="disabled" placeholder="<?php echo esc_attr( $commission_placeholder ); ?>" />
			</p>
			<?php
			echo '</div>';
		}

		return true;
	}

	/**
	 * Renders the vendor settings page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_settings_page() {
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'jquery-tiptip' );

		$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

		// handle form submission
		if ( ! empty( $_POST['wcpv_save_vendor_settings_nonce'] ) && ! empty( $_POST['vendor_data'] ) ) {
			// continue only if nonce passes
			if ( wp_verify_nonce( $_POST['wcpv_save_vendor_settings_nonce'], 'wcpv_save_vendor_settings' ) ) {

				$posted_vendor_data = $_POST['vendor_data'];

				// sanitize
				$posted_vendor_data = array_map( 'sanitize_text_field', $posted_vendor_data );
				$posted_vendor_data = array_map( 'stripslashes', $posted_vendor_data );

				// sanitize html editor content
				$posted_vendor_data['profile'] = ! empty( $_POST['vendor_data']['profile'] ) ? wp_kses_post( stripslashes( $_POST['vendor_data']['profile'] ) ) : '';

				// merge the changes with existing settings
				$posted_vendor_data = array_merge( $vendor_data, $posted_vendor_data );

				if ( update_term_meta( WC_Product_Vendors_Utils::get_logged_in_vendor(), 'vendor_data', $posted_vendor_data ) ) {

					// grab the newly saved settings
					$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();
				}
			}	
		}

		// logo image
		$logo             = ! empty( $vendor_data['logo'] ) ? $vendor_data['logo'] : '';
		
		$hide_remove_image_link = '';
		
		$logo_image_url = wp_get_attachment_image_src( $logo, 'full' );
		
		if ( empty( $logo_image_url ) ) {
			$hide_remove_image_link = 'display:none;';
		}
		
		$profile           = ! empty( $vendor_data['profile'] ) ? $vendor_data['profile'] : '';
		$email             = ! empty( $vendor_data['email'] ) ? $vendor_data['email'] : '';
		$paypal            = ! empty( $vendor_data['paypal'] ) ? $vendor_data['paypal'] : '';
		$vendor_commission = ! empty( $vendor_data['commission'] ) ? $vendor_data['commission'] : get_option( 'wcpv_vendor_settings_default_commission', '0' );

		include_once( 'views/html-vendor-store-settings-page.php' );

		return true;
	}

	/**
	 * Renders the vendor support page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_support_page() {
		ob_start();

		$this->include_vendor_support_form();

		$form = ob_get_clean();

		echo $form;

		return true;
	}

	/**
	 * Includes the vendor support form
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function include_vendor_support_form() {
		// check if template has been overriden
		if ( file_exists( get_stylesheet_directory() . '/woocommerce-product-vendors/vendor-support-form.php' ) ) {
			
			include( get_stylesheet_directory() . '/woocommerce-product-vendors/vendor-support-form.php' );

		} else  {
			include( plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'templates/vendor-support-form.php' );
		}

		return true;
	}

	/**
	 * Gets all products that are unfulfilled for the current vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return int $count
	 */
	public function unfulfilled_products_count() {
		global $wpdb;

		$sql = "SELECT COUNT( commission.id ) FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";

		$sql .= " INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS item_meta";

		$sql .= " ON commission.order_item_id = item_meta.order_item_id";

		$sql .= " WHERE 1=1";

		$sql .= " AND item_meta.meta_key = '_fulfillment_status'";

		$sql .= " AND item_meta.meta_value = 'unfulfilled'";

		$sql .= " AND commission.vendor_id = '%d'";

		if ( false === ( $count = get_transient( 'wcpv_unfulfilled_products_' . WC_Product_Vendors_Utils::get_logged_in_vendor() ) ) ) {
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			
			$count = $wpdb->get_var( $wpdb->prepare( $sql, WC_Product_Vendors_Utils::get_logged_in_vendor() ) );

			set_transient( 'wcpv_unfulfilled_products_' . WC_Product_Vendors_Utils::get_logged_in_vendor(), $count, DAY_IN_SECONDS );
		}

		return $count;
	}

	/**
	 * Shows the unfulfilled products count on orders menu item
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $menu
	 * @return array $menu modified menu
	 */
	public function unfulfilled_products_count_bubble( $menu ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {
			$count = $this->unfulfilled_products_count();

			foreach( $menu as $menu_key => $menu_data ) {
				if ( 'wcpv-vendor-orders' === $menu_data[2] ) {
					$menu[$menu_key][0] .= ' <span class="update-plugins count-' . esc_attr( absint( $count ) ) . '" title="' . esc_attr__( 'Products awaiting fulfillment', 'woocommerce-product-vendors' ) . '"><span class="plugin-count">' . number_format_i18n( $count ) . '</span></span>';
				}
			}
		}

		return $menu;
	}

	/**
	 * Clears all report transients
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function clear_reports_transients() {
		WC_Product_Vendors_Utils::clear_reports_transients();

		return true;
	}
}

WC_Product_Vendors_Vendor_Admin::init();
