<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="wrap">

	<h2><?php esc_html_e( 'Orders', 'woocommerce-product-vendors' ); ?>
		<?php 
			if ( ! empty( $_REQUEST['s'] ) ) {
				echo '<span class="subtitle">' . esc_html__( 'Search results for', 'woocommerce-product-vendors' ) . ' "' . sanitize_text_field( $_REQUEST['s'] ) . '"</span>';
			} 
		?>
	</h2>

	<ul class="subsubsub"><?php $orders_list->views(); ?></ul>

	<form id="wcpv-vendor-orders" action="" method="get">
		<input type="hidden" name="page" value="wcpv-vendor-orders" />
		<?php $orders_list->search_box( __( 'Search Order #', 'woocommerce-product-vendors' ), 'search_id' ); ?>
		<?php $orders_list->display(); ?>
	</form>

</div>
