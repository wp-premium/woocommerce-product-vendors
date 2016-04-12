<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<div class="error notice"><p><?php esc_html_e( 'Thanks for updating Product Vendors to 2.0.0+. You can optionally convert all your past data into this current version.  Please note this may take awhile so you may want to do this when your site traffic is at its lowest and it would be best to do a full site backup prior to proceeding.  If you still want to convert all the data, click on the update button.', 'woocommerce-product-vendors' ); ?> <a href="<?php echo esc_url( add_query_arg( 'do_update_wcpv', 'true', admin_url( 'admin.php?page=wc-settings&tab=products&section=wcpv_vendor_settings' ) ) ); ?>" class="wcpv-update button"><?php esc_html_e( 'Update', 'woocommerce-product-vendors' ); ?></a> <a href="<?php echo esc_url( add_query_arg( 'dismiss_wcpv', 'true', admin_url() ) ); ?>" class="button"><?php esc_html_e( 'Dismiss', 'woocommerce-product-vendors' ); ?></a></p></div>

<script type="text/javascript">
	jQuery( '.wcpv-update' ).click( 'click', function() {
		return window.confirm( '<?php echo esc_js( __( 'It is strongly recommended that you backup your database before proceeding. Are you sure you wish to run the updater now?', 'woocommerce-product-vendors' ) ); ?>' );
	});
</script>
