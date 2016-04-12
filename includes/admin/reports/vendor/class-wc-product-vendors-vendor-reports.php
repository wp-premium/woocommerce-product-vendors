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

if ( ! class_exists( 'WC_Product_Vendors_Vendor_Reports' ) ) :

class WC_Product_Vendors_Vendor_Reports {
	/**
	 * Handles output of the reports page in vendor admin.
	 */
	public static function output() {
		$reports        = self::get_reports();
		$first_tab      = array_keys( $reports );
		$current_tab    = ! empty( $_GET['tab'] ) ? sanitize_title( $_GET['tab'] ) : $first_tab[0];
		$current_report = isset( $_GET['report'] ) ? sanitize_title( $_GET['report'] ) : current( array_keys( $reports[ $current_tab ]['reports'] ) );

		include_once( 'views/html-vendor-reports-page.php' );
	}

	/**
	 * Returns the definitions for the reports to show in admin.
	 *
	 * @return array
	 */
	public static function get_reports() {
		$reports = array(
			'orders'     => array(
				'title'  => __( 'Orders', 'woocommerce-product-vendors' ),
				'reports' => array(
					"sales_by_date" => array(
						'title'       => __( 'Sales by date', 'woocommerce-product-vendors' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
					"sales_by_product" => array(
						'title'       => __( 'Sales by product', 'woocommerce-product-vendors' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
				)
			),
			'stock'     => array(
				'title'  => __( 'Stock', 'woocommerce-product-vendors' ),
				'reports' => array(
					"low_in_stock" => array(
						'title'       => __( 'Low in stock', 'woocommerce-product-vendors' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
					"out_of_stock" => array(
						'title'       => __( 'Out of stock', 'woocommerce-product-vendors' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
					"most_stocked" => array(
						'title'       => __( 'Most Stocked', 'woocommerce-product-vendors' ),
						'description' => '',
						'hide_title'  => true,
						'callback'    => array( __CLASS__, 'get_report' )
					),
				)
			)
		);

		$reports = apply_filters( 'wcpv_vendor_reports', $reports );

		foreach ( $reports as $key => $report_group ) {
			if ( isset( $reports[ $key ]['charts'] ) ) {
				$reports[ $key ]['reports'] = $reports[ $key ]['charts'];
			}

			foreach ( $reports[ $key ]['reports'] as $report_key => $report ) {
				if ( isset( $reports[ $key ]['reports'][ $report_key ]['function'] ) ) {
					$reports[ $key ]['reports'][ $report_key ]['callback'] = $reports[ $key ]['reports'][ $report_key ]['function'];
				}
			}
		}

		return $reports;
	}

	/**
	 * Get a report from our reports subfolder
	 */
	public static function get_report( $name ) {
		$name  = sanitize_title( str_replace( '_', '-', $name ) );
		$class = 'WC_Product_Vendors_Vendor_Report_' . str_replace( '-', '_', $name );

		include_once( apply_filters( 'wcpv_vendor_reports_path', 'class-wc-product-vendors-vendor-report-' . $name . '.php', $name, $class ) );

		if ( ! class_exists( $class ) ) {
			return;
		}

		$report = new $class();
		$report->output_report();
	}
}

endif;
