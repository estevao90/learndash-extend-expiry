<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Ld_Extend_Expiry_Helper', false ) ) {

	class Ld_Extend_Expiry_Helper {

		public static function ld_days_until_access_expiration( $expiration_date ) {
			$days_until_expiration = 0;

			if ( $expiration_date > 0 ) {
				$diff                  = $expiration_date - time();
				$days_until_expiration = ceil( $diff / DAY_IN_SECONDS ); // convert to days
			}
			return $days_until_expiration;
		}

		public static function is_extend_expiry_enable( $course_id ) {
			return intval( Ld_Extend_Expiry_Settings::get_setting_value( $course_id, 'ld_extend_expiry_days', Ld_Extend_Expiry_Settings::DEFAULT_EXTEND_EXPIRY_DAYS ) ) > 0;
		}

		public static function is_course_expiration_warning_activated( $course_id, $days_until_expiration ) {
			if ( $days_until_expiration <= 0 ) {
				return false; // warning should not be shown if the course is already expired or user don't have access
			}

			if ( ! self::is_extend_expiry_enable( $course_id ) ) {
				return false; // extend expiry is not activated for this course
			}

			$ld_extend_expiry_warning_days = intval( Ld_Extend_Expiry_Settings::get_setting_value( $course_id, 'ld_extend_expiry_warning_days', Ld_Extend_Expiry_Settings::DEFAULT_EXTEND_EXPIRY_WARNING_DAYS ) );
			if ( 0 === $ld_extend_expiry_warning_days ) {
				return false; // extend expiry warning is not activated for this course
			}
			return $days_until_expiration <= $ld_extend_expiry_warning_days;
		}

	}
}
