<?php
// $atts are defined in class-shortcodes.php
// don't try to load this file directly, instead call ssa()->shortcodes->ssa_past_appointments()

$past_appointments = ssa()->appointment_model->query( $atts );

$settings    = ssa()->settings->get();
$date_format = SSA_Utils::localize_default_date_strings( 'F j, Y g:i a' ) . ' (T)';
?>

<div class="ssa-past-appointments">
	<ul class="ssa-past-appointments">
		<?php if ( ! is_user_logged_in() ) : ?>
			<?php echo $atts['logged_out_message']; ?>
		<?php elseif ( empty( $past_appointments ) ) : ?>
			<?php echo $atts['no_results_message']; ?>
		<?php else : ?>
			<?php foreach ( $past_appointments as $past_appointment ) : ?>
				<li>
					<span class="ssa-past-appointments-appointment">
						<span class="ssa-past-appointments-start-date">
							<?php
							$past_appointment_datetime = ssa_datetime( $past_appointment['start_date'] );
							if ( ! empty( $past_appointment['customer_timezone'] ) ) {
								$customer_timezone_string = $past_appointment['customer_timezone'];
							} else {
								$customer_timezone_string = 'UTC';
							}
							$customer_timezone = new DateTimezone( $customer_timezone_string );
							$localized_string  = $past_appointment_datetime->setTimezone( $customer_timezone )->format( $date_format );
							$localized_string  = SSA_Utils::translate_formatted_date( $localized_string );

							echo $localized_string;

							if ( filter_var( $atts['appointment_type_displayed'], FILTER_VALIDATE_BOOLEAN ) ) {
								$past_appointment_type = new SSA_Appointment_Type_Object( $past_appointment['appointment_type_id'] );
								echo ' ' . $past_appointment_type->get_title();
							}

							if ( ! empty( $past_appointment['web_meeting_url'] ) && filter_var( $atts['web_meeting_url'], FILTER_VALIDATE_BOOLEAN ) ) {
								echo ' <a target="_blank" href="' . $past_appointment['web_meeting_url'] . '">' . 'Open Web Meeting' . '</a>';
							}
							if ( ! empty( $atts['details_link_displayed'] ) ) {
								echo ' <a target="_blank" href="' . ssa()->appointment_model->get_public_edit_url( $past_appointment['id'] ) . '">' . $atts['details_link_label'] . '</a>';
							}
							?>
						</span>
					</span>
				</li>
			<?php endforeach; ?>
		<?php endif ?>
	</ul>
</div>
