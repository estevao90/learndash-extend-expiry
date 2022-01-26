<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Access_Control', false ) ) {

	class Imm_Learndash_Ls_Access_Control {

		public function __construct() {
			// add hooks to process access
			add_filter( 'sfwd_lms_has_access', array( $this, 'sfwd_lms_has_access' ), 10, 3 );

			// add tooltip with access information
			add_action( 'learndash-topic-row-title-before', array( $this, 'learndash_resource_row_title_before' ), 10, 3 );
			add_action( 'learndash-quiz-row-title-before', array( $this, 'learndash_resource_row_title_before' ), 10, 3 );

			// add access badge
			add_action( 'learndash-topic-row-title-after', array( $this, 'learndash_resource_row_title_after' ), 10, 3 );
			add_action( 'learndash-quiz-row-title-after', array( $this, 'learndash_resource_row_title_after' ), 10, 3 );

			// add resource status
			add_action( 'learndash-lesson-row-attributes-after', array( $this, 'imm_add_resource_status' ), 10, 3 );
			add_action( 'learndash-topic-quiz-row-before', array( $this, 'imm_add_resource_status' ), 50, 3 );
			add_action( 'learndash-quiz-row-after', array( $this, 'imm_add_resource_status' ), 50, 3 );

			// add hook to delete resource access data
			add_action( 'learndash_delete_user_data', array( $this, 'learndash_delete_user_data' ), 10, 1 );
		}

		public static function get_label_for_ld_resource( $resource_id ) {
			$post_type = get_post_type( $resource_id );
			switch ( $post_type ) {
				case 'sfwd-courses':
					return 'course';
				case 'sfwd-lessons':
					return 'lesson';
				case 'sfwd-topic':
					return 'topic';
				case 'sfwd-quiz':
					return 'quiz';

				default:
					return '';
			}
		}

		public static function get_msg_login_required() {
			return esc_html__( 'You must to be logged in to have access to this content', 'learndash-extend-expiry' );
		}

		public static function get_msg_access_denied() {
			return esc_html__( "You don't currently have access to this content", 'learndash-extend-expiry' );
		}

		public static function update_resource_access( $user_id, $resource_id, $remove = false ) {
			$action_success = false;

			$user_id     = absint( $user_id );
			$resource_id = absint( $resource_id );

			if ( ( empty( $user_id ) ) || ( empty( $resource_id ) ) ) {
				return;
			}

			$user_resource_access_time = 0;
			if ( empty( $remove ) ) {
				$user_resource_access_time = get_user_meta( $user_id, 'resource_' . $resource_id . '_access_from', true );
				if ( empty( $user_resource_access_time ) ) {
					$user_resource_access_time = time();
					update_user_meta( $user_id, 'resource_' . $resource_id . '_access_from', $user_resource_access_time );
					$action_success = true;
				}
			} else {
				$user_resource_access_time = get_user_meta( $user_id, 'resource_' . $resource_id . '_access_from', true );
				if ( ! empty( $user_resource_access_time ) ) {
						delete_user_meta( $user_id, 'resource_' . $resource_id . '_access_from' );
						$action_success = true;
				}
			}

			$course_id            = learndash_get_course_id( $resource_id );
			$course_activity_args = array(
				'activity_type' => 'access',
				'user_id'       => $user_id,
				'post_id'       => $course_id,
				'course_id'     => $course_id,
			);
			$course_activity      = learndash_get_user_activity( $course_activity_args );
			if ( is_null( $course_activity ) ) {
				$course_activity_args['course_id'] = 0;
				$course_activity                   = learndash_get_user_activity( $course_activity_args );
			}

			if ( is_object( $course_activity ) ) {
				$course_activity_args            = json_decode( wp_json_encode( $course_activity ), true );
				$course_activity_args['changed'] = false;
			} else {
				$course_activity_args['changed']          = true;
				$course_activity_args['activity_started'] = 0;
			}

			if ( ( empty( $course_activity_args['course_id'] ) ) || ( $course_activity_args['course_id'] !== $course_activity_args['post_id'] ) ) {
				$course_activity_args['course_id'] = $course_activity_args['post_id'];
				$course_activity_args['changed']   = true;
			}

			if ( empty( $remove ) ) {
				if ( absint( $course_activity_args['activity_started'] ) !== $user_course_access_time ) {
					$course_activity_args['activity_started'] = $user_course_access_time;
					$course_activity_args['changed']          = true;
				}
			} else {
				$course_activity_args['activity_started'] = $user_course_access_time;
				$course_activity_args['changed']          = true;
			}

			if ( true === $course_activity_args['changed'] ) {
				$skip = false;
				if ( ( ! empty( $remove ) ) && ( ! isset( $course_activity_args['activity_id'] ) ) ) {
					$skip = true;
				}
				if ( true !== $skip ) {
					$course_activity_args['data_upgrade'] = true;
					learndash_update_user_activity( $course_activity_args );
				}
			}

			return $action_success;
		}

		public function learndash_delete_user_data( $user_id ) {
			global $wpdb;

			// delete resource access data
			$wpdb->query(
				$wpdb->prepare(
					"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s AND user_id = %d",
					'resource_%_access_from',
					absint( $user_id )
				)
			);
		}

		public function imm_add_resource_status( $resource_id, $course_id, $user_id ) {
			$resource_price_type = Imm_Learndash_Settings_Helper::get_price_type( $resource_id );
			switch ( $resource_price_type ) {
				case 'paynow':
				case 'subscribe':
				case 'closed':
					learndash_get_template_part(
						'imm/partials/resource-status.php',
						array(
							'resource_id'   => $resource_id,
							'resource_type' => get_post_type( $resource_id ),
							'user_id'       => $user_id,
							'course_id'     => $course_id,
							'price_type'    => $resource_price_type,
						),
						true
					);
					break;

				default:
					break;
			}
		}

		public function learndash_resource_row_title_before( $resource_id, $course_id, $user_id ) {
			$has_access = self::ld_resource_selling_with_access(
				$resource_id,
				$user_id,
				$course_id
			);
			if ( false === $has_access ) {
				$access_message = self::get_msg_access_denied();

				// checking price type to add more helpful message
				$resource_price_type = self::get_ld_resource_price_type( $resource_id );
				switch ( $resource_price_type ) {
					case 'free':
						$access_message = self::get_msg_login_required();
						break;
				}
				?>
<script>
jQuery( "#ld-table-list-item-<?php echo esc_attr( $resource_id ); ?>" ).attr("data-ld-tooltip", "<?php echo esc_html( $access_message ); ?>");
</script>
				<?php
			}
		}

		public function learndash_resource_row_title_after( $resource_id, $course_id, $user_id ) {
			$resource_access_str = '';
			$status_class        = '';
			$status_icon_class   = '';

			// add special class if user has access
			$has_access = self::ld_resource_selling_with_access(
				$resource_id,
				$user_id,
				$course_id
			);
			if ( $has_access ) {
				$status_class = 'ld-status-unlocked';
			}

			$resource_price_type = Imm_Learndash_Settings_Helper::get_price_type( $resource_id );
			switch ( $resource_price_type ) {
				case 'open':
					$resource_access_str = sprintf(
					// translators: placeholder: LearnDash module name.
						esc_html_x( 'Open %s', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						LearnDash_Custom_Label::get_label( self::get_label_for_ld_resource( $resource_id ) )
					);
					$status_icon_class = 'ld-icon-unlocked';
					break;

				case 'free':
					$resource_access_str = sprintf(
					// translators: placeholder: LearnDash module name.
						esc_html_x( 'Free %s', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						LearnDash_Custom_Label::get_label( self::get_label_for_ld_resource( $resource_id ) )
					);
					$status_icon_class = 'ld-icon-login';
					break;

				case 'paynow':
				case 'subscribe':
				case 'closed':
					$resource_access_str = esc_html__( 'Sold individually', 'learndash-extend-expiry' );
					$status_icon_class   = 'ld-icon-materials';
					break;

				default:
					return; // nothing to show
			}
			?>
<span class="ld-status <?php echo esc_attr( $status_class ); ?> ld-primary-color">
<span class="ld-icon <?php echo esc_attr( $status_icon_class ); ?>"></span>
			<?php echo esc_html( $resource_access_str ); ?>				
</span>
				<?php
		}

		public static function get_ld_resource_price_type( $step_id ) {
			$step_post_type = get_post_type( $step_id );

			if ( 'sfwd-courses' === $step_post_type ) {
				return null; // courses are not managed here
			}

			if ( 'sfwd-lessons' === $step_post_type ) {
				return Imm_Learndash_Settings_Helper::get_price_type( $step_id );

			} elseif ( 'sfwd-topic' === $step_post_type || 'sfwd-quiz' === $step_post_type ) {
				$step_price_type = Imm_Learndash_Settings_Helper::get_price_type( $step_id );

				if ( ! empty( $step_price_type ) ) {
					return $step_price_type; // return step price type
				}

				// check parent step
				$course_id         = learndash_get_course_id( $step_id );
				$parent_id         = learndash_get_lesson_id( $step_id, $course_id );
				$parent_price_type = Imm_Learndash_Settings_Helper::get_price_type( $parent_id );

				if ( ! empty( $parent_price_type ) ) {
					return $parent_price_type; // return parent price type
				}

				// if parent price is empty, check the parent post type to know if it is the top element of the tree (lesson)
				$parent_post_type = get_post_type( $parent_id );
				if ( 'sfwd-topic' === $parent_post_type ) {
					// if it's a topic, get the lesson id
					$lesson_id = learndash_get_lesson_id( $parent_id, $course_id );
					return Imm_Learndash_Settings_Helper::get_price_type( $lesson_id );
				}

				return null; // price type not set
			}
		}

		/**
		 * Check if a LD resource is selling individually and user has access
		 *
		 * @param [int] $resource_id LD resource ID.
		 * @param [int] $user_id WP user ID. Default 0.
		 * @param [int] $course_id LD course ID. Default 0.
		 * @return mixed true or false if LD resource is selling individually or null if LD resource is not selling individually.
		 */
		public static function ld_resource_selling_with_access( $resource_id, $user_id = 0, $course_id = 0 ) {
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			if ( empty( $course_id ) ) {
				$course_id = learndash_get_course_id( $resource_id );
			}

			$parent_id = learndash_get_lesson_id( $resource_id, $course_id );
			if ( learndash_is_sample( $parent_id ) ) {
				return null; // don't change sample item
			}

			$resource_price_type = self::get_ld_resource_price_type( $resource_id );
			switch ( $resource_price_type ) {
				case 'open':
					return true; // open resource

				case 'free':
					return ! empty( $user_id ); // user must to be logged

				case 'paynow':
				case 'subscribe':
				case 'closed':
					$user_meta_access = get_user_meta( $user_id, 'resource_' . $resource_id . '_access_from', true );
					return ! empty( $user_meta_access );

				default:
					break;
			}

			return null;
		}

		public function sfwd_lms_has_access( $has_access, $step_id, $user_id ) {
			if ( empty( $user_id ) ) {
				$user_id = get_current_user_id();
			}

			$step_price_type = self::ld_resource_selling_with_access( $step_id, $user_id );
			if ( null === $step_price_type ) {
				return $has_access; // LD isn't selling individually (nothing change)
			}
			return $step_price_type;
		}
	}
	new Imm_Learndash_Ls_Access_Control();
}
