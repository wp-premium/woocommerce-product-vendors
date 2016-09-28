<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	include_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

/**
 * Orders List Class.
 *
 * A class that generates the orders list for vendors.
 *
 * @category Order
 * @package  WooCommerce Product Vendors/Vendor Orders List
 * @version  2.0.0
 */
class WC_Product_Vendors_Vendor_Orders_List extends WP_List_Table {
	private $vendor_id;
	private $vendor_data;

	/**
	 * Init
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		global $wpdb;

		parent::__construct( array(
			'singular'  => 'order',
			'plural'    => 'orders',
			'ajax'      => false,
		) );

		$this->vendor_id   = WC_Product_Vendors_Utils::get_logged_in_vendor();
		$this->vendor_data = WC_Product_Vendors_Utils::get_vendor_data_from_user();

    	return true;
	}

	/**
	 * Prepares the items for display
	 *
	 * @todo this needs to be cached
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function prepare_items() {
		global $wpdb;

		// check if table exists before continuing
		if ( ! WC_Product_Vendors_Utils::commission_table_exists() ) {
			return;
		}

		$columns  = $this->get_columns();
		$hidden   = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		
		$this->process_bulk_action();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$orderby = ! empty( $_REQUEST[ 'orderby' ] ) ? sanitize_text_field( $_REQUEST[ 'orderby' ] ) : 'order_id';
		$order   = ( ! empty( $_REQUEST[ 'order' ] ) && $_REQUEST[ 'order' ] === 'asc' ) ? 'ASC' : 'DESC';

		$items_per_page = $this->get_items_per_page( 'orders_per_page', apply_filters( 'wcpv_orders_list_default_item_per_page', 20 ) );

		$current_page = $this->get_pagenum();
		
		$sql = "SELECT COUNT(commission.id) FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";

		$sql .= " WHERE 1=1";

		$sql .= " AND `vendor_id` = {$this->vendor_id}";

		// check if it is a search
		if ( ! empty( $_REQUEST['s'] ) ) {
			$order_id = absint( $_REQUEST['s'] );

			$sql .= " AND `order_id` = {$order_id}";

		} else {

			if ( ! empty( $_REQUEST['m'] ) ) {

				$year  = absint( substr( $_REQUEST['m'], 0, 4 ) );
				$month = absint( substr( $_REQUEST['m'], 4, 2 ) );

				$time_filter = " AND MONTH( commission.order_date ) = {$month} AND YEAR( commission.order_date ) = {$year}";

				$sql .= $time_filter;
			}

			if ( ! empty( $_REQUEST['commission_status'] ) ) { 
				$commission_status = esc_sql( $_REQUEST['commission_status'] );

				$status_filter = " AND `commission_status` = '{$commission_status}'";
				
				$sql .= $status_filter; 
			}
		}

		$total_items = $wpdb->get_var( $sql );

		$this->set_pagination_args( array(
			'total_items' => (double) $total_items,
			'per_page'    => $items_per_page,
		) );

		$offset = ( $current_page - 1 ) * $items_per_page;

		$sql = "SELECT * FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";

		$sql .= " WHERE 1=1";

		$sql .= " AND `vendor_id` = {$this->vendor_id}";

		// check if it is a search
		if ( ! empty( $_REQUEST['s'] ) ) {
			$order_id = absint( $_REQUEST['s'] );

			$sql .= " AND `order_id` = {$order_id}";

		} else {

			if ( ! empty( $_REQUEST['m'] ) ) {
				$sql .= $time_filter;
			}

			if ( ! empty( $_REQUEST['commission_status'] ) ) { 
				$sql .= $status_filter;
			}
		}

		$sql .= " ORDER BY `{$orderby}` {$order}";

		$sql .= " LIMIT {$items_per_page}";

		$sql .= " OFFSET {$offset}";

		$data = $wpdb->get_results( $sql );

		$this->items = $data;

		return true;
	}

	/**
	 * Adds additional views
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param mixed $views
	 * @return bool
	 */
	public function get_views() {
		$views = array(
			'all' => '<li class="all"><a href="' . admin_url( 'admin.php?page=wcpv-vendor-orders' ) . '">' . esc_html__( 'Show All', 'woocommerce-product-vendors' ) . '</a></li>',
		);

		return $views;
	}

	/**
	 * Generate the table navigation above or below the table
	 *
	 * @since 3.1.0
	 * @access protected
	 * @param string $which
	 */
	protected function display_tablenav( $which ) {
		if ( 'top' === $which ) {
			wp_nonce_field( 'bulk-' . $this->_args['plural'], '_wpnonce', false );
		}
		?>
		<div class="tablenav <?php echo esc_attr( $which ); ?>">

		<?php if ( $this->has_items() ): ?>
		<div class="alignleft actions bulkactions">
			<?php $this->bulk_actions( $which ); ?>
		</div>
		<?php endif;
		$this->extra_tablenav( $which );
		$this->pagination( $which );
		?>

		<br class="clear" />
		</div>
		<?php
	}
	
	/**
	 * Adds filters to the table
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $position whether top/bottom
	 * @return bool
	 */
	public function extra_tablenav( $position ) {
		if ( 'top' === $position ) {
		?>
			<div class="alignleft actions">
				<?php
					$this->months_dropdown( 'orders' );
				?>
			</div>

			<div class="alignleft actions">
				<?php
					$this->status_dropdown( 'orders' );

					submit_button( __( 'Filter', 'woocommerce-product-vendors' ), false, false, false );
				?>
			</div>
		<?php
		}
	}

	/**
	 * Displays the months filter
	 *
	 * @todo this needs to be cached
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function months_dropdown( $post_type ) {
		global $wpdb, $wp_locale;

		// check if table exists before continuing
		if ( ! WC_Product_Vendors_Utils::commission_table_exists() ) {
			return;
		}
		
		$months = $wpdb->get_results( "
			SELECT DISTINCT YEAR( commission.order_date ) AS year, MONTH( commission.order_date ) AS month
			FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission
			WHERE commission.vendor_id = " . $this->vendor_id . "
			ORDER BY commission.order_date DESC
		" );

		$month_count = count( $months );

		if ( ! $month_count || ( 1 === $month_count && 0 === $months[0]->month ) ) {
			return;
		}

		$m = isset( $_REQUEST[ 'm' ] ) ? (int) $_REQUEST[ 'm' ] : 0;
		?>
		<select name="m" id="filter-by-date">
			<option<?php selected( $m, 0 ); ?> value='0'><?php esc_html_e( 'Show all dates', 'woocommerce-product-vendors' ); ?></option>
			<?php
			foreach ( $months as $arc_row ) {
				if ( 0 === $arc_row->year ) {
					continue;
				}

				$month = zeroise( $arc_row->month, 2 );
				$year  = $arc_row->year;

				if ( '00' === $month || '0' === $year ) {
					continue;
				}
				
				printf( "<option %s value='%s'>%s</option>\n",
					selected( $m, $year . $month, false ),
					esc_attr( $arc_row->year . $month ),
					/* translators: 1: month name, 2: 4-digit year */
					sprintf( __( '%1$s %2$d', 'woocommerce-product-vendors' ), $wp_locale->get_month( $month ), $year )
				);
			}
			?>
		</select>
		
	<?php
	}

	/**
	 * Displays the paid status dropdown filter
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function status_dropdown( $post_type ) {
		$commission_status = isset( $_REQUEST[ 'commission_status' ] ) ? sanitize_text_field( $_REQUEST[ 'commission_status' ] ) : '';
	?>
		<select name="commission_status">
			<option <?php selected( $commission_status, '' ); ?> value=''><?php esc_html_e( 'Show all Commission Statuses', 'woocommerce-product-vendors' ); ?></option>
			<option <?php selected( $commission_status, 'unpaid' ); ?> value="unpaid"><?php esc_html_e( 'Unpaid', 'woocommerce-product-vendors' ); ?></option>
			<option <?php selected( $commission_status, 'paid' ); ?> value="paid"><?php esc_html_e( 'Paid', 'woocommerce-product-vendors' ); ?></option>
			<option <?php selected( $commission_status, 'void' ); ?> value="void"><?php esc_html_e( 'Void', 'woocommerce-product-vendors' ); ?></option>
		</select>
	<?php
		return true;
	}

	/**
	 * Defines the columns to show
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'                      => '<input type="checkbox" />',
			'order_id'                => __( 'Order', 'woocommerce-product-vendors' ),
			'order_status'            => __( 'Order Status', 'woocommerce-product-vendors' ),
			'order_date'              => __( 'Order Date', 'woocommerce-product-vendors' ),
			'shipping_address'        => __( 'Shipping', 'woocommerce-product-vendors' ),
			'product_name'            => __( 'Product', 'woocommerce-product-vendors' ),
			'total_commission_amount' => __( 'Commission', 'woocommerce-product-vendors' ),
			'commission_status'       => __( 'Commission Status', 'woocommerce-product-vendors' ),
			'paid_date'               => __( 'Paid Date', 'woocommerce-product-vendors' ),
			'fulfillment_status'      => __( 'Fulfillment Status', 'woocommerce-product-vendors' ),
		);

		return $columns;
	}

	/**
	 * Adds checkbox to each row
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $item
	 * @return mixed
	 */
	public function column_cb( $item ) {
		return sprintf( '<input type="checkbox" name="ids[%d]" value="%d" />', $item->id, $item->order_item_id );
	}

	/**
	 * Defines what data to show on each column
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $item
	 * @param string $column_name
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch( $column_name ) {

			case 'order_id' :
				$order = get_post( absint( $item->order_id ) );

				if ( is_object( $order ) ) {
					return '<a href="' . admin_url( 'admin.php?page=wcpv-vendor-order&id=' . absint( $item->order_id ) ) . '" class="wcpv-vendor-order-by-id">' . absint( $item->order_id ) . '</a>';
				}

				return sprintf( '%s ' . __( 'Order Not Found', 'woocommerce-product-vendors' ), '#' . absint( $item->order_id ) );

			case 'order_status' :
				$order = wc_get_order( $item->order_id );

				if ( is_object( $order ) ) {
					$order_status = $order->get_status();

					return sprintf( '<span class="wcpv-order-status-%s">%s</span>', esc_attr( $order_status ), ucwords( $order_status ) );
				} else {
					return __( 'N/A', 'woocommerce-product-vendors' );
				}
				
			case 'order_date' :
				$order = wc_get_order( absint( $item->order_id ) );

				$timezone = ! empty( $this->vendor_data['timezone'] ) ? sanitize_text_field( $this->vendor_data['timezone'] ) : '';

				if ( is_a( $order, 'WC_Order' ) ) {
					return WC_Product_Vendors_Utils::format_date( sanitize_text_field( $order->order_date ), $timezone );
				}

				return __( 'N/A', 'woocommerce-product-vendors' );

			case 'shipping_address' :
				$order = wc_get_order( absint( $item->order_id ) );

				if ( is_a( $order, 'WC_ORDER' ) ) {
					$address = $order->get_address( 'shipping' );

					return implode( ', ', $address ) . '<br /><small class="wcpv-shipping-method">' . esc_html__( 'Via', 'woocommerce-product-vendors' ) . ' ' . $order->get_shipping_method() . '</small>';
				}

				return esc_html__( 'Not Found', 'woocommerce-product-vendors' );

			case 'product_name' :
				$quantity = absint( $item->product_quantity );

				$var_attributes = '';
				$sku = '';

				// check if product is a variable product
				if ( ! empty( $item->variation_id ) ) {
					$product = get_product( absint( $item->variation_id ) );

					$attributes = maybe_unserialize( $item->variation_attributes );

					if ( ! empty( $attributes ) ) {
						foreach( $attributes as $name => $value ) {
							if ( version_compare( WC_VERSION, '2.6.0', '>=' ) ) {
								$name = wc_attribute_label( wc_sanitize_taxonomy_name( $name ) );
							}

							$var_attributes .= sprintf( __( '<br /><small>( %s: %s )</small>', 'woocommerce-product-vendors' ), $name, $value );
						}
					}

				} else {
					$product = get_product( absint( $item->product_id ) );

				}

				if ( is_object( $product ) && $product->get_sku() ) {
					$sku = sprintf( __( '%s %s: %s', 'woocommerce-product-vendors' ), '<br />', 'SKU', $product->get_sku() );  
				}

				if ( is_object( $product ) ) {
					return edit_post_link( $quantity . 'x ' . sanitize_text_field( $item->product_name ), '', '', absint( $item->product_id ) ) . $var_attributes . $sku;

				} elseif ( ! empty( $item->product_name ) ) {
					return $quantity . 'x ' . sanitize_text_field( $item->product_name );

				} else {
					return sprintf( '%s ' . __( 'Product Not Found', 'woocommerce-product-vendors' ), '#' . absint( $item->product_id ) );
				}

			case 'total_commission_amount' :
				return wc_price( sanitize_text_field( $item->total_commission_amount ) );

			case 'commission_status' :
				$status = __( 'N/A', 'woocommerce-product-vendors' );

				if ( 'unpaid' === $item->commission_status ) {
					$status = '<span class="wcpv-unpaid-status">' . esc_html__( 'UNPAID', 'woocommerce-product-vendors' ) . '</span>';
				}

				if ( 'paid' === $item->commission_status ) {
					$status = '<span class="wcpv-paid-status">' . esc_html__( 'PAID', 'woocommerce-product-vendors' ) . '</span>';
				}

				if ( 'void' === $item->commission_status ) {
					$status = '<span class="wcpv-void-status">' . esc_html__( 'VOID', 'woocommerce-product-vendors' ) . '</span>';
				}

				return $status;

			case 'paid_date' :
				$timezone = ! empty( $this->vendor_data['timezone'] ) ? sanitize_text_field( $this->vendor_data['timezone'] ) : '';

				return WC_Product_Vendors_Utils::format_date( sanitize_text_field( $item->paid_date ), $timezone );

			case 'fulfillment_status' :
				$status = WC_Product_Vendors_Utils::get_fulfillment_status( $item->order_item_id );
				$product = wc_get_product( $item->product_id );

				if ( is_object( $product ) && ( $product->is_virtual() || $product->is_downloadable() ) ) {
					return esc_html__( 'N/A', 'woocommerce-product-vendors' );
				}

				if ( $status && 'unfulfilled' === $status ) {
					$status = '<span class="wcpv-unfulfilled-status">' . esc_html__( 'UNFULFILLED', 'woocommerce-product-vendors' ) . '</span>';

				} elseif ( $status && 'fulfilled' === $status ) {
					$status = '<span class="wcpv-fulfilled-status">' . esc_html__( 'FULFILLED', 'woocommerce-product-vendors' ) . '</span>';
				
				} else {
					$status = esc_html__( 'N/A', 'woocommerce-product-vendors' );
				}

				return $status;

			default :
				return print_r( $item, true );
		}
	}

	/**
	 * Defines the hidden columns
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $columns
	 */
	public function get_hidden_columns() {
		// get user hidden columns
		$hidden = get_hidden_columns( $this->screen );

		$new_hidden = array();

		foreach( $hidden as $k => $v ) {
			if ( ! empty( $v ) ) {
				$new_hidden[] = $v;
			}
		}

		return array_merge( array(), $new_hidden );
	}

	/**
	 * Returns the columns that need sorting
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array $sort
	 */
	public function get_sortable_columns() {
		$sort = array(
			'commission_status' => array( 'commission_status', false ),
		);

		return $sort;
	}

	/**
	 * Display custom no items found text
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function no_items() {
		_e( 'No orders found.', 'woocommerce-product-vendors' );

		return true;
	}

	/**
	 * Add bulk actions
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function get_bulk_actions() {
		$actions = array(
			'fulfilled'   => __( 'Mark Fulfilled', 'woocommerce-product-vendors' ),
			'unfulfilled' => __( 'Mark Unfulfilled', 'woocommerce-product-vendors' ),
		);
		
		return $actions;
	}

	/**
	 * Processes bulk actions
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function process_bulk_action() {
		if ( empty( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'bulk-orders' ) ) {
			return;
		}

		if ( empty( $_REQUEST['ids'] ) ) {
			return;
		}

		if ( false === $this->current_action() ) {
			return;
		}

		$status = sanitize_text_field( $this->current_action() );

		$ids = array_map( 'absint', $_REQUEST['ids'] );

		$processed = 0;

		foreach( $ids as $id => $order_item_id ) {
			WC_Product_Vendors_Utils::set_fulfillment_status( absint( $order_item_id ), $this->current_action() );

			WC_Product_Vendors_Utils::send_fulfill_status_email( $this->vendor_data, $this->current_action(), $order_item_id );

			$processed++;
		}

		echo '<div class="notice-success notice"><p>' . sprintf( _n( '%d item processed.', '%d items processed', $processed, 'woocommerce-product-vendors' ), $processed ) . '</p></div>';

		WC_Product_Vendors_Utils::clear_reports_transients();
		
		return true;
	}

	/**
	 * Checks if order item is a variable product
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $item
	 * @return bool
	 */
	public function is_variable_product( $item ) {
		if ( ! empty( $item['variation_id'] ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Print column headers, accounting for hidden and sortable columns.
	 * this overrides WP core simply to make column headers use REQUEST instead of GET
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param bool $with_id Whether to set the id attribute or not
	 * @return bool
	 */
	public function print_column_headers( $with_id = true ) {
		list( $columns, $hidden, $sortable ) = $this->get_column_info();

		$current_url = set_url_scheme( 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
		$current_url = remove_query_arg( 'paged', $current_url );

		if ( isset( $_REQUEST['orderby'] ) ) {
			$current_orderby = $_REQUEST['orderby'];
		} else {
			$current_orderby = '';
		}

		if ( isset( $_REQUEST['order'] ) && 'desc' == $_REQUEST['order'] ) {
			$current_order = 'desc';
		} else {
			$current_order = 'asc';
		}

		if ( ! empty( $columns['cb'] ) ) {
			static $cb_counter = 1;
		
			$columns['cb'] = '<label class="screen-reader-text" for="cb-select-all-' . $cb_counter . '">' . esc_html__( 'Select All', 'woocommerce-product-vendors' ) . '</label>'
				. '<input id="cb-select-all-' . $cb_counter . '" type="checkbox" />';
		
			$cb_counter++;
		}

		foreach ( $columns as $column_key => $column_display_name ) {
			$class = array( 'manage-column', "column-$column_key" );

			$style = '';

			if ( in_array( $column_key, $hidden ) ) {
				$style = 'display:none;';
			}

			$style = ' style="' . $style . '"';

			if ( 'cb' == $column_key ) {
				$class[] = 'check-column';
			} elseif ( in_array( $column_key, array( 'posts', 'comments', 'links' ) ) ) {
				$class[] = 'num';
			}

			if ( isset( $sortable[ $column_key ] ) ) {
				list( $orderby, $desc_first ) = $sortable[ $column_key ];

				if ( $current_orderby == $orderby ) {
					$order = 'asc' == $current_order ? 'desc' : 'asc';
					$class[] = 'sorted';
					$class[] = $current_order;
				} else {
					$order = $desc_first ? 'desc' : 'asc';
					$class[] = 'sortable';
					$class[] = $desc_first ? 'asc' : 'desc';
				}

				$column_display_name = '<a href="' . esc_url( add_query_arg( compact( 'orderby', 'order' ), $current_url ) ) . '"><span>' . $column_display_name . '</span><span class="sorting-indicator"></span></a>';
			}

			$id = $with_id ? "id='$column_key'" : '';

			if ( ! empty( $class ) ) {
				$class = "class='" . join( ' ', $class ) . "'";
			}

			echo "<th scope='col' $id $class $style>$column_display_name</th>";
		}
	}
}
