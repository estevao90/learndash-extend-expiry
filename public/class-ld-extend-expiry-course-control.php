<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Ld_Extend_Expiry_Course_Control', false ) ) {

	class Ld_Extend_Expiry_Course_Control {

		public function __construct() {
			// add hooks to process the course expiry
			add_action( 'learndash-infobar-inside-before', array( $this, 'add_course_expiry_box' ), 10, 3 );
			add_action( 'wp_head', array( $this, 'remove_expire_alert' ), 0 );
		}

		public function remove_expire_alert() {
			global $post;

			if ( ! is_singular() || empty( $post->ID ) || learndash_get_post_type_slug( 'course' ) !== $post->post_type ) {
				return;
			}
			$user_id = get_current_user_id();
			if ( empty( $user_id ) ) {
				return;
			}

			if ( ! Ld_Extend_Expiry_Helper::is_extend_expiry_enable( $post->ID ) ) {
				return; // extend expiry is not activated for this course
			}
			remove_action( 'wp_head', 'ld_course_access_expired_alert', 1 );
		}

		public function add_course_expiry_box( $post_type, $course_id, $user_id ) {
			if ( learndash_get_post_type_slug( 'course' ) !== $post_type ) {
				return; // only courses are supported
			}

			if ( 'open' === Ld_Extend_Expiry_Settings::get_setting_value( $course_id, 'course_price_type' ) ) {
				return; // open courses don't expire
			}

			if ( ! Ld_Extend_Expiry_Helper::is_extend_expiry_enable( $course_id ) ) {
				return; // extend expiry is not activated for this course
			}

			$expiration_date       = ld_course_access_expires_on( $course_id, $user_id );
			$days_until_expiration = Ld_Extend_Expiry_Helper::ld_days_until_access_expiration( $expiration_date );

			// define kind of message to show
			$initial_msg = '';
			if ( Ld_Extend_Expiry_Helper::is_course_expiration_warning_activated( $course_id, $days_until_expiration ) ) {
				$initial_msg = sprintf(
					// translators: placeholder: bold tag, bold tag, course expiration date.
					__( 'Your access %1$s will expire %2$s on %3$s.', 'learndash-extend-expiry' ),
					'<b>',
					'</b>',
					learndash_adjust_date_time_display( $expiration_date )
				);
			} else {

				$expired_timestamp = get_user_meta( $user_id, "learndash_course_expired_$course_id", true );
				if ( empty( $expired_timestamp ) ) {
					return; // access is not expired
				}

				$initial_msg = sprintf(
					// translators: placeholder: bold tag, bold tag, course.
					__( 'Your access %1$s expired %2$s on %3$s.', 'learndash-extend-expiry' ),
					'<b>',
					'</b>',
					learndash_adjust_date_time_display( $expired_timestamp )
				);
			}

			self::render_expiry_box( $course_id, $user_id, $initial_msg );
		}

		private static function render_expiry_box( $course_id, $user_id, $initial_msg ) {
			$extend_days  = Ld_Extend_Expiry_Settings::get_setting_value( $course_id, 'ld_extend_expiry_days', Ld_Extend_Expiry_Settings::DEFAULT_EXTEND_EXPIRY_DAYS );
			$extend_price = Ld_Extend_Expiry_Settings::get_setting_value( $course_id, 'ld_extend_expiry_price', 0 );

			// translators: placeholder: number of extended day.
			$days_str = sprintf( _n( '%s day', '%s days', $extend_days, 'learndash-extend-expiry' ), $extend_days );

			?>
			<style>
				.ld-course-status:not(.ld-extend-expiry-status){
					display: none !important; <?php // hide default take a course button ?>
				}
			</style>
			<div class="ld-alert ld-alert-warning ld-extend-expiry-alert">
				<div class="ld-alert-content">
					<div class="ld-alert-icon ld-icon ld-icon-alert"></div>
					<div class="ld-alert-messages">
						<?php echo $initial_msg . esc_html( ' ' . __( 'You can extend your access as described below.', 'learndash-extend-expiry' ) ); //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>

			<div class="ld-course-status ld-extend-expiry-status ld-course-status-not-enrolled">

				<div class="ld-course-status-segment ld-course-status-seg-price">
					<span class="ld-course-status-label">
					<?php
						// translators: placeholder: number of extended day str.
					echo esc_html( sprintf( __( 'Price (add more %s)', 'learndash-extend-expiry' ), $days_str ) );
					?>
					</span>

					<div class="ld-course-status-content">
						<span class="ld-course-status-price">
						<?php
						if ( $extend_price > 0 ) {
							echo wp_kses_post( '<span class="ld-currency">' . learndash_30_get_currency_symbol() . '</span>' );
							echo wp_kses_post( $extend_price );
						} else {
							esc_html_e( 'Free', 'learndash-extend-expiry' );
						}
						?>
					</div>
				</div> 


				<div class="ld-course-status-segment ld-course-status-seg-action">
					<span class="ld-course-status-label"><?php echo esc_html_e( 'Action', 'learndash-extend-expiry' ); ?></span>

					<div class="ld-course-status-content">
						<div class="ld-course-status-action">
							<?php echo Ld_Extend_Expiry_Payment_Integration::ld_extend_expiry_payment_button( $course_id, $user_id, $extend_days, $extend_price ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Outputs Payment button HTML ?>
						</div>
					</div>

				</div>

			</div>
			<?php
		}
	}

	new Ld_Extend_Expiry_Course_Control();
}
