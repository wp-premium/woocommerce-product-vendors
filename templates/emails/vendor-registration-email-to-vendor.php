<?php
/**
 * Vendor registration email to vendor.
 *
 * @version 2.0.21
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<h3><?php esc_html_e( 'Hello! Thank you for registering to become a vendor.', 'woocommerce-product-vendors' ); ?></h3>

<p><?php esc_html_e( 'Once your application has been approved, you will be notified.', 'woocommerce-product-vendors' ); ?></p>

<p><?php esc_html_e( 'Here is your login account information:', 'woocommerce-product-vendors' ); ?></p>

<ul>
	<li><?php printf( esc_html__( 'Login Address: %s', 'woocommerce-product-vendors' ), '<a href="' . esc_url( wp_login_url() ) . '">' . wp_login_url() . '</a>' ); ?></li>
	<li><?php printf( esc_html__( 'Login Name: %s', 'woocommerce-product-vendors' ), $user_login ); ?></li>
</ul>

<a class="link" href="<?php echo esc_url( add_query_arg( array( 'action' => 'rp', 'key' => $password_reset_key, 'login' => rawurlencode( $user_login ) ), wp_login_url() ) ); ?>">
			<?php esc_html_e( 'Click here to set your password and gain access to your account.', 'woocommerce-product-vendors' ); ?></a>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
