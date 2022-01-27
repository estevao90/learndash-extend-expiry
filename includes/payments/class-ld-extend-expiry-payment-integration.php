<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Ld_Extend_Expiry_Payment_Integration', false ) ) {

	class Ld_Extend_Expiry_Payment_Integration {

		/**
		 * Generate the LearnDash extend expiry payment button output.
		 *
		 * @param int   $course_id The LD course ID.
		 * @param int   $user_id The user ID.
		 * @param int   $extend_days The number of days to extend.
		 * @param float $extend_price The price to extend days.
		 * @return string The payment button HTML output.
		 */
		public static function ld_extend_expiry_payment_button( $course_id, $user_id, $extend_days, $extend_price ) {
			$button_text = esc_html( __( 'Extend access', 'learndash-extend-expiry' ) );

			// in case of free price to extend days
			if ( 0 === intval( $extend_price ) ) {
				$extend_button = '<div class="learndash_extend_expiry_button">
                            <form action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" method="post">
                            <input type="hidden" value="' . $course_id . '" name="course_id" />
                            <input type="hidden" value="' . Ld_Extend_Expiry_Control::POST_EXTEND_EXPIRY_ACTION . '" name="action" />
                            <input type="hidden" name="course_extend_expiry" value="' . wp_create_nonce( 'course_extend_expiry_' . $user_id . '_' . $course_id ) . '" />
                            <input type="submit" value="' . $button_text . '" id="btn-extend-expiry" />
                          </form></div>';
				return $extend_button;
			}

			// format the price to be proper XXX.YY no leading dollar signs or other values.
			$extend_price = preg_replace( '/[^0-9.]/', '', $extend_price );
			$extend_price = number_format( floatval( $extend_price ), 2, '.', '' );

			$paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );
			if ( ! empty( $paypal_settings ) ) {
				$paypal_settings['paypal_sandbox'] = ( 'yes' === $paypal_settings['paypal_sandbox'] ) ? 1 : 0;
			}
			include_once LEARNDASH_LMS_LIBRARY_DIR . '/paypal/enhanced-paypal-shortcodes.php';
			$paypal_button = '';

			if ( ! empty( $paypal_settings['paypal_email'] ) ) {
				// translators: placeholder: number of extended day.
				$days_str = sprintf( _n( '%s day', '%s days', $extend_days, 'learndash-extend-expiry' ), $extend_days );
				// translators: placeholder: number of extended day str.
				$post_title = get_the_title( $course_id ) . ' - ' . esc_attr( sprintf( __( 'Add more %s', 'learndash-extend-expiry' ), $days_str ) );
				$post_title = str_replace( array( '[', ']' ), array( '', '' ), $post_title );

				$shortcode_content = do_shortcode( '[paypal type="paynow" amount="' . $extend_price . '" sandbox="' . $paypal_settings['paypal_sandbox'] . '" email="' . $paypal_settings['paypal_email'] . '" itemno="' . $course_id . '" name="' . $post_title . '" noshipping="1" nonote="1" qty="1" currencycode="' . $paypal_settings['paypal_currency'] . '" rm="2" notifyurl="' . $paypal_settings['extend_expiry_paypal_notifyurl'] . '" returnurl="' . $paypal_settings['paypal_returnurl'] . '" cancelurl="' . $paypal_settings['paypal_cancelurl'] . '" imagewidth="100px" pagestyle="paypal" lc="' . $paypal_settings['paypal_country'] . '" cbt="' . esc_html__( 'Complete Your Purchase', 'learndash-extend-expiry' ) . '" custom="' . $user_id . ';' . $extend_days . '"]' );

				if ( ! empty( $shortcode_content ) ) {
					$paypal_button = wptexturize( '<div class="learndash_checkout_button learndash_paypal_button">' . $shortcode_content . '</div>' );
				}
			}

				// Fix button title
				$paypal_button = str_replace( LearnDash_Custom_Label::get_label( 'button_take_this_course' ), $button_text, $paypal_button );
				return $paypal_button;
		}

	}
}
