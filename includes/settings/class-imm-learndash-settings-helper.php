<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Settings_Helper', false ) ) {

	class Imm_Learndash_Settings_Helper {

		public $setting_option_values;

		private static function get_setting_value( $post_id, $setting_name, $default_value = '' ) {
			$setting_value = learndash_get_setting( $post_id, $setting_name );
			if ( empty( $setting_value ) ) {
				$setting_value = $default_value;
			}
			return $setting_value;
		}

		public static function get_price_type( $post_id ) {
			$imm_sell_individually = self::get_setting_value(
				$post_id,
				'imm_ls_selling_sell_individually'
			);

			if ( 'on' === $imm_sell_individually ) {
				return self::get_setting_value(
					$post_id,
					'imm_ls_selling_price_type',
					LEARNDASH_DEFAULT_COURSE_PRICE_TYPE
				);
			}
			return null;
		}

		/**
		 * Return an array of price type, amount and cycle.
		 *
		 * @param [int] $resource_id LD resource ID.
		 * @return array Resource price details.
		 */
		public static function get_resource_price_details( $resource_id ) {
			$resource_price = array(
				'type' => self::get_price_type( $resource_id ),
			);

			if ( 'paynow' === $resource_price['type'] ) {
				$resource_price['price'] = self::get_setting_value( $resource_id, 'imm_ls_selling_price_type_paynow' );
			}

			if ( 'closed' === $resource_price['type'] ) {
				$resource_price['price']      = self::get_setting_value( $resource_id, 'imm_ls_selling_price_type_closed_price' );
				$resource_price['button_url'] = self::get_setting_value( $resource_id, 'imm_ls_selling_price_type_closed_custom_button_url' );
			}

			if ( 'subscribe' === $resource_price['type'] ) {

				$resource_price['price'] = self::get_setting_value( $resource_id, 'imm_ls_selling_price_type_subscribe_price' );
				$frequency               = self::get_setting_value( $resource_id, 'imm_ls_selling_price_billing_t3' );
				$interval                = intval( self::get_setting_value( $resource_id, 'imm_ls_selling_price_billing_p3' ) );

				$label = '';

				switch ( $frequency ) {
					case ( 'D' ):
						$label = _n( 'day', 'days', $interval, 'learndash-lessons-selling' );
						break;
					case ( 'W' ):
						$label = _n( 'week', 'weeks', $interval, 'learndash-lessons-selling' );
						break;
					case ( 'M' ):
						$label = _n( 'month', 'months', $interval, 'learndash-lessons-selling' );
						break;
					case ( 'Y' ):
						$label = _n( 'year', 'years', $interval, 'learndash-lessons-selling' );
						break;
				}

				$resource_price['frequency']       = $frequency;
				$resource_price['frequency_label'] = $label;
				$resource_price['interval']        = $interval;

			}

			return $resource_price;
		}


		public function __construct( $ld_module ) {
			$this->ld_module             = $ld_module;
			$this->setting_option_values = array();

			// include hooks to save data
			add_action( 'save_post', array( $this, 'save_sell_settings' ), 30, 1 );
		}

		public function save_sell_settings( $post_id ) {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ "learndash-{$this->ld_module}-access-settings" ] ) ) {

				// saving sell individually
				$this->save_setting( $post_id, 'imm_ls_selling_sell_individually' );
				// price type
				$price_type = $this->save_setting( $post_id, 'imm_ls_selling_price_type', LEARNDASH_DEFAULT_COURSE_PRICE_TYPE );

				// check extra price type fields
				switch ( $price_type ) {
					case 'paynow':
						$price_type_paynow = $this->save_setting( $post_id, 'imm_ls_selling_price_type_paynow' );
						if ( empty( $price_type_paynow ) ) {
							// return price type to default
							learndash_update_setting( $post_id, 'imm_ls_selling_price_type', LEARNDASH_DEFAULT_COURSE_PRICE_TYPE );
						}
						break;

					case 'subscribe':
						$price_type_subscribe_price = $this->save_setting( $post_id, 'imm_ls_selling_price_type_subscribe_price' );
						$price_billing_p3           = $this->save_setting( $post_id, 'imm_ls_selling_price_billing_p3' );
						$price_billing_t3           = $this->save_setting( $post_id, 'imm_ls_selling_price_billing_t3' );
						if ( empty( $price_type_subscribe_price ) || empty( $price_billing_p3 ) || empty( $price_billing_t3 ) ) {
							// return price type to default
							learndash_update_setting( $post_id, 'imm_ls_selling_price_type', LEARNDASH_DEFAULT_COURSE_PRICE_TYPE );
						}
						break;

					case 'closed':
						$price_type_closed_price = $this->save_setting( $post_id, 'imm_ls_selling_price_type_closed_price' );
						$this->save_setting( $post_id, 'imm_ls_selling_price_type_closed_custom_button_url' );
						break;
				}
			}

		}

		public function load_setting_option_values( $post_id ) {

			// sell individually
			$this->setting_option_values ['imm_ls_selling_sell_individually'] = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_sell_individually'
			);
			// price type
			$this->setting_option_values ['imm_ls_selling_price_type'] = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_type',
				LEARNDASH_DEFAULT_COURSE_PRICE_TYPE
			);

			// additional price type fields
			// paynow
			$this->setting_option_values ['imm_ls_selling_price_type_paynow'] = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_type_paynow'
			);
			// subscribe
			$this->setting_option_values ['imm_ls_selling_price_type_subscribe_price'] = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_type_subscribe_price'
			);
			$this->setting_option_values ['imm_ls_selling_price_billing_p3']           = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_billing_p3'
			);
			$this->setting_option_values ['imm_ls_selling_price_billing_t3']           = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_billing_t3'
			);
			// closed
			$this->setting_option_values ['imm_ls_selling_price_type_closed_price']             = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_type_closed_price'
			);
			$this->setting_option_values ['imm_ls_selling_price_type_closed_custom_button_url'] = $this->get_setting_value(
				$post_id,
				'imm_ls_selling_price_type_closed_custom_button_url'
			);

		}

		public function learndash_billing_cycle_html() {
			$price_billing_p3 = $this->setting_option_values ['imm_ls_selling_price_billing_p3'];
			$price_billing_t3 = $this->setting_option_values ['imm_ls_selling_price_billing_t3'];

			$selected_d = '';
			$selected_w = '';
			$selected_m = '';
			$selected_y = '';

			${'selected_' . strtolower( $price_billing_t3 )} = 'selected="selected"';
			return '<input min="1" max="90" step="1" name="imm_ls_selling_price_billing_p3" type="number" value="' . $price_billing_p3 . '" class="small-text" />
					<select class="select_course_price_billing_p3" name="imm_ls_selling_price_billing_t3">
						<option value="D" ' . $selected_d . '>' . esc_html__( 'day(s)', 'learndash-lessons-selling' ) . '</option>
						<option value="W" ' . $selected_w . '>' . esc_html__( 'week(s)', 'learndash-lessons-selling' ) . '</option>
						<option value="M" ' . $selected_m . '>' . esc_html__( 'month(s)', 'learndash-lessons-selling' ) . '</option>
						<option value="Y" ' . $selected_y . '>' . esc_html__( 'year(s)', 'learndash-lessons-selling' ) . '</option>
					</select>';
		}

		private function save_setting( $post_id, $setting_name, $default_value = '' ) {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST[ "learndash-{$this->ld_module}-access-settings" ][ $setting_name ] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$setting_value = esc_attr( $_POST[ "learndash-{$this->ld_module}-access-settings" ][ $setting_name ] );
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
			} elseif ( isset( $_POST[ $setting_name ] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$setting_value = esc_attr( $_POST[ $setting_name ] );
			} else {
				$setting_value = $default_value;
			}
			learndash_update_setting( $post_id, $setting_name, $setting_value );

			// return value
			return $setting_value;
		}

	}
}
