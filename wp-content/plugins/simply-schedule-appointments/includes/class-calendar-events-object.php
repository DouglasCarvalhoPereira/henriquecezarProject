<?php

/**
 * Simply Schedule Appointments Calendar Events.
 *
 * @since   4.7.2
 * @package Simply_Schedule_Appointments
 */

/**
 * Simply Schedule Appointments Calendar Events.
 *
 * @since 4.7.2
 */
class SSA_Calendar_Events_Object {
	/**
	 * The calendar event type.
	 *
	 * @since 0.0.3
	 */
	public $type = null;

	/**
	 * The calendar event type template.
	 *
	 * @since 0.0.3
	 */
	public $template = null;

	public function __construct( $type ){
		$this->type = $type;
		$this->get_template();
	}

	/**
	 * Get the template settings for the specified calendar event type.
	 * 
	 * @since 4.7.2
	 *
	 * @return void
	 */
	private function get_template() {
		$templates = ssa()->calendar_events_settings->get_calendar_event_types();

		if ( array_key_exists( $this->type, $templates ) ) {
			$this->template = $templates[ $this->type ];
		}
	}

	/**
	 * Generate the calendar event content for a given Appointment.
	 * 
	 * @since 4.7.2
	 *
	 * @param string $type the type of content is required (can be 'title', 'location' or 'details').
	 * @param int    $appointment_id the ID of the Appointment for which the code will generate the content.
	 * @param string $strip_tags whether to strip tags from the content.
	 * @return string parsed based on the Twig template and the Appointment variables.
	 */
	public function get_calendar_event_content( $type, $appointment_id, $strip_tags = false ) {
		if ( empty( $this->template ) || ! array_key_exists( $type, $this->template ) ) {
			return false;
		}
		$template_string = $this->template[ $type ];
		// prepare template string.
		$template_string = ssa()->notifications->prepare_notification_template( $template_string );

		if ( $strip_tags ) {
			$template_string = wp_strip_all_tags( $template_string );
		}

		// Deal with &nbsp, replacing it for the actual non-breaking line space entity.
		$template_string = str_replace( '&nbsp;', '&#160;', $template_string );
		// Decode html entities.
		$template_string = html_entity_decode( $template_string, ENT_QUOTES | ENT_XML1, 'UTF-8' );

		$template_vars = ssa()->templates->get_template_vars(
			null,
			array(
				'appointment_id' => $appointment_id,
			)
		);

		$rendered_template_string = ssa()->templates->render_template_string( $template_string, $template_vars );
		$rendered_template_string = html_entity_decode( $rendered_template_string, ENT_QUOTES | ENT_XML1, 'UTF-8' );

		return $rendered_template_string;
	}

}