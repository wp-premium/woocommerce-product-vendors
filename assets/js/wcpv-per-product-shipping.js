jQuery( document ).ready( function( $ ) {
	'use strict';

	// create namespace to avoid any possible conflicts
	$.wc_product_vendors_per_product_shipping = {

		init: function() {

			// per product shipping
			$( document.body )
				.on( 'init_shipping_per_product', function() {
					$( document.body ).trigger( 'init_shipping_per_product_sortable' );
				})
				.on( 'init_shipping_per_product_sortable', function() {
					$( '.wcpv-per-product-shipping-rules tbody' ).sortable({
						items:'tr',
						cursor:'move',
						axis:'y',
						scrollSensitivity:40,
						forcePlaceholderSize: true,
						helper: 'clone',
						opacity: 0.65,
						placeholder: 'wc-metabox-sortable-placeholder',
						start:function( event, ui ){
							ui.item.css( 'background-color', '#f6f6f6' );
						},
						stop:function( event, ui ){
							ui.item.removeAttr( 'style' );
						}
					});
				});

			$( '#woocommerce-product-data' ).on( 'woocommerce_variations_added woocommerce_variations_loaded', function() {
				$( document.body ).trigger( 'init_shipping_per_product' );
			});

			$( '#woocommerce-product-data' )
				.on( 'focus', '.wcpv-per-product-shipping-rules input', function() {
					$( '.wcpv-per-product-shipping-rules tr' ).removeClass( 'current' );
					$( this ).closest( 'tr' ).addClass( 'current' );
				})
				.on( 'click', '.wcpv-per-product-shipping-rules input', function() {
					$( this ).focus();

				  	return true;
				})
				.on( 'click', '.wcpv-per-product-shipping-rules .remove', function() {
					var $tbody = $( this ).closest( '.wcpv-per-product-shipping-rules' ).find( 'tbody' );

					if ( $tbody.find( 'tr.current' ).size() > 0 ) {
						$tbody.find( 'tr.current' ).find( 'input' ).val( '' );
						$tbody.find( 'tr.current' ).hide();
					} else {
						alert( wcpv_per_product_shipping_local.i18n_no_row_selected );
					}

					return false;
				})
				.on( 'click', '.wcpv-per-product-shipping-rules .insert', function() {
					var $tbody = $( this ).closest( '.wcpv-per-product-shipping-rules' ).find( 'tbody' ),
						postid = $( this ).data( 'postid' ),
						code = '<tr>\
							<td class="sort">&nbsp;</td>\
							<td class="country"><input type="text" value="" placeholder="*" name="per_product_country[' + postid + '][new][]" /></td>\
							<td class="state"><input type="text" value="" placeholder="*" name="per_product_state[' + postid + '][new][]" /></td>\
							<td class="postcode"><input type="text" value="" placeholder="*" name="per_product_postcode[' + postid + '][new][]" /></td>\
							<td class="cost"><input type="text" value="" placeholder="0.00" name="per_product_cost[' + postid + '][new][]" /></td>\
							<td class="item_cost"><input type="text" value="" placeholder="0.00" name="per_product_item_cost[' + postid + '][new][]" /></td>\
							</tr>';

					if ( $tbody.find( 'tr.current' ).size() > 0 ) {
						$tbody.find( 'tr.current' ).after( code );
					} else {
						$tbody.append( code );
					}

					return false;
				})
				.on( 'click', '.wcpv-per-product-shipping-rules .export', function() {
					var postid = $( this ).data( 'postid' ),
						csv_data = "data:application/csv;charset=utf-8," + wcpv_per_product_shipping_local.i18n_product_id + "," + wcpv_per_product_shipping_local.i18n_country_code + "," + wcpv_per_product_shipping_local.i18n_state + "," + wcpv_per_product_shipping_local.i18n_postcode + "," + wcpv_per_product_shipping_local.i18n_cost + "," + wcpv_per_product_shipping_local.i18n_item_cost + "\n";

					$( this ).closest( '.wcpv-per-product-shipping-rules' ).find( 'tbody tr:not(.sort)' ).each( function() {
						var row = postid + ',';
						
						$( this ).find( 'input' ).each( function() {
							var val = $( this ).val();
							if ( ! val )
								val = $( this ).attr( 'placeholder' );
							row = row + val + ',';
						});

						row = row.substring( 0, row.length - 1 );
						csv_data = csv_data + row + "\n";
					});

					$( this ).attr( 'href', encodeURI( csv_data ) );

					return true;
				});

				$( document.body ).trigger( 'init_shipping_per_product' );
		}
	}; // close namespace

	$.wc_product_vendors_per_product_shipping.init();
// end document ready
});
