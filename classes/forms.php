<?php

class ZMOF_Forms {
	
	private $raw_form_data = null;
	private $order_submitted = null;
	private $validated_form_data = null;
	private $sending_email = false;
	private $validation_errors = array();
	
	public function __construct() {
		
		// Process form submission (step 1)
		add_action( 'template_redirect', array( $this, 'process_submitted_order' ), 20 );
		
		// Process form confirmation (step 2)
		add_action( 'template_redirect', array( $this, 'process_finalized_order' ), 20 );
		
		// Fill the Order Form dropdown in the block field group with order forms from the settings page
		add_filter( 'acf/load_field/key=field_65540964f03de', array( $this, 'acf_load_order_forms' ) );
		
	}
	
	// Simple getters to make private methods accessible outside of this class
	public function is_order_submitted() { return $this->order_submitted === true; }
	public function is_order_valid() { return $this->is_order_submitted() && empty($this->validation_errors); }
	public function is_sending_email() { return $this->sending_email; }
	
	public function get_validated_form_data() { return $this->validated_form_data; }
	public function get_validation_errors() { return $this->validation_errors; }
	
	/**
	 * Check if an order has been finalized, which is based on a URL arg added after the form is submitted and finalized.
	 *
	 * @return bool
	 */
	public function is_order_finalized() {
		return isset($_GET['zmof-sent']) && $_GET['zmof-sent'] === '1';
	}
	
	/**
	 * Processes a form submission. If successful, goes to confirmation screen.
	 *
	 * @return void
	 */
	public function process_submitted_order() {
		$data = $this->get_post_data();
		
		// Check if the form was submitted
		$this->order_submitted = $data && $data['action'] === 'submit-order';
		if ( ! $this->order_submitted ) return;
		
		// Store the form data
		$this->validated_form_data = $data;
		
		// Do a basic validation of the data
		$errors = array();
		
		// Form ID must be provided
		if ( empty($data['form_id']) ) $errors[] = '<strong>Form ID</strong>: The order form ID could not be found.';
		
		// Required fields: name, email, location
		if ( empty($data['name']) ) $errors[] = '<strong>Name</strong>: This field is required.';
		if ( empty($data['email']) ) $errors[] = '<strong>Email</strong>: This field is required.';
		if ( empty($data['location']) ) $errors[] = '<strong>Location</strong>: This field is required.';
		
		// Email must be valid
		if ( ! empty($data['email']) && ! is_email($data['email']) ) $errors[] = '<strong>Email</strong>: Please enter a valid email address.';
		
		// Require at least one product or custom product
		if ( empty($data['products']) && empty($data['custom_products']) ) $errors[] = '<strong>Products:</strong> At least one product is required';
		
		// Order notes is optional, but has a max length of 2500 characters
		if ( ! empty($data['order_notes']) && mb_strlen($data['order_notes']) > 2500 ) {
			$errors[] = '<strong>Order Notes</strong> cannot exceed 2500 characters (Currently '. mb_strlen($data['order_notes']) .').';
		}
		
		// Check if validation failed
		if ( !empty( $errors ) ) {
			
			// Store the errors to later be displayed in the block or shortcode
			$this->validation_errors = $errors;
			
		}
		
	}
	
	/**
	 * Finalize a previously submitted and then confirmed order form, finally sending an email.
	 *
	 * @return void
	 */
	public function process_finalized_order() {
		$data = isset($_POST['zmof']) ? stripslashes_deep($_POST['zmof']) : false;
		
		// Check if the finalize order form was submitted
		$action = $data['action'] ?? false;
		if ( $action !== 'finalize-order' ) return;
		
		$submit = $data['submit'] ?? false;
		$form_data_str = $data['form_data'] ?? false;
		if ( ! $submit || ! $form_data_str ) wp_die('Error: A required field was missing when finalizing the order');
		
		$form_data = json_decode( $form_data_str, true );
		if ( ! $form_data ) wp_die('Error: The form data could not be decoded.');
		
		// Get the form that was submitted and finalized
		$form = $this->get_order_form_by_id( $form_data['form_id'] );
		if ( ! $form ) wp_die( 'Error: The form could not be found or is invalid.' );
		
		// If the back button was submitted, show the order form again
		if ( $submit === 'cancel' ) {
			$this->order_submitted = false;
			$this->raw_form_data = $form_data;
			return;
		}
		
		// Send the email(s)
		$sent = $this->send_order_email( $form_data, $form );
		
		if ( ! $sent ) {
			
			// If the email could not be sent, show the original form again with an error message.
			$this->order_submitted = false;
			$this->raw_form_data = $form_data;
			$this->validation_errors[] = 'Error: The order form email could not be sent. Please try again.';
			
		}else{
		
			// When successful, redirect indicating that the form was submitted in the URL.
			// This redirect will prevent the refresh button from sending the email again.
			$url = add_query_arg(array('zmof-sent' => '1'));
			wp_redirect( $url );
			exit;
			
		}
		
	}
	
	/**
	 * Get the order form HTML to be displayed
	 *
	 * @param string  $form_id
	 * @param string  $custom_classes
	 * @param bool    $show_title
	 *
	 * @return string
	 */
	public function get_order_form_html( $form_id, $custom_classes, $show_title ) {
		
		// Classes for the shortcode
		$classes = array();
		$classes[] = 'form-id-' . $form_id;
		if ( $custom_classes ) $classes[] = $custom_classes;
		
		// Determine what content should be displayed
		do {
			
			// 1. Check if the form ID is valid
			$form = ZMOF_Forms()->get_order_form_by_id( $form_id );
			if ( ! $form ) {
				$classes[] = 'form-error';
				$html = __( 'Order form not found: "%s"', 'zm-order-forms' );
				$html = sprintf( $html, $form_id );
				$html = wpautop($html);
				break;
			}
			
			// 2. Check if the user is logged in
			if ( ! is_user_logged_in() ) {
				$classes[] = 'login-error';
				$args = array(
					'echo' => false,
					'redirect' => get_permalink(),
					'form_id' => 'zmof-login-form',
				);
				
				$html = wpautop( __( '<h3>Sign in to view the order form:</h3>', 'zm-order-forms' ) );
				$html .= wp_login_form( $args );
				$html = apply_filters( 'zm_order_form/login_html', $html );
				break;
			}
			
			// 3. Check if the user can create an order
			if ( ! ZMOF_Forms()->can_user_create_order() ) {
				$classes[] = 'permission-error';
				$html = __( 'You do not have permission to create an order', 'zm-order-forms' );
				$html = wpautop( $html );
				break;
			}
			
			// 4. Check if a submitted order was finalized.
			if ( ZMOF_Forms()->is_order_finalized() ) {
				$classes[] = 'order-submitted';
				$html = __( 'Your order has been submitted successfully', 'zm-order-forms' );
				break;
			}
			
			// 5. Check if the order form was submitted, then review the order.
			if ( ZMOF_Forms()->is_order_submitted() && ZMOF_Forms()->is_order_valid() ) {
				
				$classes[] = 'review-order';
				
				$data = ZMOF_Forms()->get_validated_form_data();
				if ( ! $data ) wp_die('Order form submission is not available or could not be validated.');
				
				$form = ZMOF_Forms()->get_order_form_by_id( $data['form_id'] );
				if ( ! $form ) wp_die('Could not locate the order form that was submitted.');
				
				ob_start();
				
				echo '<h3 class="zm-review-order-title">Please review your order, and press Submit:</h3>';
				
				// Order summary table
				include( ZMOF_PATH . '/templates/order-summary.php' );
				
				// Confirm order form, with button to go back
				include( ZMOF_PATH . '/templates/confirm-order-form.php' );

				$html = ob_get_clean();
				
				break;
			}
			
			// 6. Otherwise, display the order form
			$classes[] = 'create-order';
			
			ob_start();
			include( ZMOF_PATH . '/templates/order-form.php' );
			$html = ob_get_clean();
			
		} while(false);
		
		return '<div class="zm-order-form '. esc_attr(implode(' ', $classes)) .'">' . $html . '</div>';
	}
	
	/**
	 * Send an email with the order details to the recipients listed in the form settings
	 *
	 * @param array $data  The submitted form field data
	 * @param array $form  The form settings
	 *
	 * @return bool
	 */
	public function send_order_email( $data, $form ) {
		
		// Set a flag to indicate we are generating an email
		$this->sending_email = true;
		
		// Get fields to use in the email:
		$form_title = $form['form_title'];
		$name = $data['name'];
		$email = $data['email'];
		$location = $data['location'];
		
		// Get site info used in footer
		$site_url = get_site_url();
		$site_title = get_bloginfo('name');
		
		// Get the order summary table
		ob_start();
		include( ZMOF_PATH . '/templates/order-summary.php' );
		$summary_table = ob_get_clean();
		
		// Prepare email fields:
		$subject = 'New "' . $form['form_title'] . '" order from ' . $name;
		
		$to = $form['send_to'];
		
		// If set, also email a copy to the user who submitted the form
		if ( $form['email_user'] ) {
			$to .= ', '. $email;
		}
		
		$headers = array(
			'Reply-To: '. $name .' <'. $email .'>',
			'Content-Type: text/html; charset=UTF-8',
		);
		
		$body = '<p>A new order form has been submitted:</p>';
		$body .= $summary_table;
		$body .= '<p><em>This email was sent by the Order Forms plugin on <a href="'. esc_attr($site_url) .'" target="_blank">'. esc_html($site_title) .'</a>.</em></p>';
		
		// Clear the email flag
		$this->sending_email = false;
		
		return wp_mail( $to, $subject, $body, $headers );
	}
	
	/**
	 * Display the Order Form dropdown in the block field group with order forms from the settings page
	 *
	 * @param array $field
	 *
	 * @return array
	 */
	public function acf_load_order_forms( $field ) {
		// Ignore the Edit Field Group screen
		if ( acf_is_screen( 'acf-field-group' ) ) return $field;
		
		// Get the order forms
		$order_forms = $this->get_order_forms();
		
		// Add the order forms to the field choices
		if ( ! empty( $order_forms ) ) {
			foreach ( $order_forms as $f ) {
				$field['choices'][ $f['form_id'] ] = $f['form_title'];
			}
		}
		
		return $field;
	}
	
	/**
	 * Get a list of raw form data from the settings page.
	 *
	 * @return array $form_data {
	 *     @type string $title
	 *     @type string $id
	 *     @type string $to
	 *     @type array $categories {
	 *         @type string $title
	 *         @type string $products
	 *     }
	 * }
	 */
	public function get_raw_order_form_data() {
		$forms = (array) get_field( 'order_forms', 'options' );
		return $forms ?: array();
	}
	
	/**
	 * Get a structured list of all order forms.
	 *
	 * @return array|false {
	 *     @type string   $form_title
	 *     @type string   $form_id
	 *     @type string   $send_to
	 *     @type string[] $locations
	 *     @type array    $categories {
	 *         @type string    $category_title
	 *         @type string[]  $products
	 *     }
	 * }
	 */
	public function get_order_forms() {
		$forms = array();
		
		$raw_forms = $this->get_raw_order_form_data();
		
		// Prepare each order form
		if ( $raw_forms ) foreach( $raw_forms as $f ) {
			$form = $this->prepare_order_form_data( $f );
			if ( $form ) {
				$forms[] = $form;
			}
		}
		
		return $forms;
	}
	
	/**
	 * Get a single structured order form by its form ID.
	 *
	 * @param string $id
	 *
	 * @return array|false {
	 *     @type string   $form_title
	 *     @type string   $form_id
	 *     @type string   $send_to
	 *     @type string[] $locations
	 *     @type array    $categories {
	 *         @type string    $category_title
	 *         @type string[]  $products
	 *     }
	 * }
	 */
	public function get_order_form_by_id( $id ) {
		$raw_forms = $this->get_raw_order_form_data();
		
		// Find the matching order form by ID, then return the prepared form data
		foreach ( $raw_forms as $f ) {
			if ( $f['id'] === $id ) {
				return $this->prepare_order_form_data( $f );
			}
		}
		
		return false;
	}
	
	/**
	 * Structures an order form's data, and expands the products into an array.
	 *
	 * @param array $form_data {
	 *     @type string $title
	 *     @type string $id
	 *     @type string $to
	 *     @type array $categories {
	 *         @type string $title
	 *         @type string $products
	 *     }
	 * }
	 *
	 * @return array {
	 *     @type string   $form_title
	 *     @type string   $form_id
	 *     @type string   $send_to
	 *     @type bool     $email_user
	 *     @type string[] $locations
	 *     @type array    $categories {
	 *         @type string    $category_title
	 *         @type string[]  $products
	 *     }
	 * }
	 */
	public function prepare_order_form_data( $form_data ) {
		
		// Get form details
		$form_title = $form_data['title'] ?? 'Untitled Form';
		$form_id    = $form_data['id']    ?? 'untitled-form';
		$send_to    = $form_data['to']    ?? '';
		$email_user = $form_data['email_user']    ?? true;
		
		// Get locations
		$locations = $this->get_locations();
		
		// Structure each category
		$categories = array();
		
		if ( isset($form_data['categories']) ) foreach( $form_data['categories'] as $c ) {
			$category_title = $c['title'] ?? 'Untitled Category';
			
			// Products are a textarea, this splits it into an array.
			$products = $this->split_string_list( $c['products'] );
			
			$categories[] = array(
				'category_title' => $category_title,
				'products'       => $products,
			);
		}
		
		// Return the formatted data
		return array(
			'form_title' => $form_title,
			'form_id'    => $form_id,
			'send_to'    => $send_to,
			'email_user' => $email_user,
			'locations'  => $locations,
			'categories' => $categories,
			'custom_category' => 'Write In',
		);
	}
	
	/**
	 * When multiple items are in a textarea, this splits it into an array.
	 *
	 * This also removes empty lines, whitespace, and duplicate values.
	 *
	 * @param string $str  Multi-line string
	 *
	 * @return string[]
	 */
	public function split_string_list( $str ) {
		if ( ! $str ) return array();
		
		// Split new lines
		$products = explode( "\n", $str );
		
		// Trim whitespace of each product
		$products = array_map( 'trim', $products );
		
		// Remove duplicate products
		$products = array_unique( $products );
		
		// Remove empty lines
		$products = array_filter( $products );
		
		return $products;
	}
	
	/**
	 * Gets data submitted by an order form during a POST request
	 *
	 * @return array|false
	 */
	public function get_post_data() {
		if ( $this->raw_form_data !== null ) {
			return $this->raw_form_data;
		}
		
		$data = isset($_POST['zmof']) ? stripslashes_deep($_POST['zmof']) : false;
		if ( !isset($data['action']) || $data['action'] !== 'submit-order' ) return false;
		
		if ( ! $this->can_user_create_order() ) {
			wp_die('Your account does not have permission to submit the order form.');
		}
		
		$name        = $data['name'] ?? '';
		$email       = $data['email'] ?? '';
		$location    = $data['location'] ?? '';      // "Manhattan"
		$products    = $data['products'] ?? array(); // [category_title][product_title][quantity] = "1", [reference_number] = "abc"
		$custom_products = $data['custom_products'] ?? array(); // [0][title], [0][quantity], [0][reference_number]
		$order_notes = $data['order_notes'] ?? '';
		$action      = $data['action'] ?? '';
		$form_id     = $data['form_id'] ?? '';
		
		// Remove empty products
		if ( $products ) foreach( $products as $category_title => $c ) {
			if ( $c ) foreach( $c as $product_title => $p ) {
				$empty_qty = !isset($p['quantity']) || $p['quantity'] === '';
				$empty_ref = !isset($p['reference_number']) || $p['reference_number'] === '';
				if ( $empty_qty && $empty_ref ) {
					unset( $products[$category_title][$product_title] );
				}
			}
			
			// If the category is now empty, remove it
			if ( empty($products[$category_title]) ) unset( $products[$category_title] );
		}
		
		// Remove empty custom_products
		if ( $custom_products ) foreach( $custom_products as $k => $p ) {
			$empty_qty = !isset($p['quantity']) || $p['quantity'] === '';
			$empty_title = !isset($p['title']) || $p['title'] === '';
			$empty_ref = !isset($p['reference_number']) || $p['reference_number'] === '';
			
			if ( $empty_qty && $empty_title && $empty_ref ) unset( $custom_products[$k] );
		}else{
			$custom_products = array();
		}
		
		// Convert linebreaks to a single \n in order notes (textarea)
		$order_notes = preg_replace( "/\r\n|\r/", "\n", $order_notes );
		
		$this->raw_form_data = array(
			'name' => $name,
			'email' => $email,
			'location' => $location,
			'products' => $products,
			'custom_products' => $custom_products,
			'order_notes' => $order_notes,
			'action' => $action,
			'form_id' => $form_id,
		);
		
		return $this->raw_form_data;
	}
	
	/**
	 * Checks if a user can create an order
	 *
	 * @param int|null $user_id
	 *
	 * @return bool
	 */
	public function can_user_create_order( $user_id = null ) {
		if ( $user_id === null ) $user_id = get_current_user_id();
		
		// Check if user is "office_manager" role or can "edit_pages"
		if ( user_can( $user_id, 'office_manager' ) ) return true;
		if ( user_can( $user_id, 'edit_pages' ) ) return true;
		
		return false;
	}
	
	/**
	 * Get locations from the settings page as an array of strings.
	 *
	 * @return string[]
	 */
	public function get_locations() {
		$location_str = get_field( 'order_form_locations', 'options' );
		
		return $this->split_string_list( $location_str );
	}
	
	
}

return new ZMOF_Forms();