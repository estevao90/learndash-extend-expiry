( function( $ ) {
	'use strict';

	$( document ).ready( function() {
		learndashLessonsSellingEditBillingCycle();
	} );

	function learndashLessonsSellingEditBillingCycle() {
		if ( $( '.sfwd_options select[name=imm_ls_selling_price_billing_t3]' ).length ) {
			const selector = $( '.sfwd_options select[name=imm_ls_selling_price_billing_t3]' );

			if ( 'undefined' !== typeof selector ) {
				const parent = selector.parent();
				let billingCycle = selector.val();

				function buildNotice( message ) {
					return '<div id="learndash_price_billing_cycle_instructions"><label class="sfwd_help_text">' + message + '</label></div>';
				}

				function outputMessage() {
					let message;
					switch ( billingCycle ) {
						case 'D':
							message = sfwd_data.valid_recurring_paypal_day_range;
							parent.append( buildNotice( message ) );
							break;
						case 'W':
							message = sfwd_data.valid_recurring_paypal_week_range;
							parent.append( buildNotice( message ) );
							break;
						case 'M':
							message = sfwd_data.valid_recurring_paypal_month_range;
							parent.append( buildNotice( message ) );
							break;
						case 'Y':
							message = sfwd_data.valid_recurring_paypal_year_range;
							parent.append( buildNotice( message ) );
							break;
						default:
							break;
					}
				}
				outputMessage();

				selector.on( 'change', function() {
					billingCycle = selector.val();
					$( '#learndash_price_billing_cycle_instructions' ).remove();
					outputMessage( billingCycle );
				} );
			}
		}
	}
}( jQuery ) );
