<?php
// version 2.0.16
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">

	<h2><?php esc_html_e( 'Store Settings', 'woocommerce-product-vendors' ); ?></h2>
	
	<form id="wcpv-vendor-settings" action="" method="post">
		<input type="hidden" name="page" value="wcpv-vendor-settings"/>
		
		<table class="form-table">
			<tbody>
				
				<tr class="form-field">
					<th scope="row" valign="top"><label for="wcpv-vendor-logo"><?php esc_html_e( 'Vendor Logo', 'woocommerce-product-vendors' ); ?></label></th>
					
					<td>
						<input type="hidden" name="vendor_data[logo]" value="<?php echo esc_attr( $logo ); ?>" />
						<a href="#" class="wcpv-upload-logo button"><?php esc_html_e( 'Upload Logo', 'woocommerce-product-vendors' ); ?></a>
						<br />
						<br />
						<?php if ( is_array( $logo_image_url ) && ! empty( $logo_image_url ) ) { ?>
								<img src="<?php echo esc_url( $logo_image_url[0] ); ?>" class="wcpv-logo-preview-image" />

						<?php } else { ?>
								<img src="" class="wcpv-logo-preview-image hide" />

						<?php } ?>
								
							<a href="#" class="wcpv-remove-image dashicons dashicons-no" style="<?php echo esc_attr( $hide_remove_image_link ); ?>" title="<?php esc_attr_e( 'Click to remove image', 'woocommerce-product-vendors' ); ?>"></a>
					</td>
				</tr>

				<tr class="form-field">
					<th scope="row" valign="top"><label for="wcpv-vendor-profile"><?php esc_html_e( 'Vendor Profile', 'woocommerce-product-vendors' ); ?></label></th>
					
					<td>
						<?php 
							$args = array( 
								'textarea_name' => 'vendor_data[profile]',
								'textarea_rows' => 5,
							);
							
							wp_editor( htmlspecialchars_decode( $profile ), 'wcpv_vendor_info', $args ); 
						?>

						<p><?php esc_html_e( 'Enter the profile information you would like for customer to see.', 'woocommerce-product-vendors' ); ?></p>
					</td>
				</tr>

				<tr class="form-field">
					<th scope="row" valign="top"><label for="wcpv-vendor-email"><?php esc_html_e( 'Vendor Email', 'woocommerce-product-vendors' ); ?></label></th>
					
					<td>
						<input type="text" name="vendor_data[email]" value="<?php echo esc_attr( $email ); ?>" />

						<p><?php esc_html_e( 'Enter the email for this vendor. This is the email where all notifications are sent such as new orders and customer inquiries.  You may enter more than one email separating each with a comma.', 'woocommerce-product-vendors' ); ?></p>
					</td>
				</tr>

				<tr class="form-field">
					<th scope="row" valign="top"><label for="wcpv-vendor-paypal"><?php esc_html_e( 'PayPal Email', 'woocommerce-product-vendors' ); ?></label></th>
					
					<td>
						<input type="email" id="wcpv-vendor-paypal" name="vendor_data[paypal]" value="<?php echo esc_attr( $paypal ); ?>" />

						<p><?php esc_html_e( 'PayPal email account where you will receive your commission.', 'woocommerce-product-vendors' ); ?></p>
					</td>
				</tr>
				
				<tr class="form-field">
					<th scope="row" valign="top"><label for="wcpv-vendor-commission"><?php esc_html_e( 'Commission', 'woocommerce-product-vendors' ); ?></label></th>
					
					<td>
						<input type="text" disabled="disabled" value="<?php echo esc_attr( $vendor_commission ); ?>" />

						<p><?php esc_html_e( 'Default commission you will receive per product sale. Please note product level commission can override this.  Check your product to confirm.', 'woocommerce-product-vendors' ); ?></p>
					</td>
				</tr>

				<tr class="form-field">
					<th scope="row" valign="top"><label for="wcpv-vendor-timezone"><?php esc_html_e( 'Timezone', 'woocommerce-product-vendors' ); ?></label></th>
					
					<td>
						<select id="wcpv-vendor-timezone" name="vendor_data[timezone]" aria-describedby="timezone-description" class="wc-enhanced-select" style="width:20%">
							<?php echo wp_timezone_choice( $tzstring ); ?>
						</select>

						<p><?php esc_html_e( 'Set the local timezone.', 'woocommerce-product-vendors' ); ?></p>
					</td>
				</tr>
			</tbody>
		</table>
		
		<?php wp_nonce_field( 'wcpv_save_vendor_settings', 'wcpv_save_vendor_settings_nonce' ); ?>
		<?php submit_button( __( 'Update', 'woocommerce-product-vendors' ), 'primary', 'submit' ); ?>
	</form>
</div>
		