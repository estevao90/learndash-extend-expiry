<?php

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'Imm_Learndash_Sell_Settings', false ) ) {

	abstract class Imm_Learndash_Sell_Settings extends LearnDash_Settings_Metabox {

		protected $ld_module;
		protected $ld_i18n_module_name;

		final protected static function get_price_type( $post_id ) {
			return Imm_Learndash_Settings_Helper::get_price_type( $post_id );
		}


		public function __construct( $ld_module, $ld_i18n_module_name ) {
			$this->ld_module           = $ld_module;
			$this->ld_i18n_module_name = $ld_i18n_module_name;

			$this->helper = new Imm_Learndash_Settings_Helper( $this->ld_module );

			add_filter( 'learndash_settings_fields', array( $this, 'add_additional_config_options' ), 30, 2 );
		}

		public function add_additional_config_options( $setting_option_fields = array(), $settings_metabox_key = '' ) {
			if ( "learndash-{$this->ld_module}-access-settings" === $settings_metabox_key
			&& ! isset( $setting_option_fields['imm_ls_selling_sell_individually'] ) ) {

				// loadings settings values
				$this->helper->load_setting_option_values( get_the_ID() );

				$setting_option_fields['imm_ls_selling_sell_individually'] = array(
					'name'                => 'imm_ls_selling_sell_individually',
					'label'               => sprintf(
					// translators: placeholder: LearnDash module name.
						esc_html_x( 'Sell this %s individually', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						$this->ld_i18n_module_name
					),
					'type'                => 'checkbox-switch',
					'value'               => $this->helper->setting_option_values['imm_ls_selling_sell_individually'],
					'child_section_state' => ( 'on' === $this->helper->setting_option_values['imm_ls_selling_sell_individually'] ) ? 'open' : 'closed',
					'default'             => '',
					'options'             => array(
						'on' => sprintf(
						// translators: placeholder: LearnDash module name.
							esc_html_x(
								'The access mode of this %s is controlled individually',
								'placeholder: LearnDash module name',
								'learndash-extend-expiry'
							),
							$this->ld_i18n_module_name
						),
						''   => '',
					),
					'help_text'           => sprintf(
					// translators: placeholder: LearnDash module name.
						esc_html_x(
							'If enabled, you will be able to control the access mode of this %s individually.',
							'placeholder: LearnDash module name',
							'learndash-extend-expiry'
						),
						$this->ld_i18n_module_name
					),
				);

				$setting_option_fields['imm_ls_selling_price_type'] = array(
					'name'           => 'imm_ls_selling_price_type',
					'label'          => esc_html__( 'Access Mode', 'learndash-extend-expiry' ),
					'type'           => 'radio',
					'value'          => $this->helper->setting_option_values['imm_ls_selling_price_type'],
					'default'        => LEARNDASH_DEFAULT_COURSE_PRICE_TYPE,
					'parent_setting' => 'imm_ls_selling_sell_individually',
					'options'        => array(
						'open'      => array(
							'label'       => esc_html__( 'Open', 'learndash-extend-expiry' ),
							'description' => sprintf(
							// translators: placeholder: LearnDash module name.
								esc_html_x(
									'The %s is not protected. Any user can access its content without the need to be logged-in or enrolled.',
									'placeholder: LearnDash module name.',
									'learndash-extend-expiry'
								),
								$this->ld_i18n_module_name
							),
						),
						'free'      => array(
							'label'       => esc_html__( 'Free', 'learndash-extend-expiry' ),
							'description' => sprintf(
							// translators: placeholder: LearnDash module name.
								esc_html_x(
									'The %s is protected. Registration and enrollment are required in order to access the content.',
									'placeholder: LearnDash module name.',
									'learndash-extend-expiry'
								),
								$this->ld_i18n_module_name
							),
						),
						'paynow'    => array(
							'label'               => esc_html__( 'Buy now', 'learndash-extend-expiry' ),
							'description'         => sprintf(
							// translators: placeholder: LearnDash module name, LearnDash module name.
								esc_html_x(
									'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (one-time fee) in order to gain access.',
									'placeholder: LearnDash module name, LearnDash module name',
									'learndash-extend-expiry'
								),
								$this->ld_i18n_module_name,
								$this->ld_i18n_module_name
							),
							'inline_fields'       => array(
								'imm_ls_selling_price_type_paynow' => $this->get_price_type_paynow_fields(),
							),
							'inner_section_state' => ( 'paynow' === $this->helper->setting_option_values['imm_ls_selling_price_type'] ) ? 'open' : 'closed',
						),
						'subscribe' => array(
							'label'               => esc_html__( 'Recurring', 'learndash-extend-expiry' ),
							'description'         => sprintf(
							// translators: placeholder: LearnDash module name, LearnDash module name.
								esc_html_x(
									'The %1$s is protected via the LearnDash built-in PayPal and/or Stripe. Users need to purchase the %2$s (recurring fee) in order to gain access.',
									'placeholder: LearnDash module name, LearnDash module name',
									'learndash-extend-expiry'
								),
								$this->ld_i18n_module_name,
								$this->ld_i18n_module_name
							),
							'inline_fields'       => array(
								'imm_ls_selling_price_type_subscribe' => $this->get_price_type_subscribe_fields(),
							),
							'inner_section_state' => ( 'subscribe' === $this->helper->setting_option_values['imm_ls_selling_price_type'] ) ? 'open' : 'closed',
						),
						'closed'    => array(
							'label'               => esc_html__( 'Closed', 'learndash-extend-expiry' ),
							'description'         => sprintf(
							// translators: placeholder: LearnDash module name, group.
								esc_html_x(
									'The %1$s can only be accessed through admin enrollment (manual), %2$s enrollment, or integration (shopping cart or membership) enrollment. No enrollment button will be displayed, unless a URL is set (optional).',
									'placeholder: LearnDash module name, group',
									'learndash-extend-expiry'
								),
								$this->ld_i18n_module_name,
								learndash_get_custom_label_lower( 'group' )
							),
							'inline_fields'       => array(
								'imm_ls_selling_type_closed' => $this->get_price_type_closed_fields(),
							),
							'inner_section_state' => ( 'closed' === $this->helper->setting_option_values['imm_ls_selling_price_type'] ) ? 'open' : 'closed',
						),
					),
				);

			}

			// Always return $setting_option_fields
			return $setting_option_fields;
		}

		private function get_price_type_paynow_fields() {
			$this->setting_option_fields = array(
				'imm_ls_selling_price_type_paynow' => array(
					'name'    => 'imm_ls_selling_price_type_paynow',
					'label'   => sprintf(
						// translators: placeholder: LearnDash module name.
						esc_html_x( '%s Price', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						ucfirst( $this->ld_i18n_module_name )
					),
					'type'    => 'number',
					'attrs'   => array(
						'step' => 0.01,
						'min'  => 0.01,
					),
					'class'   => '-medium',
					'value'   => $this->helper->setting_option_values['imm_ls_selling_price_type_paynow'],
					'default' => '',
				),
			);
			parent::load_settings_fields();
			return $this->setting_option_fields;
		}

		private function get_price_type_subscribe_fields() {
			$this->setting_option_fields = array(
				'imm_ls_selling_price_type_subscribe_price' => array(
					'name'    => 'imm_ls_selling_price_type_subscribe_price',
					'label'   => sprintf(
						// translators: placeholder: LearnDash module name.
						esc_html_x( '%s Price', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						ucfirst( $this->ld_i18n_module_name )
					),
					'type'    => 'number',
					'attrs'   => array(
						'step' => 0.01,
						'min'  => 0.01,
					),
					'class'   => '-medium',
					'value'   => $this->helper->setting_option_values['imm_ls_selling_price_type_subscribe_price'],
					'default' => '',
				),
				'imm_ls_selling_price_type_subscribe_billing_cycle' => array(
					'name'  => 'imm_ls_selling_price_type_subscribe_billing_cycle',
					'label' => esc_html__( 'Billing Cycle', 'learndash-extend-expiry' ),
					'type'  => 'custom',
					'html'  => $this->helper->learndash_billing_cycle_html(),
				),
			);

			parent::load_settings_fields();
			return $this->setting_option_fields;
		}

		private function get_price_type_closed_fields() {
			$this->setting_option_fields = array(
				'imm_ls_selling_price_type_closed_price' => array(
					'name'    => 'imm_ls_selling_price_type_closed_price',
					'label'   => sprintf(
						// translators: placeholder: LearnDash module name.
						esc_html_x( '%s Price', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						ucfirst( $this->ld_i18n_module_name )
					),
					'type'    => 'number',
					'attrs'   => array(
						'step' => 0.01,
						'min'  => 0.01,
					),
					'class'   => '-medium',
					'value'   => $this->helper->setting_option_values['imm_ls_selling_price_type_closed_price'],
					'default' => '',
				),
				'imm_ls_selling_price_type_closed_custom_button_url' => array(
					'name'      => 'imm_ls_selling_price_type_closed_custom_button_url',
					'label'     => esc_html__( 'Button URL', 'learndash-extend-expiry' ),
					'type'      => 'text',
					'class'     => 'full-text',
					'value'     => $this->helper->setting_option_values['imm_ls_selling_price_type_closed_custom_button_url'],
					'help_text' => sprintf(
						// translators: placeholder: "Take this" button label, LearnDash module name
						esc_html_x( 'Redirect the "%1$s %2$s" button to a specific URL.', 'placeholder: "Take this" button label, LearnDash module name', 'learndash-extend-expiry' ),
						esc_html__( 'Take this', 'learndash-extend-expiry' ),
						ucfirst( $this->ld_i18n_module_name )
					),
					'default'   => '',
				),
			);

			parent::load_settings_fields();
			return $this->setting_option_fields;
		}

	}

}
