<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Stripe', false ) ) {

	class Imm_Learndash_Ls_Stripe {

		public function __construct() {
			add_action( 'init', array( $this, 'load_customization_files' ) );
		}

		public function load_customization_files() {
			// return if stripe is not installed
			if ( ! defined( 'LEARNDASH_STRIPE_VERSION' ) || ! defined( 'LEARNDASH_STRIPE_PLUGIN_PATH' ) ) {
				return;
			}

			$options = get_option( 'learndash_stripe_settings', array() );

			require_once LEARNDASH_EXTEND_EXPIRY_PLUGIN_DIR . 'includes/payments/stripe/class-imm-learndash-ls-stripe-base.php';

			if ( isset( $options['integration_type'] ) && 'legacy_checkout' === $options['integration_type'] ) {
				include LEARNDASH_EXTEND_EXPIRY_PLUGIN_DIR . 'includes/payments/stripe/class-imm-learndash-ls-stripe-legacy-checkout.php';
			} else {
				include LEARNDASH_EXTEND_EXPIRY_PLUGIN_DIR . 'includes/payments/stripe/class-imm-learndash-ls-stripe-checkout.php';
			}
		}

	}
	new Imm_Learndash_Ls_Stripe();
}
