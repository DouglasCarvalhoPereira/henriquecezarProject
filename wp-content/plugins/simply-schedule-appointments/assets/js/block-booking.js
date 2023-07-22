var el = wp.element.createElement,
	registerBlockType = wp.blocks.registerBlockType,
	ServerSideRender = wp.ServerSideRender,
	SelectControl = wp.components.SelectControl,
	CheckboxControl = wp.components.CheckboxControl,
	RangeControl = wp.components.RangeControl,
	InspectorControls = wp.blockEditor.InspectorControls,
	PanelColorSettings = wp.blockEditor.PanelColorSettings,
	PanelBody = wp.components.PanelBody;
	Button = wp.components.Button;

registerBlockType("ssa/booking", {
	title: "Appointment Booking Form",
	description:
		"Displays an Appointment Booking Form. You can customize the appointment type and styles.",
	icon: "calendar-alt",
	category: "widgets",

	edit: function (props) {

		// For exsiting/old shortcodes before introducing the checkboxes; run Conversion
		// Needed only for the selected type to be checked in UI
		if(props.attributes.type){
			let type = props.attributes.type;
			props.setAttributes({ types : [type] });
			props.setAttributes({ type : '' } );
		}

		var apptTypeOptions = 
			{
				All: 'All',
			};
		Object.keys(ssaAppointmentTypes).forEach(function (key) {
			apptTypeOptions[key] = ssaAppointmentTypes[key]
		});

		function onCheckChange(isChecked) {
			let element = this.valueOf()
			if(isChecked){
				if(!props.attributes.types.includes(element)){
					if(element==='All'){
						props.setAttributes({ types: Object.keys(apptTypeOptions) });
					} else {
						let selectedApptType = [...props.attributes.types]
						selectedApptType.push(element)
						props.setAttributes({ types: selectedApptType });
					}
				}
			} else {
				if(props.attributes.types.includes(element)){
					if(element==='All'){
						let selectedAppTypes = Object.keys(apptTypeOptions)
						index = selectedAppTypes.indexOf('All');
						selectedAppTypes.splice(index, 1);
						props.setAttributes({ types: selectedAppTypes });
					} else {
						let selectedApptType = [...props.attributes.types]
						var index = selectedApptType.indexOf(element);
						selectedApptType.splice(index, 1);
						props.setAttributes({ types: selectedApptType });
					}
				}
			}
		}

		var apptTypeCheckboxes = Object.keys(apptTypeOptions).filter((option) => {
			if(props.attributes.types.includes('All')){
				return option === 'All'
			}
			return true
		}).map(function(key) {
			// Render the checkboxes elements inside a parent container
			// Only render the uncheck all button besides the All checkbox
			return el(
							"div",
							{ className: "ssa-checkboxes-input-container" },
							el(CheckboxControl, {
								onChange: onCheckChange.bind(key),
								label: apptTypeOptions[key],
								checked: props.attributes.types.includes(key)
							}),
							(key === 'All' && !props.attributes.types.includes('All') && props.attributes.types.length ) ?
							el(Button, {
								isSecondary: true,
								className: "ssa-block-booking-uncheck-all",
								onClick: function () {
										props.setAttributes({
												types: [],
										});
								}
							}, 'Uncheck All') : 
							null,
						)
			
		})

		var LabelsOptions = [
			{
				value: "All",
				label: "All",
			},
		];
		Object.keys(ssaAppointmentTypeLabels).forEach(function (key) {
			LabelsOptions.push({
				value: key,
				label: ssaAppointmentTypeLabels[key],
			});
		});
		return [
			el(
				"div",
				{ className: "ssa-block-container" },
				el(ServerSideRender, {
					block: "ssa/booking",
					attributes: props.attributes,
				}),
				el("div", {
					className: "ssa-block-handler",
				})
			),
			el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: "Select Appointment types", initialOpen: true },
					el(SelectControl, {
						label: 'Filter by',
						value: props.attributes.filter,
						options: [
							{
								value: "types",
								label: "Appointment types",
							},
							{
								value: "label",
								label: "Label",
							},
						],
						onChange: function (value) {
							props.setAttributes({ filter: value });
						},
					}),
					props.attributes.filter === 'label' ?
					el(SelectControl, {
						label: "Labels",
						value: props.attributes.label,
						options: LabelsOptions,
						onChange: function (value) {
							props.setAttributes({ label: value });
						},
					}) :
					el('div', null, apptTypeCheckboxes)
				),
				el(PanelColorSettings, {
					title: "Colors",
					colorSettings: [
						{
							value: props.attributes.accent_color,
							label: "Accent Color",
							onChange: function (value) {
								props.setAttributes({
									accent_color: value,
								});
							},
						},
						{
							value: props.attributes.background,
							label: "Background Color",
							onChange: function (value) {
								props.setAttributes({
									background: value,
								});
							},
						},
					],
				}),
				el(
					PanelBody,
					{ title: "Padding", initialOpen: true },
					el(RangeControl, {
						initialPosition: 0,
						value: props.attributes.padding,
						onChange: function (value) {
							props.setAttributes({
								padding: value,
							});
						},
						min: 0,
						max: 100,
					}),
					el(SelectControl, {
						label: "Padding Unit",
						value: props.attributes.padding_unit,
						options: [
							{
								value: "px",
								label: "px",
							},
							{
								value: "em",
								label: "em",
							},
							{
								value: "rem",
								label: "rem",
							},
							{
								value: "vw",
								label: "vw",
							},
							{
								value: "percent",
								label: "%",
							},
						],
						onChange: function (value) {
							props.setAttributes({ padding_unit: value });
						},
					})
				)
			),
		];
	},

	save: function () {
		return null;
	},
});
