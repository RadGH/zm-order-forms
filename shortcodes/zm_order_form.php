<?php

function shortcode_zm_order_form( $atts, $content = '', $shortcode_name = 'zm_order_form' ) {
	$atts = shortcode_atts(array(
		'id' => '',                  // Form id, from the settings page
		'classes' => 'zm-shortcode', // Custom classes added to the element
		'show_title' => 'true',
	), $atts, $shortcode_name );
	
	// Check that the form ID is valid
	$form = ZMOF_Forms()->get_order_form_by_id( $atts['id'] );
	if ( ! $form ) {
		return '<p><em>Order form not found: "'. esc_html($atts['id']) .'"</em></p>';
	}
	
	// Check if the user can create an order
	if ( ! ZMOF_Forms()->can_user_create_order() ) {
		return '<p><em>Your account does not have permission to submit the order form.</em></p>';
	}
	
	// Check if the order form was submitted. Displays a form to review and finalize the order.
	if ( ZMOF_Forms()->is_order_submitted() ) {
		return ZMOF_Forms()->get_review_order_form();
	}
	
	// Check if a submitted order was finalized. Displays a confirmation message.
	if ( ZMOF_Forms()->is_order_finalized() ) {
		return ZMOF_Forms()->get_order_finalized_messsage();
	}
	
	// Display the order form
	// $atts is passed to this template.
	ob_start();
	include( ZMOF_PATH . '/templates/order-form.php' );
	return ob_get_clean();
}
add_shortcode( 'zm_order_form', 'shortcode_zm_order_form' );