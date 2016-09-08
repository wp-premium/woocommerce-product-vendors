<?php
/**
 * Vendor registration email to admin.
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<h3><?php esc_html_e( 'Hello! A vendor has requested to be registered.', 'woocommerce-product-vendors' ); ?></h3>

<p><?php esc_html_e( 'Vendor information:', 'woocommerce-product-vendors' ); ?></p>

<ul>
	<li><?php printf( esc_html__( 'Email', 'woocommerce-product-vendors' ) . ': %s', $user_email ); ?></li>
	<li><?php printf( esc_html__( 'First Name', 'woocommerce-product-vendors' ) . ': %s', $first_name ); ?></li>
	<li><?php printf( esc_html__( 'Last Name', 'woocommerce-product-vendors' ) . ': %s', $last_name ); ?></li>
	<li><?php printf( esc_html__( 'Vendor Name', 'woocommerce-product-vendors' ) . ': %s', stripslashes( $vendor_name ) ); ?></li>
	<li><?php printf( esc_html__( 'Vendor Description', 'woocommerce-product-vendors' ) . ':<br />%s', stripslashes( $vendor_desc ) ); ?></li>
</ul>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
