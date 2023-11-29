<?php

class ZMOF_Blocks {
	
	public function __construct() {
		
		// Registers custom blocks
		add_action( 'init', array( $this, 'register_blocks' ) );
		
	}
	
	public function register_blocks() {
		
		// Download List
		register_block_type( ZMOF_PATH . '/blocks/order-form', array(
			'supports' => array(
				'anchor' => true,
				'align' => true,
				'color' => array(
					'text' => false,
					'background' => true,
					'gradients' => true,
				),
			),
		));
		
	}
	
}

return new ZMOF_Blocks();