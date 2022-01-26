<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Stripe_Base', false ) ) {

	abstract class Imm_Learndash_Ls_Stripe_Base extends LearnDash_Stripe_Integration_Base {
		private $ld_resource;
		private $resource_pricing;

		protected $default_button;
		protected $stripe_button;

		public function __construct() {
			$this->options = get_option( 'learndash_stripe_settings', array() );

			$this->secret_key      = $this->get_secret_key();
			$this->publishable_key = $this->get_publishable_key();
			$this->endpoint_secret = $this->get_endpoint_secret();

			add_filter( 'imm_ls_payment_payment_button', array( $this, 'imm_payment_button' ), 10, 2 );
			// add_action( 'init', array( $this, 'imm_process_webhook' ) );
			add_action( 'wp_footer', array( $this, 'output_transaction_message' ) );
		}

		/**
		 * Check if Stripe transaction is legit
		 *
		 * @return boolean True if legit, false otherwise
		 */
		public function is_transaction_legit() {
			if ( wp_verify_nonce( $_POST['stripe_nonce'], 'stripe-nonce-' . $_POST['stripe_resource_id'] . $_POST['stripe_price'] . $_POST['stripe_price_type'] ) ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Output modified payment button
		 *
		 * @param  string $default_button Learndash default payment button.
		 * @param  array  $params         Button parameters.
		 * @return string                 Modified button
		 */
		public function imm_payment_button( $default_button, $params = null ) {
			if ( $this->key_is_empty() || empty( $this->endpoint_secret ) ) {
				return $default_button;
			}

			// Also ensure the price it not zero
			if ( ( ! isset( $params['resource_pricing']['price'] ) ) || ( empty( $params['resource_pricing']['price'] ) ) ) {
				return $default_button;
			}

			// check if resource is set
			if ( ! isset( $params['post'] ) ) {
				return $default_button;
			}

			$this->ld_resource      = $params['post'];
			$this->resource_pricing = $params['resource_pricing'];

			$this->default_button = $default_button;
			$this->stripe_button  = $this->stripe_button();

			if ( ! empty( $this->stripe_button ) ) {
				return $default_button . $this->stripe_button;
			} else {
				return $default_button;
			}
		}

		/**
		 * Stripe payment button
		 *
		 * @return string         Payment button
		 */
		public function stripe_button() {
			global $learndash_stripe_script_loaded;

			if ( ! isset( $learndash_stripe_script_loaded ) ) {
				$learndash_stripe_script_loaded = false;
			}

			$params = $this->get_resource_args();

			ob_start();

			if ( $this->is_paypal_active() ) {
				$stripe_button_text = apply_filters( 'learndash_stripe_purchase_button_text', __( 'Use a Credit Card', 'learndash-lessons-selling' ) );
			} else {
				$stripe_button_text = apply_filters(
					'learndash_stripe_purchase_button_text',
					sprintf(
					// translators: placeholder: LearnDash module name.
						esc_html_x( 'Take this %s', 'placeholder: LearnDash module name', 'learndash-lessons-selling' ),
						ucfirst( Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $this->ld_resource->ID ) )
					)
				);
			}

			$stripe_button  = '';
			$stripe_button .= '<div class="learndash_checkout_button learndash_stripe_button">';
			$stripe_button .= '<form class="learndash-stripe-checkout" name="" action="" method="post">';
			$stripe_button .= '<input type="hidden" name="action" value="imm_ls_ld_stripe_init_checkout" />';
			$stripe_button .= '<input type="hidden" name="stripe_email" value="' . esc_attr( $params['user_email'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_user_id" value="' . esc_attr( $params['user_id'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_resource_id" value="' . esc_attr( $params['resource_id'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_plan_id" value="' . esc_attr( $params['resource_plan_id'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_name" value="' . esc_attr( $params['resource_name'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_currency" value="' . esc_attr( $params['currency'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_price" value="' . esc_attr( $params['resource_price'] ) . '" />';
			$stripe_button .= '<input type="hidden" name="stripe_price_type" value="' . esc_attr( $params['resource_price_type'] ) . '" />';

			if ( 'subscribe' === $params['resource_price_type'] ) {
				$stripe_button .= '<input type="hidden" name="stripe_interval_count" value="' . esc_attr( $params['resource_interval_count'] ) . '" />';
				$stripe_button .= '<input type="hidden" name="stripe_interval" value="' . esc_attr( $params['resource_interval'] ) . '" />';
			}

			$stripe_button_nonce = wp_create_nonce( 'stripe-nonce-' . $params['resource_id'] . $params['resource_price'] . $params['resource_price_type'] );
			$stripe_button      .= '<input type="hidden" name="stripe_nonce" value="' . esc_attr( $stripe_button_nonce ) . '" />';

			$stripe_button .= '<input class="learndash-stripe-checkout-button btn-join button" type="submit" value="' . esc_attr( $stripe_button_text ) . '">';
			$stripe_button .= '</form>';
			$stripe_button .= '</div>';

			if ( ! $learndash_stripe_script_loaded ) {
				$this->button_scripts();
				$learndash_stripe_script_loaded = true;
			}

			$stripe_button .= ob_get_clean();

			return $stripe_button;
		}

		/**
		 * Get resource button args
		 *
		 * @param int $resource_id The LD resource ID.
		 * @return array             LD resource args
		 */
		public function get_resource_args( $resource_id = null ) {
			if ( ! isset( $resource_id ) ) {
				$ld_resource      = $this->ld_resource;
				$resource_pricing = $this->resource_pricing;
			} else {
				$ld_resource      = get_post( $resource_id );
				$resource_pricing = Imm_Learndash_Settings_Helper::get_resource_price_details( $resource_id );

			}

			if ( ! $ld_resource ) {
				return false;
			}

			$resource_args = array();

			$resource_args['user_id'] = get_current_user_id();
			if ( $resource_args['user_id'] ) {
				$user                        = get_userdata( $resource_args['user_id'] );
				$resource_args['user_email'] = $user->user_email;
			} else {
				$resource_args['user_email'] = null;
			}

			$resource_args['resource_price']          = $resource_pricing['price'];
			$resource_args['resource_price_type']     = $resource_pricing['type'];
			$resource_args['resource_plan_id']        = sprintf(
				'learndash-%s-%s',
				Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $ld_resource->ID ),
				$ld_resource->ID
			);
			$resource_args['resource_interval_count'] = isset( $resource_pricing['interval'] ) ? $resource_pricing['interval'] : null;
			$resource_args['resource_interval']       = $this->get_label_interval( isset( $resource_pricing['frequency'] ) ? $resource_pricing['frequency'] : null );
			$resource_args['currency']                = strtolower( $this->options['currency'] );
			$resource_args['resource_image']          = get_the_post_thumbnail_url( $ld_resource->ID, 'medium' );
			$resource_args['resource_name']           = $ld_resource->post_title;
			$resource_args['resource_id']             = $ld_resource->ID;

			$resource_args['resource_price'] = preg_replace( '/.*?(\d+(?:\.?\d+))/', '$1', $resource_args['resource_price'] );
			if ( ! $this->is_zero_decimal_currency( $this->options['currency'] ) ) {
				$resource_args['resource_price'] = $resource_args['resource_price'] * 100;
			}

			return $resource_args;
		}

		public function get_label_interval( $interval ) {
			switch ( $interval ) {
				case 'D':
					return 'day';
				case 'W':
					return 'week';
				case 'M':
					return 'month';
				case 'Y':
					return 'year';
				default:
					return null;
			}
		}

	}
}
