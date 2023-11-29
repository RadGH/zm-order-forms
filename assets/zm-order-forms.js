(function() {

	// Set up an order form with hooks and validation
	const setup_order_form = function( $container ) {

		// Elements
		let $form = $container.find('form'); // form
		let $validation_errors = $container.find('.zm-validation-errors'); // div
		let $validation_error_list = $validation_errors.find('.error-list'); // ul
		let $category_list = $container.find('.zmof-category-list'); // div (contains multiple tables)

		// Fields
		let $name = $container.find('#zmof-name'); // input[type=text]
		let $email = $container.find('#zmof-email'); // input[type=email]
		let $location = $container.find('#zmof-location'); // select (empty if no locations in settings)
		let $submit = $container.find('.zmof-submit input[type="submit"]'); // input[type=submit]

		// Custom products
		let $custom_product_container = $container.find('.zmof-custom-products');
		let $custom_product_table = $custom_product_container.find('.zmof-category-table');
		let $custom_product_add_button = $custom_product_container.find('.add-product');

		// Functions
		const clear_validation_errors = function() {
			$validation_error_list.html('');
			$validation_errors.css('display', 'none');
		};

		const on_validation_error = function( validation_errors ) {
			// Clear previous errors
			clear_validation_errors();

			// Add each reason to the list
			for ( let i in validation_errors ) {
				let field_title = validation_errors[i].title;

				for ( let j in validation_errors[i].reasons ) {
					// Add each reason to the list
					$validation_error_list.append('<li><strong>'+ field_title +':</strong> '+ validation_errors[i].reasons[j] +'</li>');
				}
			}

			// Show the validation errors div
			$validation_errors.css('display', '');

			// Scroll to the validation errors div
			jQuery('html, body').animate({
				scrollTop: $validation_errors.offset().top - 100
			}, {
				duration: 300
			});
		};

		const get_invalid_reasons = function( data ) {
			let reasons = [];

			for ( var i in data.methods ) {
				switch( data.methods[i] ) {

					case 'required':
						if ( data.value === "" || data.value === null ) {
							reasons.push( 'This field is required.' );
						}
						break;

					case 'email':
						if ( ! data.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/) ) {
							reasons.push( 'Please enter a valid email address.' );
						}
						break;

					case 'product':
						// Check if at least one quantity is filled
						$filled_quantities = $category_list.add($custom_product_table).find('input.product-quantity').filter(function() { return jQuery(this).val() !== ''; });

						if ( $filled_quantities.length < 1 ) {
							reasons.push( 'At least one product is required.' );
							break;
						}

						// Check for filled quantities with black reference numbers
						// (Each product with a quantity must have a reference number)
						$filled_quantities.each(function() {
							let $table = jQuery(this).closest('.zmof-category-table');
							let $tr = jQuery(this).closest('tr');

							let category_title = $table.attr('data-category-title');
							if ( ! category_title ) category_title = 'Untitled Category';

							let product_title = $tr.find('input.product-title').val();
							if ( ! product_title ) product_title = $tr.find('td.col-title span').text();
							if ( ! product_title ) product_title = 'Untitled Product';

							let $reference_number_input = $tr.find('input.product-reference-number');

							if ( $reference_number_input.val() === '' ) {
								reasons.push( '['+ category_title +'] ' + product_title.trim() + ': Reference number is required.' );
							}
						});

						break;

				}
			}

			return reasons.length > 0 ? reasons : false;
		};

		const get_form_validation_errors = function() {

			// Validations to be tested
			let validations = [];

			// Add fields to be validated
			validations.push({
				value: $name.val(),
				title: 'Your Name',
				methods: [ 'required' ]
			});

			validations.push({
				value: $email.val(),
				title: 'Your Email',
				methods: [ 'required', 'email' ]
			});

			if ( $location.length > 0 ) validations.push({
				value: $location.val(),
				title: 'Location',
				methods: [ 'required' ]
			});

			// At least one quantity field must be filled out
			validations.push({
				value: null,
				title: 'Products',
				methods: [ 'product' ],
			});

			// Perform validation on each item
			let errors = [];

			for ( var i in validations ) {
				let invalid_reasons = get_invalid_reasons( validations[i] );

				if ( invalid_reasons ) {
					errors.push({
						'title': validations[i].title,
						'reasons': invalid_reasons // array of strings that explain why the field is invalid
					});
				}
			}

			if ( errors.length < 1 ) {
				return false;
			}else{
				return errors;
			}
		};

		const validate_form = function() {
			let validation_errors = get_form_validation_errors();

			if ( ! validation_errors ) {
				clear_validation_errors();
				return true;
			}else{
				on_validation_error( validation_errors );
				return false; // do not submit form
			}
		}

		const add_custom_product_row = function() {
			// Get the first row (index: 0) to use as a blueprint
			let $row = $custom_product_container.find('tbody > tr').first().clone();

			// Get the next row index
			let index = $custom_product_container.find('tbody > tr').length;

			// Clear the values
			$row.find('input').val('');

			// Function to replace an attribute with a search and replacement string
			let replace_attr = function( $el, attr, search, replacement ) {
				let attr_val = $el.attr(attr);
				$el.attr(attr, attr_val.replace(search, replacement));
			};

			// Replace the index in certain attributes including:
			// inputs: name, id
			// labels: for
			$row.find('input, label').each(function() {
				let $el = jQuery(this);
				let element_type = $el.prop('tagName').toLowerCase();

				if ( element_type === 'input' ) {
					replace_attr( $el, 'name', '0', index );
					replace_attr( $el, 'id', '0', index );
				}else if ( element_type === 'label' ) {
					replace_attr( $el, 'for', '0', index );
				}
			});

			// Append the new row
			$custom_product_table.find('tbody').append( $row );
		};

		$form.on('submit', function(e) {
			// Validate the form. If invalid, do not submit
			if ( ! validate_form() ) return false;
		});

		$submit.on('click keypress', function(e) {
			// If "keypress" event, check only the enter key
			if ( e.type === 'keypress' && e.which !== 13 ) return;

			// Validate the form. If invalid, do not submit
			if ( ! validate_form() ) return false;
		});

		$custom_product_add_button.on('click keypress', function(e) {
			// If "keypress" event, check only the enter key
			if ( e.type === 'keypress' && e.which !== 13 ) return;

			// Add a new custom product row
			add_custom_product_row();
			return false;
		});

		// Click on an abbreviation to see the full text in an alert
		$container.find('abbr').on('click keypress', function(e) {
			// If "keypress" event, check only the enter key
			if ( e.type === 'keypress' && e.which !== 13 ) return;

			alert( jQuery(this).attr('title') );
			return false;
		});

	};

	// Once the document is ready, set up each form
	jQuery(document).ready(function($) {

		jQuery('.zm-order-form').each(function() {
			setup_order_form( jQuery(this) );
		});

	});

})();