<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WC_Product_Vendors_Bookings {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		// clear bookings query (cache)
		add_action( 'parse_query', array( $this, 'clear_bookings_cache' ) );

		// remove bookings menu if user is not managing any vendors
		add_action( 'admin_menu', array( $this, 'remove_bookings_menu' ), 99 );

		add_action( 'admin_menu', array( $this, 'remove_bookings_global_availability_menu' ), 99 );

		// filter products for specific vendor
		add_filter( 'get_booking_products_args', array( $this, 'filter_products' ) );

		// filter resources for specific vendor
		add_filter( 'get_booking_resources_args', array( $this, 'filter_products' ) );

		// filter products from booking list
		add_filter( 'pre_get_posts', array( $this, 'filter_products_booking_list' ) );

		// filter products from booking calendar
		add_filter( 'woocommerce_bookings_in_date_range_query', array( $this, 'filter_bookings_calendar' ) );

		// remove resources for vendors
		add_filter( 'woocommerce_register_post_type_bookable_resource', array( $this, 'remove_resource' ) );

		// add vendor email for confirm booking email
		add_filter( 'woocommerce_email_recipient_new_booking', array( $this, 'filter_booking_emails' ), 10, 2 );

		// add vendor email for cancelled booking email
		add_filter( 'woocommerce_email_recipient_booking_cancelled', array( $this, 'filter_booking_emails' ), 10, 2 );

		// remove wc booking post type access
		add_filter( 'woocommerce_register_post_type_wc_booking', array( $this, 'remove_wc_booking_post_type' ) );

		// remove bookable person post type access
		add_filter( 'woocommerce_register_post_type_bookable_person', array( $this, 'remove_bookable_person_post_type' ) );

		// redirect vendors trying to access global availability page directly
		add_action( 'init', array( $this, 'redirect_global_availability_page' ) );

		return true;
	}

	/**
	 * Checks if bookings is enabled for this vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $post_type_args
	 */
	public function is_bookings_enabled() {
		$vendor_data = get_term_meta( WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), 'vendor_data', true );

		if ( empty( $vendor_data ) || 'no' === $vendor_data['enable_bookings'] ) {
			return false;
		}

		return true;
	}

	/**
	 * Remove bookings resources page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $post_type_args
	 */
	public function remove_resource( $post_type_args ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			$post_type_args['capability_type'] = 'manage_booking_resource';
		}

		return $post_type_args;
	}

	/**
	 * Remove bookings menu item when user has no access
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function remove_bookings_menu() {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			if ( ! $this->is_bookings_enabled() ) {
				remove_menu_page( 'edit.php?post_type=wc_booking' );

				return;
			}

			// remove create bookings menu page
			remove_submenu_page( 'edit.php?post_type=wc_booking', 'create_booking' );
		}

		return true;
	}

	/**
	 * Remove bookings global availability menu
	 *
	 * @access public
	 * @since 2.0.2
	 * @version 2.0.2
	 * @return bool
	 */
	public function remove_bookings_global_availability_menu() {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			// remove create bookings menu page
			remove_submenu_page( 'edit.php?post_type=wc_booking', 'wc_bookings_global_availability' );
		}

		return true;
	}

	/**
	 * Remove bookings global availability page access by redirecting
	 *
	 * @access public
	 * @since 2.0.2
	 * @version 2.0.2
	 * @return bool
	 */
	public function redirect_global_availability_page() {
		if ( WC_Product_Vendors_Utils::is_vendor() && isset( $_GET['page'] ) && 'wc_bookings_global_availability' === $_GET['page'] ) {
			wp_safe_redirect( admin_url( 'edit.php?post_type=wc_booking' ) );
			exit;
		}
	}

	/**
	 * Filter products for specific vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $query_args
	 * @return array $products
	 */
	public function filter_products( $query_args ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			$product_ids = WC_Product_Vendors_Utils::get_vendor_product_ids();

			$product_ids = ! empty( $product_ids ) ? $product_ids : array( '0' );

			$query_args['post__in'] = $product_ids;
		}

		return $query_args;
	}

	/**
	 * Filter products booking list to specific vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $query
	 * @return bool
	 */
	public function filter_products_booking_list( $query ) {
		global $typenow, $current_screen;

		if ( ! $query->is_main_query() ) {
			return;
		}

		remove_filter( 'pre_get_posts', array( $this, 'filter_products_booking_list' ) );
		
		if ( 'wc_booking' === $typenow && WC_Product_Vendors_Utils::auth_vendor_user() && is_admin() && 'edit-wc_booking' === $current_screen->id ) {
			$product_ids = WC_Product_Vendors_Utils::get_vendor_product_ids();

			$product_ids = ! empty( $product_ids ) ? $product_ids : array( '0' );
			$query->set( 'meta_key', '_booking_product_id' );
			$query->set( 'meta_compare', 'IN' );
			$query->set( 'meta_value', $product_ids );
		}

		return true;
	}	

	/**
	 * Filter products booking calendar to specific vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $booking_ids booking ids
	 * @return array
	 */
	public function filter_bookings_calendar( $booking_ids ) {
		$filtered_ids = array();

		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			$product_ids = WC_Product_Vendors_Utils::get_vendor_product_ids();

			if ( ! empty( $product_ids ) ) {
				foreach( $booking_ids as $id ) {
					$booking = get_wc_booking( $id );

					if ( in_array( $booking->product_id, $product_ids ) ) {
						$filtered_ids[] = $id;
					}
				}

				$filtered_ids = array_unique( $filtered_ids );

				return $filtered_ids;
			} else {
				return array();
			}
		}

		return $booking_ids;
	}

	/**
	 * Add vendor email to bookings admin emails
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $recipients
	 * @param object $this_email
	 * @return array $recipients
	 */
	public function filter_booking_emails( $recipients, $this_email ) {
		if ( ! empty( $this_email ) ) {
			$vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( $this_email->product_id );
			$vendor_data = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor_id );

			if ( ! empty( $vendor_id ) && ! empty( $vendor_data ) ) {
				if ( isset( $recipients ) ) {
					$recipients .= ',' . $vendor_data['email'];
				} else {
					$recipients = $vendor_data['email'];
				}
			}
		}

		return $recipients;
	}

	/**
	 * Removes post type if bookings is not enabled
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $args
	 * @return array $args
	 */
	public function remove_wc_booking_post_type( $args ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			$vendor_data = get_term_meta( WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), 'vendor_data', true );

			if ( empty( $vendor_data ) || 'no' === $vendor_data['enable_bookings'] ) {
				$args['capability_type'] = 'manage_bookings';
			}
		}

		return $args;
	}

	/**
	 * Removes post type if bookings is not enabled
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $args
	 * @return array $args
	 */
	public function remove_bookable_person_post_type( $args ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			if ( ! $this->is_bookings_enabled() ) {
				$args['capability_type'] = 'manage_bookable_person';
			}
		}

		return $args;
	}

	/**
	 * Clears the bookings query cache on page load
	 *
	 * @access public
	 * @since 2.0.1
	 * @version 2.0.1
	 * @return bool
	 */
	public function clear_bookings_cache() {
		global $wpdb, $typenow, $current_screen;

		if ( 'wc_booking' === $typenow && is_admin() && ( 'edit-wc_booking' === $current_screen->id || 'wc_booking_page_booking_calendar' === $current_screen->id ) ) {

			$wpdb->query( "DELETE FROM $wpdb->options WHERE option_name LIKE '%book_dr%'" );
		}

		return true;
	}
}

new WC_Product_Vendors_Bookings();
