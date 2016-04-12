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
		
		add_action( 'save_post', array( $this, 'save_product' ) );

		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit' ), 10, 2 );
		add_action( 'bulk_edit_custom_box', array( $this, 'quick_edit' ), 10, 2 );

		add_action( 'save_post', array( $this, 'bulk_and_quick_edit_save_post' ), 10, 2 );

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

	/**
	 * Handles product saving
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @return bool
	 */
	public function save_product( $post_id ) {
		if ( ! current_user_can( 'manage_categories' ) ) {
			return;
		}

		// if not a product bail
		if ( 'product' !== get_post_type( $post_id ) ) {
			return;
		}

		$term = ! empty( $_POST['wcpv_product_term'] ) ? absint( $_POST['wcpv_product_term'] ) : '';

		wp_set_object_terms( $post_id, $term, WC_PRODUCT_VENDORS_TAXONOMY );

		return true;
	}

	/**
	 * Add vendor selection on quick and bulk edit
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $column_name the name of the column to add it to
	 * @param string $post_type
	 * @return bool
	 */
	public function quick_edit( $column_name, $post_type ) {
		if ( 'taxonomy-wcpv_product_vendors' !== $column_name || 'product' !== $post_type ) {
			return;
		}

		$args = array(
			'hide_empty'   => false,
			'hierarchical' => false,
		);

		$terms = get_terms( WC_PRODUCT_VENDORS_TAXONOMY, $args );

		if ( ! empty( $terms ) ) {
			$output = '<fieldset class="inline-edit-col-center"><div class="inline-edit-group"><label class="alignleft"><span class="title">' . esc_html__( 'Vendors', 'woocommerce-product-vendors' ) . '</span>';

			$output .= '<select class="wcpv-product-vendor-terms-dropdown" name="wcpv_product_term">';

			$output .= '<option value="no">' . esc_html__( 'No Change', 'woocommerce-product-vendors' ) . '</option>';
			$output .= '<option value="novendor">' . esc_html__( 'No Vendor', 'woocommerce-product-vendors' ) . '</option>';

			foreach( $terms as $term ) {
				$output .= '<option value="' . esc_attr( $term->term_id ) . '">' . $term->name . '</option>';
			}

			$output .= '</select>';

			$output .= '</label></div></fieldset>';

			echo $output;
		}
	}

	/**
	 * Handles quick and bulk edit saves
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param int $post_id
	 * @param object $post
	 * @return int
	 */
	public function bulk_and_quick_edit_save_post( $post_id, $post ) {
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		// Don't save revisions and autosaves
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return $post_id;
		}

		// Check post type is product
		if ( 'product' !== $post->post_type ) {
			return $post_id;
		}

		// Check user permission
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return $post_id;
		}

		if ( empty( $_REQUEST['wcpv_product_term'] ) || 'no' === $_REQUEST['wcpv_product_term'] ) {
			return $post_id;
		}

		$term = ! empty( $_REQUEST['wcpv_product_term'] ) ? absint( $_REQUEST['wcpv_product_term'] ) : '';

		if ( 'novendor' === $term ) {
			$term = '';
		}
		
		// check if it is a quick edit or bulk edit
		if ( ! empty( $_REQUEST['woocommerce_quick_edit'] ) ) {
			// update the product term
			wp_set_object_terms( $post_id, $term, WC_PRODUCT_VENDORS_TAXONOMY );

			// Clear transient
			wc_delete_product_transients( $post_id );

		} elseif ( ! empty( $_REQUEST['woocommerce_bulk_edit'] ) && ! empty( $_REQUEST['post'] ) ) {
			foreach( $_REQUEST['post'] as $post ) {
				// update the product term
				wp_set_object_terms( absint( $post ), $term, WC_PRODUCT_VENDORS_TAXONOMY );

				// Clear transient
				wc_delete_product_transients( absint( $post ) );
			}
		}

		return $post_id;
	}
}

new WC_Product_Vendors_Taxonomy();
