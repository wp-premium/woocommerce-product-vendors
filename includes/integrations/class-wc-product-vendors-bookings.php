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

		// remove global availability page and roll our own
		add_action( 'admin_menu', array( $this, 'remove_bookings_global_availability_menu' ), 99 );

		// add our own version of the global availability page
		add_action( 'admin_menu', array( $this, 'add_bookings_global_availability_menu' ) );

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
		add_filter( 'woocommerce_register_post_type_wc_booking', array( $this, 'maybe_remove_wc_booking_post_type' ) );

		// remove bookable person post type access
		add_filter( 'woocommerce_register_post_type_bookable_person', array( $this, 'maybe_remove_bookable_person_post_type' ) );

		// filter availability rules
		add_filter( 'woocommerce_booking_get_availability_rules', array( $this, 'filter_availability_rules' ), 10, 3 );

		// filter bookings global availability
		add_filter( 'pre_update_option_wc_global_booking_availability', array( $this, 'before_update_global_availability' ), 10, 2 );

		add_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ), 10, 2 );

		// filters the product type
		add_filter( 'product_type_selector', array( $this, 'filter_product_type' ), 99 );

		// modify the booking status views
		add_filter( 'views_edit-wc_booking', array( $this, 'booking_status_views' ) );

		// setup dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_vendor_dashboard_widget' ), 99999 );

		// redirect the page after creating bookings
		add_filter( 'wp_redirect', array( $this, 'create_booking_redirect' ) );

		return true;
	}

	/**
	 * Add dashboard widgets for vendors
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @return bool
	 */
	public function add_vendor_dashboard_widget() {
		if ( WC_Product_Vendors_Utils::is_bookings_enabled() ) {
			wp_add_dashboard_widget(
				'wcpv_vendor_bookings_dashboard_widget',
				__( 'Recent Bookings', 'woocommerce-product-vendors' ),
				array( $this, 'render_bookings_dashboard_widget' )
			);
		}

		return true;
	}

	/**
	 * Renders the bookings dashboard widgets for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_bookings_dashboard_widget() {
		if ( false === ( $bookings = get_transient( 'wcpv_reports_bookings_wg_' . WC_Product_Vendors_Utils::get_logged_in_vendor() ) ) ) {
			$args = array(
				'post_type'      => 'wc_booking',
				'posts_per_page' => 20,
				'post_status'    => 'any',
			);

			$bookings = get_posts( apply_filters( 'wcpv_bookings_list_widget_args', $args ) );
			
			if ( ! empty( $bookings ) ) {
				// filter out only bookings with products of the vendor
				$bookings = array_filter( $bookings, array( $this, 'filter_booking_products' ) );
			}

			set_transient( 'wcpv_reports_bookings_wg_' . WC_Product_Vendors_Utils::get_logged_in_vendor(), $bookings, DAY_IN_SECONDS );
		}

		if ( empty( $bookings ) ) {
			echo '<p>' . __( 'There are no bookings available.', 'woocommerce-product-vendors' ) . '</p>';

			return;
		}
		?>
		
		<table class="wcpv-vendor-bookings-widget wp-list-table widefat fixed striped posts">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Booking ID', 'woocommerce-product-vendors' ); ?></th>
					<th><?php esc_html_e( 'Booked Product', 'woocommerce-product-vendors' ); ?></th>
					<th><?php esc_html_e( '# of Persons', 'woocommerce-product-vendors' ); ?></th>
					<th><?php esc_html_e( 'Booked By', 'woocommerce-product-vendors' ); ?></th>
					<th><?php esc_html_e( 'Order', 'woocommerce-product-vendors' ); ?></th>
					<th><?php esc_html_e( 'Start Date', 'woocommerce-product-vendors' ); ?></th>
					<th><?php esc_html_e( 'End Date', 'woocommerce-product-vendors' ); ?></th>
				</tr>
			</thead>

			<tbody id="the-list">
				<?php
				foreach( $bookings as $booking ) {
					$booking_item = get_wc_booking( $booking->ID );
					?>
					<tr>
						<td><a href="<?php echo get_edit_post_link( $booking->ID ); ?>" title="<?php esc_attr_e( 'Edit Booking', 'woocommerce-product-vendors' ); ?>"><?php printf( __( 'Booking #%d', 'woocommerce-product-vendors' ), $booking->ID ); ?></a></td>

						<td><a href="<?php echo get_edit_post_link( $booking_item->get_product()->id ); ?>" title="<?php esc_attr_e( 'Edit Product', 'woocommerce-product-vendors' ); ?>"><?php echo $booking_item->get_product()->post->post_title; ?></a></td>

						<td>
							<?php 
							if ( $booking_item->has_persons() ) {
								echo $booking_item->get_persons_total();
							} else {
								esc_html_e( 'N/A', 'woocommerce-product-vendors' );
							} ?>
						</td>

						<td>
							<?php
							if ( $booking_item->get_customer() ) {
							?>
								<a href="mailto:<?php echo esc_attr( $booking_item->get_customer()->email ); ?>"><?php echo $booking_item->get_customer()->name; ?></a>
							<?php
							} else {
								esc_html_e( 'N/A', 'woocommerce-product-vendors' );
							} ?>
						</td>

						<td>
							<?php
							if ( $booking_item->get_order() ) {
							?>
								<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-order&id=' . $booking_item->order_id ); ?>" title="<?php esc_attr_e( 'Order Detail', 'woocommerce-product-vendors' ); ?>"><?php printf( __( '#%d', 'woocommerce-product-vendors' ), $booking_item->order_id ); ?></a> &mdash; <?php echo $booking_item->get_order()->get_status(); ?>
							<?php
							} else {
								esc_html_e( 'N/A', 'woocommerce-product-vendors' );
							}
							?>
						</td>

						<td><?php echo $booking_item->get_start_date(); ?></td>
						<td><?php echo $booking_item->get_end_date(); ?></td>
					</tr>
					<?php
				}
				?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Filters the product ids for logged in vendor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $term the slug of the term
	 * @return array $ids product ids
	 */
	public function filter_booking_products( $item ) {
		$product_ids = WC_Product_Vendors_Utils::get_vendor_product_ids();

		$booking_item = get_wc_booking( $item->ID );

		if ( is_object( $booking_item ) && is_object( $booking_item->get_product() ) && $booking_item->get_product()->id && in_array( $booking_item->get_product()->id, $product_ids ) ) {
			return $item;
		}
	}
	
	/**
	 * When saving global availability rules, save it in vendor meta as well
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @param array $new_values
	 * @param array $old_values
	 * @return array $new_values
	 */
	public function before_update_global_availability( $new_values, $old_values ) {
		remove_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ) );
		$old_values = get_option( 'wc_global_booking_availability', array() );
		add_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ), 10, 2 );

		if ( ! empty( $_POST['bookings_availability_submitted'] ) ) {
			$availability = array();
			$row_size     = isset( $_POST['wc_booking_availability_type'] ) ? sizeof( $_POST['wc_booking_availability_type'] ) : 0;
			for ( $i = 0; $i < $row_size; $i ++ ) {
				$availability[ $i ]['type']     = wc_clean( $_POST['wc_booking_availability_type'][ $i ] );
				$availability[ $i ]['bookable'] = wc_clean( $_POST['wc_booking_availability_bookable'][ $i ] );
				$availability[ $i ]['priority'] = intval( $_POST['wc_booking_availability_priority'][ $i ] );

				switch ( $availability[ $i ]['type'] ) {
					case 'custom' :
						$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_date'][ $i ] );
						$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_date'][ $i ] );
					break;
					case 'months' :
						$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_month'][ $i ] );
						$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_month'][ $i ] );
					break;
					case 'weeks' :
						$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_week'][ $i ] );
						$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_week'][ $i ] );
					break;
					case 'days' :
						$availability[ $i ]['from'] = wc_clean( $_POST['wc_booking_availability_from_day_of_week'][ $i ] );
						$availability[ $i ]['to']   = wc_clean( $_POST['wc_booking_availability_to_day_of_week'][ $i ] );
					break;
					case 'time' :
					case 'time:1' :
					case 'time:2' :
					case 'time:3' :
					case 'time:4' :
					case 'time:5' :
					case 'time:6' :
					case 'time:7' :
						$availability[ $i ]['from'] = wc_booking_sanitize_time( $_POST['wc_booking_availability_from_time'][ $i ] );
						$availability[ $i ]['to']   = wc_booking_sanitize_time( $_POST['wc_booking_availability_to_time'][ $i ] );
					break;
					case 'time:range' :
						$availability[ $i ]['from'] = wc_booking_sanitize_time( $_POST['wc_booking_availability_from_time'][ $i ] );
						$availability[ $i ]['to']   = wc_booking_sanitize_time( $_POST['wc_booking_availability_to_time'][ $i ] );

						$availability[ $i ]['from_date'] = wc_clean( $_POST['wc_booking_availability_from_date'][ $i ] );
						$availability[ $i ]['to_date']   = wc_clean( $_POST['wc_booking_availability_to_date'][ $i ] );
					break;
				}

				if ( isset( $_POST['wc_booking_availability_vendor'][ $i ] ) ) {
					$availability[ $i ]['vendor'] = absint( $_POST['wc_booking_availability_vendor'][ $i ] );
				} elseif ( WC_Product_Vendors_Utils::is_vendor() ) {
					$availability[ $i ]['vendor'] = absint( WC_Product_Vendors_Utils::get_logged_in_vendor() );
				}
			}
		}

		$modified_values = $availability;

		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			if ( is_array( $modified_values ) ) {
				$modified_old_values = array();

				// add the rest of the rules back in
				foreach( $old_values as $old_value ) {
					// skip the ones that belongs to current vendor
					if ( ! empty( $old_value['vendor'] ) && (int) WC_Product_Vendors_Utils::get_logged_in_vendor() === $old_value['vendor'] ) {
						continue;
					}

					$modified_old_values[] = $old_value;
				}
			}
			
			$modified_values = array_merge( $modified_values, $modified_old_values );
		}

		return $modified_values;
	}

	/**
	 * Filter out only rules for the vendor
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @param array $option
	 * @return array $option
	 */
	public function before_display_global_availability( $option = false ) {
		if ( WC_Product_Vendors_Utils::is_vendor() && is_admin() ) {
			remove_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ) );
			$options = get_option( 'wc_global_booking_availability', array() );
			add_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ), 10, 2 );

			$filtered_options = array();

			foreach( $options as $option ) {
				// only add the ones that belong to current vendor
				if ( ! empty( $option['vendor'] ) && (int) WC_Product_Vendors_Utils::get_logged_in_vendor() === $option['vendor'] ) {
					$filtered_options[] = $option;
				}
			}

			return $filtered_options;
		}

		return false;
	}

	/**
	 * Filters the global availability rules for specific vendor's products only
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @param array $rules
	 * @param int $for_resource
	 * @param object $booking
	 * @return array $availability_rules
	 */	
	public function filter_availability_rules( $rules, $for_resource, $booking ) {
		// Rule types
		$resource_rules        = array();
		$filtered_global_rules = array();
		$product_rules         = $booking->wc_booking_availability;
		remove_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ) );
		$global_rules          = get_option( 'wc_global_booking_availability', array() );
		add_filter( 'pre_option_wc_global_booking_availability', array( $this, 'before_display_global_availability' ), 10, 2 );

		// to prevent duplicate queries from bookings, cache vendor data into
		// super global
		if ( ! isset( $GLOBALS['wcpv_is_vendor_booking_product_' . $booking->id] ) ) {
			$GLOBALS['wcpv_is_vendor_booking_product_' . $booking->id] = false;

			if ( $vendor = WC_Product_Vendors_Utils::is_vendor_product( $booking->id ) ) {
				$GLOBALS['wcpv_is_vendor_booking_product_' . $booking->id] = $vendor;
			}
		}

		if ( $vendor = $GLOBALS['wcpv_is_vendor_booking_product_' . $booking->id] ) {
			// filter rules that belong to this vendor's product
			if ( ! empty( $global_rules ) ) {
				foreach( $global_rules as $rule ) {
					if ( ! empty( $rule['vendor'] ) && $vendor[0]->term_id === $rule['vendor'] ) {
						$filtered_global_rules[] = $rule;
					}
				}
			}
		} else {
			// filter rules that don't belong to this vendor's product
			if ( ! empty( $global_rules ) ) {
				foreach( $global_rules as $rule ) {
					if ( empty( $rule['vendor'] ) ) {
						$filtered_global_rules[] = $rule;
					}
				}
			}
		}

		// Get availability of each resource - no resource has been chosen yet
		if ( $booking->has_resources() && ! $for_resource ) {
			$resources      = $booking->get_resources();
			$resource_rules = array();

			if ( $booking->get_default_availability() ) {
				// If all blocks are available by default, we should not hide days if we don't know which resource is going to be used.
			} else {
				foreach ( $resources as $resource ) {
					$resource_rule = (array) get_post_meta( $resource->ID, '_wc_booking_availability', true );
					$resource_rules = array_merge( $resource_rules, $resource_rule );
				}
			}

		// Standard handling
		} elseif ( $for_resource ) {
			$resource_rules = (array) get_post_meta( $for_resource, '_wc_booking_availability', true );
		}

		$availability_rules = array_filter( array_reverse( array_merge( WC_Product_Booking_Rule_Manager::process_availability_rules( $resource_rules, 'resource' ), WC_Product_Booking_Rule_Manager::process_availability_rules( $product_rules, 'product' ), WC_Product_Booking_Rule_Manager::process_availability_rules( $filtered_global_rules, 'global' ) ) ) );

		usort( $availability_rules, array( $booking, 'priority_sort' ) );

		return $availability_rules;
	}

	/**
	 * Filters the product type
	 *
	 * @access public
	 * @since 2.0.9
	 * @version 2.0.9
	 * @param array $types
	 * @return array $post_type_args
	 */
	public function filter_product_type( $types ) {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() && ! WC_Product_Vendors_Utils::is_bookings_enabled() ) {
			unset( $types['booking'] );
		}

		return $types;
	}

	/**
	 * Modifies the booking status views
	 *
	 * @access public
	 * @since 2.0.9
	 * @version 2.0.9
	 * @param array $views
	 * @return array $post_type_args
	 */
	public function booking_status_views( $views ) {
		global $typenow;

		if ( WC_Product_Vendors_Utils::auth_vendor_user() && 'wc_booking' === $typenow ) {
			$new_views = array();

			// remove the count from each status
			foreach( $views as $k => $v ) {
				$new_views[$k] = preg_replace( '/\(\d+\)/', '', $v );
			}

			$views = $new_views;
		}

		return $views;
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
			if ( ! WC_Product_Vendors_Utils::is_bookings_enabled() ) {
				remove_menu_page( 'edit.php?post_type=wc_booking' );

				// remove create bookings menu page
				remove_submenu_page( 'edit.php?post_type=wc_booking', 'create_booking' );

				return;
			}
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
		// remove create bookings menu page
		remove_submenu_page( 'edit.php?post_type=wc_booking', 'wc_bookings_global_availability' );

		return true;
	}

	/**
	 * Adds the submenu page
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @return bool
	 */
	public function add_bookings_global_availability_menu() {
		add_submenu_page( 'edit.php?post_type=wc_booking', __( 'Global Availability', 'woocommerce-product-vendors' ), __( 'Global Availability', 'woocommerce-product-vendors' ), 'manage_bookings', 'wcpv_bookings_global_availability', array( $this, 'global_availability_page' ) );

		return true;
	}

	/**
	 * Renders the global availability page
	 *
	 * @access public
	 * @since 2.1.0
	 * @version 2.1.0
	 * @return bool
	 */
	public function global_availability_page() {
		global $wpdb, $wp_scripts;

		wp_enqueue_script( 'jquery-ui-sortable' );
		wp_enqueue_script( 'wc_bookings_writepanel_js' );
		wp_enqueue_script( 'wc_bookings_settings_js' );

		$jquery_version = isset( $wp_scripts->registered['jquery-ui-core']->ver ) ? $wp_scripts->registered['jquery-ui-core']->ver : '1.9.2';

		wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/' . $jquery_version . '/themes/smoothness/jquery-ui.css' );

		include( 'views/html-bookings-global-availability-settings.php' );

		return true;
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
	public function maybe_remove_wc_booking_post_type( $args ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			if ( ! WC_Product_Vendors_Utils::is_bookings_enabled() ) {
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
	public function maybe_remove_bookable_person_post_type( $args ) {
		if ( WC_Product_Vendors_Utils::is_vendor() ) {
			if ( ! WC_Product_Vendors_Utils::is_bookings_enabled() ) {
				$args['capability_type'] = 'manage_bookable_person';
			}
		}

		return $args;
	}

	public function create_booking_redirect( $location ) {
		if ( ! WC_Product_Vendors_Utils::is_vendor() ) {
			return $location;
		}

		if ( ! is_admin() ) {
			return $location;
		}

		// most likely an admin, no need to redirect
		if ( current_user_can( 'manage_options' ) ) {
			return $location;
		}

		if ( ! WC_Product_Vendors_Utils::is_bookings_enabled() ) {
			return $location;
		}

		if ( preg_match( '/\bpost=(\d+)/', $location, $matches  ) ) {
			// check the post type
			$post = get_post( $matches[1] );

			if ( 'shop_order' === $post->post_type ) {
				wp_safe_redirect( admin_url( 'admin.php?page=wcpv-vendor-order&id=' . $post->ID ) );
				exit;
			}
		}

		return $location;
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
