<?php
/**
 * Order details email (plain text).
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

do_action( 'woocommerce_email_before_order_table', $order, true, false, $email );

echo "\n";

foreach ( $order->get_items() as $item_id => $item ) :
	$_product     = apply_filters( 'woocommerce_order_item_product', $order->get_product_from_item( $item ), $item );
	$item_meta    = new WC_Order_Item_Meta( $item, $_product );

	$vendor_id = WC_Product_Vendors_Utils::get_vendor_id_from_product( $_product->id );

	// remove the order items that are not from this vendor
	if ( $this_vendor !== $vendor_id ) {
		continue;
	}

	if ( apply_filters( 'woocommerce_order_item_visible', true, $item ) ) {

		// Title
		echo apply_filters( 'woocommerce_order_item_name', $item['name'], $item, false );

		// SKU
		if ( $_product->get_sku() ) {
			echo ' (#' . $_product->get_sku() . ')';
		}

		// allow other plugins to add additional product information here
		do_action( 'woocommerce_order_item_meta_start', $item_id, $item, $order );

		// Variation
		echo ( $item_meta_content = $item_meta->display( true, true ) ) ? "\n" . $item_meta_content : '';

		// Quantity
		echo "\n" . sprintf( __( 'Quantity: %s', 'woocommerce-product-vendors' ), apply_filters( 'woocommerce_email_order_item_quantity', $item['qty'], $item ) );

		// Cost
		echo "\n" . sprintf( __( 'Cost: %s', 'woocommerce-product-vendors' ), $order->get_formatted_line_subtotal( $item ) );

		// allow other plugins to add additional product information here
		do_action( 'woocommerce_order_item_meta_end', $item_id, $item, $order );
	}

	echo "\n\n";

endforeach;

echo "==========\n\n";

do_action( 'woocommerce_email_after_order_table', $order, true, false, $email );
