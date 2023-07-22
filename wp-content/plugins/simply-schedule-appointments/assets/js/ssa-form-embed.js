;(function (ssaFormEmbed, undefined) {

	var appointmentId = null;
	// checks if the iframe is on the page.
	var updateFormField = function (e) {
		appointmentId = e.data.id;
		// gets the specific iframe that emit the message event
		var container = e.source.frameElement.parentNode;
		var input = container.querySelector('.ssa_appointment_form_field_appointment_id')
		
		if(!appointmentId) appointmentId='';
		
		input.setAttribute("value", appointmentId);
		input.dispatchEvent(new Event('change', { bubbles: true } ));

		// get fields with conditional logic dependent on this ssa field
		var connectedFields = JSON.parse(input.dataset.fields);
		// run conditional logic immdeiately - works for fields that are not waiting on merge tag
		// in other words, fields that are connected to ssa field value itself
		typeof gf_apply_rules === "function" &&
			gf_apply_rules(input.dataset.formId, connectedFields);

		// schedule conditional logic for fields outside of ssa - works for fields that are waiting on merge tags
		typeof gf_apply_rules === "function" &&
			gform.addAction(
				"gform_input_change",
				function (elem, formId, fieldId) {
					// defer to next tick using timeout
					setTimeout(() => {
						gf_apply_rules(input.dataset.formId, connectedFields);
					}, 0);
				},
				10,
				"ssa_conditional_tag"
			);
	};

	ssaFormEmbed.listen = function(e) {
		if (typeof e.data == 'object' && e.data.hasOwnProperty('ssaType')) {
			if (e.data.ssaType === 'appointment') {
				updateFormField(e);
			}
		}
	}

}(window.ssaFormEmbed = window.ssaFormEmbed || {}));

window.addEventListener('message', ssaFormEmbed.listen, false);