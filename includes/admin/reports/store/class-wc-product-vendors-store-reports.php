<?php
/**
 * Report class responsible for adding reports.
 *
 * @since      2.0.0
 *
 * @package    WooCommerce Product Vendors
 * @subpackage WooCommerce Product Vendors/Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Product_Vendors_Store_Reports' ) ) :

class WC_Product_Vendors_Store_Reports {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		// reports
		add_filter( 'woocommerce_admin_reports', array( $this, 'add_report' ), 11 );

		return true;	
	}

	/**
	 * Get a report from our reports subfolder
	 */
	public static function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Product_Vendors_Store_Report_' . str_replace( '-', '_', $name );

		include_once( apply_filters( 'wcpv_store_reports_path', 'class-wc-product-vendors-store-report-' . $name . '.php', $name, $class ) );

		if ( ! class_exists( $class ) ) {
			return;
		}

		$report = new $class();
		$report->output_report();
	}

	/**
	 * Add vendor report
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $reports
	 * @return bool
	 */
	public function add_report( $reports ) {
		$reports['vendors'] = array(
			'title'  => __( 'Vendors', 'woocommerce-product-vendors' ),
			'reports' => array(
				"sales_by_date" => array(
					'title'       => __( 'Sales by date', 'woocommerce-product-vendors' ),
					'description' => '',
					'hide_title'  => true,
					'callback'    => array( __CLASS__, 'get_report' )
				),
			)
		);

		return $reports;
	}
}

new WC_Product_Vendors_Store_Reports();
endif;
