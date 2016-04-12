<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Vendor Dashboard Class.
 *
 * A class that handles the dashboard for vendors.
 *
 * @category Dashboard
 * @package  WooCommerce Product Vendors/Vendor Dashboard
 * @version  2.0.0
 */
class WC_Product_Vendors_Vendor_Dashboard {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		// setup the vendor admin pages
		add_action( 'admin_init', array( $this, 'setup_vendor_dashboard' ) );

		// setup dashboard widget
		add_action( 'wp_dashboard_setup', array( $this, 'add_vendor_dashboard_widget' ) );

		return true;
	}

	/**
	 * Setup dashboard for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function setup_vendor_dashboard() {
		// don't display the added capabilites in profile
		add_filter( 'additional_capabilities_display', '__return_false' );

		// remove the color scheme picker in profile
		remove_action( 'admin_color_scheme_picker', 'admin_color_scheme_picker' );

		// remove all dashboard widgets
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_plugins', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_primary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_secondary', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_incoming_links', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_drafts', 'dashboard', 'side' );
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal');
		remove_meta_box( 'woocommerce_dashboard_recent_reviews', 'dashboard', 'normal' );
		remove_meta_box( 'woocommerce_dashboard_status', 'dashboard', 'normal' );

		// remove welcome panel
		remove_action( 'welcome_panel', 'wp_welcome_panel' );

		// remove update nag
		remove_action( 'admin_notices', 'update_nag', 3 );

			// remove plugin update nags
		remove_action( 'load-update-core.php', 'wp_update_plugins', 999 );

		// remove theme update nags
		remove_action( 'load-update-core.php', 'wp_update_themes', 999 );

		// remove footer thankyou message from WP
		add_filter( 'admin_footer_text', '__return_null' );

		// remove footer WP version
		add_filter( 'update_footer', '__return_null', 11 );

		// set vendor dashboard columns to only 1
		add_filter( 'screen_layout_columns', array( $this, 'set_dashboard_columns' ) );
		add_filter( 'get_user_option_screen_layout_dashboard', array( $this, 'set_user_dashboard_columns' ) );

		// check if user is pending vendor - add message
		if ( WC_Product_Vendors_Utils::is_pending_vendor() ) {
			add_action( 'welcome_panel', array( $this, 'add_pending_vendor_message' ) );

			// remove screen options tab
			add_filter( 'screen_options_show_screen', '__return_false' );

			// remove screen help tab
			add_filter( 'contextual_help', array( $this, 'remove_help_tabs' ), 999, 3 );
		}

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

		if ( ! empty( $vendor_data ) && 'yes' === $vendor_data['enable_bookings'] && class_exists( 'WC_Bookings' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Add dashboard widgets for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_vendor_dashboard_widget() {
		wp_add_dashboard_widget(
			'wcpv_vendor_sales_dashboard_widget',
			__( 'Sales Summary', 'woocommerce-product-vendors' ),
			array( $this, 'render_sales_dashboard_widget' )
		);

		if ( $this->is_bookings_enabled() ) {
			wp_add_dashboard_widget(
				'wcpv_vendor_bookings_dashboard_widget',
				__( 'Recent Bookings', 'woocommerce-product-vendors' ),
				array( $this, 'render_bookings_dashboard_widget' )
			);
		}

		return true;
	}

	/**
	 * Renders the sales dashboard widgets for vendors
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function render_sales_dashboard_widget() {
		global $wpdb;

		$vendor_product_ids = WC_Product_Vendors_Utils::get_vendor_product_ids();

		$sql = "SELECT SUM( commission.product_amount ) AS total_product_amount FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";

		$sql .= " LEFT JOIN {$wpdb->posts} AS posts";
		$sql .= " ON commission.order_id = posts.ID";
		$sql .= " WHERE 1=1";
		$sql .= " AND commission.vendor_id = %d";
		$sql .= " AND MONTH( commission.order_date ) = MONTH( NOW() )";

		if ( false === ( $total_product_amount = get_transient( 'wcpv_reports_wg_sales_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) ) ) {
			$total_product_amount = $wpdb->get_var( $wpdb->prepare( $sql, WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) );

			set_transient( 'wcpv_reports_wg_sales_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), $total_product_amount, DAY_IN_SECONDS );
		}

		// Get top seller
		$query            = array();
		$query['fields']  = "SELECT SUM( order_item_meta.meta_value ) as qty, order_item_meta_2.meta_value as product_id
			FROM {$wpdb->posts} as posts";
		$query['join']    = "INNER JOIN {$wpdb->prefix}woocommerce_order_items AS order_items ON posts.ID = order_id ";
		$query['join']   .= "INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON order_items.order_item_id = order_item_meta.order_item_id ";
		$query['join']   .= "INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta_2 ON order_items.order_item_id = order_item_meta_2.order_item_id ";
		$query['where']   = "WHERE posts.post_type IN ( '" . implode( "','", wc_get_order_types( 'order-count' ) ) . "' ) ";
		$query['where']  .= "AND posts.post_status IN ( 'wc-" . implode( "','wc-", apply_filters( 'wcpv_reports_order_statuses', array( 'completed', 'processing', 'on-hold' ) ) ) . "' ) ";
		$query['where']  .= "AND order_item_meta.meta_key = '_qty' ";
		$query['where']  .= "AND order_item_meta_2.meta_key = '_product_id' ";
		$query['where']  .= "AND posts.post_date >= '" . date( 'Y-m-01', current_time( 'timestamp' ) ) . "' ";
		$query['where']  .= "AND posts.post_date <= '" . date( 'Y-m-d H:i:s', current_time( 'timestamp' ) ) . "' ";
		$query['where']  .= "AND order_item_meta_2.meta_value IN ( '" . implode( "','", $vendor_product_ids ) . "' ) ";
		$query['groupby'] = "GROUP BY product_id";
		$query['orderby'] = "ORDER BY qty DESC";
		$query['limits']  = "LIMIT 1";

		$top_seller = $wpdb->get_row( implode( ' ', apply_filters( 'wcpv_dashboard_status_widget_top_seller_query', $query ) ) );

		// Commission
		if ( WC_Product_Vendors_Utils::commission_table_exists() ) {

			$sql = "SELECT SUM( commission.total_commission_amount ) FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";
			$sql .= " WHERE 1=1";
			$sql .= " AND commission.vendor_id = %d";
			$sql .= " AND commission.commission_status = 'paid'";
			$sql .= " AND MONTH( commission.order_date ) = MONTH( NOW() )";

			if ( false === ( $commission = get_transient( 'wcpv_reports_wg_commission_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) ) ) {
				$commission = $wpdb->get_var( $wpdb->prepare( $sql, WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) );

				set_transient( 'wcpv_reports_wg_commission_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), $commission, DAY_IN_SECONDS );
			}
		}

		// Awaiting shipping
		if ( WC_Product_Vendors_Utils::commission_table_exists() ) {

			$sql = "SELECT COUNT( commission.id ) FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";
			$sql .= " INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta ON commission.order_item_id = order_item_meta.order_item_id";
			$sql .= " WHERE 1=1";
			$sql .= " AND commission.vendor_id = %d";
			$sql .= " AND order_item_meta.meta_key = '_fulfillment_status'";
			$sql .= " AND order_item_meta.meta_value = 'unfulfilled'";

			if ( false === ( $unfulfilled_products = get_transient( 'wcpv_reports_wg_fulfillment_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) ) ) {
				$unfulfilled_products = $wpdb->get_var( $wpdb->prepare( $sql, WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) );

				set_transient( 'wcpv_reports_wg_fulfillment_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), $unfulfilled_products, DAY_IN_SECONDS );
			}
		}

		// Counts
		$on_hold_count    = 0;
		$processing_count = 0;

		foreach ( wc_get_order_types( 'order-count' ) as $type ) {
			$counts           = (array) wp_count_posts( $type );
			$on_hold_count    += isset( $counts['wc-on-hold'] ) ? $counts['wc-on-hold'] : 0;
			$processing_count += isset( $counts['wc-processing'] ) ? $counts['wc-processing'] : 0;
		}

		$stock          = absint( max( get_option( 'woocommerce_notify_low_stock_amount' ), 1 ) );
		$nostock        = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );
		$transient_name = 'wc_low_stock_count';

		if ( false === ( $lowinstock_count = get_transient( 'wcpv_reports_wg_lowstock_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) ) ) {
			$query_from = apply_filters( 'wcpv_report_low_in_stock_query_from', "FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
				INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
				WHERE 1=1
				AND posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status = 'publish'
				AND postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes'
				AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$stock}'
				AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) > '{$nostock}'
				AND posts.ID IN ( '" . implode( "','", $vendor_product_ids ) . "' )
			" );

			$lowinstock_count = absint( $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" ) );

			set_transient( 'wcpv_reports_wg_lowstock_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), $lowinstock_count, DAY_IN_SECONDS );
		}

		if ( false === ( $outofstock_count = get_transient( 'wcpv_reports_wg_nostock_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) ) ) {
			$query_from = apply_filters( 'wcpv_report_out_of_stock_query_from', "FROM {$wpdb->posts} as posts
				INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
				INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
				WHERE 1=1
				AND posts.post_type IN ( 'product', 'product_variation' )
				AND posts.post_status = 'publish'
				AND postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes'
				AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$nostock}'
				AND posts.ID IN ( '" . implode( "','", $vendor_product_ids ) . "' )
			" );

			$outofstock_count = absint( $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" ) );
			
			set_transient( 'wcpv_reports_wg_nostock_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), $outofstock_count, DAY_IN_SECONDS );
		}
		?>
		<ul class="wc_status_list">
			<?php if ( WC_Product_Vendors_Utils::is_admin_vendor() ) { ?>
				<li class="sales-this-month">
					<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-reports&range=month' ); ?>">
						<?php printf( __( "<strong>%s</strong> net sales this month", 'woocommerce-product-vendors' ), wc_price( $total_product_amount ) ); ?>
					</a>
				</li>
			<?php } ?>

			<?php
			if ( empty( $top_seller ) || ! $top_seller->qty ) {
				$top_seller_id = 0;
				$top_seller_title = __( 'N/A', 'woocommerce-product-vendors' );
				$top_seller_qty = '0';
			} else {
				$top_seller_id = $top_seller->product_id;
				$top_seller_title = get_the_title( $top_seller->product_id );
				$top_seller_qty = $top_seller->qty;
			}
			?>
				<li class="best-seller-this-month">
					<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-reports&tab=orders&report=sales_by_product&range=month&product_ids=' . $top_seller_id ); ?>">
						<?php printf( __( "%s top seller this month (sold %d)", 'woocommerce-product-vendors' ), "<strong>" . $top_seller_title . "</strong>", $top_seller_qty ); ?>
					</a>
				</li>

			<?php if ( WC_Product_Vendors_Utils::is_admin_vendor() ) { ?>
				<li class="commission">
					<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-orders' ); ?>">
						<?php printf( __( "<strong>%s</strong> commission this month", 'woocommerce-product-vendors' ), wc_price( $commission ) ); ?>
					</a>
				</li>
			<?php } ?>

			<li class="unfulfilled-products">
				<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-orders' ); ?>">
					<?php printf( _n( "<strong>%s product</strong> awaiting fulfillment", "<strong>%s products</strong> awaiting fulfillment", $unfulfilled_products, 'woocommerce-product-vendors' ), $unfulfilled_products ); ?>
				</a>
			</li>

			<li class="low-in-stock">
				<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-reports&tab=stock&report=low_in_stock' ); ?>">
					<?php printf( _n( "<strong>%s product</strong> low in stock", "<strong>%s products</strong> low in stock", $lowinstock_count, 'woocommerce-product-vendors' ), $lowinstock_count ); ?>
				</a>
			</li>
			
			<li class="out-of-stock">
				<a href="<?php echo admin_url( 'admin.php?page=wcpv-vendor-reports&tab=stock&report=out_of_stock' ); ?>">
					<?php printf( _n( "<strong>%s product</strong> out of stock", "<strong>%s products</strong> out of stock", $outofstock_count, 'woocommerce-product-vendors' ), $outofstock_count ); ?>
				</a>
			</li>

			<?php do_action( 'wcpv_after_sales_dashboard_status_widget' ); ?>
		</ul>
		<?php
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
		if ( false === ( $bookings = get_transient( 'wcpv_reports_bookings_wg_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ) ) ) ) {
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

			set_transient( 'wcpv_reports_bookings_wg_' . WC_Product_Vendors_Utils::get_logged_in_vendor( 'id' ), $bookings, DAY_IN_SECONDS );
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

		if ( is_object( $booking_item ) && $booking_item->get_product()->id && in_array( $booking_item->get_product()->id, $product_ids ) ) {
			return $item;
		}
	}

	/**
	 * Set dashboard columns to 1
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $columns
	 */
	public function set_dashboard_columns( $columns ) {
		$columns['dashboard'] = 1;
		
		return $columns;
	}

	/**
	 * Set dashboard columns to 1
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return int
	 */
	public function set_user_dashboard_columns() {
		return 1;
	}
}

new WC_Product_Vendors_Vendor_Dashboard();
