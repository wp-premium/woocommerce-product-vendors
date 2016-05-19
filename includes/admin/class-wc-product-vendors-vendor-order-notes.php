<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Order Notes Class.
 *
 * A class that generates the order notes.
 *
 * @category Order
 * @package  WooCommerce Product Vendors/Vendor Order Notes
 * @version  2.0.0
 */
class WC_Product_Vendors_Vendor_Order_Notes {
	/**
	 * Constructor
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @return bool
	 */
	public function __construct() {
		// add note ajax
		add_action( 'wp_ajax_wc_product_vendors_vendor_add_order_note', array( $this, 'add_order_note' ) );

		return true;
	}

	/**
	 * Adds a note (comment) to the order.
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param string $note Note to add.
	 * @param int $is_customer_note (default: 0) Is this a note for the customer?
	 * @param bool added_by_user Was the note added by a user?
	 * @return int Comment ID.
	 */
	public function add_order_note() {
		if ( ! wp_verify_nonce( $_POST['security'], '_wc_product_vendors_vendor_add_order_note_nonce' ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( empty( $_POST['note'] ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( empty( $_POST['post_id'] ) ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		if ( ! WC_Product_Vendors_Utils::auth_vendor_user() ) {
			wp_die( __( 'Cheatin&#8217; huh?', 'woocommerce-product-vendors' ) );
		}

		$post_id          = $_POST['post_id'];
		$note             = $_POST['note'];
		$note_type        = sanitize_text_field( $_POST['note_type'] );
		$is_customer_note = 0;

		// check which note type it is
		if ( ! empty( $note_type ) && 'customer' === $note_type ) {
			$is_customer_note = 1;
		}

		$user                 = get_user_by( 'id', get_current_user_id() );
		$comment_author       = $user->display_name;
		$comment_author_email = $user->user_email;

		$comment_post_ID        = absint( $post_id );
		$comment_author_url     = '';
		$comment_content        = wpautop( wptexturize( wp_kses_post( $note ) ) );
		$comment_agent          = 'WooCommerce';
		$comment_type           = 'order_note';
		$comment_parent         = 0;
		$comment_approved       = 1;
		$commentdata            = apply_filters( 'wcpv_new_order_note_data', compact( 'comment_post_ID', 'comment_author', 'comment_author_email', 'comment_author_url', 'comment_content', 'comment_agent', 'comment_type', 'comment_parent', 'comment_approved' ), array( 'order_id' => $post_id, 'is_customer_note' => $is_customer_note ) );

		$comment_id = wp_insert_comment( $commentdata );

		if ( $is_customer_note ) {
			add_comment_meta( $comment_id, 'is_customer_note', 1 );

			do_action( 'wcpv_customer_order_note', $post_id, $commentdata['comment_content'], WC_Product_Vendors_Utils::get_logged_in_vendor() );
		}

		// retrieve note
		$note = get_comment( $comment_id );

		$output = '';
		$output .= '<li rel="' . esc_attr( $comment_id ) . '" class="note ';

		if ( $is_customer_note ) {
			$output .= 'customer-note';
		}

		$output .= '"><div class="note_content">';
		$output .= $comment_content;
		$output .= '</div>';

		$output .= '<p class="meta">';
		$output .= '<abbr class="exact-date" title="' . esc_attr( $note->comment_date ) . '">';
		$output .= sprintf( __( 'added on %1$s at %2$s', 'woocommerce-product-vendors' ), date_i18n( wc_date_format(), strtotime( $note->comment_date ) ), date_i18n( wc_time_format(), strtotime( $note->comment_date ) ) );
		$output .= '</abbr>';

		if ( $note->comment_author !== 'WooCommerce' ) {
			$output .= sprintf( ' ' . __( 'by %s (%s)', 'woocommerce-product-vendors' ), $note->comment_author, WC_Product_Vendors_Utils::get_logged_in_vendor( 'name' ) );
		}
		
		$output .= '</p>';

		$output .= '</li>';

		echo $output;
		exit;
	}

	/**
	 * Outputs the order notes
	 *
	 * @access public
	 * @since 2.0.0
	 * @version 2.0.0
	 * @param object $post
	 * @return mixed
	 */
	public function output( $post ) {
		global $post;

		$args = array(
			'post_id'   => $post->ID,
			'orderby'   => 'comment_ID',
			'order'     => 'DESC',
			'approve'   => 'approve',
			'type'      => 'order_note'
		);

		$current_user = wp_get_current_user();

		$current_user_email = $current_user->user_email;

		remove_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		$notes = get_comments( $args );

		add_filter( 'comments_clauses', array( 'WC_Comments', 'exclude_order_comments' ), 10, 1 );

		echo '<ul class="order_notes">';

		if ( $notes ) {

			foreach( $notes as $note ) {
				// get the user of the comment
				$user = ! empty( $note->comment_author_email ) ? get_user_by( 'email', $note->comment_author_email ) : false;

				// only show order notes from WooCommerce, store owners and vendor themselves
				if ( 'WooCommerce' === $note->comment_author || ( $user && user_can( $user, 'manage_options' ) ) || $note->comment_author_email === $current_user_email ) {

					$note_classes = get_comment_meta( $note->comment_ID, 'is_customer_note', true ) ? array( 'customer-note', 'note' ) : array( 'note' );

					$note_classes = apply_filters( 'woocommerce_order_note_class', $note_classes, $note );

					?>
					<li rel="<?php echo absint( $note->comment_ID ) ; ?>" class="<?php echo esc_attr( implode( ' ', $note_classes ) ); ?>">
						<div class="note_content">
							<?php echo wpautop( wptexturize( wp_kses_post( $note->comment_content ) ) ); ?>
						</div>
						<p class="meta">
							<abbr class="exact-date" title="<?php echo $note->comment_date; ?>"><?php printf( __( 'added on %1$s at %2$s', 'woocommerce-product-vendors' ), date_i18n( wc_date_format(), strtotime( $note->comment_date ) ), date_i18n( wc_time_format(), strtotime( $note->comment_date ) ) ); ?></abbr>
							<?php if ( $note->comment_author !== 'WooCommerce' ) printf( ' ' . __( 'by %s', 'woocommerce-product-vendors' ), $note->comment_author ); ?>
						</p>
					</li>
					<?php
				}
			}

		} else {
			echo '<li>' . esc_html__( 'There are no notes yet.', 'woocommerce-product-vendors' ) . '</li>';
		}

		echo '</ul>';
		?>
		<div class="add_note">
			<h4><?php esc_html_e( 'Add note', 'woocommerce-product-vendors' ); ?> <?php echo wc_help_tip( __( 'Add a note for your reference, or add a customer note (the user will be notified).', 'woocommerce-product-vendors' ) ); ?></h4>
			<p>
				<textarea type="text" name="order_note" id="add_order_note" class="input-text" cols="20" rows="5"></textarea>
			</p>
			<p>
				<select name="order_note_type" id="order_note_type">
					<option value=""><?php esc_html_e( 'Private note', 'woocommerce-product-vendors' ); ?></option>
					<option value="customer"><?php esc_html_e( 'Note to customer', 'woocommerce-product-vendors' ); ?></option>
				</select>
				<a href="#" class="add_note button" data-id="<?php echo esc_attr( $post->ID ); ?>"><?php esc_html_e( 'Add', 'woocommerce-product-vendors' ); ?></a>
			</p>
		</div>
		<?php
	}
}
