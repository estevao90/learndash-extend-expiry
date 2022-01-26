<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Payment_Integration', false ) ) {

	class Imm_Learndash_Ls_Payment_Integration {

		public function __construct() {

		}

		/**
		 * Generate the IMM LearnDash payment buttons output.
		 *
		 * @param int   $resource_id The LD resource ID.
		 * @param array $resource_pricing Resource price details.
		 * @return string The payment buttons HTML output.
		 */
		public static function imm_ls_payment_buttons( $resource_id, $resource_pricing ) {
			$user_id     = get_current_user_id();
			$button_text = sprintf(
			// translators: LearnDash module name
				esc_html_x(
					'Take this %1$s',
					'placeholder: LearnDash module name',
					'learndash-lessons-selling'
				),
				ucfirst( Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $resource_id ) )
			);

			// format the resource price to be proper XXX.YY no leading dollar signs or other values.
			if ( ( 'paynow' === $resource_pricing['type'] ) || ( 'subscribe' === $resource_pricing['type'] ) ) {
				if ( '' !== $resource_pricing['price'] ) {
					$resource_pricing['price'] = preg_replace( '/[^0-9.]/', '', $resource_pricing['price'] );
					$resource_pricing['price'] = number_format( floatval( $resource_pricing['price'] ), 2, '.', '' );
				}
			}

			$paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
			if ( ! empty( $paypal_settings ) ) {
				$paypal_settings['paypal_sandbox'] = ( 'yes' === $paypal_settings['paypal_sandbox'] ) ? 1 : 0;
			}

			if ( 'closed' === $resource_pricing['type'] ) {

				if ( empty( $resource_pricing['button_url'] ) ) {
					$post_button = '';
				} else {
					$post_button_url = trim( $resource_pricing['button_url'] );
					/**
					 * If the value does NOT start with [http://, https://, /] we prepend the home URL.
					 */
					if ( ( stripos( $resource_pricing['button_url'], 'http://', 0 ) !== 0 ) &&
					( stripos( $resource_pricing['button_url'], 'https://', 0 ) !== 0 ) &&
					( strpos( $resource_pricing['button_url'], '/', 0 ) !== 0 ) ) {
						$resource_pricing['button_url'] = get_home_url( null, $resource_pricing['button_url'] );
					}
					$post_button = '<a class="btn-join" href="' . esc_url( $resource_pricing['button_url'] ) . '" id="btn-join">' . $button_text . '</a>';
				}

				return $post_button;

			} elseif ( ! empty( $resource_pricing['price'] ) ) {
				include_once LEARNDASH_LMS_LIBRARY_DIR . '/paypal/enhanced-paypal-shortcodes.php';

				$paypal_button = '';

				if ( ! empty( $paypal_settings['paypal_email'] ) ) {

					$post_title = str_replace( array( '[', ']' ), array( '', '' ), get_the_title( $resource_id ) );

					if ( 'paynow' === $resource_pricing['type'] ) {
						$shortcode_content = do_shortcode( '[paypal type="paynow" amount="' . $resource_pricing['price'] . '" sandbox="' . $paypal_settings['paypal_sandbox'] . '" email="' . $paypal_settings['paypal_email'] . '" itemno="' . $resource_id . '" name="' . $post_title . '" noshipping="1" nonote="1" qty="1" currencycode="' . $paypal_settings['paypal_currency'] . '" rm="2" notifyurl="' . $paypal_settings['imm_ls_selling_paypal_notifyurl'] . '" returnurl="' . $paypal_settings['paypal_returnurl'] . '" cancelurl="' . $paypal_settings['paypal_cancelurl'] . '" imagewidth="100px" pagestyle="paypal" lc="' . $paypal_settings['paypal_country'] . '" cbt="' . esc_html__( 'Complete Your Purchase', 'learndash-lessons-selling' ) . '" custom="' . $user_id . '"]' );
					} elseif ( 'subscribe' === $resource_pricing['type'] ) {
						$shortcode_content = do_shortcode( '[paypal type="subscribe" a3="' . $resource_pricing['price'] . '" p3="' . $resource_pricing['interval'] . '" t3="' . $resource_pricing['frequency'] . '" sandbox="' . $paypal_settings['paypal_sandbox'] . '" email="' . $paypal_settings['paypal_email'] . '" itemno="' . $resource_id . '" name="' . $post_title . '" noshipping="1" nonote="1" qty="1" currencycode="' . $paypal_settings['paypal_currency'] . '" rm="2" notifyurl="' . $paypal_settings['imm_ls_selling_paypal_notifyurl'] . '" cancelurl="' . $paypal_settings['paypal_cancelurl'] . '" returnurl="' . $paypal_settings['paypal_returnurl'] . '" imagewidth="100px" pagestyle="paypal" lc="' . $paypal_settings['paypal_country'] . '" cbt="' . esc_html__( 'Complete Your Purchase', 'learndash-lessons-selling' ) . '" custom="' . $user_id . '"]' );
					}

					if ( ! empty( $shortcode_content ) ) {
						$paypal_button = wptexturize( '<div class="learndash_checkout_button learndash_paypal_button">' . $shortcode_content . '</div>' );
					}
				}

				// Fix button title
				$paypal_button = str_replace( LearnDash_Custom_Label::get_label( 'button_take_this_course' ), $button_text, $paypal_button );

				$payment_params = array(
					'resource_pricing' => $resource_pricing,
					'post'             => get_post( $resource_id ),
				);

				/**
				 * Filters PayPal payment button markup.
				 *
				 * @param string $payment_button Payment button markup.
				 * @param array  $payment_params An array of payment paramter details.
				 */
				$payment_buttons = apply_filters( 'imm_ls_payment_payment_button', $paypal_button, $payment_params );

				if ( ! empty( $payment_buttons ) ) {

					if ( ( ! empty( $paypal_button ) ) && ( $payment_buttons !== $paypal_button ) ) {

						$button  = '';
						$button .= '<div id="learndash_checkout_buttons_course_' . $resource_id . '" class="learndash_checkout_buttons">';
						$button .= '<input id="btn-join-' . $resource_id . '" class="btn-join btn-join-' . $resource_id . ' button learndash_checkout_button" data-jq-dropdown="#jq-dropdown-' . $resource_id . '" type="button" value="' . $button_text . '" />';
						$button .= '</div>';

						global $dropdown_button;
				  // phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
						$dropdown_button .= '<div id="jq-dropdown-' . esc_attr( $resource_id ) . '" class="jq-dropdown jq-dropdown-tip checkout-dropdown-button">';
						$dropdown_button .= '<ul class="jq-dropdown-menu">';
						$dropdown_button .= '<li>';
						$dropdown_button .= str_replace( $button_text, esc_html__( 'Use Paypal', 'learndash-lessons-selling' ), $payment_buttons );
						$dropdown_button .= '</li>';
						$dropdown_button .= '</ul>';
						$dropdown_button .= '</div>';
				  // phpcs:enable

						/**
						 * Filters Dropdown payment button markup.
						 *
						 * @param string $button Dropdown payment button markup.
						 */
						return apply_filters( 'imm_ls_payment_dropdown_payment_button', $button );

					} else {
						return '<div id="learndash_checkout_buttons_course_' . $resource_id . '" class="learndash_checkout_buttons">' . $payment_buttons . '</div>';
					}
				}
			} else {
				return '';
			}
		}

	}
	new Imm_Learndash_Ls_Payment_Integration();
}
