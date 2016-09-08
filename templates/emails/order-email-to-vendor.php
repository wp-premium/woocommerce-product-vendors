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
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<p><?php printf( esc_html__( 'You have received an order from %s. The order is as follows:', 'woocommerce-product-vendors' ), esc_html( $order->billing_first_name ) . ' ' . esc_html( $order->billing_last_name ) ); ?></p>

<h2><?php printf( esc_html__( 'Order #%s', 'woocommerce-product-vendors' ), $order->get_order_number() ); ?> (<?php printf( '<time datetime="%s">%s</time>', date_i18n( 'c', strtotime( $order->order_date ) ), date_i18n( wc_date_format(), strtotime( $order->order_date ) ) ); ?>)</h2>

<?php $email->render_order_details_table( $order, $sent_to_admin, $plain_text, $email, $this_vendor ); ?>

<?php do_action( 'woocommerce_email_order_meta', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_customer_details', $order, $sent_to_admin, $plain_text, $email ); ?>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
