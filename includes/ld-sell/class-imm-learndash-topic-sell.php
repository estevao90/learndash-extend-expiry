<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Topic_Sell', false ) ) {

	class Imm_Learndash_Topic_Sell extends Imm_Learndash_Sell_Settings {

		public function __construct() {
			parent::__construct(
				'topic',
				esc_html__( 'topic', 'learndash-extend-expiry' )
			);

			// add hooks
			add_filter( 'learndash-topic-row-class', array( $this, 'learndash_topic_row_class' ), 10, 2 );
		}

		public function learndash_topic_row_class( $row_class, $topic ) {
			$has_access = Imm_Learndash_Ls_Access_Control::ld_resource_selling_with_access(
				$topic->ID,
				get_current_user_id()
			);
			if ( true === $has_access ) {
				$row_class .= ' imm-ls-has-access';
			} elseif ( false === $has_access ) {
				$row_class .= ' imm-ls-no-access';
			}

			// Always return $row_class.
			return $row_class;
		}
	}

	new Imm_Learndash_Topic_Sell();
}
