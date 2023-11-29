<?php

add_action( 'acf/init', function() {
	acf_add_options_page( array(
		'page_title' => 'ZingMap Order Forms',
		'menu_slug' => 'zmof-settings',
		'parent_slug' => 'options-general.php',
		'menu_title' => 'Order Forms',
		'position' => '',
		'redirect' => false,
	) );
} );
