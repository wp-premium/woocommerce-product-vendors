<?php
/**
 * Vendor approval.
 *
 * @version 2.0.21
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( 'wc_product_vendors_admin_vendor' === $role ) {
	$message = __( 'You have full administration access.', 'woocommerce-product-vendors' );

} else {
	$message = __( 'You have limited management access.', 'woocommerce-product-vendors' );
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<h3><?php esc_html_e( 'Hello! You have been approved to be a vendor on this store.', 'woocommerce-product-vendors' ); ?></h3>

<p><?php echo esc_html( $message ); ?></p>

<p><?php esc_html_e( 'Please login to the site and visit your vendor dashboard to start managing your products.', 'woocommerce-product-vendors' ); ?></p>

<ul>
	<li><?php printf( esc_html__( 'Login Address: %s', 'woocommerce-product-vendors' ), '<a href="' . esc_url( wp_login_url() ) . '">' . wp_login_url() . '</a>' ); ?></li>
</ul>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
