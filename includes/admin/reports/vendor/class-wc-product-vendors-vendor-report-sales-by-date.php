<?php
/**
 * Report class responsible for handling sales by date reports.
 *
 * @since      2.0.0
 *
 * @package    WooCommerce Product Vendors
 * @subpackage WooCommerce Product Vendors/Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-admin-report.php' );

class WC_Product_Vendors_Vendor_Report_Sales_By_Date extends WC_Admin_Report {
	public $chart_colors = array();
	public $current_range;
	private $report_data;

	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		$current_range = ! empty( $_GET['range'] ) ? sanitize_text_field( $_GET['range'] ) : '7day';

		if ( ! in_array( $current_range, array( 'custom', 'year', 'last_month', 'month', '7day' ) ) ) {
			$current_range = '7day';
		}

		$this->current_range = $current_range;
	}

	/**
	 * Get the report data
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array of objects
	 */
	public function get_report_data() {
		if ( empty( $this->report_data ) ) {
			$this->query_report_data();
		}

		return $this->report_data;
	}

	/**
	 * Get the report based on parameters
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return array of objects
	 */
	public function query_report_data() {
		global $wpdb;

		$this->report_data = new stdClass;

		// check if table exists before continuing
		if ( ! WC_Product_Vendors_Utils::commission_table_exists() ) {
			return $this->report_data;
		}

		$sql = "SELECT * FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";

		$sql .= " WHERE 1=1";
		$sql .= " AND commission.vendor_id = %d";
		$sql .= " AND commission.commission_status != 'void'";

		switch( $this->current_range ) {
			case 'year' :
				$sql .= " AND YEAR( commission.order_date ) = YEAR( CURDATE() )";
				break;

			case 'last_month' :
				$sql .= " AND MONTH( commission.order_date ) = MONTH( NOW() ) - 1";
				break;

			case 'month' :
				$sql .= " AND MONTH( commission.order_date ) = MONTH( NOW() )";
				break;

			case 'custom' :
				$start_date = ! empty( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
				$end_date = ! empty( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';

				$sql .= " AND DATE( commission.order_date ) BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
				break;

			case 'default' :
			case '7day' :
				$sql .= " AND DATE( commission.order_date ) BETWEEN DATE_SUB( NOW(), INTERVAL 7 DAY ) AND NOW()";
				break;
		}

		if ( false === ( $results = get_transient( 'wcpv_reports_legend_' . WC_Product_Vendors_Utils::get_logged_in_vendor() . '_' . $this->current_range ) ) ) {
			
			// Enable big selects for reports
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );

			$results = $wpdb->get_results( $wpdb->prepare( $sql, WC_Product_Vendors_Utils::get_logged_in_vendor() ) );

			set_transient( 'wcpv_reports_legend_' . WC_Product_Vendors_Utils::get_logged_in_vendor() . '_' . $this->current_range, $results, DAY_IN_SECONDS );
		}

		$total_product_amount           = 0.00;
		$total_shipping_amount          = 0.00;
		$total_shipping_tax_amount      = 0.00;
		$total_product_tax_amount       = 0.00;
		$total_earned_commission_amount = 0.00;
		$total_commission_amount        = 0.00;

		$total_orders = array();

		foreach( $results as $data ) {

			$total_orders[] = $data->order_id;
			
			$total_product_amount           += (float) sanitize_text_field( $data->product_amount );
			$total_product_tax_amount       += (float) sanitize_text_field( $data->product_tax_amount );
			$total_shipping_amount          += (float) sanitize_text_field( $data->product_shipping_amount );
			$total_shipping_tax_amount      += (float) sanitize_text_field( $data->product_shipping_tax_amount );
			$total_earned_commission_amount += (float) sanitize_text_field( $data->total_commission_amount );

			// show only paid commissions
			if ( 'paid' === $data->commission_status ) {
				$total_commission_amount   += (float) sanitize_text_field( $data->total_commission_amount );
			}
		}

		$total_orders = count( array_unique( $total_orders ) );
		$total_sales = $total_product_amount + $total_product_tax_amount + $total_shipping_amount + $total_shipping_tax_amount;
		$net_sales = $total_sales - $total_product_tax_amount - $total_shipping_amount - $total_shipping_tax_amount;
		$total_tax_amount = $total_product_tax_amount + $total_shipping_tax_amount;

		$this->report_data->total_sales           = $total_sales;
		$this->report_data->net_sales             = wc_format_decimal( $net_sales );
		$this->report_data->average_sales         = wc_format_decimal( $net_sales / ( $this->chart_interval + 1 ), 2 );
		$this->report_data->total_orders          = $total_orders;
		$this->report_data->total_items           = count( $results );
		$this->report_data->total_shipping        = wc_format_decimal( $total_shipping_amount );
		$this->report_data->total_commission      = wc_format_decimal( $total_commission_amount );
		$this->report_data->total_earned          = wc_format_decimal( $total_earned_commission_amount );
		$this->report_data->total_tax             = wc_format_decimal( $total_tax_amount );
	}

	/**
	 * Get the legend for the main chart sidebar
	 * @return array
	 */
	public function get_chart_legend() {
		$legend = array();
		$data   = $this->get_report_data();

		switch ( $this->chart_groupby ) {
			case 'day' :
				$average_sales_title = sprintf( __( '%s average daily sales', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->average_sales ) . '</strong>' );
			break;
			case 'month' :
			default :
				$average_sales_title = sprintf( __( '%s average monthly sales', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->average_sales ) . '</strong>' );
			break;
		}

		$legend[] = array(
			'title'            => sprintf( __( '%s gross sales in this period', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->total_sales ) . '</strong>' ),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and including shipping and taxes.', 'woocommerce-product-vendors' ),
			'color'            => $this->chart_colors['sales_amount'],
			'highlight_series' => 4
		);

		$legend[] = array(
			'title'            => sprintf( __( '%s net sales in this period', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->net_sales ) . '</strong>' ),
			'placeholder'      => __( 'This is the sum of the order totals after any refunds and excluding shipping and taxes.', 'woocommerce-product-vendors' ),
			'color'            => $this->chart_colors['net_sales_amount'],
			'highlight_series' => 5
		);

		if ( $data->average_sales > 0 ) {
			$legend[] = array(
				'title'            => $average_sales_title,
				'color'            => $this->chart_colors['average'],
				'highlight_series' => 3
			);
		}

		$legend[] = array(
			'title'            => sprintf( __( '%s orders placed', 'woocommerce-product-vendors' ), '<strong>' . $data->total_orders . '</strong>' ),
			'color'            => $this->chart_colors['order_count'],
			'highlight_series' => 0
		);

		$legend[] = array(
			'title'            => sprintf( __( '%s items purchased', 'woocommerce-product-vendors' ), '<strong>' . $data->total_items . '</strong>' ),
			'color'            => $this->chart_colors['item_count'],
			'highlight_series' => 1
		);

		$legend[] = array(
			'title'            => sprintf( __( '%s charged for shipping', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->total_shipping ) . '</strong>' ),
			'color'            => $this->chart_colors['shipping_amount'],
			'highlight_series' => 2
		);

		$legend[] = array(
			'title'            => sprintf( __( '%s total earned commission', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->total_earned ) . '</strong>' ),
			'placeholder'      => __( 'This is the sum of the earned commission including shipping and taxes if applicable.', 'woocommerce-product-vendors' ),
			'color'            => $this->chart_colors['commission'],
			'highlight_series' => 6
		);

		$legend[] = array(
			'title'            => sprintf( __( '%s total paid commission', 'woocommerce-product-vendors' ), '<strong>' . wc_price( $data->total_commission ) . '</strong>' ),
			'placeholder'      => __( 'This is the sum of the commission paid including shipping and taxes if applicable.', 'woocommerce-product-vendors' ),
			'color'            => $this->chart_colors['commission'],
			'highlight_series' => 6
		);

		return $legend;
	}

	/**
	 * Output the report
	 */
	public function output_report() {
		$ranges = array(
			'year'         => __( 'Year', 'woocommerce-product-vendors' ),
			'last_month'   => __( 'Last Month', 'woocommerce-product-vendors' ),
			'month'        => __( 'This Month', 'woocommerce-product-vendors' ),
			'7day'         => __( 'Last 7 Days', 'woocommerce-product-vendors' ),
		);

		$this->chart_colors = array(
			'sales_amount'     => '#b1d4ea',
			'net_sales_amount' => '#3498db',
			'average'          => '#95a5a6',
			'order_count'      => '#dbe1e3',
			'item_count'       => '#ecf0f1',
			'shipping_amount'  => '#5cc488',
			'earned'           => '#FF69B4',
			'commission'       => '#FF69B4',
		);

		$current_range = $this->current_range;

		$this->calculate_current_range( $this->current_range );

		include( WC()->plugin_path() . '/includes/admin/views/html-report-by-date.php' );
	}

	/**
	 * Output an export link
	 */
	public function get_export_button() {
		?>
		<a
			href="#"
			download="report-<?php echo esc_attr( $this->current_range ); ?>-<?php echo date_i18n( 'Y-m-d', current_time('timestamp') ); ?>.csv"
			class="export_csv"
			data-export="chart"
			data-xaxes="<?php esc_attr_e( 'Date', 'woocommerce-product-vendors' ); ?>"
			data-exclude_series="2"
			data-groupby="<?php echo $this->chart_groupby; ?>"
			data-range="<?php echo $this->current_range; ?>"
			data-custom-range="<?php echo 'custom' === $this->current_range ? $this->start_date . '-' . $this->end_date : ''; ?>"
		>
			<?php esc_html_e( 'Export CSV', 'woocommerce-product-vendors' ); ?>
		</a>
		<?php
	}

	/**
	 * Round our totals correctly
	 * @param  string $amount
	 * @return string
	 */
	private function round_chart_totals( $amount ) {
		if ( is_array( $amount ) ) {
			return array( $amount[0], wc_format_decimal( $amount[1], wc_get_price_decimals() ) );
		} else {
			return wc_format_decimal( $amount, wc_get_price_decimals() );
		}
	}

	/**
	 * Get the main chart
	 *
	 * @return string
	 */
	public function get_main_chart() {
		global $wp_locale, $wpdb;

		// check if table exists before continuing
		if ( ! WC_Product_Vendors_Utils::commission_table_exists() ) {
			return $this->report_data;
		}

		$select = "SELECT COUNT( DISTINCT commission.order_id ) AS count, COUNT( commission.order_id ) AS order_item_count, SUM( commission.product_amount + commission.product_shipping_amount + commission.product_tax_amount + commission.product_shipping_tax_amount ) AS total_sales, SUM( commission.product_shipping_amount ) AS total_shipping, SUM( commission.product_tax_amount ) AS total_tax, SUM( commission.product_shipping_tax_amount ) AS total_shipping_tax, SUM( commission.total_commission_amount ) AS total_earned, SUM( commission.total_commission_amount ) AS total_commission, commission.order_date";

		$sql = $select;
		$sql .= " FROM " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " AS commission";
		$sql .= " WHERE 1=1";
		$sql .= " AND commission.vendor_id = %d";
		$sql .= " AND commission.commission_status != 'void'";

		switch( $this->current_range ) {
			case 'year' :
				$sql .= " AND YEAR( commission.order_date ) = YEAR( CURDATE() )";
				break;

			case 'last_month' :
				$sql .= " AND MONTH( commission.order_date ) = MONTH( NOW() ) - 1";
				break;

			case 'month' :
				$sql .= " AND MONTH( commission.order_date ) = MONTH( NOW() )";
				break;

			case 'custom' :
				$start_date = ! empty( $_GET['start_date'] ) ? sanitize_text_field( $_GET['start_date'] ) : '';
				$end_date = ! empty( $_GET['end_date'] ) ? sanitize_text_field( $_GET['end_date'] ) : '';

				$sql .= " AND DATE( commission.order_date ) BETWEEN '" . $start_date . "' AND '" . $end_date . "'";
				break;

			case 'default' :
			case '7day' :
				$sql .= " AND DATE( commission.order_date ) BETWEEN DATE_SUB( NOW(), INTERVAL 7 DAY ) AND NOW()";
				break;
		}
			
		$sql .= " GROUP BY DATE( commission.order_date )";
			
		if ( false === ( $results = get_transient( 'wcpv_reports_' . WC_Product_Vendors_Utils::get_logged_in_vendor() . '_' . $this->current_range ) ) ) {

			// Enable big selects for reports
			$wpdb->query( 'SET SESSION SQL_BIG_SELECTS=1' );
			
			$results = $wpdb->get_results( $wpdb->prepare( $sql, WC_Product_Vendors_Utils::get_logged_in_vendor() ) );

			set_transient( 'wcpv_reports_' . WC_Product_Vendors_Utils::get_logged_in_vendor() . '_' . $this->current_range, $results, DAY_IN_SECONDS );
		}

		// Prepare data for report
		$order_counts         = $this->prepare_chart_data( $results, 'order_date', 'count', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$order_item_counts    = $this->prepare_chart_data( $results, 'order_date', 'order_item_count', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$order_amounts        = $this->prepare_chart_data( $results, 'order_date', 'total_sales', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$shipping_amounts     = $this->prepare_chart_data( $results, 'order_date', 'total_shipping', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$shipping_tax_amounts = $this->prepare_chart_data( $results, 'order_date', 'total_shipping_tax', $this->chart_interval, $this->start_date, $this->chart_groupby );
		
		$tax_amounts          = $this->prepare_chart_data( $results, 'order_date', 'total_tax', $this->chart_interval, $this->start_date, $this->chart_groupby );

		$total_earned         = $this->prepare_chart_data( $results, 'order_date', 'total_earned', $this->chart_interval, $this->start_date, $this->chart_groupby );

		$total_commission     = $this->prepare_chart_data( $results, 'order_date', 'total_commission', $this->chart_interval, $this->start_date, $this->chart_groupby );

		$net_order_amounts = array();

		foreach ( $order_amounts as $order_amount_key => $order_amount_value ) {
			$net_order_amounts[ $order_amount_key ]    = $order_amount_value;
			$net_order_amounts[ $order_amount_key ][1] = $net_order_amounts[ $order_amount_key ][1] - $shipping_amounts[ $order_amount_key ][1] - $shipping_tax_amounts[ $order_amount_key ][1] - $tax_amounts[ $order_amount_key ][1];
		}

		// Encode in json format
		$chart_data = json_encode( array(
			'order_counts'      => array_values( $order_counts ),
			'order_item_counts' => array_values( $order_item_counts ),
			'order_amounts'     => array_map( array( $this, 'round_chart_totals' ), array_values( $order_amounts ) ),
			'net_order_amounts' => array_map( array( $this, 'round_chart_totals' ), array_values( $net_order_amounts ) ),
			'shipping_amounts'  => array_map( array( $this, 'round_chart_totals' ), array_values( $shipping_amounts ) ),
			'total_earned'      => array_map( array( $this, 'round_chart_totals' ), array_values( $total_earned ) ),
			'total_commission'  => array_map( array( $this, 'round_chart_totals' ), array_values( $total_commission ) ),
		) );
		?>
		<div class="chart-container">
			<div class="chart-placeholder main"></div>
		</div>
		<script type="text/javascript">

			var main_chart;

			jQuery(function(){
				var order_data = jQuery.parseJSON( '<?php echo $chart_data; ?>' );
				var drawGraph = function( highlight ) {
					var series = [
						{
							label: "<?php echo esc_js( __( 'Number of orders', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.order_counts,
							color: '<?php echo $this->chart_colors['order_count']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colors['order_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'left' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Number of items sold', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.order_item_counts,
							color: '<?php echo $this->chart_colors['item_count']; ?>',
							bars: { fillColor: '<?php echo $this->chart_colors['item_count']; ?>', fill: true, show: true, lineWidth: 0, barWidth: <?php echo $this->barwidth; ?> * 0.5, align: 'center' },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Shipping amount', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.shipping_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colors['shipping_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							prepend_tooltip: "<?php echo get_woocommerce_currency_symbol(); ?>"
						},
						{
							label: "<?php echo esc_js( __( 'Average sales amount', 'woocommerce-product-vendors' ) ) ?>",
							data: [ [ <?php echo min( array_keys( $order_amounts ) ); ?>, <?php echo $this->report_data->average_sales; ?> ], [ <?php echo max( array_keys( $order_amounts ) ); ?>, <?php echo $this->report_data->average_sales; ?> ] ],
							yaxis: 2,
							color: '<?php echo $this->chart_colors['average']; ?>',
							points: { show: false },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							hoverable: false
						},
						{
							label: "<?php echo esc_js( __( 'Gross Sales amount', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.order_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colors['sales_amount']; ?>',
							points: { show: true, radius: 5, lineWidth: 2, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 2, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						},
						{
							label: "<?php echo esc_js( __( 'Net Sales amount', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.net_order_amounts,
							yaxis: 2,
							color: '<?php echo $this->chart_colors['net_sales_amount']; ?>',
							points: { show: true, radius: 6, lineWidth: 4, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 5, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						},
						{
							label: "<?php echo esc_js( __( 'Total Earned Commission Amount', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.total_earned,
							yaxis: 2,
							color: '<?php echo $this->chart_colors['earned']; ?>',
							points: { show: true, radius: 6, lineWidth: 4, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 5, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						},
						{
							label: "<?php echo esc_js( __( 'Total Commission Amount', 'woocommerce-product-vendors' ) ) ?>",
							data: order_data.total_commission,
							yaxis: 2,
							color: '<?php echo $this->chart_colors['commission']; ?>',
							points: { show: true, radius: 6, lineWidth: 4, fillColor: '#fff', fill: true },
							lines: { show: true, lineWidth: 5, fill: false },
							shadowSize: 0,
							<?php echo $this->get_currency_tooltip(); ?>
						}
					];

					if ( highlight !== 'undefined' && series[ highlight ] ) {
						highlight_series = series[ highlight ];

						highlight_series.color = '#9c5d90';

						if ( highlight_series.bars ) {
							highlight_series.bars.fillColor = '#9c5d90';
						}

						if ( highlight_series.lines ) {
							highlight_series.lines.lineWidth = 5;
						}
					}

					main_chart = jQuery.plot(
						jQuery('.chart-placeholder.main'),
						series,
						{
							legend: {
								show: false
							},
							grid: {
								color: '#aaa',
								borderColor: 'transparent',
								borderWidth: 0,
								hoverable: true
							},
							xaxes: [ {
								color: '#aaa',
								position: "bottom",
								tickColor: 'transparent',
								mode: "time",
								timeformat: "<?php if ( $this->chart_groupby == 'day' ) echo '%d %b'; else echo '%b'; ?>",
								monthNames: <?php echo json_encode( array_values( $wp_locale->month_abbrev ) ) ?>,
								tickLength: 1,
								minTickSize: [1, "<?php echo $this->chart_groupby; ?>"],
								font: {
									color: "#aaa"
								}
							} ],
							yaxes: [
								{
									min: 0,
									minTickSize: 1,
									tickDecimals: 0,
									color: '#d4d9dc',
									font: { color: "#aaa" }
								},
								{
									position: "right",
									min: 0,
									tickDecimals: 2,
									alignTicksWithAxis: 1,
									color: 'transparent',
									font: { color: "#aaa" }
								}
							],
						}
					);

					jQuery('.chart-placeholder').resize();
				}

				drawGraph();

				jQuery('.highlight_series').hover(
					function() {
						drawGraph( jQuery(this).data('series') );
					},
					function() {
						drawGraph();
					}
				);
			});
		</script>
		<?php
	}
}
