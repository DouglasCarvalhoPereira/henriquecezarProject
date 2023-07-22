<?php
// $atts are defined in class-shortcodes.php
// don't try to load this file directly, instead call ssa()->shortcodes->ssa_upcoming_appointments()

$settings              = ssa()->settings->get();
$upcoming_appointments = ssa()->appointment_model->query( $atts );
$date_format           = SSA_Utils::localize_default_date_strings( 'F j, Y g:i a' ) . ' (T)';
?>

<div class="ssa-upcoming-appointments">
	<ul class="ssa-upcoming-appointments">
		<?php if ( ! is_user_logged_in() ) : ?>
			<?php echo $atts['logged_out_message']; ?>
		<?php elseif ( empty( $upcoming_appointments ) ) : ?>
			<?php echo $atts['no_results_message']; ?>
		<?php else : ?>
			<?php
			foreach ( $upcoming_appointments as $upcoming_appointment ) :
				$appointment_obj = new SSA_Appointment_Object( $upcoming_appointment['id'] );
				$upcoming_appointment_type = new SSA_Appointment_Type_Object( $upcoming_appointment['appointment_type_id'] );

				$members_names = array();
				if ( $appointment_obj->get_staff_members() ){
					foreach (  $appointment_obj->get_staff_members() as $member){
						$members_names[] = $member->name;
					}
				}
				$label= $upcoming_appointment_type->data['label'];
				?>
				<li class="ssa-upcoming-appointment">
					<i aria-hidden="true" class="md-icon md-primary md-size-2x md-theme-<?php echo $label;?> material-icons"> person </i>
					<div class="md-list-text-container">
						<h4>
							<?php
							// output name of the client who booked the meeting.
							$user_name = $upcoming_appointment['customer_information']['Name'];
							echo ( ' ' . ucfirst( $user_name ) . ' ' );
							?>
						</h4>
						<!-- Appointment type -->
						<p>
							<?php
							if ( filter_var( $atts['appointment_type_displayed'], FILTER_VALIDATE_BOOLEAN ) ) {
								echo ' ' . $upcoming_appointment_type->get_title();
							}
							?>
						<!-- Appointment team members -->
							<?php
							if ( filter_var( $atts['team_members_displayed'], FILTER_VALIDATE_BOOLEAN ) && ! empty( $members_names ) ) {
								echo ' with ' . join( ', ', $members_names );
							}
							?>
						</p>
						<!-- Appointment Time -->
						<p>
							<?php
							$upcoming_appointment_datetime = ssa_datetime( $upcoming_appointment['start_date'] );
							if ( ! empty( $upcoming_appointment['customer_timezone'] ) ) {
								$customer_timezone_string = $upcoming_appointment['customer_timezone'];
							} else {
								$customer_timezone_string = 'UTC';
							}

							// display the localized time according to business timezone.
							$date_timezone    = new DateTimeZone( $settings['global']['timezone_string'] );
							$localized_string = $upcoming_appointment_datetime->setTimezone( $date_timezone )->format( $date_format );
							$localized_string = SSA_Utils::translate_formatted_date( $localized_string );

							echo $localized_string;
							?>

						</p>
						<!-- Appointment Links -->
						<p class="ssa-upcoming-appointment-links">
							<?php
							// link meeting details to the admin ssa panel.
							if ( ! empty( $upcoming_appointment['web_meeting_url'] ) && filter_var( $atts['web_meeting_url'], FILTER_VALIDATE_BOOLEAN ) ) {
								echo ' <a target="_blank" href="' . $upcoming_appointment['web_meeting_url'] . '">' . ' Open Web Meeting ' . '</a>';
							}

							if ( ! empty( $atts['details_link_displayed'] ) ) {
								echo '<a target="_blank" href=' . ssa()->wp_admin->url( '/ssa/appointment/' ) . $upcoming_appointment['id'] . '>' . $atts['details_link_label'] . '</a>';
							}
							?>

						</p>
					</div>
				</li>
				<?php endforeach; ?>
				<?php 
					if ( ! empty( $upcoming_appointments ) ) {
							echo '<a href=' . ssa()->wp_admin->url( '/ssa/appointments' ) . '><li class="ssa-upcoming-appointment"><div class="md-list-text-container">View all upcoming appointments</div></li></a>';
					}
				?>
		<?php endif; ?>
	</ul>
</div>
