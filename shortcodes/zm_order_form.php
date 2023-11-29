<?php

function shortcode_zm_order_form( $atts, $content = '', $shortcode_name = 'zm_order_form' ) {
	$atts = shortcode_atts(array(
		'id' => '',                  // Form id, from the settings page
		'classes' => 'zm-shortcode', // Custom classes added to the element
		'show_title' => 'true',      // Whether to show the form title
	), $atts, $shortcode_name );
	
	$form_id = $atts['id'];
	$custom_classes = $atts['classes'];
	$show_title = ($atts['show_title'] === 'true' || $atts['show_title'] === '1');
	
	return ZMOF_Forms()->get_order_form_html( $form_id, $custom_classes, $show_title );
}
add_shortcode( 'zm_order_form', 'shortcode_zm_order_form' );