<?php
/**
 * Report class responsible for handling out of stock reports.
 *
 * @since      2.0.0
 *
 * @package    WooCommerce Product Vendors
 * @subpackage WooCommerce Product Vendors/Reports
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'WC_Report_Stock' ) ) {
	include_once( WC()->plugin_path() . '/includes/admin/reports/class-wc-report-stock.php' );
}

class WC_Product_Vendors_Vendor_Report_Out_Of_Stock extends WC_Report_Stock {
	/**
	 * No items found text
	 */
	public function no_items() {
		_e( 'No out of stock products found.', 'woocommerce-product-vendors' );
	}

	/**
	 * Get Products matching stock criteria
	 */
	public function get_items( $current_page, $per_page ) {
		global $wpdb;

		$this->max_items = 0;
		$this->items     = array();

		// Get products using a query - this is too advanced for get_posts :(
		$stock = absint( max( get_option( 'woocommerce_notify_no_stock_amount' ), 0 ) );

		$vendor_product_ids = implode( "', '", array_map( 'absint', WC_Product_Vendors_Utils::get_vendor_product_ids() ) );

		$query_from = apply_filters( 'wcpv_report_out_of_stock_query_from', "FROM {$wpdb->posts} as posts
			INNER JOIN {$wpdb->postmeta} AS postmeta ON posts.ID = postmeta.post_id
			INNER JOIN {$wpdb->postmeta} AS postmeta2 ON posts.ID = postmeta2.post_id
			WHERE 1=1
			AND posts.post_type IN ( 'product', 'product_variation' )
			AND posts.post_status = 'publish'
			AND postmeta2.meta_key = '_manage_stock' AND postmeta2.meta_value = 'yes'
			AND postmeta.meta_key = '_stock' AND CAST(postmeta.meta_value AS SIGNED) <= '{$stock}'
			AND posts.ID IN ( '{$vendor_product_ids}' )
		" );

		$this->items     = $wpdb->get_results( $wpdb->prepare( "SELECT posts.ID as id, posts.post_parent as parent {$query_from} GROUP BY posts.ID ORDER BY posts.post_title DESC LIMIT %d, %d;", ( $current_page - 1 ) * $per_page, $per_page ) );
		
		$this->max_items = $wpdb->get_var( "SELECT COUNT( DISTINCT posts.ID ) {$query_from};" );
	}
}
