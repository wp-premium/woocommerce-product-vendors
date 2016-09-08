<?php
/**
 * Order email addresses to vendor.
 *
 * @version 2.0.0
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?><table id="addresses" cellspacing="0" cellpadding="0" style="width: 100%; vertical-align: top;" border="0">
	<tr>
		<?php if ( ! wc_ship_to_billing_address_only() && $order->needs_shipping_address() && ( $shipping = $order->get_formatted_shipping_address() ) ) : ?>
			<td class="td" style="text-align:left; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;" valign="top" width="50%">
				<h3><?php esc_html_e( 'Shipping Address', 'woocommerce-product-vendors' ); ?></h3>

				<p class="text"><?php echo $shipping; ?></p>
			</td>
		<?php endif; ?>
	</tr>
</table>
