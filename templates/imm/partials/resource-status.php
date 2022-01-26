<?php
/**
 * Learndash Lessons Selling Imm template that displays a status box for paid LD resources
 * Available Variables:
 *
 * $resource_id   :   The current LD resource ID
 * $resource_type :   The current LD resource type
 * $user_id       :   The current user ID
 * $course_id     :   The current course ID
 * $price_type    :   The LD resource price type
 *
 * @since 1.0.0
 *
 * @package Learndash_Extend_Expiry/templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// check if user has access
$has_access = Imm_Learndash_Ls_Access_Control::ld_resource_selling_with_access( $resource_id, $user_id, $course_id );
if ( $has_access ) {
	return; // user already has access to the resource
}

$resource_pricing = Imm_Learndash_Settings_Helper::get_resource_price_details( $resource_id );
?>

<div id="imm-ls-resource-status-<?php echo esc_attr( $resource_id ); ?>" 
	style="<?php 'sfwd-lessons' === $resource_type ? '' : print( 'display: none' ); ?>"
  class="imm-ls-resource ld-course-status ld-course-status-not-enrolled">

		
		<div class="ld-course-status-segment ld-course-status-seg-status">

		  <span class="ld-course-status-label"><?php echo esc_html__( 'Current Status', 'learndash-extend-expiry' ); ?></span>
	  
			<div class="imm-ls-resource-status-content">
				<span class="ld-status ld-status-waiting ld-tertiary-background" data-ld-tooltip="
				<?php
					printf(
						// translators: placeholder: LearnDash module name
						esc_attr_x( 'Enroll in this %s to get access', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
						esc_html( Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $resource_id ) )
					);
					?>
				">
				<?php esc_html_e( 'Not Enrolled', 'learndash-extend-expiry' ); ?></span>
			</div>
	  
		</div> <!--/.ld-course-status-segment-->

		
		<div class="ld-course-status-segment ld-course-status-seg-price">

		<span class="ld-course-status-label"><?php echo esc_html__( 'Price', 'learndash-extend-expiry' ); ?></span>

	  <div class="imm-ls-resource-status-content">

		<span class="ld-course-status-price">
			  <?php
				if ( isset( $resource_pricing['price'] ) && ! empty( $resource_pricing['price'] ) ) :
						echo wp_kses_post( '<span class="ld-currency">' . learndash_30_get_currency_symbol() . '</span>' );
					  echo wp_kses_post( $resource_pricing['price'] );
				else :
					$label = 'closed' === $resource_pricing['type'] ? __( 'Closed', 'learndash-extend-expiry' ) : __( 'Free', 'learndash-extend-expiry' );
					echo esc_html( $label );
				endif;

				if ( isset( $resource_pricing['type'] ) && 'subscribe' === $resource_pricing['type'] ) :
					?>
				  <span class="ld-text ld-recurring-duration">
					  <?php
						echo sprintf(
						// translators: Recurring duration message.
							esc_html_x( 'Every %1$s %2$s', 'Recurring duration message', 'learndash-extend-expiry' ),
							esc_html( $resource_pricing['interval'] ),
							esc_html( $resource_pricing['frequency_label'] )
						);
						?>
				  </span>
				<?php endif; ?>
	  </span>

	  </div>

		</div> <!--/.ld-course-status-segment-->


	<?php
		$course_status_class = 'ld-course-status-segment ld-course-status-seg-action status-' .
			( isset( $resource_pricing['type'] ) ? sanitize_title( $resource_pricing['type'] ) : '' );
	?>
	
		<div class="<?php echo esc_attr( $course_status_class ); ?>">

			<span class="ld-course-status-label"><?php echo esc_html_e( 'Get Started', 'learndash-extend-expiry' ); ?></span>

			<div class="imm-ls-resource-status-content">
				<div class="ld-course-status-action">
				<?php
					$login_model = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_mode_enabled' );
					$login_url   = 'yes' === $login_model ? '#login' : wp_login_url( get_permalink() );

				switch ( $resource_pricing['type'] ) {
					case ( 'paynow' ):
					case ( 'subscribe' ):
						$ld_payment_buttons = Imm_Learndash_Ls_Payment_Integration::imm_ls_payment_buttons( $resource_id, $resource_pricing );
						echo $ld_payment_buttons;

						if ( ! is_user_logged_in() ) :
							echo '<span class="ld-text">';
							if ( ! empty( $ld_payment_buttons ) ) {
								esc_html_e( 'or', 'learndash-extend-expiry' );
							}
							echo '<a class="ld-login-text" href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login', 'learndash-extend-expiry' ) . '</a></span>';
						endif;
						break;
					case ( 'closed' ):
						$button = Imm_Learndash_Ls_Payment_Integration::imm_ls_payment_buttons( $resource_id, $resource_pricing );
						if ( empty( $button ) ) :
							echo '<span class="ld-text">' . sprintf(
							// translators: placeholder: LearnDash module name
								esc_html_x( 'This %s is currently closed', 'placeholder: LearnDash module name', 'learndash-extend-expiry' ),
								esc_html( Imm_Learndash_Ls_Access_Control::get_label_for_ld_resource( $resource_id ) )
							)
							. '</span>';
						else :
							echo $button;
						endif;
						break;
				}
				?>
			  </div>
			</div>
	  
		</div> <!--/.ld-course-status-action-->

</div>

<?php if ( 'sfwd-lessons' !== $resource_type ) : ?>
<script>
  jQuery("#imm-ls-resource-status-<?php echo esc_attr( $resource_id ); ?>").appendTo("#ld-table-list-item-<?php echo esc_attr( $resource_id ); ?>");
 jQuery("#imm-ls-resource-status-<?php echo esc_attr( $resource_id ); ?>").show();
</script>
<?php endif; ?>
