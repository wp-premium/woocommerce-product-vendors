jQuery( document ).ready( function( $ ) {
	'use strict';

	// create namespace to avoid any possible conflicts
	$.wc_product_vendors_vendor_frontend = {

		init: function() {
			var topOfForm = $( '.wcpv-shortcode-registration-form' ).position();

			$( document.body ).on( 'submit', 'form.wcpv-shortcode-registration-form', function( e ) {
				e.preventDefault();

				var $data = {
						action: 'wc_product_vendors_registration',
						ajaxRegistrationNonce: wcpv_registration_local.ajaxRegistrationNonce,
						form_items: $( this ).serialize()
					},
					form = $( this );

				// clear all messages first
				$( '.wcpv-registration-message' ).remove();

				form.block({ message: null, overlayCSS: { background: '#fff', opacity: 0.6 } });

				$.post( wcpv_registration_local.ajaxurl, $data, function( response ) {
					form.unblock();

					if ( response.length && 'success' === response ) {
						$( document.body ).trigger( 'wcpv_vendor_registration_on_success' );

						form.before( '<p class="wcpv-shortcode-registration-success wcpv-registration-message">' + wcpv_registration_local.success + '</p>' );
						form.fadeOut( 'fast' );
						
						// clear all fields
						$( 'input, textarea', form ).not( 'input[type="submit"]' ).val( '' );
					} else {
						var errors = '';

						$.each( response.errors, function( index, value ) {
							errors += '<span class="error">' + value + '</span>';
						});

						form.before( '<div class="wcpv-shortcode-registration-form-errors wcpv-registration-message">' + errors + '</div' );

					}

					$( 'html, body' ).scrollTop( topOfForm.top - 50 );
				});
			});
		}
	}; // close namespace

	$.wc_product_vendors_vendor_frontend.init();
// end document ready
});
