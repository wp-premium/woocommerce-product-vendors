<?php
/**
 * Order fulfillment status to admin (plain text).
 *
 * @version 2.0.16
 * @since 2.0.16
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
	$order_id = $order->get_id();
} else {
	$order_id = $order->id;
}

echo "= " . $email_heading . " =\n\n";

echo __( 'Hello! A vendor has updated an order item fulfillment status.', 'woocommerce-product-vendors' ) . "\n\n";

echo __( 'Order Information', 'woocommerce-product-vendors' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Vendor: %s', 'woocommerce-product-vendors' ), $vendor_name ) . "\n\n";
echo sprintf( __( 'Order Number: %s', 'woocommerce-product-vendors' ), $order->get_order_number() ) . "\n\n";
echo sprintf( __( 'Order Item: %s', 'woocommerce-product-vendors' ), $order_item_name ) . "\n\n";
echo sprintf( __( 'Fulfillment Status: %s', 'woocommerce-product-vendors' ), ucfirst( $fulfillment_status ) ) . "\n\n";
echo admin_url( 'post.php?post=' . $order_id . '&action=edit' ) . "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
