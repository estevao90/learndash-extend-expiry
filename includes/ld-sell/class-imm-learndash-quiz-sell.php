<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Quiz_Sell', false ) ) {

	class Imm_Learndash_Quiz_Sell extends Imm_Learndash_Sell_Settings {

		public function __construct() {
			parent::__construct(
				'quiz',
				esc_html__( 'quiz', 'learndash-lessons-selling' )
			);

			// add hooks
			add_filter( 'learndash_quiz_row_classes', array( $this, 'learndash_quiz_row_classes' ), 10, 2 );
		}

		public function learndash_quiz_row_classes( $classes, $quiz ) {
			$has_access = Imm_Learndash_Ls_Access_Control::ld_resource_selling_with_access(
				$quiz['post']->ID,
				get_current_user_id()
			);
			if ( true === $has_access ) {
				$classes['anchor'] .= ' imm-ls-has-access';
			} elseif ( false === $has_access ) {
				$classes['anchor'] .= ' imm-ls-no-access';
			}

			// Always return $classes.
			return $classes;
		}

	}

	new Imm_Learndash_Quiz_Sell();
}
