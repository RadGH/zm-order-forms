(function() {

	const o = this;

	o.on_init = function() {

		// Only run on_init() once:
		o.on_init = function() {};

	};

	o.on_ready = function() {

		// Only run on_ready() once:
		o.on_ready = function() {};

		// Get the order form field from the settings page
		let $order_forms_field = jQuery('.acf-field[data-key="field_6553f2c521415"]');

		if ( $order_forms_field.length === 1 ) {
			// Generate form IDs automatically on the form settings page
			o.generate_form_ids( $order_forms_field );

			// Update the shortcode whenever a form ID changes
			o.update_shortcodes( $order_forms_field );
		}

	};

	// On the form settings screen, automatically generate a form ID each time a new form is added
	o.generate_form_ids = function( $order_forms_field ) {

		// ACF must be loaded
		if ( typeof acf === 'undefined' ) return;

		// Convert a string to a slug: "Hello World" -> "hello-world"
		const convert_to_slug = function( text ) {
			return text.toLowerCase().replace(/ /g,'-').replace(/[^\w-]+/g,'');
		};

		// Generate a form ID based on the title whenever the form title changes
		// This only applies to newly created forms (existing forms do not trigger the "append" action)
		const on_title_change = function( $title_input, $id_input ) {
			let title = $title_input.val();
			let id = convert_to_slug( title );
			$id_input.val( id ).trigger('change');
		};

		// Use the ACF hook action 'append' which is called when a new repeater row is added
		acf.addAction('append', function( $row ) {

			// Get the field containing the appended row
			let $field = $row.closest('.acf-field');

			// Check if the field is the Order Forms repeater field
			if ( $field.attr('data-key') !== 'field_6553f2c521415' ) return;

			// Get the form title input
			let $title_input = $row.find('.acf-field[data-key="field_6553f8d3ac610"] > .acf-input input[type="text"]');

			// Get the form id input
			let $id_input = $row.find('.acf-field[data-key="field_6553fd2c43771"] > .acf-input input[type="text"]');

			// When the form title changes, update the form ID to match, but convert to a slug
			$title_input.on('change keyup', function() {
				on_title_change( $title_input, $id_input );
			});

		});

	};

	// Update the shortcode whenever a form ID changes
	o.update_shortcodes = function( $order_forms_field ) {

		let id_selector = '.acf-field[data-key="field_6553fd2c43771"] > .acf-input input[type="text"]';

		// When any form ID changes, update the shortcode
		$order_forms_field.on( 'change keyup update_shortcode', id_selector, function() {

			// Get the elements
			let $id_input = jQuery(this);
			let $id_field = $id_input.closest('.acf-field');
			let $shortcode = $id_field.find('.zmof-form-shortcode');

			if ( $id_field.length > 0 && $shortcode.length > 0 ) {
				$shortcode.text('[zm_order_form id="' + $id_input.val() + '"]');
			}

		});

		// Update all shortcodes on first load
		$order_forms_field.find( id_selector ).trigger('update_shortcode');

	};

	// When the document is ready, run the on_ready function
	jQuery(document).on('ready', o.on_ready);

	// Initialize as soon as this script is ready
	o.on_init();

})();