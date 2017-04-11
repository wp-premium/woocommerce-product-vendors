<?php
/**
 * Order email to vendor.
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
	$order_date = $order->get_date_created();
	$billing_first_name = $order->get_billing_first_name();
	$billing_last_name = $order->get_billing_last_name();
} else {
	$order_date = $order->order_date;
	$billing_first_name = $order->billing_first_name;
	$billing_last_name = $order->billing_last_name;
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'You have received an order from %s. The order is as follows:', 'woocommerce-product-vendors' ), esc_html( $billing_first_name ) . ' ' . esc_html( $billing_last_name ) ); ?></p>

<h2><?php printf( esc_html__( 'Order #%s', 'woocommerce-product-vendors' ), $order->get_order_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order_date ) ), date_i18n( wc_date_format(), strtotime( $order_date ) ) ); ?>)</h2>

<?php $email->render_order_details_table( $order, $sent_to_admin, $plain_text, $email, $this_vendor ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
