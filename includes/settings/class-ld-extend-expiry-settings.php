<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Ld_Extend_Expiry_Settings', false ) ) {

	class Ld_Extend_Expiry_Settings {

		const DEFAULT_EXTEND_EXPIRY_DAYS         = 0;
		const DEFAULT_EXTEND_EXPIRY_WARNING_DAYS = 5;

		public function __construct() {
			// add custom fields
			add_filter( 'learndash_settings_fields', array( $this, 'add_additional_config_options' ), 30, 2 );
			add_action( 'save_post', array( $this, 'save_additional_settings' ), 30, 1 );
		}

		public static function get_setting_value( $post_id, $setting_name, $default_value = '' ) {
			$setting_value = learndash_get_setting( $post_id, $setting_name );
			if ( '' === $setting_value ) {
				$setting_value = $default_value;
			}
			return $setting_value;
		}

		private function save_setting( $post_id, $setting_name, $default_value = '' ) {
		  // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['learndash-course-access-settings'][ $setting_name ] ) ) {
        // phpcs:ignore WordPress.Security.NonceVerification.Missing
				$setting_value = esc_attr( $_POST['learndash-course-access-settings'][ $setting_name ] );
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

		public function save_additional_settings( $post_id ) {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing
			if ( isset( $_POST['learndash-course-access-settings'] ) ) {
				$this->save_setting( $post_id, 'ld_extend_expiry_days', self::DEFAULT_EXTEND_EXPIRY_DAYS );
				$this->save_setting( $post_id, 'ld_extend_expiry_price' );
				$this->save_setting( $post_id, 'ld_extend_expiry_warning_days', self::DEFAULT_EXTEND_EXPIRY_WARNING_DAYS );
			}
		}

		public function add_additional_config_options( $setting_option_fields = array(), $settings_metabox_key = '' ) {
			if ( 'learndash-course-access-settings' === $settings_metabox_key
			&& ! isset( $setting_option_fields['ld_extend_expiry_days'] ) ) {

				$setting_option_fields['ld_extend_expiry_days'] = array(
					'name'           => 'ld_extend_expiry_days',
					'label'          => esc_html__( 'Extend Access Days', 'learndash-extend-expiry' ),
					'type'           => 'number',
					'class'          => 'small-text',
					'value'          => self::get_setting_value( get_the_ID(), 'ld_extend_expiry_days', self::DEFAULT_EXTEND_EXPIRY_DAYS ),
					'input_label'    => esc_html__( 'days', 'learndash-extend-expiry' ),
					'parent_setting' => 'expire_access',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'help_text'      => sprintf(
					// translators: placeholder: course.
						esc_html_x( 'Set the number of days a user will have access to the %s from the extended access buy date. Set zero if you do not want to activate this functionality.', 'placeholder: course.', 'learndash-extend-expiry' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type' => 'integer',
							),
						),
					),
				);

				$setting_option_fields['ld_extend_expiry_price'] = array(
					'name'           => 'ld_extend_expiry_price',
					'label'          => esc_html__( 'Extend Access Price', 'learndash-extend-expiry' ),
					'type'           => 'number',
					'value'          => self::get_setting_value( get_the_ID(), 'ld_extend_expiry_price' ),
					'parent_setting' => 'expire_access',
					'attrs'          => array(
						'step' => 0.01,
						'min'  => 0,
					),
					'help_text'      => sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Set the price to extend the %s access expiration. Set zero if access extension is free.', 'placeholder: course.', 'learndash-extend-expiry' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type' => 'number',
							),
						),
					),
				);

				$setting_option_fields['ld_extend_expiry_warning_days'] = array(
					'name'           => 'ld_extend_expiry_warning_days',
					'label'          => esc_html__( 'Extend Access Warning Days', 'learndash-extend-expiry' ),
					'type'           => 'number',
					'class'          => 'small-text',
					'value'          => self::get_setting_value( get_the_ID(), 'ld_extend_expiry_warning_days', self::DEFAULT_EXTEND_EXPIRY_WARNING_DAYS ),
					'input_label'    => esc_html__( 'days', 'learndash-extend-expiry' ),
					'parent_setting' => 'expire_access',
					'attrs'          => array(
						'step' => 1,
						'min'  => 0,
					),
					'help_text'      => sprintf(
					// translators: placeholder: course, default value.
						esc_html_x( 'Set the number of days before %1$s access expiration to start to show the access extend offer to a user. Default: %2$s', 'placeholder: course.', 'learndash-extend-expiry' ),
						learndash_get_custom_label_lower( 'course' ),
						self::DEFAULT_EXTEND_EXPIRY_WARNING_DAYS
					),
					'rest'           => array(
						'show_in_rest' => LearnDash_REST_API::enabled(),
						'rest_args'    => array(
							'schema' => array(
								'type' => 'integer',
							),
						),
					),
				);

			}

			// Always return $setting_option_fields
			return $setting_option_fields;
		}

	}
	new Ld_Extend_Expiry_Settings();

}
