<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Ls_Template_Manager', false ) ) {

	class Imm_Learndash_Ls_Template_Manager {

		public function __construct() {
			// add hooks to process templates
			add_filter( 'learndash_template', array( $this, 'learndash_template' ), 10, 2 );
		}

		public function learndash_template( $filepath, $name ) {
			// check if template was override
			$override_path = plugin_dir_path( __FILE__ ) . "../../templates/$name";
			if ( is_file( $override_path ) ) {
							$filepath = $override_path;
			}

			// Always return $filepath.
			return $filepath;
		}
	}
	new Imm_Learndash_Ls_Template_Manager();
}
