<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Stripe_Checkout', false ) ) {

	class Imm_Learndash_Ls_Stripe_Checkout extends Imm_Learndash_Ls_Stripe_Base {

		public function __construct() {
			parent::__construct();

			add_action( 'wp_ajax_nopriv_imm_ls_ld_stripe_init_checkout', array( $this, 'ajax_init_checkout' ) );
			add_action( 'wp_ajax_imm_ls_ld_stripe_init_checkout', array( $this, 'ajax_init_checkout' ) );
		}

		/**
		 * AJAX function handler for init checkout
		 *
		 * @uses imm_ls_ld_stripe_init_checkout WP AJAX action string
		 * @return void
		 */
		public function ajax_init_checkout() {
			if ( ! $this->is_transaction_legit() ) {
				wp_die( esc_html__( 'Cheatin\' huh?', 'learndash-extend-expiry' ) );
			}

			$resource_id = intval( $_POST['stripe_resource_id'] ); //phpcs:ignore WordPress.Security.NonceVerification.Missing
			$session_id  = $this->set_session( $resource_id );

			if ( ! isset( $session_id['error'] ) ) {
				echo wp_json_encode(
					array(
						'status'     => 'success',
						'session_id' => $session_id,
					)
				);
			} else {
				echo wp_json_encode(
					array(
						'status'  => 'error',
						'payload' => $session_id['error'],
					)
				);
			}

			wp_die();
		}

		public function set_session( $resource_id = null ) {
			$this->config();
			$params = $this->get_resource_args( $resource_id );

			$stripe_customer_id = get_user_meta( get_current_user_id(), 'stripe_customer_id', true );
			$customer           = ! empty( $stripe_customer_id ) ? $stripe_customer_id : null;

			$user_email = is_user_logged_in() ? wp_get_current_user()->user_email : null;

			$resourse_page_url   = get_permalink( $resource_id );
			$success_url         = ! empty( $this->options['return_url'] ) ? $this->options['return_url'] : $resourse_page_url;
			$success_url         = add_query_arg(
				array(
					'imm_ld_stripe' => 'success',
					'session_id'    => '{CHECKOUT_SESSION_ID}',
				),
				$success_url
			);
			$client_reference_id = array(
				'resource_id' => $resource_id,
			);
			$client_reference_id = array_map(
				function( $key, $value ) {
					return "{$key}={$value}";
				},
				array_keys( $client_reference_id ),
				$client_reference_id
			);
			$client_reference_id = implode( ';', $client_reference_id );

			$line_items = array(
				array(
					'name'     => $params['resource_name'],
					'images'   => $params['resource_images'],
					'amount'   => $params['resource_price'],
					'currency' => $params['currency'],
					'quantity' => 1,
				),
			);

			$payment_intent_data = null;
			if ( 'paynow' === $params['resource_price_type'] ) {
				$payment_intent_data = array(
					'receipt_email' => $user_email,
				);
			}

			$subscription_data = null;
			if ( 'subscribe' === $params['resource_price_type'] ) {
				if ( empty( $params['resource_interval'] ) || empty( $params['resource_interval_count'] ) || empty( $params['resource_price'] ) ) {
					return;
				}

				$plan_id = get_post_meta( $resource_id, 'stripe_plan_id', false );
				$plan_id = end( $plan_id );

				if ( ! empty( $plan_id ) ) {
					try {
						$plan = \Stripe\Plan::retrieve(
							array(
								'id'     => $plan_id,
								'expand' => array( 'product' ),
							)
						);

						if ( ( isset( $plan ) && is_object( $plan ) ) &&
							$plan->amount !== $params['resource_price'] ||
							strtolower( $params['currency'] ) !== $plan->currency ||
							$plan->id !== $plan_id ||
							$plan->interval !== $params['resource_interval'] ||
							htmlspecialchars_decode( $plan->product->name ) !== stripslashes( sanitize_text_field( $params['resource_name'] ) ) ||
							$plan->interval_count !== $params['resource_interval_count']
						) {
							// Don't delete the old plan as old subscription may
							// still attached to it

							// Create a new plan
							$plan = \Stripe\Plan::create(
								array(
									// Required
									'amount'         => esc_attr( $params['resource_price'] ),
									'currency'       => strtolower( $params['currency'] ),
									'id'             => $params['resource_plan_id'] . '-' . $this->generate_random_string( 5 ),
									'interval'       => $params['resource_interval'],
									'product'        => array(
										'name' => stripslashes( sanitize_text_field( $params['resource_name'] ) ),
									),
									// Optional
									'interval_count' => esc_attr( $params['resource_interval_count'] ),
								)
							);

							$plan_id = $plan->id;

							add_post_meta( $resource_id, 'stripe_plan_id', $plan_id, false );
						}
					} catch ( Exception $e ) {
						// Create a new plan
						$plan = \Stripe\Plan::create(
							array(
								// Required
								'amount'         => esc_attr( $params['resource_price'] ),
								'currency'       => strtolower( $params['currency'] ),
								'id'             => $params['resource_plan_id'] . '-' . $this->generate_random_string( 5 ),
								'interval'       => $params['resource_interval'],
								'product'        => array(
									'name' => stripslashes( sanitize_text_field( $params['resource_name'] ) ),
								),
								// Optional
								'interval_count' => esc_attr( $params['resource_interval_count'] ),
							)
						);

						$plan_id = $plan->id;

						add_post_meta( $resource_id, 'stripe_plan_id', $plan_id, false );
					}
				} else {
					// Create a new plan
					$plan = \Stripe\Plan::create(
						array(
							// Required
							'amount'         => esc_attr( $params['resource_price'] ),
							'currency'       => strtolower( $params['currency'] ),
							'id'             => $params['resource_plan_id'] . '-' . $this->generate_random_string( 5 ),
							'interval'       => $params['resource_interval'],
							'product'        => array(
								'name' => stripslashes( sanitize_text_field( $params['resource_name'] ) ),
							),
							// Optional
							'interval_count' => esc_attr( $params['resource_interval_count'] ),
						)
					);

					$plan_id = $plan->id;

					add_post_meta( $resource_id, 'stripe_plan_id', $plan_id, false );
				}

				$subscription_data = array(
					'items' => array(
						array(
							'plan' => $plan_id,
						),
					),
				);

				$line_items = null;
			}

			$session = false;
			try {
				$session = \Stripe\Checkout\Session::create(
					apply_filters(
						'learndash_stripe_session_args',
						array(
							'allow_promotion_codes' => true,
							'customer'              => $customer,
							'payment_method_types'  => $this->get_payment_methods(),
							'line_items'            => $line_items,
							'client_reference_id'   => $client_reference_id,
							'success_url'           => $success_url,
							'cancel_url'            => $resourse_page_url,
							'payment_intent_data'   => $payment_intent_data,
							'subscription_data'     => $subscription_data,
						)
					)
				);
			} catch ( Exception $e ) {
				return $e->getJsonBody();
			}

			if ( is_object( $session ) && is_a( $session, 'Stripe\Checkout\Session' ) ) {
				$this->session_id = $session->id;
				setcookie( 'ld_stripe_session_' . $resource_id, $this->session_id, time() + DAY_IN_SECONDS );
				return $this->session_id;
			}
		}


		/**
		 * Integration button scripts
		 *
		 * @return void
		 */
		public function button_scripts() {
			?>
			<script src="https://cdn.jsdelivr.net/npm/js-cookie@rc/dist/js.cookie.min.js"></script> <?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
			<script src="https://js.stripe.com/v3/"></script> <?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
			<script type="text/javascript">
			"use strict";

			function ownKeys(object, enumerableOnly) { var keys = Object.keys(object); if (Object.getOwnPropertySymbols) { var symbols = Object.getOwnPropertySymbols(object); if (enumerableOnly) symbols = symbols.filter(function (sym) { return Object.getOwnPropertyDescriptor(object, sym).enumerable; }); keys.push.apply(keys, symbols); } return keys; }

			function _objectSpread(target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i] != null ? arguments[i] : {}; if (i % 2) { ownKeys(Object(source), true).forEach(function (key) { _defineProperty(target, key, source[key]); }); } else if (Object.getOwnPropertyDescriptors) { Object.defineProperties(target, Object.getOwnPropertyDescriptors(source)); } else { ownKeys(Object(source)).forEach(function (key) { Object.defineProperty(target, key, Object.getOwnPropertyDescriptor(source, key)); }); } } return target; }

			function _defineProperty(obj, key, value) { if (key in obj) { Object.defineProperty(obj, key, { value: value, enumerable: true, configurable: true, writable: true }); } else { obj[key] = value; } return obj; }

			jQuery(document).ready(function ($) {
				var stripe = Stripe('<?php echo esc_attr( $this->publishable_key ); ?>');
				var ld_stripe_ajaxurl = <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>;
				$(document).on('submit', '.learndash-stripe-checkout', function (e) {
				e.preventDefault();
				var inputs = $(this).serializeArray();
				inputs = inputs.reduce(function (new_inputs, value, index, inputs) {
				new_inputs[value.name] = value.value;
				return new_inputs;
				}, {});
				var LD_Cookies = Cookies.noConflict();
				var ld_stripe_session_id = LD_Cookies.get('ld_stripe_session_id_' + inputs.stripe_resource_id);

				if (typeof ld_stripe_session_id != 'undefined') {
				stripe.redirectToCheckout({
				sessionId: ld_stripe_session_id
				}).then(function (result) {
				if (result.error.message.length > 0) {
				alert(result.error.message);
				}
				});
				} else {
				$('.checkout-dropdown-button').hide();
				$(this).closest('.learndash_checkout_buttons').addClass('ld-loading');
				$('head').append('<style class="ld-stripe-css">' + '.ld-loading::after { background: none !important; }' + '.ld-loading::before { width: 30px !important; height: 30px !important; left: 53% !important; top: 62% !important; }' + '</style>');
				$('.learndash_checkout_buttons .learndash_checkout_button').css({
				backgroundColor: 'rgba(182, 182, 182, 0.1)'
				}); 

				// Set Stripe session
				$.ajax({
				url: ld_stripe_ajaxurl,
				type: 'POST',
				dataType: 'json',
				data: _objectSpread({}, inputs)
				}).done(function (response) {
				if (response.status === 'success') {
				LD_Cookies.set('ld_stripe_session_id_' + inputs.stripe_resource_id, response.session_id); // If session is created

				stripe.redirectToCheckout({
				sessionId: response.session_id
				}).then(function (result) {
				if (result.error.message.length > 0) {
				alert(result.error.message);
				}
				});
				} else {
				console.log( response );
				alert( response.payload.message );
				}

				$('.learndash_checkout_buttons').removeClass('ld-loading');
				$('style.ld-stripe-css').remove();
				$('.learndash_checkout_buttons .learndash_checkout_button').css({
				backgroundColor: ''
				});
				});
				}
				});
				});
			</script>
			<?php
		}

		/**
		 * Output transaction message
		 *
		 * @return void
		 */
		public function output_transaction_message() {
			if ( ! isset( $_GET['imm_ld_stripe'] ) || empty( $_GET['imm_ld_stripe'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			switch ( $_GET['imm_ld_stripe'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				case 'success':
					$message = __( 'Your transaction was successful. Please log in to access your course.', 'learndash-extend-expiry' );
					break;

				default:
					$message = false;
					break;
			}

			if ( ! $message ) {
				return;
			}

			?>

	  <script type="text/javascript">
		  jQuery( document ).ready( function( $ ) {
			  alert( '<?php echo $message; ?>' );
		  });
	  </script>

			<?php
		}

		/**
		 * Record transaction in database
		 *
		 * @param  array  $session  Transaction data passed through $_POST
		 * @param  int    $course_id    Post ID of a course
		 * @param  int    $user_id      ID of a user
		 * @param  string $user_email   Email of the user
		 */
		public function record_transaction( $session, $course_id, $user_id, $user_email ) {
			// ld_debug( 'Starting Transaction Creation.' );

			$currency = $session->display_items[0]->currency;
			$amount   = $session->display_items[0]->amount;

			$transaction = array(
				'stripe_nonce'               => 'n/a',
				'stripe_sesion_id'           => $session->id,
				'stripe_client_reference_id' => $session->client_reference_id,
				'stripe_customer'            => $session->customer,
				'stripe_payment_intent'      => $session->payment_intent,
				'customer_email'             => $user_email,
				'stripe_price'               => ! $this->is_zero_decimal_currency( $currency ) && $amount > 0 ? number_format( $amount / 100, 2 ) : $amount,
				'stripe_currency'            => $currency,
				'stripe_name'                => get_the_title( $course_id ),
				'user_id'                    => $user_id,
				'course_id'                  => $course_id,
				'subscription'               => $session->subscription,
			);

			// ld_debug( 'Course Title: ' . $course_title );

			$post_id = wp_insert_post(
				array(
					'post_title'  => "Course {$transaction['course_title']} Purchased By {$user_email}",
					'post_type'   => 'sfwd-transactions',
					'post_status' => 'publish',
					'post_author' => $user_id,
				)
			);

			// ld_debug( 'Created Transaction. Post Id: ' . $post_id );
			foreach ( $transaction as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

	}
	new Imm_Learndash_Ls_Stripe_Checkout();
}
