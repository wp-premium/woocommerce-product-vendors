jQuery( document ).ready( function( $ ) {
	'use strict';

	// create namespace to avoid any possible conflicts
	$.wc_product_vendors_vendor_admin = {

		init: function() {
			$( '#your-profile tr.show-admin-bar' ).remove();

			// check if vendor is pending - show pending message
			if ( wcpv_vendor_admin_local.isPendingVendor ) {
				$( '#dashboard-widgets' ).html( '<p class="wcpv-pending-vendor-message">' + wcpv_vendor_admin_local.pending_vendor_message + '</p>' );
			}

			$( '.taxonomy-wcpv_product_vendors, .toplevel_page_wcpv-vendor-settings' ).on( 'click', '.wcpv-upload-logo', function( e ) {
				e.preventDefault();

				// create the media frame
				var i18n = wcpv_vendor_admin_local,
					inputField = $( this ).parents( '.form-field' ).find( 'input[name="vendor_data[logo]"]' ),
					previewField = $( this ).parents( '.form-field' ).find( '.wcpv-logo-preview-image' ),
					mediaFrame = wp.media.frames.mediaFrame = wp.media({

						title: i18n.modalLogoTitle,

						button: {
							text: i18n.buttonLogoText
						},

						// only images
						library: {
							type: 'image'
						},

						multiple: false
					});

				// after a file has been selected
				mediaFrame.on( 'select', function() {
					var selection = mediaFrame.state().get( 'selection' );

					selection.map( function( attachment ) {
	
						attachment = attachment.toJSON();

						if ( attachment.id ) {

							// add attachment id to input field
							inputField.val( attachment.id );

							// show preview image
							previewField.prop( 'src', attachment.url ).removeClass( 'hide' );

							// show remove image icon
							$( inputField ).parents( '.form-field' ).find( '.wcpv-remove-image' ).show();
						}
					});
				});

				// open the modal frame
				mediaFrame.open();
			});
		
			$( '.taxonomy-wcpv_product_vendors, .toplevel_page_wcpv-vendor-settings' ).on( 'click', '.wcpv-remove-image', function( e ) {
				e.preventDefault();

				$( this ).hide();
				$( this ).parents( '.form-field' ).find( '.wcpv-logo-preview-image' ).prop( 'src', '' ).addClass( 'hide' );
				$( 'input[name="vendor_data[logo]"]' ).val( '' );
			});

			// remove product visibility options
			$( '.vendor #catalog-visibility' ).hide();

			// remove product level tax settings
			$( '#general_product_data #_tax_status' ).prop( 'disabled', true ).parents( '.options_group' ).hide();

			// remove product shipping class settings
			$( '#product_shipping_class' ).parents( '.options_group' ).hide();

			// remove product shipping class settings on variation level
			$( document.body ).on( 'woocommerce_variations_loaded woocommerce_variations_added', function() {
				$( 'select[id^="variable_shipping_class"]' ).parent( 'p' ).hide();
				$( 'select[name^="variable_tax_class"]' ).parent( 'p' ).hide();
			});

			// vendor switcher
			$( '#wpadminbar' ).on( 'click', '.wcpv-vendor-switch', function( e ) {
				e.preventDefault();

				var data = {
						'action': 'wc_product_vendors_switch',
						'switch_vendor_nonce': $( this ).find( '#wcpv_vendor_switch_nonce' ).val(),
						'vendor': $( this ).find( '.wcpv-vendor' ).val()
					}

				$.post( wcpv_vendor_admin_local.ajaxurl, data, function( response ) {
					if ( 'switched' === response ) {
						// reload the page
						location.reload( true );
					}
				});
			});

			// vendor support form
			$( document.body ).on( 'submit', 'form.wcpv-vendor-support-form', function( e ) {
				e.preventDefault();

				var $data = {
						action: 'wc_product_vendors_vendor_support',
						ajaxVendorSupportNonce: wcpv_vendor_admin_local.ajaxVendorSupportNonce,
						form_items: $( this ).serialize()
					},
					form = $( this );

				// clear all messages first
				$( '.wcpv-vendor-support-form-message' ).remove();

				form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });

				$.post( wcpv_vendor_admin_local.ajaxurl, $data, function( response ) {
					form.unblock();

					if ( response.length && 'success' === response ) {
						form.before( '<p class="wcpv-vendor-support-form-success wcpv-vendor-support-form-message">' + wcpv_vendor_admin_local.vendorSupportSuccess + '</p>' );

						// clear all fields
						$( 'input, textarea', form ).not( 'input[type="submit"]' ).val( '' );
					} else {
						var errors = '';

						$.each( response.errors, function( index, value ) {
							errors += '<span class="error">' + value + '</span>';
						});

						form.before( '<div class="wcpv-vendor-support-form-errors wcpv-vendor-support-form-message">' + errors + '</div' );
					}
				});
			});

			// order notes
			$( '#woocommerce-order-notes' ).on( 'click', 'a.add_note', function() {
				if ( ! $( 'textarea#add_order_note' ).val() ) {
					return;
				}

				$( '#woocommerce-order-notes' ).block({
					message: null,
					overlayCSS: {
						background: '#fff',
						opacity: 0.6
					}
				});

				var data = {
					action:    'wc_product_vendors_vendor_add_order_note',
					post_id:   $( this ).data( 'id' ),
					note:      $( 'textarea#add_order_note' ).val(),
					note_type: $( 'select#order_note_type' ).val(),
					security:  wcpv_vendor_admin_local.ajaxAddOrderNoteNonce
				};

				$.post( wcpv_vendor_admin_local.ajaxurl, data, function( response ) {
					$( 'ul.order_notes' ).prepend( response );
					$( '#woocommerce-order-notes' ).unblock();
					$( '#add_order_note' ).val( '' );
				});

				return false;
			});

			// remove product bulk edit items
			$( '.inline-edit-col .product_shipping_class-checklist' ).prev().prev( '.title.inline-edit-categories-label' ).remove();
			$( '.inline-edit-col .product_shipping_class-checklist' ).remove();

			$( '.tax_status' ).parent().prev( '.title' ).remove();
			$( '.tax_status' ).remove();

			$( '.tax_class' ).parent().prev( '.title' ).remove();
			$( '.tax_class' ).remove();

			$( '.shipping_class' ).parent().prev( '.title' ).remove();
			$( '.shipping_class' ).remove();

			$( '.pass-shipping-tax' ).parent().prev( '.title' ).remove();
			$( '.pass-shipping-tax' ).remove();

			$( '.inline-edit-product select.featured' ).parents( 'label' ).eq( 0 ).remove();
			$( '.inline-edit-product label.featured' ).remove();

			$( '.inline-edit-product select.visibility' ).parents( 'label' ).eq( 0 ).remove();
			$( '.inline-edit-product select.visibility' ).closest( 'label' ).remove();

			// remove bookings detail items
			$( '#woocommerce-customer-data' ).find( 'tr.view' ).remove();

			// remove bookings resources panel
			$( 'li.bookings_tab.bookings_resources_tab' ).hide();
			$( '#bookings_resources' ).hide();
			$( '#_wc_booking_has_resources' ).parent( 'label' ).hide();
		}
	}; // close namespace

	$.wc_product_vendors_vendor_admin.init();
// end document ready
});
