<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h3><?php esc_html_e( 'Vendor', 'woocommerce-product-vendors' ); ?></h3>

<table class="form-table">
	<tbody>
		<tr>
			<th scope="row">
				<label for="wcpv-managing"><?php esc_html_e( 'Managing', 'woocommerce-product-vendors' ); ?></label></th>
			<td>
				<?php
					$vendors = WC_Product_Vendors_Utils::get_all_vendor_data( $user->ID );

					foreach( $vendors as $vendor ) {
						echo '<em class="wcpv-vendor-names">' . esc_html( $vendor['name'] ) . '</em><br />';
					}
				?>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wcpv-publish-products"><?php esc_html_e( 'Publish Products', 'woocommerce-product-vendors' ); ?></label></th>
			<td>
				<select id="wcpv-publish-products" name="wcpv_publish_products">
					<option value="disallow" <?php selected( $selected, 'disallow' ); ?>><?php esc_html_e( 'Needs Approval', 'woocommerce-product-vendors' ); ?></option>
					<option value="allow" <?php selected( $selected, 'allow' ); ?>><?php esc_html_e( 'Allow', 'woocommerce-product-vendors' ); ?></option>
				</select>
				<p><?php esc_html_e( 'This setting determines if this user is allow to publish products live without approval.', 'woocommerce-product-vendors' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>
