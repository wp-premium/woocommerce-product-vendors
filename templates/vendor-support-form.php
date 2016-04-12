<?php
/**
 * Vendor Support Form Template
 *
 * @version 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="wrap">

	<h2><?php esc_html_e( 'Support', 'woocommerce-product-vendors' ); ?></h2>

	<p><?php esc_html_e( 'Here you can get support on any issues you may have.', 'woocommerce-product-vendors' ); ?></p>

	<form class="wcpv-vendor-support-form" action="" method="post">
		<?php do_action( 'wcpv_vendor_support_form_start' ); ?>

		<p class="form-row form-row-wide">
			<label for="wcpv-vendor-question"><?php esc_html_e( 'Please provide your support question below.', 'woocommerce-product-vendors' ); ?> <span class="required">*</span></label>
			<textarea class="input-text" name="vendor_question" id="wcpv-vendor-question" rows="4" tabindex="5"><?php if ( ! empty( $_POST['vendor_question'] ) ) echo trim( $_POST['vendor_question'] ); ?></textarea>
		</p>

		<?php do_action( 'wcpv_vendor_support_form' ); ?>

		<p class="form-row">
			<input type="submit" class="button" name="submit" value="<?php esc_attr_e( 'Submit', 'woocommerce-product-vendors' ); ?>" tabindex="6" />
		</p>

		<?php do_action( 'wcpv_vendor_support_form_end' ); ?>
	</form>
</div>
