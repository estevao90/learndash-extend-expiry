<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Paypal', false ) ) {

	class Imm_Learndash_Ls_Paypal {

		const SETTING_NAME = 'imm_ls_selling_paypal_notifyurl';

		public function __construct() {
			// configuration hooks
			add_filter( 'learndash_settings_fields', array( $this, 'add_imm_paypal_notify_option' ), 30, 2 );
			add_action( 'admin_init', array( $this, 'save_paypal_notify_option' ) );

			// process hooks
			add_filter( 'query_vars', array( $this, 'add_query_vars' ) );
			add_action( 'parse_request', array( $this, 'parse_ipn_request' ) );
			add_action( 'generate_rewrite_rules', array( $this, 'paypal_rewrite_rules' ) );
		}

		public function add_imm_paypal_notify_option( $setting_option_fields, $settings_section_key ) {
			if ( 'settings_paypal' === $settings_section_key
			&& ! isset( $setting_option_fields[ self::SETTING_NAME ] ) ) {

				$setting_option_fields[ self::SETTING_NAME ] = array(
					'name'      => self::SETTING_NAME,
					'label'     => esc_html__( 'IMM Lessons Selling PayPal Notify URL', 'learndash-extend-expiry' ),
					'type'      => 'text',
					'value'     => $this->get_paypal_notifyurl(),
					'help_text' => esc_html__( 'Enter the URL used for IMM Lessons Selling IPN notifications.', 'learndash-extend-expiry' ),
				);
			}

			return $setting_option_fields;
		}

		public function save_paypal_notify_option() {
       // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( ( isset( $_POST['action'] ) ) && ( 'update' === $_POST['action'] ) && ( isset( $_POST['option_page'] ) ) && ( 'learndash_lms_settings_paypal' === $_POST['option_page'] ) ) {
			 // phpcs:ignore WordPress.Security.NonceVerification.Missing
				if ( isset( $_POST['learndash_settings_paypal'][ self::SETTING_NAME ] ) ) {
         // phpcs:ignore WordPress.Security.NonceVerification.Missing
					$setting_value = esc_attr( $_POST['learndash_settings_paypal'][ self::SETTING_NAME ] );
					update_option( self::SETTING_NAME, $setting_value );
				}
			}
		}

		public function add_query_vars( $vars ) {
			$paypal_email = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( ! empty( $paypal_email ) ) {
				$vars = array_merge( array( 'imm-sell-lessons' ), $vars );
			}
			return $vars;
		}

		public function parse_ipn_request( $wp ) {
			$paypal_email = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( ! empty( $paypal_email ) ) {
				if ( ( array_key_exists( 'imm-sell-lessons', $wp->query_vars ) ) && ( 'paypal' === $wp->query_vars['imm-sell-lessons'] ) ) {
						/**
						 * Include PayPal IPN
						 */
						require_once LEARNDASH_EXTEND_EXPIRY_PLUGIN_DIR . 'includes/payments/paypal/class-imm-learndash-ls-paypal-ipn.php';
				}
			}
		}

		public function paypal_rewrite_rules( $wp_rewrite ) {
			$paypal_email = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_PayPal', 'paypal_email' );
			if ( ! empty( $paypal_email ) ) {
				$wp_rewrite->rules = array_merge( array( 'imm-sell-lessons/paypal' => 'index.php?imm-sell-lessons=paypal' ), $wp_rewrite->rules );
			}
		}

		private function get_paypal_notifyurl() {
			$setting_value = get_option( self::SETTING_NAME );
			if ( empty( $setting_value ) ) {
				$setting_value = $this->get_default_paypal_notifyurl();
			}
			return $setting_value;
		}

		private function get_default_paypal_notifyurl() {
			global $wp_rewrite;

			if ( ( isset( $wp_rewrite ) ) && ( $wp_rewrite->using_permalinks() ) ) {
				$default_paypal_notifyurl = trailingslashit( get_home_url() ) . 'imm-sell-lessons/paypal';
			} else {
				$default_paypal_notifyurl = add_query_arg( 'imm-sell-lessons', 'paypal', get_home_url() );
			}

			return $default_paypal_notifyurl;
		}

	}
	new Imm_Learndash_Ls_Paypal();
}
