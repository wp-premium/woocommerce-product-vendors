<?php
/**
 * Order fulfillment status to admin.
 *
 * @version 2.0.16
 * @since 2.0.16
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<h3><?php esc_html_e( 'Hello! A vendor has updated an order item fulfillment status.', 'woocommerce-product-vendors' ); ?></h3>

<p><?php esc_html_e( 'Order Information:', 'woocommerce-product-vendors' ); ?></p>

<ul>
	<li><?php printf( esc_html__( 'Vendor', 'woocommerce-product-vendors' ) . ': %s', $vendor_name ); ?></li>
	<li><?php printf( esc_html__( 'Order Number', 'woocommerce-product-vendors' ) . ': %d', $order->id ); ?></li>
	<li><?php printf( esc_html__( 'Order Item', 'woocommerce-product-vendors' ) . ': %s', $order_item_name ); ?></li>
	<li><?php printf( esc_html__( 'Fulfillment Status', 'woocommerce-product-vendors' ) . ': %s', ucfirst( $fulfillment_status ) ); ?></li>
	<li><a href="<?php echo admin_url( 'post.php?post=' . $order->id . '&action=edit' ); ?>"><?php echo admin_url( 'post.php?post=' . $order->id . '&action=edit' ); ?></a></li>
</ul>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
