<?php
/**
 * @see https://www.advancedcustomfields.com/resources/acf-blocks-key-concepts/
 * @global array $block      The block settings and attributes.
 * @global string $content   The block inner HTML (empty).
 * @global bool $is_preview  True during backend preview render.
 * @global array $context    The context provided to the block by the post or its parent block.
 */

$post_id = $context['postId'] ?? false;
$post_type = $context['postType'] ?? false;

// Classes
$classes = array();

// Custom classes
if ( !empty($block['className']) ) {
	$classes[] = $block['className'];
}

// Alignment
$align = $block['align'] ?? 'full';
if ( !empty($align) ) $classes[] = 'align' . $align;

// Background color
if ( !empty($block['backgroundColor']) ) {
	$classes[] = 'has-background';
	$classes[] = 'has-'. $block['backgroundColor'] .'-background-color';
}

// Gradient background
if ( !empty($block['gradient']) ) {
	$classes[] = 'has-background';
	$classes[] = 'has-'. $block['gradient'] .'-gradient-background';
}

// Text color
if ( !empty($block['textColor']) ) {
	$classes[] = 'has-text-color';
	$classes[] = 'has-'. $block['textColor'] .'-color';
}

// Custom classes
$classes[] = 'zm-block';

// Get ACF fields
$form_id = get_field( 'form_id' );
$show_title = get_field( 'show_title' );

// Display the list using the shortcode [zm_order_form_list]
echo shortcode_zm_order_form(array(
	'id' => $form_id,
	'classes' => implode(' ', $classes),
	'show_title' => $show_title ? 'true' : 'false',
));