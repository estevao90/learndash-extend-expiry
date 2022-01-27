<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Ld_Extend_Expiry_Control', false ) ) {

	class Ld_Extend_Expiry_Control {
		const POST_EXTEND_EXPIRY_ACTION = 'ld_extend_expiry';

		public function __construct() {
			// process post
			add_action( 'admin_post_' . self::POST_EXTEND_EXPIRY_ACTION, array( $this, 'ld_process_course_extend_expiry' ) );

			// process extend expiry
			add_filter( 'ld_course_access_expires_on', array( $this, 'ld_course_access_expires_on' ), 10, 3 );
		}

		public static function ld_update_extend_expiry_access( $user_id, $course_id, $ld_extend_days ) {
			$old_access_from    = (int) get_user_meta( $user_id, 'learndash_extend_expiry_access_from_course_' . $course_id, true );
			$extend_access_data = (int) get_user_meta( $user_id, 'learndash_extend_expiry_course_' . $course_id, true );

			if ( $old_access_from > 0 ) {
				// restore access from
				update_user_meta( $user_id, 'course_' . $course_id . '_access_from', $old_access_from );
				delete_user_meta( $user_id, 'learndash_extend_expiry_access_from_course_' . $course_id );

				// calculate extend days increment based on old access from and current date
				$course_access_upto = ld_course_access_expires_on( $course_id, $user_id );
				$diff_today         = time() - $course_access_upto;
				if ( $diff_today > 0 ) {
						$extend_days_increment = ceil( $diff_today / DAY_IN_SECONDS );
						$ld_extend_days       += $extend_days_increment;
				}
			}

			// update extend days
			$extend_access_data += $ld_extend_days;
			update_user_meta( $user_id, 'learndash_extend_expiry_course_' . $course_id, $extend_access_data );

			// remove expired meta
			delete_user_meta( $user_id, 'learndash_course_expired_' . $course_id );
		}

		public function ld_course_access_expires_on( $course_access_upto, $course_id, $user_id ) {
			if ( empty( $course_access_upto ) ) {
				return $course_access_upto; // expiry not set
			}

			$extend_access_data = get_user_meta( $user_id, 'learndash_extend_expiry_course_' . $course_id, true );
			if ( abs( intval( $extend_access_data ) ) > 0 ) {
				$course_access_upto += ( abs( intval( $extend_access_data ) ) * DAY_IN_SECONDS );
			}

			// if access expired, save the access from timestamp to update after extend the expiration
			if ( time() >= $course_access_upto ) {
				$access_from = (int) get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );
				if ( $access_from > 0 ) {
					update_user_meta( $user_id, 'learndash_extend_expiry_access_from_course_' . $course_id, $access_from );
				}
			}

			return $course_access_upto;
		}

		public function ld_process_course_extend_expiry() {
			$user_id = get_current_user_id();

			// check if is a extend expiry request
			if ( ( isset( $_POST['course_extend_expiry'] ) ) && ( isset( $_POST['course_id'] ) ) ) {
				$post_id = intval( $_POST['course_id'] );
				$post    = get_post( $post_id );
				if ( ( ! $post ) || ( ! is_a( $post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $post->post_type ) ) {
					return;
				}
			} else {
				return;
			}

			// validate user_id
			if ( empty( $user_id ) ) {
				$login_url = apply_filters( 'learndash_course_join_redirect', wp_login_url( get_permalink( $post_id ) ), $post_id );
				if ( ! empty( $login_url ) ) {
					learndash_safe_redirect( $login_url );
				}
			}

			// validate nonce
			if ( ! wp_verify_nonce( $_POST['course_extend_expiry'], 'course_extend_expiry_' . $user_id . '_' . $post_id ) ) {
				return;
			}

			// validate extend expiry configuration
			$ld_extend_days  = intval( Ld_Extend_Expiry_Settings::get_setting_value( $post_id, 'ld_extend_expiry_days', Ld_Extend_Expiry_Settings::DEFAULT_EXTEND_EXPIRY_DAYS ) );
			$ld_extend_price = floatval( Ld_Extend_Expiry_Settings::get_setting_value( $post_id, 'ld_extend_price', 0 ) );
			if ( 0 === $ld_extend_days || $ld_extend_price > 0 ) {
				return;
			}

			// extend days
			self::ld_update_extend_expiry_access( $user_id, $post_id, $ld_extend_days );

			// redirect to course
			learndash_safe_redirect( get_permalink( $post_id ) );
		}

	}
	new Ld_Extend_Expiry_Control();
}
