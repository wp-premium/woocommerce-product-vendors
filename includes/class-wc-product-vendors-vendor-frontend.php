<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Frontend Class.
 *
 * Handles the frontend UI items.
 *
 * @category Frontend
 * @package  WooCommerce Product Vendors/Frontend
 * @version  2.0.0
 */
class WC_Product_Vendors_Vendor_Frontend {
	/**
	 * Init
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public static function init() {
		$self = new self();

		add_action( 'woocommerce_before_my_account', array( $self, 'add_section' ), 0 );

		add_action( 'woocommerce_after_shop_loop_item', array( $self, 'add_sold_by_loop' ), 9 );

		add_action( 'woocommerce_single_product_summary', array( $self, 'add_sold_by_single' ), 39 );

		add_filter( 'woocommerce_get_item_data', array( $self, 'add_sold_by_cart' ), 10, 2 );

		add_action( 'woocommerce_order_item_meta_end', array( $self, 'add_sold_by_order_details' ), 10, 3 );

		add_action( 'woocommerce_archive_description', array( $self, 'display_vendor_logo_profile' ) );

		add_action( 'wp_enqueue_scripts', array( $self, 'register_frontend_scripts_styles' ) );

    	return true;
	}

	/**
	 * Registers frontend scripts and styles
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_frontend_scripts_styles() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_register_script( 'wcpv-frontend-scripts', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/js/wcpv-frontend-scripts' . $suffix . '.js', array( 'jquery' ), WC_PRODUCT_VENDORS_VERSION );
		
		wp_register_style( 'wcpv-frontend-styles', WC_PRODUCT_VENDORS_PLUGIN_URL . '/assets/css/wcpv-frontend-styles.css', null, WC_PRODUCT_VENDORS_VERSION );
		
		wp_enqueue_style( 'wcpv-frontend-styles' );

		return true;
	}

	/**
	 * Add vendor specific section to my accounts page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_section() {
		if ( WC_Product_Vendors_Utils::auth_vendor_user() ) {

			printf( '<a href="%s" title="%s" class="button vendor-dashboard-link">%s</a>', esc_url( admin_url() ), esc_attr( __( 'Vendor Dashboard', 'woocommerce-product-vendors' ) ), __( 'Vendor Dashboard', 'woocommerce-product-vendors' ) );
		}

		return true;
	}

	/**
	 * Add sold by vendor to product archive pages
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_sold_by_loop() {
		global $post;

		$sold_by = get_option( 'wcpv_vendor_settings_display_show_by', 'yes' );

		if ( 'yes' === $sold_by ) {

			$sold_by = WC_Product_Vendors_Utils::get_sold_by_link( $post->ID );

			echo '<em class="wcpv-sold-by-loop">' . apply_filters( 'wcpv_sold_by_text', esc_html__( 'Sold By:', 'woocommerce-product-vendors' ) ) . ' <a href="' . esc_url( $sold_by['link'] ) . '" title="' . esc_attr( $sold_by['name'] ) . '">' . $sold_by['name'] . '</a></em>';
		}

		return true;
	}

	/**
	 * Add sold by vendor to product archive pages
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_sold_by_single() {
		global $post;

		$sold_by = get_option( 'wcpv_vendor_settings_display_show_by', 'yes' );

		if ( 'yes' === $sold_by ) {

			$sold_by = WC_Product_Vendors_Utils::get_sold_by_link( $post->ID );

			echo '<em class="wcpv-sold-by-single">' . apply_filters( 'wcpv_sold_by_text', esc_html__( 'Sold By:', 'woocommerce-product-vendors' ) ) . ' <a href="' . esc_url( $sold_by['link'] ) . '" title="' . esc_attr( $sold_by['name'] ) . '">' . $sold_by['name'] . '</a></em>';
		}

		return true;
	}

	/**
	 * Add sold by vendor to cart page
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param array $values
	 * @param object $cart_item
	 * @return array $values
	 */
	public function add_sold_by_cart( $values, $cart_item ) {
		$sold_by = get_option( 'wcpv_vendor_settings_display_show_by', 'yes' );

		if ( 'yes' === $sold_by ) {

			$sold_by = WC_Product_Vendors_Utils::get_sold_by_link( $cart_item['data']->id );

			$values[] = array(
				'name' => apply_filters( 'wcpv_sold_by_text', esc_html__( 'Sold By', 'woocommerce-product-vendors' ) ),
				'display' => '<em class="wcpv-sold-by-cart"><a href="' . esc_url( $sold_by['link'] ) . '" title="' . esc_attr( $sold_by['name'] ) . '">' . $sold_by['name'] . '</a></em>',
			);
		}

		return $values;
	}

	/**
	 * Add sold by vendor to all order details
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $item_id
	 * @param object $item
	 * @param object $order
	 * @return bool
	 */
	public function add_sold_by_order_details( $item_id, $item, $order ) {
		$sold_by = get_option( 'wcpv_vendor_settings_display_show_by', 'yes' );

		if ( 'yes' === $sold_by ) {

			$sold_by = WC_Product_Vendors_Utils::get_sold_by_link( $item['product_id'] );

			echo '<br /><em class="wcpv-sold-by-order-details">' . apply_filters( 'wcpv_sold_by_text', esc_html__( 'Sold By:', 'woocommerce-product-vendors' ) ) . ' <a href="' . esc_url( $sold_by['link'] ) . '" title="' . esc_attr( $sold_by['name'] ) . '">' . $sold_by['name'] . '</a></em>';
		}

		return true;
	}

	/**
	 * Displays the vendor logo and profile
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function display_vendor_logo_profile() {
		global $wp_query;

		$term =	$wp_query->queried_object;

		if ( ! is_object( $term ) || empty( $term->term_id ) ) {
			return;
		}

		if ( is_tax( WC_PRODUCT_VENDORS_TAXONOMY, $term->term_id ) ) {
			$vendor_data = get_term_meta( $term->term_id, 'vendor_data', true );

			// logo
			if ( ! empty( $vendor_data['logo'] ) && 'yes' === get_option( 'wcpv_vendor_settings_vendor_display_logo', 'yes' ) ) {

				echo '<p class="wcpv-vendor-logo">' . wp_get_attachment_image( absint( $vendor_data['logo'] ), 'full' ) . '</p>' . PHP_EOL;
			}

			// ratings
			$show_ratings = get_option( 'wcpv_vendor_settings_vendor_review', 'yes' );

			if ( 'yes' === $show_ratings ) {
				echo WC_Product_Vendors_Utils::get_vendor_rating_html( $term->term_id );
			}

			// profile
			if ( ! empty( $vendor_data['profile'] ) && 'yes' === get_option( 'wcpv_vendor_settings_vendor_display_profile', 'yes' ) ) {

				echo '<div class="wcpv-vendor-profile entry-summary">' . wpautop( wp_kses_post( $vendor_data['profile'] ) ) . '</div>' . PHP_EOL;
			}
		}

		return true;
	}
}

WC_Product_Vendors_Vendor_Frontend::init();
