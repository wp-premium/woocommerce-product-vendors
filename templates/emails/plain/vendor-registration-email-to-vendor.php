<?php
/**
 * Vendor registration email to vendor (plain text).
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

echo "= " . $email_heading . " =\n\n";

echo __( 'Hello! Thank you for registering to become a vendor.', 'woocommerce-product-vendors' ) . "\n\n";

echo __( 'Once your application has been approved, you will be able to login.', 'woocommerce-product-vendors' ) . "\n\n";

echo __( 'Here is your login account information:', 'woocommerce-product-vendors' ) . "\n\n";

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo sprintf( __( 'Login Address: %s', 'woocommerce-product-vendors' ), admin_url() ) . "\n\n";
echo sprintf( __( 'Login Name: %s', 'woocommerce-product-vendors' ), $user_login ) . "\n\n";
echo sprintf( __( 'Login Password: %s', 'woocommerce-product-vendors' ), $user_pass ) . "\n\n";

echo "\n=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
