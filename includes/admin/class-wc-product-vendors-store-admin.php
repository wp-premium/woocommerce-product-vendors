<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Store Admin Class.
 *
 * General admin class to handle all things admin side for store.
 *
 * @category Admin
 * @package  WooCommerce Product Vendors/Admin
 * @version  2.0.0
 */
class WC_Product_Vendors_Store_Admin {
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

		// enqueues scripts and styles
		add_action( 'admin_enqueue_scripts', array( $self, 'enqueue_scripts_styles' ) );

		// displays count bubble on pending items such as products or vendors
		add_filter( 'add_menu_classes', array( $self, 'pending_items_count' ) );

		// adds the screen ids to WooCommerce so WooCommerce scripts and styles will load
		add_filter( 'woocommerce_screen_ids', array( $self, 'add_screen_ids_to_wc' ) );

		// add fields to taxonomy edit page
		add_action( WC_PRODUCT_VENDORS_TAXONOMY . '_edit_form_fields' , array( $self , 'edit_vendor_fields' ) );

		// add fields to taxonomy on create page
		add_action( WC_PRODUCT_VENDORS_TAXONOMY . '_add_form_fields', array( $self, 'add_vendor_fields' ) );

		// save custom fields from taxonomy
		add_action( 'edited_' . WC_PRODUCT_VENDORS_TAXONOMY, array( $self , 'save_vendor_fields' ) );
		
		// save custom fields from taxonomy
		add_action( 'created_' . WC_PRODUCT_VENDORS_TAXONOMY, array( $self , 'save_vendor_fields' ) );

		// modify taxonomy columns
		add_filter( 'manage_edit-' . WC_PRODUCT_VENDORS_TAXONOMY . '_columns', array( $self, 'modify_vendor_columns' ) );

		// modify taxonomy columns 
		add_filter( 'manage_' . WC_PRODUCT_VENDORS_TAXONOMY . '_custom_column', array( $self, 'render_vendor_columns' ), 10, 3 );

		// add a new column to users
		add_filter( 'manage_users_columns', array( $self, 'add_custom_user_column' ) );

		// modify user columns
		add_action( 'manage_users_custom_column', array( $self, 'add_user_column_data' ), 10, 3 );
		
		// add vendor section to user profile
		add_action( 'edit_user_profile', array( $self, 'add_product_vendor_user_profile_section' ) );
		add_action( 'show_user_profile', array( $self, 'add_product_vendor_user_profile_section' ) );
		
		// save user profile
		add_action( 'edit_user_profile_update', array( $self, 'save_product_vendor_user_profile_section' ) );

		// add commission top level menu item
		add_action( 'admin_menu', array( $self, 'register_commissions_menu_item' ) );

		// set the screen option
		add_filter( 'set-screen-option', array( $self, 'set_screen_option' ), 99, 3 );
    	
    	// adds fields to attachments
    	add_filter( 'attachment_fields_to_edit', array( $self, 'add_attachments_field' ), 10, 2 );

    	// save fields to attachments
    	add_filter( 'attachment_fields_to_save', array( $self, 'save_attachments_field' ), 10, 2 );

    	// add vendor settings section to products tab
		add_filter( 'woocommerce_get_sections_products', array( $self, 'add_vendor_settings_section' ) );
		
		// get vendor settings
		add_filter( 'woocommerce_get_settings_products', array( $self, 'add_vendor_settings' ), 10, 2 );

		// save vendor settings
		add_filter( 'woocommerce_admin_settings_sanitize_option', array( $self, 'save_vendor_settings' ), 10, 3 );

		// add sold by vendor on order item
		add_action( 'woocommerce_after_order_itemmeta', array( $self, 'add_sold_by_order_item_detail' ), 10, 3 );

		// add a commission field to the product general tab
		add_action( 'woocommerce_product_options_general_product_data', array( $self, 'add_product_commission_field_general' ) );

		// save commission field for the product general tab
		add_action( 'woocommerce_process_product_meta_simple', array( $self, 'save_product_commission_field_general' ) );
		add_action( 'woocommerce_process_product_meta_booking', array( $self, 'save_product_commission_field_general' ) );

		// add a commission field for the product variation
		add_action( 'woocommerce_product_after_variable_attributes', array( $self, 'add_product_commission_field_variation' ), 10, 3 );

		// save commission field for product variation
		add_action( 'woocommerce_process_product_meta_variable', array( $self, 'save_product_commission_field_variable' ) );
		add_action( 'woocommerce_save_product_variation', array( $self, 'save_product_commission_field_variation' ), 10, 2 );

		// add variation commission bulk edit
		add_action( 'woocommerce_variable_product_bulk_edit_actions', array( $self, 'add_variation_vendor_bulk_edit' ) );

		// add a pass shipping/tax field to the product general tab
		add_action( 'woocommerce_product_options_general_product_data', array( $self, 'add_product_pass_shipping_tax_field_general' ) );

		// save pass shipping/tax field for the product general tab
		add_action( 'woocommerce_process_product_meta_simple', array( $self, 'save_product_pass_shipping_tax_field_general' ) );
		add_action( 'woocommerce_process_product_meta_booking', array( $self, 'save_product_pass_shipping_tax_field_general' ) );

		// save pass shipping/tax field for variable product general tab
		add_action( 'woocommerce_process_product_meta_variable', array( $self, 'save_product_pass_shipping_tax_field_general' ) );

		// add pass shipping/tax to product bulk edit menu
		add_action( 'woocommerce_product_bulk_edit_end', array( $self, 'add_product_bulk_edit_pass_shipping_tax' ) );

		// save pass shipping/tax to product bulk edit
		add_action( 'woocommerce_product_bulk_edit_save', array( $self, 'save_product_bulk_edit_pass_shipping_tax' ) );

		// clear reports transients
		add_action( 'woocommerce_new_order', array( $self, 'clear_reports_transients' ) );
		add_action( 'save_post', array( $self, 'clear_reports_transients' ) );
		add_action( 'delete_post', array( $self, 'clear_reports_transients' ) );
		add_action( 'woocommerce_order_status_changed', array( $self, 'clear_reports_transients' ) );
		add_action( 'wcpv_commissions_status_changed', array( $self, 'clear_reports_transients' ) );

		// reports ajax search for vendors
		add_action( 'wp_ajax_wcpv_vendor_search_ajax', array( $self, 'vendor_search_ajax' ) );

		// exports commissions for the current view
		add_action( 'wp_ajax_wcpv_export_commissions_ajax', array( $self, 'export_commissions_ajax' ) );

		// exports unpaid commissions
		add_action( 'wp_ajax_wcpv_export_unpaid_commissions_ajax', array( $self, 'export_unpaid_commissions_ajax' ) );

		// process when vendor role is updated from pending to admin or manager
		add_action( 'set_user_role', array( $self, 'role_update' ), 10, 3 );
		
		// add clear transients button in WC system tools
		add_filter( 'woocommerce_debug_tools', array( $self, 'add_debug_tool' ) );
		
		// Filter order item meta label
		add_filter( 'woocommerce_attribute_label', array( $self, 'filter_order_attribute_label' ), 10, 2 );

		// add quick edit items and process save
		add_action( 'quick_edit_custom_box', array( $self, 'quick_edit' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $self, 'quick_edit' ), 10, 2 );
		add_action( 'save_post', array( $self, 'bulk_and_quick_edit_save_post' ), 10, 2 );

		// saves the vendor to the product
		add_action( 'save_post', array( $self, 'save_product_vendor' ) );

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
		$screen_ids[] = 'edit-wcpv_product_vendors';

		return $screen_ids;
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
		return apply_filters( 'wcpv_store_admin_screen_ids', array(
			'edit-wcpv_product_vendors',
			'toplevel_page_wcpv-commissions',
			'product',
			'woocommerce_page_wc-reports',
			'woocommerce_page_wc-settings',
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

		wp_register_script( 'wcpv-admin-scripts', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/js/wcpv-admin-scripts' . $suffix . '.js', array( 'jquery' ), WC_PRODUCT_VENDORS_VERSION, true );

		wp_register_style( 'wcpv-admin-styles', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/css/wcpv-admin-styles.css' );

		$localized_vars = array(
			'isPendingVendor'           => current_user_can( 'wc_product_vendors_pending_vendor' ) ? true : false,
			'pending_vendor_message'    => __( 'Thanks for registering to become a vendor.  Your application is being reviewed at this time.', 'woocommerce-product-vendors' ),
			'modalLogoTitle'            => __( 'Add Logo', 'woocommerce-product-vendors' ),
			'buttonLogoText'            => __( 'Add Logo', 'woocommerce-product-vendors' ),
			'ajaxurl'                   => admin_url( 'admin-ajax.php' ),
			'vendor_search_nonce'       => wp_create_nonce( '_wcpv_vendor_search_nonce' ),
			'i18n_matches_1'            => _x( 'One result is available, press enter to select it.', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_matches_n'            => _x( '%qty% results are available, use up and down arrow keys to navigate.', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_no_matches'           => _x( 'No matches found', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_ajax_error'           => _x( 'Loading failed', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_input_too_short_1'    => _x( 'Please enter 1 or more characters', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_input_too_short_n'    => _x( 'Please enter %qty% or more characters', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_input_too_long_1'     => _x( 'Please delete 1 character', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_input_too_long_n'     => _x( 'Please delete %qty% characters', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_selection_too_long_1' => _x( 'You can only select 1 item', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_selection_too_long_n' => _x( 'You can only select %qty% items', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_load_more'            => _x( 'Loading more results&hellip;', 'enhanced select', 'woocommerce-product-vendors' ),
			'i18n_searching'            => _x( 'Searching&hellip;', 'enhanced select', 'woocommerce-product-vendors' ),
		);
		
		wp_localize_script( 'wcpv-admin-scripts', 'wcpv_admin_local', $localized_vars );

		if ( in_array( $current_screen->id, $this->get_screen_ids() ) ) {

			if ( ! WC_Product_Vendors_Utils::is_vendor() ) {
				wp_enqueue_script( 'wcpv-admin-scripts' );
			}

			wp_enqueue_style( 'wcpv-admin-styles' );

			wp_enqueue_script( 'wc-users', WC()->plugin_url() . '/assets/js/admin/users' . $suffix . '.js', array( 'jquery', 'wc-enhanced-select' ), WC_VERSION, true );

			wp_localize_script(
				'wc-users',
				'wc_users_params',
				array(
					'countries' => json_encode( array_merge( WC()->countries->get_allowed_country_states(), WC()->countries->get_shipping_country_states() ) ),
					'i18n_select_state_text' => esc_attr__( 'Select an option&hellip;', 'woocommerce-product-vendors' ),
				)
			);
		}

		return true;
	}

	/**
	 * Role update / send email
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $user_id
	 * @param string $new_role
	 * @param string $old_role
	 * @return bool
	 */
	public function role_update( $user_id, $new_role, $old_role ) {
		if ( ! current_user_can( 'manage_vendors' ) ) {
			return;
		}

		if ( $new_role !== $old_role && in_array( $new_role, array( 'wc_product_vendors_admin_vendor', 'wc_product_vendors_manager_vendor' ) ) ) {

			$emails = WC()->mailer()->get_emails();

			if ( ! empty( $emails ) ) {
				$emails[ 'WC_Product_Vendors_Approval' ]->trigger( $user_id, $new_role, $old_role );
			}
		}

		return true;
	}

	/**
	 * Shows the pending count bubble on sidebar menu items
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $menu
	 * @return array $menu modified menu
	 */
	public function pending_items_count( $menu ) {
		// pending vendors
		$users = count_users();

		$pending_vendors_count = ! empty( $users['avail_roles']['wc_product_vendors_pending_vendor'] ) ? $users['avail_roles']['wc_product_vendors_pending_vendor'] : '';

		// draft products from vendors pending review
		$products = wp_count_posts( 'product', 'readable' );

		$pending_products_count = ! empty( $products->pending ) ? $products->pending : '';

		foreach( $menu as $menu_key => $menu_data ) {
			if ( 'users.php' === $menu_data[2] && ! empty( $pending_vendors_count ) && current_user_can( 'manage_vendors' ) ) {
				$menu[$menu_key][0] .= ' <span class="update-plugins count-' . $pending_vendors_count . '" title="' . esc_attr__( 'Products awaiting review', 'woocommerce-product-vendors' ) . '"><span class="plugin-count">' . number_format_i18n( $pending_vendors_count ) . '</span></span>';
			}

			if ( 'edit.php?post_type=product' === $menu_data[2] && ! empty( $products->pending ) && current_user_can( 'manage_vendors' ) ) {
				$menu[$menu_key][0] .= ' <span class="update-plugins count-' . $pending_products_count . '" title="' . esc_attr__( 'Products awaiting review', 'woocommerce-product-vendors' ) . '"><span class="plugin-count">' . number_format_i18n( $pending_products_count ) . '</span></span>';
			}
		}

		return $menu;
	}

	/**
	 * Adds vendor fields to vendor create page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.16
	 * @return bool
	 */
	public function add_vendor_fields() {
		$tzstring = WC_Product_Vendors_Utils::get_default_timezone_string();

		include_once( 'views/html-create-vendor-fields-page.php' );

		return true;
	}

	/**
	 * Adds additional fields for product vendor term
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.16
	 * @param object $taxonomy
	 * @return bool
	 */
	public function edit_vendor_fields( $term ) {
		wp_enqueue_script( 'wc-enhanced-select' );
		wp_enqueue_script( 'jquery-tiptip' );

		$vendor_data = get_term_meta( $term->term_id, 'vendor_data', true );

		$notes                = ! empty( $vendor_data['notes'] ) ? $vendor_data['notes'] : '';
		$logo                 = ! empty( $vendor_data['logo'] ) ? $vendor_data['logo'] : '';
		$profile              = ! empty( $vendor_data['profile'] ) ? $vendor_data['profile'] : '';
		$email                = ! empty( $vendor_data['email'] ) ? $vendor_data['email'] : '';
		$commission           = ! empty( $vendor_data['commission'] ) ? $vendor_data['commission'] : '';
		$commission_type      = ! empty( $vendor_data['commission_type'] ) ? $vendor_data['commission_type'] : 'percentage';
		$instant_payout       = ! empty( $vendor_data['instant_payout'] ) ? $vendor_data['instant_payout'] : 'no';
		$paypal               = ! empty( $vendor_data['paypal'] ) ? $vendor_data['paypal'] : '';
		$per_product_shipping = ! empty( $vendor_data['per_product_shipping'] ) ? $vendor_data['per_product_shipping'] : 'no';
		$enable_bookings      = ! empty( $vendor_data['enable_bookings'] ) ? $vendor_data['enable_bookings'] : 'no';
		$admins               = ! empty( $vendor_data['admins'] ) ? $vendor_data['admins'] : '';
		$tzstring             = ! empty( $vendor_data['timezone'] ) ? $vendor_data['timezone'] : '';

		$selected_admins = array();

		if ( empty( $tzstring ) ) {
			$tzstring = WC_Product_Vendors_Utils::get_default_timezone_string();
		}

		if ( ! empty( $admins ) ) {
			$admin_ids = array_filter( array_map( 'absint', explode( ',', $vendor_data['admins'] ) ) );

			foreach ( $admin_ids as $admin_id ) {
				$admin = get_user_by( 'id', $admin_id );

				if ( is_object( $admin ) ) {
					$selected_admins[ $admin_id ] = esc_html( $admin->display_name ) . ' (#' . absint( $admin->ID ) . ') &ndash; ' . esc_html( $admin->user_email );
				}
			}
		}

		$hide_remove_image_link = '';
		
		$logo_image_url = wp_get_attachment_image_src( $logo, 'full' );
		
		if ( empty( $logo_image_url ) ) {
			$hide_remove_image_link = 'display:none;';
		}

		include_once( 'views/html-edit-vendor-fields-page.php' );

		return true;
	}

	/**
	 * Saves additional fields for product vendor term
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $term_id
	 * @return bool
	 */
	public function save_vendor_fields( $term_id ) {
		if ( ! empty( $_POST['vendor_data'] ) ) {

			$posted_vendor_data = $_POST['vendor_data'];

			// sanitize
			$posted_vendor_data = array_map( 'sanitize_text_field', $posted_vendor_data );
			$posted_vendor_data = array_map( 'stripslashes', $posted_vendor_data );			
						
			// sanitize html editor content
			$posted_vendor_data['profile'] = ! empty( $_POST['vendor_data']['profile'] ) ? wp_kses_post( stripslashes( $_POST['vendor_data']['profile'] ) ) : '';
			
			// validate commission as it takes an absolute number
			$posted_vendor_data['commission'] = WC_Product_Vendors_Utils::sanitize_commission( $posted_vendor_data['commission'] );

			// account for checkbox fields
			$posted_vendor_data['enable_bookings']      = ! isset( $posted_vendor_data['enable_bookings'] ) ? 'no' : 'yes';
			$posted_vendor_data['per_product_shipping'] = ! isset( $posted_vendor_data['per_product_shipping'] ) ? 'no' : 'yes';
			$posted_vendor_data['instant_payout']       = ! isset( $posted_vendor_data['instant_payout'] ) ? 'no' : 'yes';

			update_term_meta( $term_id, 'vendor_data', $posted_vendor_data );
		}

		return true;
	}

	/**
	 * Modifies the vendor columns
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $columns modified columns
	 */
	public function modify_vendor_columns( $columns ) {
		unset( $columns['description'] );

		// add admins to column
		$columns['admins'] = __( 'Admins', 'woocommerce-product-vendors' );

		// rename count column
		$columns['posts'] = __( 'Products', 'woocommerce-product-vendors' );

		// add notes to column
		$columns['notes'] = __( 'Notes', 'woocommerce-product-vendors' );

		// add vendor id to column
		$columns['vendor_id'] = __( 'Vendor ID', 'woocommerce-product-vendors' );

		return $columns;
	}

	/**
	 * Renders the modified vendor column
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $value current value
	 * @param string $column_name current column name
	 * @param int $term_id current term id
	 * @return string $value
	 */
	public function render_vendor_columns( $value, $column_name, $term_id ) {
		$vendor_data = get_term_meta( $term_id, 'vendor_data', true );

		if ( 'vendor_id' === $column_name ) {
			$value .= esc_html( $term_id );
		}

		if ( 'admins' === $column_name && ! empty( $vendor_data['admins'] ) ) {
			
			$admin_ids = array_filter( array_map( 'absint', explode( ',', $vendor_data['admins'] ) ) );

			foreach ( $admin_ids as $admin_id ) {
				$admin = get_user_by( 'id', $admin_id );

				if ( is_object( $admin ) ) {
					$value .= '<a href="' . get_edit_user_link( $admin_id ) . '" class="wcpv-vendor-column-user">' . esc_html( $admin->display_name ) . ' (#' . absint( $admin->ID ) . ' &ndash; ' . esc_html( $admin->user_email ) . ')</a><br />';
				}
			} 
		}

		if ( 'notes' === $column_name && ! empty( $vendor_data['notes'] ) ) {
			$value .= esc_html( $vendor_data['notes'] );
		}

		return $value;
	}

	/**
	 * Add column to the user taxonomy columns
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $columns
	 * @return bool
	 */
	public function add_custom_user_column( $columns ) {
		$columns['vendors'] = __( 'Managed Vendors', 'woocommerce-product-vendors' );

		return $columns;
	}

	/**
	 * Modifies the user taxonomy columns
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $content
	 * @return string $vendors
	 */
	public function add_user_column_data( $content, $column_slug, $user_id ) {
		$vendor_data = WC_Product_Vendors_Utils::get_all_vendor_data( $user_id );
		$vendors = '';

		if ( 'vendors' === $column_slug && ! empty( $vendor_data ) ) {
			$vendor_names = array();

			foreach( $vendor_data as $data ) {
				$vendor_names[] = $data['name'];
			}

			$vendors = implode( '<br />', $vendor_names );
		}

		return $vendors;
	}

	/**
	 * Add vendor section fields to user profile
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.1.0
	 * @param object $user
	 * @return bool
	 */
	public function add_product_vendor_user_profile_section( $user ) {
		// display section only if current user is an admin and the editing user is a vendor
		if ( ! WC_Product_Vendors_Utils::is_vendor( $user->ID ) || ! current_user_can( 'manage_vendors' ) ) {
			return;
		}

		$publish_products = 'disallow';
		$manage_customers = 'disallow';

		// check for user publish products capability
		if ( $user->has_cap( 'publish_products' ) ) {
			$publish_products = 'allow';
		}

		// check for create users capability
		if ( $user->has_cap( 'create_users' ) && $user->has_cap( 'edit_users' ) ) {
			$manage_customers = 'allow';
		}

		include_once( 'views/html-edit-user-profile-page.php' );

		return true;
	}

	/**
	 * Save vendor section fields to user profile
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $user
	 * @return bool
	 */
	public function save_product_vendor_user_profile_section( $user_id ) {
		$publish_products = ! empty( $_POST['wcpv_publish_products'] ) ? sanitize_text_field( $_POST['wcpv_publish_products'] ) : 'disallow';
		$manage_customers = ! empty( $_POST['wcpv_manage_customers'] ) ? sanitize_text_field( $_POST['wcpv_manage_customers'] ) : 'disallow';

		$roles_caps = new WC_Product_Vendors_Roles_Caps;

		// update user capability
		if ( 'disallow' === $publish_products ) {
			$roles_caps->remove_publish_products( $user_id );
		} else {
			$roles_caps->add_publish_products( $user_id );
		}

		// update user capability
		if ( 'disallow' === $manage_customers ) {
			$roles_caps->remove_manage_users( $user_id );
		} else {
			$roles_caps->add_manage_users( $user_id );
		}

		return true;
	}

	/**
	 * Register the commission menu item
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_commissions_menu_item() {
		$hook = add_menu_page( __( 'Vendor Commission', 'woocommerce-product-vendors' ), __( 'Commission', 'woocommerce-product-vendors' ), 'manage_vendors', 'wcpv-commissions', array( $this, 'render_commission_page' ), 'dashicons-chart-pie', 56.77 );

		add_action( "load-$hook", array( $this, 'add_screen_options' ) );

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
	public function add_screen_options() {
		$option = 'per_page';

		$args = array(
			'label'   => __( 'Commissions', 'woocommerce-product-vendors' ),
			'default' => apply_filters( 'wcpv_commission_list_default_item_per_page', 20 ),
			'option'  => 'commissions_per_page',
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
		if ( 'commissions_per_page' === $option ) {
			return $value;
		}

		return $status;
	}

	/**
	 * Renders the commission page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_commission_page() {
		$commissions_list = new WC_Product_Vendors_Store_Admin_Commission_List( new WC_Product_Vendors_Commission( new WC_Product_Vendors_PayPal_MassPay ) );

		$commissions_list->prepare_items();
	?>
		<div class="wrap">

			<h2><?php esc_html_e( 'Vendor Commission', 'woocommerce-product-vendors' ); ?>
				<?php 
					if ( ! empty( $_REQUEST['s'] ) ) {
						echo '<span class="subtitle">' . esc_html__( 'Search results for', 'woocommerce-product-vendors' ) . ' "' . sanitize_text_field( $_REQUEST['s'] ) . '"</span>';
					} 
				?>
			</h2>

			<ul class="subsubsub"><?php $commissions_list->views(); ?></ul>
		
			<form id="wcpv-commission-list" action="" method="get">
				<input type="hidden" name="page" value="wcpv-commissions" />
				<?php $commissions_list->search_box( esc_html__( 'Search Order #', 'woocommerce-product-vendors' ), 'search_id' ); ?>
				<?php $commissions_list->display(); ?>
			</form>
		</div>
	<?php
		return true;
	}

	/**
	 * Adds extra vendor field to attachment so we know who the attachment belongs to
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $form_fields
	 * @param object $post
	 * @return array $form_fields
	 */
	public function add_attachments_field( $form_fields, $post ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			$post_vendor = get_post_meta( $post->ID, '_wcpv_vendor', true );

			$form_fields['vendor'] = array( 
				'label' => __( 'Vendor', 'woocommerce-product-vendors' ),
				'input' => 'text',
				'value' => $post_vendor,
			);
		}

		return $form_fields;
	}

	/**
	 * Saves attachment extra fields
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $post
	 * @param array $data
	 * @return array $post
	 */
	public function save_attachments_field( $post, $data ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			if ( ! empty( $data['vendor'] ) ) {
				// save vendor id to attachment post meta
				update_post_meta( $post['ID'], '_wcpv_vendor', absint( $data['vendor'] ) );
			}
		}

		return $post;
	}

	/**
	 * Add vendor settings section to products tab
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $sections existing sections
	 * @return array $sections modified sections
	 */
	public function add_vendor_settings_section( $sections ) {
		$sections['wcpv_vendor_settings'] = __( 'Vendors', 'woocommerce-product-vendors' );

		return $sections;
	}

	/**
	 * Add vendor settings to vendor settings section
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $settings existing settings
	 * @param string $current_section current section name
	 * @return array $settings
	 */
	public function add_vendor_settings( $settings, $current_section ) {
		if ( 'wcpv_vendor_settings' === $current_section ) {
			$new_settings = array(
				array(
					'title'    => __( 'Payments', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_payments',
					'type'     => 'title',
				),

				array(
					'title'    => __( 'Payout Schedule', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Choose the frequency of commission payout for vendors.  Any commission that is unpaid will follow the schedule set here.  Note that by saving this option, a payout will initiate now and recur based on your settings from today\'s date.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_payout_schedule',
					'default'  => 'manual',
					'type'     => 'select',
					'options'  => array(
						'manual'    => __( 'Manual', 'woocommerce-product-vendors' ),
						'weekly'    => __( 'Weekly', 'woocommerce-product-vendors' ),
						'biweekly'  => __( 'Bi-Weekly', 'woocommerce-product-vendors' ),
						'monthly'   => __( 'Monthly', 'woocommerce-product-vendors' ),
					),
					'desc_tip' =>  true,					
					'autoload' => false
				),

				array(
					'title'         => __( 'PayPal Mass Payments Environment', 'woocommerce-product-vendors' ),
					'id'            => 'wcpv_vendor_settings_paypal_masspay_environment',
					'desc'          => __( 'PayPal Mass Payments sandbox mode can be used to test payouts.  You will need API credentials for this.  Please refer to <a href="https://developer.paypal.com/docs/integration/admin/manage-apps/" target="_blank" title="PayPal Documentation">PayPal Documentation</a>', 'woocommerce-product-vendors' ),
					'type'          => 'select',
					'default'       => 'sandbox',
					'options'       => array(
						'sandbox' => __( 'Sandbox', 'woocommerce-product-vendors' ),
						'live'    => __( 'Live', 'woocommerce-product-vendors' ),
					),
					'autoload'      => false
				),

				array(
					'title'    => __( '(Sandbox) PayPal Mass Payments API Client ID', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Enter the API Client ID.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_paypal_masspay_client_id_sandbox',
					'default'  => '',
					'type'     => 'text',					
					'autoload' => false
				),

				array(
					'title'    => __( '(Sandbox) PayPal Mass Payments API Client Secret', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Enter the API Client Secret.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_paypal_masspay_client_secret_sandbox',
					'default'  => '',
					'type'     => 'text',					
					'autoload' => false
				),

				array(
					'title'    => __( 'PayPal Mass Payments API Client ID', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Enter the API Client ID.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_paypal_masspay_client_id_live',
					'default'  => '',
					'type'     => 'text',					
					'autoload' => false
				),

				array(
					'title'    => __( 'PayPal Mass Payments API Client Secret', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Enter the API Client Secret.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_paypal_masspay_client_secret_live',
					'default'  => '',
					'type'     => 'text',					
					'autoload' => false
				),

				array(
					'title'    => __( 'Default Commission', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Enter a default commission that works globally for all vendors as a fallback if commission is not set per vendor level.  Enter a positive number.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_default_commission',
					'default'  => '0',
					'type'     => 'text',
					'desc_tip' =>  true,					
					'autoload' => false
				),

				array(
					'title'    => __( 'Commission Type', 'woocommerce-product-vendors' ),
					'desc'     => __( 'Choose whether the commission amount will be a fixed amount or a percentage of the cost.', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_default_commission_type',
					'default'  => 'percentage',
					'type'     => 'select',
					'options'       => array(
						'percentage' => __( 'Percentage', 'woocommerce-product-vendors' ),
						'fixed'      => __( 'Fixed', 'woocommerce-product-vendors' ),
					),
					'autoload' => false
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'wcpv_vendor_settings_payments'
				),

				array(
					'title'    => __( 'Display', 'woocommerce-product-vendors' ),
					'id'       => 'wcpv_vendor_settings_display',
					'type'     => 'title',
				),

				array(
					'title'         => __( 'Show [Sold By]', 'woocommerce-product-vendors' ),
					'desc'          => __( 'Enable this to show [Sold By Vendor Name] for each product.', 'woocommerce-product-vendors' ),
					'id'            => 'wcpv_vendor_settings_display_show_by',
					'type'          => 'checkbox',
					'default'       => 'yes',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Show Vendor Review', 'woocommerce-product-vendors' ),
					'desc'          => __( 'Enable this to show vendor\'s overall review rating on vendor\'s page.', 'woocommerce-product-vendors' ),
					'id'            => 'wcpv_vendor_settings_vendor_review',
					'type'          => 'checkbox',
					'default'       => 'yes',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Show Vendor Logo', 'woocommerce-product-vendors' ),
					'desc'          => __( 'Enable this to show vendor\'s logo on vendor\'s page.', 'woocommerce-product-vendors' ),
					'id'            => 'wcpv_vendor_settings_vendor_display_logo',
					'type'          => 'checkbox',
					'default'       => 'yes',
					'autoload'      => false
				),

				array(
					'title'         => __( 'Show Vendor Profile', 'woocommerce-product-vendors' ),
					'desc'          => __( 'Enable this to show vendor\'s profile on vendor\'s page.', 'woocommerce-product-vendors' ),
					'id'            => 'wcpv_vendor_settings_vendor_display_profile',
					'type'          => 'checkbox',
					'default'       => 'yes',
					'autoload'      => false
				),

				array(
					'type' 	=> 'sectionend',
					'id' 	=> 'wcpv_vendor_settings_display'
				),
			);

			$settings = apply_filters( 'wcpv_vendor_settings', $new_settings );
		}

		return $settings;
	}

	/**
	 * Save vendor general/global settings
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function save_vendor_settings( $value, $option, $raw_value ) {
		global $current_section;

		if ( 'wcpv_vendor_settings' !== $current_section ) {
			return $value;
		}

		if ( 'wcpv_vendor_settings_default_commission' === $option['id'] ) {
			return WC_Product_Vendors_Utils::sanitize_commission( $value );
		}

		return $value;
	}

	/**
	 * Add sold by vendor to order item
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_sold_by_order_item_detail( $item_id, $item, $product ) {
		$sold_by = get_option( 'wcpv_vendor_settings_display_show_by', 'yes' );

		if ( 'yes' === $sold_by && ! empty( $item['product_id'] ) && WC_Product_Vendors_Utils::is_vendor_product( $item['product_id'] ) ) {

			$sold_by = WC_Product_Vendors_Utils::get_sold_by_link( $item['product_id'] );

			echo '<em class="wcpv-sold-by-order-details">' . apply_filters( 'wcpv_sold_by_text', __( 'Sold By:', 'woocommerce-product-vendors' ) ) . ' <a href="' . esc_url( $sold_by['link'] ) . '" title="' . esc_attr( $sold_by['name'] ) . '">' . $sold_by['name'] . '</a></em>';
		}
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
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			global $post;
			
			$vendor_id        = WC_Product_Vendors_Utils::get_vendor_id_from_product( $post->ID );
			$vendor_data      = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );
			$commission_data  = WC_Product_Vendors_Utils::get_product_commission( $post->ID, $vendor_data );

			$commission_placeholder = ! empty( $commission_data['commission'] ) ? $commission_data['commission'] : '';

			$commission_type = __( 'Fixed', 'woocommerce-product-vendors' );

			if ( 'percentage' === $commission_data['type'] ) {
				$commission_type = '%';
			}

			echo '<div class="options_group show_if_simple show_if_variable show_if_booking">';

			woocommerce_wp_text_input( array( 
				'id'                => '_wcpv_product_commission', 
				'label'             => sprintf( __( 'Commission %s:', 'woocommerce-product-vendors' ), '(' . $commission_type . ')' ), 
				'desc_tip'          => 'true', 
				'description'       => __( 'Enter a default commission for this product. Enter a positive number.', 'woocommerce-product-vendors' ), 
				'placeholder'       => $commission_placeholder, 
				'type'              => 'number', 
				'custom_attributes' => array( 'step' => 'any', 'min' => '0' ) 
			) );

			echo '</div>';
		}

		return true;
	}

	/**
	 * Save the commission field for the product general tab
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function save_product_commission_field_general( $post_id ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			if ( empty( $post_id ) ) {
				return;
			}

			$commission = WC_Product_Vendors_Utils::sanitize_commission( $_POST['_wcpv_product_commission'] );

			update_post_meta( $post_id, '_wcpv_product_commission', $commission );
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
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			$commission = get_post_meta( $variation->ID, '_wcpv_product_commission', true );

			global $post;
			
			$vendor_id        = WC_Product_Vendors_Utils::get_vendor_id_from_product( $post->ID );
			$vendor_data      = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );
			$commission_data  = WC_Product_Vendors_Utils::get_product_commission( $post->ID, $vendor_data );

			$commission_placeholder = ! empty( $commission_data['commission'] ) ? $commission_data['commission'] : '';

			$commission_type = __( 'Fixed', 'woocommerce-product-vendors' );

			if ( 'percentage' === $commission_data['type'] ) {
				$commission_type = '%';
			}

			echo '<div class="options_group show_if_variable show_if_booking">';
			?>
			<p class="wcpv-commission form-row form-row-first">
				<label><?php echo esc_html__( 'Commission', 'woocommerce-product-vendors' ) . ' (' . $commission_type . ')'; ?>: <?php echo wc_help_tip( __( 'Enter a commission for this product variation.  Enter a positive number.', 'woocommerce-product-vendors' ) ); ?></label>

				<input type="number" name="_wcpv_product_variation_commission[<?php echo $loop; ?>]" value="<?php echo esc_attr( $commission ); ?>" placeholder="<?php echo esc_attr( $commission_placeholder ); ?>" step="any" min="0" />
			</p>
			<?php
			echo '</div>';
		}

		return true;
	}

	/**
	 * Save the commission field for the product variable
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $product_id
	 * @return bool
	 */
	public function save_product_commission_field_variable( $product_id ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			if ( empty( $product_id ) ) {
				return;
			}

			$commission = WC_Product_Vendors_Utils::sanitize_commission( $_POST['_wcpv_product_commission'] );

			update_post_meta( $product_id, '_wcpv_product_commission', $commission );
		}
		
		return true;		
	}

	/**
	 * Save the commission field for the product variation
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $variation_id
	 * @param int $i loop count
	 * @return bool
	 */
	public function save_product_commission_field_variation( $variation_id, $i ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			if ( empty( $variation_id ) ) {
				return;
			}

			$commission = WC_Product_Vendors_Utils::sanitize_commission( $_POST['_wcpv_product_variation_commission'][ $i ] );

			update_post_meta( $variation_id, '_wcpv_product_commission', $commission );
		}
		
		return true;
	}

	/**
	 * Add a pass shipping field to the product general tab
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_product_pass_shipping_tax_field_general() {
		global $post;

		$pass_shipping_tax = get_post_meta( $post->ID, '_wcpv_product_default_pass_shipping_tax', true );

		// set default to yes if nothing is set
		if ( empty( $pass_shipping_tax ) ) {
			$pass_shipping_tax = 'yes';
		}

		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			$output = '';

			$output .= '<div class="options_group show_if_simple show_if_variable show_if_booking">' . PHP_EOL;

			$output .= '<p class="form-field wcpv_product_default_pass_shipping_tax_field"><label for="wcpv_product_default_pass_shipping_tax">' . wp_kses_post( __( 'Pass shipping/tax', 'woocommerce-product-vendors' ) ) . '</label><input type="checkbox" name="_wcpv_product_default_pass_shipping_tax" id="wcpv_product_default_pass_shipping_tax" value="yes" ' . checked( 'yes', $pass_shipping_tax, false ) . '/>' . PHP_EOL;

			$output .= '<span class="description">' . wp_kses_post( __( 'Check box to pass the shipping and tax charges for this product to the vendor.', 'woocommerce-product-vendors' ) ) . '</span>' . PHP_EOL;

			$output .= '</p>' . PHP_EOL;

			$output .= '</div>';

			echo $output;
		}

		return true;
	}

	/**
	 * Save the pass shipping field for the product general tab
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function save_product_pass_shipping_tax_field_general( $post_id ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			if ( empty( $post_id ) ) {
				return;
			}

			if ( ! empty( $_POST['_wcpv_product_default_pass_shipping_tax'] ) ) {
				update_post_meta( $post_id, '_wcpv_product_default_pass_shipping_tax', 'yes' );

			} else {

				update_post_meta( $post_id, '_wcpv_product_default_pass_shipping_tax', 'no' );
			}
		}

		return true;
	}

	/**
	 * Adds bulk edit action for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_variation_vendor_bulk_edit() {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
	?>
			<optgroup label="<?php esc_attr_e( 'Vendor', 'woocommerce-product-vendors' ); ?>">
				<option value="variable_vendor_commission"><?php esc_html_e( 'Commission', 'woocommerce-product-vendors' ); ?></option>
				<option value="variable_pass_shipping_tax"><?php esc_html_e( 'Toggle Pass shipping/tax', 'woocommerce-product-vendors' ); ?></option>
			</optgroup>
	<?php
		}
	}

	/**
	 * Ajax search for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return json $found_vendors
	 */
	public function vendor_search_ajax() {
		$nonce = $_GET['security'];

		// bail if nonce don't check out
		if ( ! wp_verify_nonce( $nonce, '_wcpv_vendor_search_nonce' ) ) {
		     wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );	
		}

		$term = (string) wc_clean( stripslashes( $_GET['term'] ) );

		if ( empty( $term ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		$args = array(
			'hide_empty' => false,
			'name__like' => $term,
		);

		$vendor_terms = get_terms( WC_PRODUCT_VENDORS_TAXONOMY, $args );

		$found_vendors = array();

		if ( ! empty( $vendor_terms ) ) {
			foreach( $vendor_terms as $term ) {
				$found_vendors[ $term->term_id ] = $term->name;
			}
		}

		wp_send_json( $found_vendors );
	}

	/**
	 * Add pass shipping/tax setting to product bulk edit menu
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_product_bulk_edit_pass_shipping_tax() {
	?>
		<label>
			<span class="title"><?php esc_html_e( 'Pass Shipping/Tax to Vendor?', 'woocommerce-product-vendors' ); ?></span>
				<span class="input-text-wrap">
					<select class="pass-shipping-tax" name="_wcpv_product_default_pass_shipping_tax">
					<?php
					$options = array(
						''    => __( '— No Change —', 'woocommerce-product-vendors' ),
						'yes' => __( 'Yes', 'woocommerce-product-vendors' ),
						'no'  => __( 'No', 'woocommerce-product-vendors' )
					);
					
					foreach ( $options as $key => $value ) {
						echo '<option value="' . esc_attr( $key ) . '">' . esc_html( $value ) . '</option>';
					}
					?>
				</select>
			</span>
		</label>
	<?php
	}

	/**
	 * Filters the order item meta label without underscore
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $product
	 * @return bool
	 */
	public function filter_order_attribute_label( $key, $product ) {
		if ( '_fulfillment_status' === $key ) {
			return __( 'Fulfillment Status', 'woocommerce-product-vendors' );
		}

		if ( '_commission_status' === $key ) {
			return __( 'Commission Status', 'woocommerce-product-vendors' );
		}

		return $key;
	}

	/**
	 * Save pass shipping/tax setting to product bulk edit
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $product
	 * @return bool
	 */
	public function save_product_bulk_edit_pass_shipping_tax( $product ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() && current_user_can( 'manage_vendors' ) ) {
			if ( empty( $product ) ) {
				return;
			}

			if ( ! empty( $_REQUEST['_wcpv_product_default_pass_shipping_tax'] ) && 'yes' === $_REQUEST['_wcpv_product_default_pass_shipping_tax'] ) {
				update_post_meta( $product->id, '_wcpv_product_default_pass_shipping_tax', 'yes' );

			} else {
				update_post_meta( $product->id, '_wcpv_product_default_pass_shipping_tax', 'no' );
			}
		}

		return true;		
	}

	/**
	 * Handles saving of the vendro to product
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function save_product_vendor( $post_id ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// don't continue if it is bulk/quick edit
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) || ! empty( $_REQUEST['woocommerce_bulk_edit'] ) ) {
			return;
		}

		// if not a product bail
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		$term = ! empty( $_POST['wcpv_product_term'] ) ? absint( $_POST['wcpv_product_term'] ) : '';

		wp_set_object_terms( $post_id, $term, WC_PRODUCT_VENDORS_TAXONOMY );

		return true;
	}

	/**
	 * Add vendor selection on quick and bulk edit
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $column_name the name of the column to add it to
	 * @param string $post_type
	 * @return bool
	 */
	public function quick_edit( $column_name, $post_type ) {
		if ( WC_Product_Vendors_Utils::is_vendor() || ! current_user_can( 'manage_vendors' ) ) {
			return;
		}

		if ( 'taxonomy-wcpv_product_vendors' !== $column_name || 'product' !== $post_type ) {
			return;
		}

		$args = array(
			'hide_empty'   => false,
			'hierarchical' => false,
		);

		$terms = get_terms( WC_PRODUCT_VENDORS_TAXONOMY, $args );

		if ( ! empty( $terms ) ) {
			$output = '<fieldset class="inline-edit-col-center"><div class="inline-edit-group"><label class="alignleft"><span class="title">' . esc_html__( 'Vendors', 'woocommerce-product-vendors' ) . '</span>';

			$output .= '<select class="wcpv-product-vendor-terms-dropdown" name="wcpv_qe_product_term">';

			$output .= '<option value="no">' . esc_html__( 'No Change', 'woocommerce-product-vendors' ) . '</option>';
			$output .= '<option value="novendor">' . esc_html__( 'No Vendor', 'woocommerce-product-vendors' ) . '</option>';

			foreach( $terms as $term ) {
				$output .= '<option value="' . esc_attr( $term->term_id ) . '">' . esc_html( $term->name ) . '</option>';
			}

			$output .= '</select>';

			$output .= '</label></div></fieldset>';

			echo $output;
		}
	}

	/**
	 * Handles quick and bulk edit saves
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @param object $post
	 * @return int
	 */
	public function bulk_and_quick_edit_save_post( $post_id, $post ) {
		if ( WC_Product_Vendors_Utils::is_vendor() || ! current_user_can( 'manage_vendors' ) ) {
			return $post_id;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' !== $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( empty( $_REQUEST['wcpv_qe_product_term'] ) || 'no' === $_REQUEST['wcpv_qe_product_term'] ) {
			return $post_id;
		}

		$term = ! empty( $_REQUEST['wcpv_qe_product_term'] ) ? absint( $_REQUEST['wcpv_qe_product_term'] ) : '';

		if ( 'novendor' === $term ) {
			$term = '';
		}
		
		// check if it is a quick edit or bulk edit
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) {
			// update the product term
			wp_set_object_terms( $post_id, $term, WC_PRODUCT_VENDORS_TAXONOMY );

			// Clear transient
			wc_delete_product_transients( $post_id );

		} elseif ( ! empty( $_REQUEST['woocommerce_bulk_edit'] ) && ! empty( $_REQUEST['post'] ) ) {
			foreach( $_REQUEST['post'] as $post ) {
				// update the product term
				wp_set_object_terms( absint( $post ), $term, WC_PRODUCT_VENDORS_TAXONOMY );

				// Clear transient
				wc_delete_product_transients( absint( $post ) );
			}
		}

		return $post_id;
	}

	/**
	 * Generates the CSV ( commissions ) download of current view
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $query
	 */
	public function export_commissions_ajax() {
		$order_id          = ! empty( $_POST['order_id'] ) ? absint( $_POST['order_id'] ) : '';
		$year              = ! empty( $_POST['year'] ) ? sanitize_text_field( $_POST['year'] ) : '';
		$month             = ! empty( $_POST['month'] ) ? sanitize_text_field( $_POST['month'] ) : '';
		$commission_status = ! empty( $_POST['commission_status'] ) ? sanitize_text_field( $_POST['commission_status'] ) : '';
		$vendor_id         = ! empty( $_POST['vendor'] ) ? absint( $_POST['vendor'] ) : '';
		$nonce             = ! empty( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';

		// bail if nonce don't check out
		if ( ! wp_verify_nonce( $nonce, '_wcpv_export_commissions_nonce' ) ) {
		     wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );	
		}

		$commission = new WC_Product_Vendors_Commission( new WC_Product_Vendors_PayPal_MassPay );
		
		$query = $commission->csv_filtered_query( $order_id, $year, $month, $commission_status, $vendor_id );

		echo $query;
		exit;
	}

	/**
	 * Handles export of unpaid commissions
	 *
	 * @access public
	 * @since 2.0.6
	 * @version 2.0.6
	 * @return bool
	 */
    public function export_unpaid_commissions_ajax() {
		$nonce = ! empty( $_POST['nonce'] ) ? sanitize_text_field( $_POST['nonce'] ) : '';
		
		// bail if nonce don't check out
		if ( ! wp_verify_nonce( $nonce, '_wcpv_export_unpaid_commissions_nonce' ) ) {
		     wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );	
		}

		$currency = get_woocommerce_currency();
		$commission = new WC_Product_Vendors_Commission( new WC_Product_Vendors_PayPal_MassPay );

		$unpaid_commissions = $commission->get_unpaid_commission_data();

		$commissions = array();

		foreach( $unpaid_commissions as $commission ) {
			if ( ! isset( $commissions[ $commission->vendor_id ] ) ) {
				$commissions[ $commission->vendor_id ] = wc_format_decimal( 0, 2 );
			}

			$commissions[ $commission->vendor_id ] += wc_format_decimal( $commission->total_commission_amount, 2 );
		}

		$payout_note = apply_filters( 'wcpv_export_unpaid_commissions_note', sprintf( __( 'Total commissions earned from %1$s as of %2$s on %3$s', 'woocommerce-product-vendors' ), get_bloginfo( 'name', 'display' ), date( 'H:i:s' ), date( 'd-m-Y' ) ) );

		$commissions_data = array();

		foreach( $commissions as $vendor_id => $total ) {
			$vendor = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );

			$recipient = $vendor['name'];

			if ( ! empty( $vendor['paypal'] ) ) {
				$recipient = $vendor['paypal'];
			}

			$commissions_data[] = array(
				$recipient,
				$total,
				$currency,
				$vendor_id,
				$payout_note
			);
		}

		// prepare CSV
		$headers = array(
			'Recipient',
			'Payment',
			'Currency',
			'Customer ID',
			'Note'
		);

		array_unshift( $commissions_data, $headers );

		// convert the array to string recursively
		$commissions_data = implode( PHP_EOL, array_map( array( 'WC_Product_Vendors_Utils', 'convert2string' ), $commissions_data ) );		

		echo $commissions_data;
		exit;
    }

	/**
	 * Add debug tool button
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $tools
	 */
	public function add_debug_tool( $tools ) {
		if ( ! empty( $_GET['action'] ) && 'wcpv_clear_transients' === $_GET['action'] ) {
			WC_Product_Vendors_Utils::clear_reports_transients();

			echo '<div class="updated"><p>' . esc_html__( 'Product Vendor Transients Cleared', 'woocommerce-product-vendors' ) . '</p></div>';
		}

		$tools['wcpv_clear_transients'] = array(
			'name'    => __( 'Product Vendors Transients', 'woocommerce-product-vendors' ),
			'button'  => __( 'Clear all transients/cache', 'woocommerce-product-vendors' ),
			'desc'    => __( 'This will clear all Product Vendors related transients/caches such as reports.', 'woocommerce-product-vendors' ),
		);

		return $tools;
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

WC_Product_Vendors_Store_Admin::init();
