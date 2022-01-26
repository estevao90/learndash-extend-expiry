<?php
// phpcs:disable WordPress.Security.NonceVerification.Missing

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Stripe_Legacy_Checkout', false ) ) {

	class Imm_Learndash_Ls_Stripe_Legacy_Checkout extends Imm_Learndash_Ls_Stripe_Base {

		public function __construct() {
			parent::__construct();

			add_action( 'template_redirect', array( $this, 'process_checkout' ) );
		}

		private function get_stripe_resource_id() {
			return isset( $_POST['stripe_resource_id'] ) ? $_POST['stripe_resource_id'] : 0;
		}

		public function process_checkout() {
			$transaction_status                        = array();
			$transaction_status['stripe_message_type'] = 'error';
			$transaction_status['stripe_resource_id']  = $this->get_stripe_resource_id();
			$transaction_status['stripe_message']      = '';

			if ( isset( $_POST['action'] ) && 'imm_ls_ld_stripe_init_checkout' === $_POST['action'] ) {
				$this->config();

				if ( ( isset( $_POST['stripe_token_id'] ) ) && ( ! empty( $_POST['stripe_token_id'] ) ) ) {
					$token_id = sanitize_text_field( $_POST['stripe_token_id'] );
				} else {
					$transaction_status['stripe_message'] = __( 'No token found. Please activate javascript to make a purchase.', 'learndash-lessons-selling' );
					$this->show_notification( $transaction_status );
				}
				$transaction_status['token_id'] = $token_id;

				if ( isset( $_POST['stripe_token_email'] ) ) {
					$token_email = sanitize_text_field( $_POST['stripe_token_email'] );
				} else {
					$token_email = '';
				}
				$transaction_status['token_email'] = $token_email;

				if ( ( isset( $_POST['stripe_resource_id'] ) ) && ( ! empty( $_POST['stripe_resource_id'] ) ) ) {
					$resource_id = sanitize_text_field( $_POST['stripe_resource_id'] );
				} else {
					$resource_id = 0;
				}
				$transaction_status['resource_id'] = $resource_id;

				if ( ! $this->is_transaction_legit() ) {
					$transaction_status['stripe_message'] = __( 'The resource form data doesn\'t match with the official resource data. Cheatin\' huh?', 'learndash-lessons-selling' );
					$this->show_notification( $transaction_status );
				}

				if ( is_user_logged_in() ) {
					$user_id     = get_current_user_id();
					$customer_id = get_user_meta( $user_id, 'stripe_customer_id', true );
					$customer_id = $this->add_stripe_customer( $user_id, $customer_id, $token_email, $token_id );

				} else {
					// Needed a flag so we know at the end of this was a new user vs existing user so we can return the correct message.
					// The problem was at the end if this is an existing user there is no email. So the message was incorrect.
					$is_new_user = false;

					$user = get_user_by( 'email', $token_email );

					if ( false === $user ) {
						// Call Stripe API first so user acccount won't be created if there's error
						$customer_id = $this->add_stripe_customer( false, false, $token_email, $token_id );

						$password = wp_generate_password( 18, true, false );
						$new_user = $this->create_user( $token_email, $password, $token_email );

						if ( ! is_wp_error( $new_user ) ) {
							$user_id = $new_user;
							$user    = get_user_by( 'ID', $user_id );

							update_user_meta( $user_id, 'stripe_customer_id', $customer_id );

							// Need to allow for older versions of WP.
							global $wp_version;
							if ( version_compare( $wp_version, '4.3.0', '<' ) ) {
								wp_new_user_notification( $user_id, $password ); // phpcs:ignore WordPress.WP.DeprecatedParameters.Wp_new_user_notificationParam2Found
							} elseif ( version_compare( $wp_version, '4.3.0', '==' ) ) {
								wp_new_user_notification( $user_id, 'both' ); // phpcs:ignore WordPress.WP.DeprecatedParameters.Wp_new_user_notificationParam2Found
							} elseif ( version_compare( $wp_version, '4.3.1', '>=' ) ) {
								wp_new_user_notification( $user_id, null, 'both' );
							}
							$is_new_user = true;

						} else {
							$error_code                           = $new_user->get_error_code();
							$transaction_status['stripe_message'] = __( 'Failed to create a new user account. Please try again. Reason: ', 'learndash-lessons-selling' ) . $new_user->get_error_message( $error_code );
							$this->show_notification( $transaction_status );
						}
					} else {
						$user_id     = $user->ID;
						$customer_id = get_user_meta( $user_id, 'stripe_customer_id', true );
						$customer_id = $this->add_stripe_customer( $user_id, $customer_id, $token_email, $token_id );
					}
				}

				$site_name = get_bloginfo( 'name' );
				if ( 'paynow' === $_POST['stripe_price_type'] ) {
					try {
						$charge = \Stripe\Charge::create(
							array(
								'amount'        => sanitize_text_field( $_POST['stripe_price'] ),
								'currency'      => sanitize_text_field( strtolower( $_POST['stripe_currency'] ) ),
								'customer'      => $customer_id,
								'description'   => sprintf( '%s: %s', $site_name, stripslashes( sanitize_text_field( $_POST['stripe_name'] ) ) ),
								'receipt_email' => $user->user_email,
							)
						);

						add_user_meta( $user_id, 'stripe_charge_id', $charge->id, false );

					} catch ( \Stripe\Error\Card $e ) {
						// Card is declined
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'];
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\RateLimit $e ) {
						// Too many requests made to the API
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'];
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\InvalidRequest $e ) {
						// Invalid parameters suplied to the API
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please contact website administrator.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\Authetication $e ) {
						// Authentication failed
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please contact website administrator.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\ApiConnection $e ) {
						// Network communication with Stripe failed
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please try again later.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\Base $e ) {
						// Generic error
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please try again later.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( Exception $e ) {
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please try again later.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );
					}
				} elseif ( 'subscribe' === $_POST['stripe_price_type'] ) {

					$id       = sanitize_text_field( $_POST['stripe_plan_id'] ) . '-' . substr( md5( time() ), 0, 5 );
					$interval = sanitize_text_field( $_POST['stripe_interval'] );

					try {
						$plan_ids = get_post_meta( $course_id, 'stripe_plan_id', false );

						if ( empty( $plan_ids ) ) {
							$plan_args = array(
								// Required
								'amount'         => sanitize_text_field( $_POST['stripe_price'] ),
								'currency'       => strtolower( $this->options['currency'] ),
								'id'             => $id,
								'interval'       => $interval,
								'product'        => array(
									'name' => stripslashes( sanitize_text_field( $_POST['stripe_name'] ) ),
								),
								// Optional
								'interval_count' => sanitize_text_field( $_POST['stripe_interval_count'] ),
							);

							$plan = \Stripe\Plan::create( $plan_args );

							add_post_meta( $course_id, 'stripe_plan_id', $id, false );

							$current_id = $id;

						} else {
							try {
								$last_id = end( $plan_ids );
								reset( $plan_ids );

								$plan = \Stripe\Plan::retrieve(
									array(
										'id'     => $last_id,
										'expand' => array( 'product' ),
									)
								);

								if ( $plan->amount !== $_POST['stripe_price'] ||
								$plan->currency !== strtolower( $this->options['currency'] ) || // phpcs:ignore WordPress.PHP.YodaConditions.NotYoda
								$plan->id !== $last_id ||
								$plan->interval !== $interval ||
								htmlspecialchars_decode( $plan->product->name ) !== stripslashes( sanitize_text_field( $_POST['stripe_name'] ) ) ||
								$plan->interval_count !== $_POST['stripe_interval_count']
								) {
									// Create a new plan
									$plan = \Stripe\Plan::create(
										array(
											// Required
											'amount'   => sanitize_text_field( $_POST['stripe_price'] ),
											'currency' => strtolower( $this->options['currency'] ),
											'id'       => $id,
											'interval' => $interval,
											'product'  => array(
												'name' => stripslashes( sanitize_text_field( $_POST['stripe_name'] ) ),
											),
											// Optional
											'interval_count' => sanitize_text_field( $_POST['stripe_interval_count'] ),
										)
									);

									add_post_meta( $course_id, 'stripe_plan_id', $id, false );

									$current_id = $id;
								} else {
									$current_id = $last_id;
								}
							} catch ( Exception $e ) {
												// Create a new plan
											$plan = \Stripe\Plan::create(
												array(
													// Required
													'amount' => sanitize_text_field( $_POST['stripe_price'] ),
													'currency' => strtolower( $this->options['currency'] ),
													'id' => $id,
													'interval' => $interval,
													'product' => array(
														'name' => stripslashes( sanitize_text_field( $_POST['stripe_name'] ) ),
													),
													// Optional
													'interval_count' => sanitize_text_field( $_POST['stripe_interval_count'] ),
												)
											);

												add_post_meta( $course_id, 'stripe_plan_id', $id, false );

												$current_id = $id;
							}
						}

						$subscription = \Stripe\Subscription::create(
							array(
								'customer' => $customer_id,
								'items'    => array(
									array(
										'plan' => $current_id,
									),
								),
							)
						);

						// Bail if susbscription is not active
						if ( 'active' !== $subscription->status ) {
							$transaction_status['stripe_message'] = __( 'Failed to create a subscription. Please check your card and try it again later.', 'learndash-lessons-selling' );
							$this->show_notification( $transaction_status );
						}

						add_user_meta( $user_id, 'stripe_subscription_id', $subscription->id, false );
						add_user_meta( $user_id, 'stripe_plan_id', $current_id, false );

					} catch ( \Stripe\Error\Card $e ) {
						// Card is declined
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'];
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\RateLimit $e ) {
						// Too many requests made to the API
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'];
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\InvalidRequest $e ) {
						// Invalid parameters suplied to the API
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please contact website administrator.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\Authetication $e ) {
						// Authentication failed
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please contact website administrator.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\ApiConnection $e ) {
						// Network communication with Stripe failed
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please try again later.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( \Stripe\Error\Base $e ) {
						// Generic error
						$body  = $e->getJsonBody();
						$error = $body['error'];

						$transaction_status['stripe_message'] = $error['message'] . ' ' . __( 'Please try again later.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );

					} catch ( Exception $e ) {
						$error = __( 'Unknown error.', 'learndash-lessons-selling' );

						$transaction_status['stripe_message'] = $error . ' ' . __( 'Please try again later.', 'learndash-lessons-selling' );
						$this->show_notification( $transaction_status );
					}
				}

				// If charge or subscription is successful

				// Associate resource with user
				Imm_Learndash_Ls_Access_Control::update_resource_access( $user_id, $resource_id );

				$transaction = $_POST;

				if ( ! $this->is_zero_decimal_currency( $transaction['stripe_currency'] ) ) {
					$transaction['stripe_price'] = number_format( $transaction['stripe_price'] / 100, 2 );
				}

				// Log transaction
				$this->record_transaction( $transaction, $resource_id, $user_id, $token_email );

				if ( ! empty( $this->options['return_url'] ) ) {
					wp_redirect( $this->options['return_url'] ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit();
				}

				$transaction_status['stripe_message_type'] = 'success';
				$resource_label                            = Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $resource_id );
				// Fall through to this if there is not a valid redirect URL. Again I hate using sessions for this. Not time to rewrite all this logic for now.
				if ( true === $is_new_user ) {
					$transaction_status['stripe_message'] = sprintf(
					// translators: placeholder: LearnDash module name.
						esc_html_x( 'The transaction was successful. Please check your email and log in to access the %s.', 'placeholder: LearnDash module name', 'learndash-lessons-selling' ),
						$resource_label
					);
				} else {
					if ( is_user_logged_in() ) {
						$transaction_status['stripe_message'] = sprintf(
						// translators: placeholder: LearnDash module name.
							esc_html_x( 'The transaction was successful. You now have access the %s.', 'placeholder: LearnDash module name', 'learndash-lessons-selling' ),
							$resource_label
						);
					} else {
						$transaction_status['stripe_message'] = sprintf(
						// translators: placeholder: LearnDash module name.
							esc_html_x( 'The transaction was successful. Please log in to access the %s.', 'placeholder: LearnDash module name', 'learndash-lessons-selling' ),
							$resource_label
						);
					}
				}
				$this->show_notification( $transaction_status );
			}
		}



		/**
		 * Save transient and redirect to display message to user
		 *
		 * @param array $transaction_status Transaction status.
		 * @return void
		 */
		public function show_notification( $transaction_status = array() ) {
			$unique_id    = wp_generate_password( 10, false, false );
			$transient_id = 'ld_' . $unique_id;

			set_transient( $transient_id, $transaction_status, HOUR_IN_SECONDS );

			$redirect_url = add_query_arg( 'imm-ld-trans-id', $unique_id );
			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Record transaction in database
		 *
		 * @param array  $transaction  Transaction data passed through $_POST.
		 * @param int    $resource_id  Post ID of a LD resource.
		 * @param int    $user_id      ID of a user.
		 * @param string $user_email   Email of the user.
		 */
		public function record_transaction( $transaction, $resource_id, $user_id, $user_email ) {

			$transaction['user_id']     = $user_id;
			$transaction['resource_id'] = $resource_id;
			$transaction['course_id']   = learndash_get_course_id( $resource_id );

			$resource_title = $_POST['stripe_name'];
			$resource_label = ucfirst( Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $resource_id ) );

			$post_id = wp_insert_post(
				array(
					'post_title'  => "{$resource_label} {$resource_title} Purchased By {$user_email}",
					'post_type'   => 'sfwd-transactions',
					'post_status' => 'publish',
					'post_author' => $user_id,
				)
			);

			foreach ( $transaction as $key => $value ) {
				update_post_meta( $post_id, $key, $value );
			}
		}

		/**
		 * Output button scripts
		 *
		 * @return void
		 */
		public function button_scripts() {
			?>
		<script src="https://checkout.stripe.com/checkout.js"></script> <?php //phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				var Stripe_Handler = {
					init: function( form_ref ) {
						var handler = StripeCheckout.configure({
							key         : '<?php echo esc_attr( $this->get_publishable_key() ); ?>',
							amount      : parseInt( $( 'input[name="stripe_price"]', form_ref ).val() ),
							currency    : $( 'input[name="stripe_currency"]', form_ref ).val(),
							description : $( 'input[name="stripe_name"]', form_ref ).val(),
							email       : $( 'input[name="stripe_email"]', form_ref ).val(),
							locale      : 'auto',
							name        : '<?php echo esc_attr( get_bloginfo( 'name', 'raw' ) ); ?>',
							token: function(token) {
								// Use the token to create the charge with a server-side script.
								// You can access the token ID with `token.id`		
								var stripe_token_id = $( '<input type="hidden" name="stripe_token_id" />', form_ref ).val( token.id );
								var stripe_token_email = $( '<input type="hidden" name="stripe_token_email" />', form_ref ).val( token.email );
								$( form_ref ).append( stripe_token_id );
								$( form_ref ).append( stripe_token_email );
								$( form_ref ).submit();
							}
						});

						$( 'input.learndash-stripe-checkout-button', form_ref ).on( 'click', function(e) {
							// Open Checkout with further options
							handler.open({

							});
							e.preventDefault();
						});

						// Close Checkout on page navigation
						$( window ).on( 'popstate', function() {
							handler.close();
						} );
					}
				};

				$( '.learndash_stripe_button form.learndash-stripe-checkout input.learndash-stripe-checkout-button' ).each( function() {
					var parent_form = $( this ).parent( 'form.learndash-stripe-checkout' );
					Stripe_Handler.init( parent_form );
				});
			});
	</script>
			<?php
		}

		/**
		 * Add Customer to Stripe
		 *
		 * @param int    $user_id     ID of a user.
		 * @param int    $customer_id Stripe customer ID.
		 * @param string $token_email Email of a user, got from token.
		 * @param string $token_id    Token ID.
		 * @return string Stripe customer ID
		 */
		public function add_stripe_customer( $user_id, $customer_id, $token_email, $token_id ) {
			$this->config();

			if ( ! empty( $customer_id ) && ! empty( $user_id ) ) {
				$customer = \Stripe\Customer::retrieve( $customer_id );

				if ( isset( $customer->deleted ) && $customer->deleted ) {
					$customer = \Stripe\Customer::create(
						array(
							'email'  => $token_email,
							'source' => $token_id,
						)
					);
				}

				$customer_id = $customer->id;

				update_user_meta( $user_id, 'stripe_customer_id', $customer_id );
			} else {
				try {
					$customer = \Stripe\Customer::create(
						array(
							'email'  => $token_email,
							'source' => $token_id,
						)
					);

					$customer_id = $customer->id;

					if ( ! empty( $user_id ) ) {
						update_user_meta( $user_id, 'stripe_customer_id', $customer_id );
					}
				} catch ( Exception $e ) {
					$body  = $e->getJsonBody();
					$error = $body['error'];

					$transaction_status['stripe_message_type'] = 'error';
					$transaction_status['stripe_resource_id']  = $this->get_stripe_resource_id();
					$transaction_status['stripe_message']      = $error['message'];
					$this->show_notification( $transaction_status );
				}
			}

			return $customer_id;
		}

		/**
		 * Output Stripe error alert
		 */
		public function output_transaction_message() {
			if ( ( isset( $_GET['imm-ld-trans-id'] ) ) && ( ! empty( $_GET['imm-ld-trans-id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended

				$transient_id       = 'ld_' . $_GET['imm-ld-trans-id']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$transaction_status = get_transient( $transient_id );
				delete_transient( $transient_id );

				if ( ! empty( $transaction_status ) &&
				isset( $transaction_status['stripe_message_type'] ) &&
				isset( $transaction_status['stripe_resource_id'] ) &&
				isset( $transaction_status['stripe_message'] ) &&
				! empty( $transaction_status['stripe_resource_id'] ) &&
				! empty( $transaction_status['stripe_message'] ) ) {
					?>
						<script type="text/javascript">
						jQuery( document ).ready( function() { 
							if ( jQuery( '#ld-expand-<?php echo esc_attr( $transaction_status['stripe_resource_id'] ); ?>').length ) {
								jQuery( '<p class="learndash-<?php echo ( 'error' === $transaction_status['stripe_message_type'] ? 'error' : 'success' ); ?>"><?php echo esc_html( htmlentities( $transaction_status['stripe_message'], ENT_QUOTES ) ); ?></p>' ).prependTo( '#ld-expand-<?php echo esc_attr( $transaction_status['stripe_resource_id'] ); ?> .ld-item-list-item-preview');
							}
						});
						</script>
							<?php
				}
			}
		}
	}
	new Imm_Learndash_Ls_Stripe_Legacy_Checkout();
}
