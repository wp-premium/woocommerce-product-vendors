<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Vendors Widget.
 *
 * Displays vendor context widget.
 *
 * @category Widgets
 * @package  WooCommerce Product Vendors/Widgets/Widget
 * @version  2.0.0
 * @extends  WP_Widget
 */
class WC_Product_Vendors_Vendor_Widget extends WP_Widget {
	public function __construct() {
		// Instantiate the parent object
		parent::__construct(
			'wcpv_vendor_widget',
			__( 'Vendors', 'woocommerce-product-vendors' ),
			array( 'description' => __( 'A widget to display vendor information in context.', 'woocommerce-product-vendors' ) )
		);
	}

	public function widget( $args, $instance ) {
		global $post;

		$display_widget = false;

		$vendor = WC_Product_Vendors_Utils::is_vendor_product( $post->ID );

		if ( 'current' === $instance['vendor'] ) {
			if ( is_singular( 'product' ) && $vendor ) {
				$display_widget = true;
			}

			if ( $vendor ) {
				$vendor = WC_Product_Vendors_Utils::get_vendor_data_by_id( $vendor[0]->term_id );
			}
		} else {
			$display_widget = true;

			$vendor = WC_Product_Vendors_Utils::get_vendor_data_by_id( $instance['vendor'] );
		}

		if ( $display_widget && $vendor ) {
			$html = '';

			$html .= $args['before_widget'];

			if ( ! empty( $instance['title'] ) ) {
				$html .= $args['before_title'] . apply_filters( 'widget_title', esc_html( $instance['title'] ) ) . $args['after_title'];
			}

			$html .= '<h3 class="wcpv-widget-vendor-title">' . esc_html( $vendor['name'] ) . '</h3>' . PHP_EOL;

			$logo = wp_get_attachment_image( absint( $vendor['logo'] ), 'medium' );

			if ( $logo ) {
				$html .= $logo . PHP_EOL;
			}

			$allowed_html = array(
				'a' => array(
					'href'  => array(),
					'title' => array(),
				),
				'br'     => array(),
				'em'     => array(),
				'strong' => array(),
			);

			$html .= '<p>' . wp_kses( $vendor['profile'], $allowed_html ) . '</p>' . PHP_EOL;

			$link = get_term_link( $vendor['term_id'], WC_PRODUCT_VENDORS_TAXONOMY );

			$html .= '<p><a href="' . esc_url( $link ) . '" title="' . esc_attr( $vendor['name'] ) . '">' . esc_html__( 'View more products from this vendor', 'woocommerce-product-vendors' ) . '</a></p>' . PHP_EOL;

			$html .= $args['after_widget'];

			echo apply_filters( 'wcpv_vendor_widget_content', $html, $args, $vendor );
		}
	}

	public function update( $new_instance, $old_instance ) {
		$instance = array();

		$instance['title']  = ! empty( $new_instance['title'] ) ? strip_tags( sanitize_text_field( $new_instance['title'] ) ) : '';
		$instance['vendor'] = ! empty( $new_instance['vendor'] ) ? sanitize_text_field( $new_instance['vendor'] ) : 'current';

		return $instance;
	}

	public function form( $instance ) {
		$vendors = WC_Product_Vendors_Utils::get_vendors();

		$defaults = array(
			'title'  => '',
			'vendor' => 'current',
		);

		$instance = wp_parse_args( $instance, $defaults );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'woocommerce-product-vendors' ); ?></label>
			<input class="widefat" id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $instance['title'] ); ?>" />			
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'vendor' ) ); ?>"><?php esc_html_e( 'Vendor', 'woocommerce-product-vendors' ); ?></label><br />
			<select name="<?php echo esc_attr( $this->get_field_name( 'vendor' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'vendor' ) ); ?>" class="widefat">
				<option value="current" <?php selected( 'current', $instance['vendor'] ); ?>><?php esc_html_e( 'Current Vendor', 'woocommerce-product-vendors' ); ?></option>

				<?php
				foreach ( $vendors as $vendor ) {
				?>
					<option value="<?php echo esc_attr( $vendor->term_id ); ?>" <?php selected( $vendor->term_id, $instance['vendor'] ); ?>><?php echo esc_html( $vendor->name ); ?></option>
				<?php
				}
				?>
			</select><br /><br />

			<span><?php esc_html_e( 'Selecting "Current Vendor", will display the details of the vendors whose product(s) are being viewed at the time. It will not show on other pages.', 'woocommerce-product-vendors' ); ?></span>
		</p>
		<?php
	}
}
