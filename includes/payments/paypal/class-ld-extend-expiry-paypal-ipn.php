<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'LEARNDASH_VERSION' ) || ! defined( 'LEARNDASH_EXTEND_EXPIRY_VERSION' ) ) {
	exit;
}

if ( ! class_exists( 'Ld_Extend_Expiry_Paypal_IPN' ) ) {

	class Ld_Extend_Expiry_Paypal_IPN {

		private static $ipn_transaction_log;
		private static $ipn_transaction_data;
		private static $ipn_transaction_post_id;

		private static $ld_paypal_settings;

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.2
		 */
		private function __construct() {
		}

		public static function init() {
			self::$ipn_transaction_log     = '';
			self::$ipn_transaction_data    = array();
			self::$ipn_transaction_post_id = 0;

			self::$ld_paypal_settings = array();
		}

		/**
		 * Entry point for IPN processing
		 *
		 * @since 3.2.2
		 */
		public static function ipn_process() {
			// Create our initial Transaction.
			self::ipn_init_transaction();
			self::ipn_debug( '---' );

			self::ipn_init_post_data();
			self::ipn_debug( '---' );

			self::ipn_init_settings();
			self::ipn_debug( '---' );

			self::ipn_init_listener();
			self::ipn_debug( '---' );

			self::ipn_validate_post_data();
			self::ipn_debug( '---' );

			self::ipn_process_post_data();
			self::ipn_debug( '---' );

			self::ipn_process_user_data();
			self::ipn_debug( '---' );

			self::ipn_complete_transaction();
			self::ipn_debug( '---' );

			self::ipn_debug( 'IPN Processing Completed Successfully.' );
			self::ipn_exit();
			// we're done here.
		}

		public static function ipn_init_post_data() {
      // phpcs:ignore WordPress.Security.NonceVerification.Missing
			self::$ipn_transaction_data = $_POST;
			self::$ipn_transaction_data = array_map( 'trim', self::$ipn_transaction_data );
			self::$ipn_transaction_data = array_map( 'esc_attr', self::$ipn_transaction_data );

			// First log our incoming vars.
			self::ipn_debug( 'IPN Post vars<pre>' . print_r( self::$ipn_transaction_data, true ) . '</pre>' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
		}

		/**
		 * Load LD PayPal Settings
		 */
		public static function ipn_init_settings() {
			self::$ld_paypal_settings = LearnDash_Settings_Section::get_section_settings_all( 'LearnDash_Settings_Section_PayPal' );

			if ( ! isset( self::$ld_paypal_settings['paypal_sandbox'] ) ) {
				self::$ld_paypal_settings['paypal_sandbox'] = '';
			}
			self::$ld_paypal_settings['paypal_sandbox'] = ( 'yes' === self::$ld_paypal_settings['paypal_sandbox'] ) ? 1 : 0;

			// Then log the PayPal settings.
			self::ipn_debug( 'LearnDash Paypal Settings<pre>' . print_r( self::$ld_paypal_settings, true ) . '</pre>' ); //phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r

			if ( ( ! isset( self::$ld_paypal_settings['paypal_email'] ) ) || ( empty( self::$ld_paypal_settings['paypal_email'] ) ) ) {
				self::ipn_debug( "ERROR: LD PayPal settings 'paypal_email' is empty. [" . self::$ld_paypal_settings['paypal_email'] . ']' );
				self::ipn_exit();
			}
			self::$ld_paypal_settings['paypal_email'] = sanitize_email( self::$ld_paypal_settings['paypal_email'] );
			self::$ld_paypal_settings['paypal_email'] = strtolower( self::$ld_paypal_settings['paypal_email'] );

			if ( ! is_email( self::$ld_paypal_settings['paypal_email'] ) ) {
				self::ipn_debug( "ERROR: LD PayPal settings 'paypal_email' is invalid. [" . self::$ld_paypal_settings['paypal_email'] . ']' );
				self::ipn_exit();
			}
		}

		public static function ipn_init_listener() {
			self::ipn_debug( 'IPN Listener Loading...' );
			$listener_file = LEARNDASH_LMS_LIBRARY_DIR . '/paypal/ipnlistener.php';
			if ( ! file_exists( $listener_file ) ) {
				self::ipn_debug( "ERROR: Required file not found $listener_file" );
				self::ipn_exit();
			}
			require $listener_file;
			$learndash_paypal_ipn_listener = new IpnListener();

			/**
			 * Fires after instansiating a ipnlistener object to allow override of public attributes.
			 *
			 * @since 2.2.1.2
			 *
			 * @param Object  $learndash_paypal_ipn_listener An instance of IpnListener Class.
			 */
			do_action_ref_array( 'learndash_ipnlistener_init', array( &$learndash_paypal_ipn_listener ) );

			self::ipn_debug( 'IPN Listener Loaded' );

			if ( ! empty( self::$ld_paypal_settings['paypal_sandbox'] ) ) {
				self::ipn_debug( 'PayPal Sandbox Enabled.' );
				$learndash_paypal_ipn_listener->use_sandbox = true;
			} else {
				self::ipn_debug( 'PayPal Live Enabled.' );
				$learndash_paypal_ipn_listener->use_sandbox = false;
			}

			try {
				self::ipn_debug( 'Checking IPN Post Method.' );
				$learndash_paypal_ipn_listener->requirePostMethod();
				$learndash_paypal_ipn_verified = $learndash_paypal_ipn_listener->processIpn();
				self::ipn_debug( 'IPN Post method check completed.' );
				if ( ! $learndash_paypal_ipn_verified ) {
					/**
					 * An Invalid IPN *may* be caused by a fraudulent transaction
					 * attempt. It's a good idea to have a developer or sys admin
					 * manually investigate any invalid IPN.
					 */
					self::ipn_debug( 'ERROR: Invalid IPN. Shutting Down Processing.' );
					self::ipn_exit();
				}
			} catch ( Exception $e ) {
				self::ipn_debug( 'IPN Post method error: <pre>' . print_r( $e->getMessage(), true ) . '</pre>' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
				self::ipn_debug( 'Found Exception. Ending Script.' );
				self::ipn_exit();
			}
		}

		public static function ipn_validate_post_data() {
			if ( ! isset( self::$ipn_transaction_data['payment_status'] ) ) {
				self::ipn_debug( "ERROR: Missing 'payment_status' in IPN data" );
				self::ipn_exit();
			}

			if ( 'completed' !== strtolower( self::$ipn_transaction_data['payment_status'] ) ) {
				self::ipn_debug( "ERROR: 'payment_status' not 'completed' in IPN data" );
				self::ipn_exit();
			}
			self::ipn_debug( "Valid IPN 'payment_status': " . self::$ipn_transaction_data['payment_status'] );

			if ( ! isset( self::$ipn_transaction_data['payment_type'] ) ) {
				self::ipn_debug( "ERROR: Missing transaction 'payment_type' in IPN data" );
				self::ipn_exit();
			}

			if ( 'instant' !== self::$ipn_transaction_data['payment_type'] ) {
				self::ipn_debug( "ERROR: Invalid 'payment_type' in IPN data. [" . self::$ipn_transaction_data['payment_type'] . ']' );
				self::ipn_exit();
			}
			self::ipn_debug( "Valid IPN 'payment_type' : " . self::$ipn_transaction_data['payment_type'] );

			if ( ( ! isset( self::$ipn_transaction_data['mc_gross'] ) ) || ( empty( self::$ipn_transaction_data['mc_gross'] ) ) ) {
				self::ipn_debug( "ERROR: Missing or empty 'mc_gross' in IPN data." );
				self::ipn_exit();
			}
			self::ipn_debug( "Valid IPN 'mc_gross' : " . self::$ipn_transaction_data['mc_gross'] );

			if ( ( ! isset( self::$ipn_transaction_data['item_number'] ) ) || ( empty( self::$ipn_transaction_data['item_number'] ) ) ) {
				self::ipn_debug( "ERROR: Invalid or missing 'item_number' in IPN data" );
				self::ipn_exit();
			}

			if ( ! isset( self::$ipn_transaction_data['payer_email'] ) ) {
				self::ipn_debug( "ERROR: Missing transaction 'payer_email' in IPN data" );
				self::ipn_exit();
			}

			self::$ipn_transaction_data['payer_email'] = sanitize_email( self::$ipn_transaction_data['payer_email'] );
			self::$ipn_transaction_data['payer_email'] = strtolower( self::$ipn_transaction_data['payer_email'] );

			if ( ! is_email( self::$ipn_transaction_data['payer_email'] ) ) {
				self::ipn_debug( "ERROR: Invalid 'payer_email' in IPN data. [" . self::$ipn_transaction_data['payer_email'] . ']' );
				self::ipn_exit();
			}
			self::ipn_debug( "Valid IPN 'payer_email' : " . self::$ipn_transaction_data['payer_email'] );

			if ( isset( self::$ipn_transaction_data['first_name'] ) ) {
				self::$ipn_transaction_data['first_name'] = esc_attr( self::$ipn_transaction_data['first_name'] );
			} else {
				self::$ipn_transaction_data['first_name'] = '';
			}

			if ( isset( self::$ipn_transaction_data['last_name'] ) ) {
				self::$ipn_transaction_data['last_name'] = esc_attr( self::$ipn_transaction_data['last_name'] );
			} else {
				self::$ipn_transaction_data['last_name'] = '';
			}

			$valid_ipn_email = false;

			if ( isset( self::$ipn_transaction_data['receiver_email'] ) ) {
				self::$ipn_transaction_data['receiver_email'] = sanitize_email( self::$ipn_transaction_data['receiver_email'] );
				self::$ipn_transaction_data['receiver_email'] = strtolower( self::$ipn_transaction_data['receiver_email'] );

				if ( self::$ipn_transaction_data['receiver_email'] === self::$ld_paypal_settings['paypal_email'] ) {
					$valid_ipn_email = true;
				}
				self::ipn_debug( 'Receiver Email: ' . self::$ipn_transaction_data['receiver_email'] . ' Valid Receiver Email? :' . ( true === $valid_ipn_email ? 'YES' : 'NO' ) );
			}

			if ( isset( self::$ipn_transaction_data['business'] ) ) {
				self::$ipn_transaction_data['business'] = sanitize_email( self::$ipn_transaction_data['business'] );
				self::$ipn_transaction_data['business'] = strtolower( self::$ipn_transaction_data['business'] );

				if ( self::$ipn_transaction_data['business'] === self::$ld_paypal_settings['paypal_email'] ) {
					$valid_ipn_email = true;
				}
				self::ipn_debug( 'Business Email: ' . self::$ipn_transaction_data['business'] . ' Valid Business Email? :' . ( true === $valid_ipn_email ? 'YES' : 'NO' ) );
			}

			if ( true !== $valid_ipn_email ) {
				self::ipn_debug( 'Error: IPN with invalid receiver/business email!' );
				self::ipn_exit();
			}
		}

		public static function ipn_process_post_data() {
			self::$ipn_transaction_data['post_id'] = 0;

			self::$ipn_transaction_data['post_id']   = absint( self::$ipn_transaction_data['item_number'] );
			self::$ipn_transaction_data['course_id'] = learndash_get_course_id( self::$ipn_transaction_data['post_id'] );

			self::ipn_debug( 'Purchased Extend Access [' . self::$ipn_transaction_data['post_id'] . ']' );

			if ( empty( self::$ipn_transaction_data['course_id'] ) ) {
				self::ipn_debug( "ERROR: Invalid 'post_id' in IPN data. Unable to determine related LearnDash course." );
				self::ipn_exit();
			}

			$ld_extend_days  = intval( Ld_Extend_Expiry_Settings::get_setting_value( self::$ipn_transaction_data['course_id'], 'ld_extend_expiry_days', Ld_Extend_Expiry_Settings::DEFAULT_EXTEND_EXPIRY_DAYS ) );
			$ld_extend_price = Ld_Extend_Expiry_Settings::get_setting_value( self::$ipn_transaction_data['course_id'], 'ld_extend_expiry_price', 0 );
			if ( 0 === $ld_extend_days || $ld_extend_price <= 0 ) {
				self::ipn_debug( 'ERROR: Extend Days or Extend Price missing on server. Aborting' );
				self::ipn_exit();
			}
			self::$ipn_transaction_data['ld_extend_days'] = intval( $ld_extend_days );

			$server_extend_price = preg_replace( '/[^0-9.]/', '', $ld_extend_price );
			$server_extend_price = number_format( floatval( $server_extend_price ), 2, '.', '' );
			self::ipn_debug( 'Extend Price [' . $server_extend_price . ']' );

			$ipn_extend_price = preg_replace( '/[^0-9.]/', '', self::$ipn_transaction_data['mc_gross'] );
			$ipn_extend_price = floatval( $ipn_extend_price );
			self::ipn_debug( 'IPN GrossTax [' . $ipn_extend_price . ']' );

			if ( isset( self::$ipn_transaction_data['tax'] ) ) {
				$ipn_tax_price = preg_replace( '/[^0-9.]/', '', self::$ipn_transaction_data['tax'] );
			} else {
				$ipn_tax_price = 0;
			}
			$ipn_tax_price = floatval( $ipn_tax_price );
			self::ipn_debug( 'IPN Tax [' . $ipn_tax_price . ']' );

			$ipn_extend_price = $ipn_extend_price - $ipn_tax_price;
			$ipn_extend_price = number_format( floatval( $ipn_extend_price ), 2, '.', '' );
			self::ipn_debug( 'IPN Gross - Tax (result) [' . $ipn_extend_price . ']' );

			if ( floatval( $server_extend_price ) === floatval( $ipn_extend_price ) ) {
				self::ipn_debug( 'IPN Price match: IPN Price [' . $ipn_extend_price . '] Server Price [' . $server_extend_price . ']' );
			} else {
				self::ipn_debug( 'Error: IPN Price mismatch: IPN Price [' . $ipn_extend_price . '] Server Price [' . $server_extend_price . ']' );
				self::ipn_exit();
			}
		}

		public static function ipn_process_user_data() {
			if ( ! empty( self::$ipn_transaction_data['custom'] ) ) {
				$custom_data = explode( ';', self::$ipn_transaction_data['custom'] );
				if ( 2 === count( $custom_data ) ) {
					$user_id        = $custom_data[0];
					$ld_extend_days = $custom_data[1];
				} else {
					self::ipn_debug( 'Error: Missing custom data in IPN data: ' . self::$ipn_transaction_data['custom'] );
					self::ipn_exit();
				}
			} else {
				self::ipn_debug( 'Error: Missing custom field in IPN data.' );
				self::ipn_exit();
			}

			// get user
			$user = get_user_by( 'id', absint( $user_id ) );
			if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) ) {
				self::ipn_debug( "Valid 'custom user' in IPN data: [" . absint( $user_id ) . ']. Matched to User ID [' . $user->ID . ']' );
				self::$ipn_transaction_data['user_id'] = $user->ID;
			} else {
				self::ipn_debug( "Error: Unknown User ID 'custom' in IPN data: " . absint( $user_id ) );
				self::ipn_exit();
			}

			// validate extend days
			if ( intval( $ld_extend_days ) === self::$ipn_transaction_data['ld_extend_days'] ) {
				self::ipn_debug( 'IPN Extend Days match: IPN Days [' . self::$ipn_transaction_data['ld_extend_days'] . '] Server Days [' . $ld_extend_days . ']' );
			} else {
				self::ipn_debug( 'Error: IPN Extend Days mismatch: IPN Days [' . self::$ipn_transaction_data['ld_extend_days'] . '] Server Days [' . $ld_extend_days . ']' );
				self::ipn_exit();
			}

			self::ipn_extend_access();
		}

		/**
		 * Logs the message to the IPN Processing log.
		 *
		 * @param string $msg The message.
		 * @since 3.2.2
		 */
		public static function ipn_debug( $msg = '' ) {
			if ( ! empty( $msg ) ) {
				if ( '---' === $msg ) {
					$dattime = '';
					$msg     = "\r\n" . $msg;
				} else {
					$dattime = learndash_adjust_date_time_display( time(), 'Y-m-d H:i:s' );
				}
				self::$ipn_transaction_log .= $dattime . ' ' . $msg . "\r\n";
			}
		}

		public static function ipn_exit() {

			if ( ! empty( self::$ipn_transaction_log ) ) {
				$transaction_post_id = self::ipn_init_transaction();
				if ( ! empty( $transaction_post_id ) ) {
					/**
					 * Filters if we save the PayPal processing log to transaction.
					 *
					 * @since 3.3.0
					 *
					 * @param boolean $save_log True to save processing log.
					 */
					if ( apply_filters( 'learndash_paypal_save_processing_log', true ) ) {
						update_post_meta( $transaction_post_id, 'processing_log', self::$ipn_transaction_log );
					}
				}
			}
			exit();
		}

		public static function ipn_init_transaction() {
			if ( empty( self::$ipn_transaction_post_id ) ) {
				self::$ipn_transaction_post_id = wp_insert_post(
					array(
						'post_title'  => 'LearnDash LMS - Extend Expiry PayPal IPN Transaction',
						'post_type'   => 'sfwd-transactions',
						'post_status' => 'draft',
						'post_author' => 0,
					)
				);

				self::ipn_debug( 'Starting Transaction. Post Id: ' . self::$ipn_transaction_post_id );
			}

			return self::$ipn_transaction_post_id;
		}

		public static function ipn_complete_transaction() {

			$transaction_post_id = self::ipn_init_transaction();

			if ( ! empty( $transaction_post_id ) ) {
				self::ipn_debug( 'Completing Transaction: Post Id: ' . $transaction_post_id );

				$post_id = wp_insert_post(
					array(
						'ID'          => $transaction_post_id,
						'post_title'  => self::ipn_get_transaction_title(),
						'post_type'   => 'sfwd-transactions',
						'post_status' => 'publish',
						'post_author' => self::$ipn_transaction_data['user_id'],
					)
				);

				unset( self::$ipn_transaction_data['post'] );
				foreach ( self::$ipn_transaction_data as $k => $v ) {
					update_post_meta( $post_id, $k, $v );
				}
			}
		}

		public static function ipn_get_transaction_title() {
			$transaction_post_title = '';
			if ( ! empty( self::$ipn_transaction_data['post_id'] ) ) {
				$post_title             = get_the_title( self::$ipn_transaction_data['post_id'] );
				$transaction_post_title = __( 'Add more days', 'learndash-extend-expiry' ) . ' - ' . $post_title;
			}

			if ( empty( $transaction_post_title ) ) {
				$transaction_post_title = 'Unknown';
			}

			$transaction_post_title .= ' Purchased By ' . self::$ipn_transaction_data['payer_email'];

			return $transaction_post_title;
		}

		public static function ipn_extend_access() {
			if ( ( ! empty( self::$ipn_transaction_data['user_id'] ) ) && ( ! empty( self::$ipn_transaction_data['post_id'] ) ) ) {
				// call the function to extend the access.
				self::ipn_debug( 'Starting to extend access: User ID[' . absint( self::$ipn_transaction_data['user_id'] ) . '] Course[' . self::$ipn_transaction_data['course_id'] . '] Extend Days[' . self::$ipn_transaction_data['ld_extend_days'] . ']' );
				Ld_Extend_Expiry_Control::ld_update_extend_expiry_access( absint( self::$ipn_transaction_data['user_id'] ), self::$ipn_transaction_data['course_id'], self::$ipn_transaction_data['ld_extend_days'] );
			}
		}

		// End of functions.
	}
}
Ld_Extend_Expiry_Paypal_IPN::ipn_process();
