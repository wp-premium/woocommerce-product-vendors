<?php
/**
 * Product added notice.
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<?php do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<h3><?php printf( esc_html__( 'Hello! A vendor ( %s ) has added a new product awaiting review.', 'woocommerce-product-vendors' ), $vendor_name ); ?></h3>

<p><a href="<?php echo esc_url( $product_link ); ?>"><?php echo $product_name; ?></a></p>

<?php do_action( 'woocommerce_email_footer', $email ); ?>
