<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Taxonomy Class
 *
 * Add custom taxonomy to WordPress.
 *
 * @category Taxonomy
 * @package  WooCommerce Product Vendors/Taxonomy
 * @version  2.0.0
 */
class WC_Product_Vendors_Taxonomy {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		// registers vendor taxonomy
		add_action( 'init', array( $this, 'register_vendor_taxonomy' ), 9 );

    	return true;
	}

	/**
	 * Register vendor taxonomy
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function register_vendor_taxonomy() {
		$labels = array(
			'name'              => _x( 'Vendors', 'taxonomy general name', 'woocommerce-product-vendors' ),
			'singular_name'     => _x( 'Vendor', 'taxonomy singular name', 'woocommerce-product-vendors' ),
			'search_items'      => __( 'Search Vendors', 'woocommerce-product-vendors' ),
			'all_items'         => __( 'All Vendors', 'woocommerce-product-vendors' ),
			'popular_items'     => __( 'Popular Vendors', 'woocommerce-product-vendors' ),
			'parent_item'       => __( 'Parent Vendor', 'woocommerce-product-vendors' ),
			'parent_item_colon' => __( 'Parent Vendor:', 'woocommerce-product-vendors' ),
			'edit_item'         => __( 'Edit Vendor', 'woocommerce-product-vendors' ),
			'view_item'         => __( 'View Vendor Page', 'woocommerce-product-vendors' ),
			'update_item'       => __( 'Update Vendor', 'woocommerce-product-vendors' ),
			'add_new_item'      => __( 'Add New Vendor', 'woocommerce-product-vendors' ),
			'new_item_name'     => __( 'New Vendor Name', 'woocommerce-product-vendors' ),
			'menu_name'         => __( 'Vendors', 'woocommerce-product-vendors' ),
			'not_found'         => __( 'No Vendors Found', 'woocommerce-product-vendors' ),
		);

		$args = array(
			'hierarchical'       => false,
			'labels'             => $labels,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'query_var'          => true,
			'capabilities'       => array( 'manage_categories' ),
			'rewrite'            => array( 'slug' => apply_filters( 'wcpv_vendor_slug', 'vendor' ) ),
			'show_in_quick_edit' => false,
		);

		if ( current_user_can( 'manage_categories' ) ) {
			$args['meta_box_cb'] = array( $this, 'add_meta_box' );
		}

		register_taxonomy( WC_PRODUCT_VENDORS_TAXONOMY, array( 'product' ), apply_filters( 'wcpv_vendor_taxonomy_args', $args ) );
	}

	/**
	 * Adds the taxonomy meta box
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function add_meta_box() {
		global $post;

		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		$args = array(
			'hide_empty'   => false,
			'hierarchical' => false,
		);

		$terms = get_terms( WC_PRODUCT_VENDORS_TAXONOMY, $args );

		if ( ! empty( $terms ) ) {
			$post_term = wp_get_post_terms( $post->ID, WC_PRODUCT_VENDORS_TAXONOMY );

			$post_term = ! empty( $post_term ) ? $post_term[0]->term_id : '';

			$output = '<select class="wcpv-product-vendor-terms-dropdown" name="wcpv_product_term">';

			$output .= '<option value="">' . esc_html__( 'Select a Vendor', 'woocommerce-product-vendors' ) . '</option>';

			foreach( $terms as $term ) {
				$output .= '<option value="' . esc_attr( $term->term_id ) . '" ' . selected( $post_term, $term->term_id, false ) . '>' . $term->name . '</option>';
			}

			$output .= '</select>';

			echo $output;
		} else {
			printf( __( 'Please create vendors by going %sHere%s', 'woocommerce-product-vendors' ), '<a href="' . admin_url( 'edit-tags.php?taxonomy=wcpv_product_vendors&post_type=product' ) . '" title="' . esc_attr__( 'Vendors', 'woocommerce-product-vendors' ) . '">', '</a>' );
		}
	}
}

new WC_Product_Vendors_Taxonomy();
