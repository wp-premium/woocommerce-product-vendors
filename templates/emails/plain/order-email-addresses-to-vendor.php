<?php
/**
 * Order email addresses to vendor (plain text).
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ( $shipping = $order->get_formatted_shipping_address() ) ) :
	echo __( 'Shipping Address', 'woocommerce-product-vendors' ) . "\n\n";

	echo $shipping;
endif;
