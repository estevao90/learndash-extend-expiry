<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Lesson_Sell', false ) ) {

	class Imm_Learndash_Lesson_Sell extends Imm_Learndash_Sell_Settings {

		public function __construct() {
			parent::__construct(
				'lesson',
				esc_html__( 'lesson', 'learndash-extend-expiry' )
			);

			// add hooks
			add_filter( 'learndash_lesson_row_atts', array( $this, 'learndash_lesson_row_attrs' ), 10, 4 );
			add_filter( 'learndash_lesson_attributes', array( $this, 'learndash_lesson_attributes' ), 10, 2 );

			add_filter( 'learndash-lesson-row-class', array( $this, 'learndash_lesson_row_class' ), 10, 2 );
			add_filter( 'imm-learndash-nav-widget-lesson-class', array( $this, 'learndash_lesson_row_class' ), 10, 2 );

		}

		public function learndash_lesson_row_attrs( $attribute, $lesson_id, $course_id, $user_id ) {
			switch ( self::get_price_type( $lesson_id ) ) {
				case 'open':
					return ''; // free access

				case 'free':
					if ( empty( $user_id ) ) { // user have to be logged
						return 'data-ld-tooltip="' . Imm_Learndash_Ls_Access_Control::get_msg_login_required() . '"';
					}
					break;

				case 'paynow':
				case 'subscribe':
				case 'closed':
					// check current access
					$has_access = Imm_Learndash_Ls_Access_Control::ld_resource_selling_with_access(
						$lesson_id,
						$user_id,
						$course_id
					);
					if ( ! $has_access ) {
						return 'data-ld-tooltip="' . Imm_Learndash_Ls_Access_Control::get_msg_access_denied() . '"';
					}
					break;

				default:
					return $attribute; // always return $attribute.
			}
		}

		public function learndash_lesson_row_class( $lesson_class, $lesson ) {
			return $this->add_lesson_has_access_class( $lesson['post']->ID, get_current_user_id(), $lesson_class );
		}

		private function add_lesson_has_access_class( $lesson_id, $user_id, $classes ) {
			$has_access = Imm_Learndash_Ls_Access_Control::ld_resource_selling_with_access(
				$lesson_id,
				$user_id
			);

			if ( true === $has_access ) {
				$classes .= ' imm-ls-has-access';
			} elseif ( false === $has_access ) {
				$classes .= ' imm-ls-no-access';
			}

			return $classes;
		}

		public function learndash_lesson_attributes( $attributes, $lesson ) {
			switch ( self::get_price_type( $lesson['post']->ID ) ) {
				case 'open':
					$attributes[] = array(
						// translators: placeholder: Lesson.
						'label' => sprintf( esc_html_x( 'Open %s', 'placeholder: Lesson', 'learndash-extend-expiry' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
						'icon'  => 'ld-icon-unlocked',
						'class' => 'ld-status-unlocked ld-primary-color',
					);
					break;

				case 'free':
					$attributes[] = array(
						// translators: placeholder: Lesson.
						'label' => sprintf( esc_html_x( 'Free %s', 'placeholder: Lesson', 'learndash-extend-expiry' ), LearnDash_Custom_Label::get_label( 'lesson' ) ),
						'icon'  => 'ld-icon-login',
						'class' => 'ld-primary-color' . ( is_user_logged_in() ? ' ld-status-unlocked ' : '' ),
					);
					break;

				case 'paynow':
				case 'subscribe':
				case 'closed':
					// check current access
					$has_access = Imm_Learndash_Ls_Access_Control::ld_resource_selling_with_access(
						$lesson['post']->ID,
						get_current_user_id()
					);
					// define attribute
					$attributes[] = array(
						'label' => esc_html__( 'Sold individually', 'learndash-extend-expiry' ),
						'icon'  => 'ld-icon-materials',
						'class' => 'ld-primary-color' . ( $has_access ? ' ld-status-unlocked ' : '' ),
					);
					break;

				default:
					break;
			}
			return $attributes; // always return $attribute.
		}
	}

	new Imm_Learndash_Lesson_sell();
}
