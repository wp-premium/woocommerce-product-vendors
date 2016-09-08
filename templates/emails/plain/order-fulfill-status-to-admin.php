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

echo "= " . $email_heading . " =\n\n";

echo __( 'Hello! A vendor has updated an order item fulfillment status.', 'woocommerce-product-vendors' ) . "\n\n";

echo __( 'Order Information', 'woocommerce-product-vendors' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Vendor: %s', 'woocommerce-product-vendors' ), $vendor_name ) . "\n\n";
echo sprintf( __( 'Order Number: %d', 'woocommerce-product-vendors' ), $order->id ) . "\n\n";
echo sprintf( __( 'Order Item: %s', 'woocommerce-product-vendors' ), $order_item_name ) . "\n\n";
echo sprintf( __( 'Fulfillment Status: %s', 'woocommerce-product-vendors' ), ucfirst( $fulfillment_status ) ) . "\n\n";
echo admin_url( 'post.php?post=' . $order->id . '&action=edit' ) . "\n\n";
echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
