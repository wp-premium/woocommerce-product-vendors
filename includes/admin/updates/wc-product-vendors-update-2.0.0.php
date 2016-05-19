<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// shop_commission ( registered post type )
// shop_vendor ( registered taxonomy )

global $wpdb;

$wpdb->hide_errors();

// update shop_vendor taxonomy name to wcpv_product_vendors
$wpdb->update( $wpdb->term_taxonomy, array( 'taxonomy' => WC_PRODUCT_VENDORS_TAXONOMY ), array( 'taxonomy' => 'shop_vendor' ) );

// update product commission meta name
$wpdb->update( $wpdb->postmeta, array( 'meta_key' => '_wcpv_product_commission' ), array( 'meta_key' => '_product_vendors_commission' ) );

// get all vendor ( term ) ids
$sql = "SELECT DISTINCT term_tax.term_id, term_tax.description FROM $wpdb->term_taxonomy AS term_tax";
$sql .= " WHERE term_tax.taxonomy = '" . WC_PRODUCT_VENDORS_TAXONOMY . "'";

$vendors = $wpdb->get_results( $sql ); // array of object term_id properties

// loop through all vendors and get their data and reset it using WP 4.4 term meta
foreach( $vendors as $vendor ) {
	$vendor_data = get_option( 'shop_vendor_' . $vendor->term_id );

	if ( ! empty( $vendor_data ) ) {
		$new_vendor_data['profile']              = $vendor->description;
		$new_vendor_data['commission']           = $vendor_data['commission'];
		$new_vendor_data['commission_type']      = 'percentage';
		$new_vendor_data['enable_bookings']      = 'no';
		$new_vendor_data['per_product_shipping'] = 'no';
		$new_vendor_data['paypal']               = $vendor_data['paypal_email'];

		if ( ! empty( $vendor_data['admins'] ) ) {
			$new_vendor_data['admins'] = implode( ',', $vendor_data['admins'] );

			$admin_emails = array();

			foreach( $vendor_data['admins'] as $admin ) {
				$user = get_userdata( $admin );
				$admin_emails[] = $user->user_email;
			}

			$new_vendor_data['email'] = implode( ',', $admin_emails );
		}
		
		update_term_meta( $vendor->term_id, 'vendor_data', $new_vendor_data );	
	}

	// delete option
	delete_option( 'shop_vendor_' . $id->term_id );
}

// get all commission data
$commissions = get_posts( array( 'posts_per_page' => -1, 'post_type' => 'shop_commission', 'post_status' => array( 'publish', 'private' ) ) );

if ( ! empty( $commissions ) ) {
	$processed_commissions = get_option( 'wcpv_processed_commissions', array() );
	$commission_ids = array();

	// loop through commissions and create new commission
	foreach( $commissions as $commission ) {
		// check if we have already processed commission
		if ( in_array( $commission->ID, $processed_commissions ) ) {
			continue;
		}

		$vendor_id                   = get_post_meta( $commission->ID, '_commission_vendor', true );
		$product_id                  = get_post_meta( $commission->ID, '_commission_product', true );
		$commission_status           = get_post_meta( $commission->ID, '_paid_status', true );
		$commission_amount           = get_post_meta( $commission->ID, '_commission_amount', true );
		$order_id                    = get_post_meta( $commission->ID, '_commission_order', true );
		$order_date                  = $commission->post_date;
		$product_shipping_amount     = 0;
		$product_shipping_tax_amount = 0;
		$product_tax_amount          = 0;
		$paid_date                   = $order_date;
		$variation_attributes        = '';
		
		// get vendor name
		$vendor_term = get_term( $vendor_id, 'shop_vendor' );
		$vendor_name = $vendor_term->name;

		// get the order items
		$sql = "SELECT order_items.order_item_id, order_item_meta.meta_key, order_item_meta.meta_value, order_items.order_item_name FROM {$wpdb->prefix}woocommerce_order_items AS order_items";
		$sql .= " LEFT JOIN {$wpdb->prefix}woocommerce_order_itemmeta AS order_item_meta";
		$sql .= " ON order_items.order_item_id = order_item_meta.order_item_id";
		$sql .= " WHERE order_items.order_id = %s";

		$order_items = $wpdb->get_results( $wpdb->prepare( $sql, $order_id ) );

		if ( ! empty( $order_items ) ) {
			$product_qty = 1;
			$order_item_id = $order_items[0]->order_item_id;
			$product_name = $order_items[0]->order_item_name;

			foreach( $order_items as $item ) {

				if ( '_qty' === $item->meta_key ) {
					$product_qty = $item->meta_value;
				}

				if ( '_variation_id' === $item->meta_key ) {
					$variation_id = $item->meta_value;
				}

				if ( '_line_total' === $item->meta_key ) {
					$product_amount = $item->meta_value;
				}

				if ( '_commission' === $item->meta_key ) {
					$total_commission_amount = $item->meta_value;
				}
			}

			$sql = "INSERT INTO " . WC_PRODUCT_VENDORS_COMMISSION_TABLE . " ( `order_id`, `order_item_id`, `order_date`, `vendor_id`, `vendor_name`, `product_id`, `variation_id`, `product_name`, `variation_attributes`, `product_amount`, `product_quantity`, `product_shipping_amount`, `product_shipping_tax_amount`, `product_tax_amount`, `product_commission_amount`, `total_commission_amount`, `commission_status`, `paid_date` )";
			
			$sql .= " VALUES ( %d, %d, %s, %d, %s, %d, %d, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s )";

			$wpdb->query( $wpdb->prepare( $sql, $order_id, $order_item_id, $order_date, $vendor_id, $vendor_name, $product_id, $variation_id, $product_name, $variation_attributes, $product_amount, $product_qty, $product_shipping_amount, $product_shipping_tax_amount, $product_tax_amount, $product_commission_amount, $commission_amount, $commission_status, $paid_date ) );

			$commission_ids[] = $commission->ID;

			// delete all lingering items from version 1.0.0
			wp_delete_post( $commission->ID, true );
			delete_post_meta( $commission->ID, '_commission_vendor' );
			delete_post_meta( $commission->ID, '_commission_product' );
			delete_post_meta( $commission->ID, '_paid_status' );
			delete_post_meta( $commission->ID, '_commission_amount' );
			delete_post_meta( $commission->ID, '_commission_order' );
			delete_option( 'shop_vendor_' . $commission->ID );
			delete_post_meta( $order_id, '_commissions_processed' );

			// delete all order item meta
			$wpdb->query( "DELETE FROM {$wpdb->prefix}woocommerce_order_itemmeta WHERE `meta_key` = '_commission'" );

			// check first to see if meta has already been added
			$check_sql = "SELECT `meta_value`";
			$check_sql .= " FROM {$wpdb->prefix}woocommerce_order_itemmeta";
			$check_sql .= " WHERE `order_item_id` = %d";
			$check_sql .= " AND `meta_key` = %s";

			$result = $wpdb->get_results( $wpdb->prepare( $check_sql, $order_item_id, '_fulfillment_status' ) );

			if ( empty( $result ) ) {	
				// add ship and paid status to order item meta
				$sql = "INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta ( `order_item_id`, `meta_key`, `meta_value` )";
				$sql .= " VALUES ( %d, '_fulfillment_status', 'fulfilled' )";

				$wpdb->query( $wpdb->prepare( $sql, $order_item_id ) );
			}

			// check first to see if meta has already been added
			$check_sql = "SELECT `meta_value`";
			$check_sql .= " FROM {$wpdb->prefix}woocommerce_order_itemmeta";
			$check_sql .= " WHERE `order_item_id` = %d";
			$check_sql .= " AND `meta_key` = %s";

			$result = $wpdb->get_results( $wpdb->prepare( $check_sql, $order_item_id, '_commission_status' ) );

			if ( empty( $result ) ) {
				$sql = "INSERT INTO {$wpdb->prefix}woocommerce_order_itemmeta ( `order_item_id`, `meta_key`, `meta_value` )";
				$sql .= " VALUES ( %d, '_commission_status', %s )";

				$wpdb->query( $wpdb->prepare( $sql, $order_item_id, $commission_status ) );
			}
		}
	}

	// add commission id to the list of processed commissions
	$processed_commissions = array_unique( array_merge( $processed_commissions, $commission_ids ) );
	
	update_option( 'wcpv_processed_commissions', $processed_commissions );
}

// all done
update_option( 'wcpv_version', '2.0.0' );
