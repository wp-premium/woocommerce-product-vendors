<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<h3><?php esc_html_e( 'Vendor Management', 'woocommerce-product-vendors' ); ?></h3>

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
					<option value="disallow" <?php selected( $publish_products, 'disallow' ); ?>><?php esc_html_e( 'Needs Approval', 'woocommerce-product-vendors' ); ?></option>
					<option value="allow" <?php selected( $publish_products, 'allow' ); ?>><?php esc_html_e( 'Allow', 'woocommerce-product-vendors' ); ?></option>
				</select>
				<p><?php esc_html_e( 'This setting determines if this vendor user is allowed to publish products live without approval.', 'woocommerce-product-vendors' ); ?></p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wcpv-manage-customers"><?php esc_html_e( 'Manage Customers', 'woocommerce-product-vendors' ); ?></label></th>
			<td>
				<select id="wcpv-manage-customers" name="wcpv_manage_customers">
					<option value="disallow" <?php selected( $manage_customers, 'disallow' ); ?>><?php esc_html_e( 'Not Allow', 'woocommerce-product-vendors' ); ?></option>
					<option value="allow" <?php selected( $manage_customers, 'allow' ); ?>><?php esc_html_e( 'Allowed', 'woocommerce-product-vendors' ); ?></option>
				</select>
				<p><?php esc_html_e( 'This setting determines if this vendor user can manage customers which includes their own existing customers and create new customers.', 'woocommerce-product-vendors' ); ?></p>
			</td>
		</tr>
	</tbody>
</table>
