<?php
/**
 * Vendor List Template
 *
 * @version 2.0.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<ul class="wcpv-vendor-list-shortcode">
	<?php if ( ! empty( $vendors ) ) :
		foreach( $vendors as $vendor ) {
			$vendor_data = get_term_meta( $vendor->term_id, 'vendor_data', true );

			?>
			<li>
				<?php if ( $atts['show_name'] && 'false' !== $atts['show_name'] ) { ?>
					<a href="<?php echo esc_url( get_term_link( $vendor->term_id, WC_PRODUCT_VENDORS_TAXONOMY ) ); ?>" class="wcpv-vendor-name"><?php echo esc_html( $vendor->name ); ?></a>
				<?php } ?>

				<?php if ( $atts['show_logo'] && 'false' !== $atts['show_logo'] && ! empty( $vendor_data['logo'] ) ) { ?>
					<a href="<?php echo esc_url( get_term_link( $vendor->term_id, WC_PRODUCT_VENDORS_TAXONOMY ) ); ?>" class="wcpv-vendor-logo"><?php echo  wp_get_attachment_image( absint( $vendor_data['logo'] ), 'full' ); ?></a>
				<?php } ?>
			</li>
		<?php } ?>
	<?php endif; ?>
</ul>
