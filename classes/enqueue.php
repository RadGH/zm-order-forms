<?php

class ZMOF_Enqueue {
	
	private $public_assets_enqueued = false;
	
	public function __construct() {

		// Register block styles on the front-end and editor. This does not actually enqueue the asset (the block and shortcode handle that)
		add_action( 'wp_enqueue_scripts', array( $this, 'register_block_styles' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'register_block_styles' ) );
		
		// Register CSS and JS on the dashboard, order form settings page.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		
		// Register block editor scripts on the editor page
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_scripts' ) );
		
		// Hook used by the shortcode or block to enqueue assets when the order form is displayed on a page
		add_action( 'zm_order_forms/enqueue_scripts', array( $this, 'enqueue_public_scripts' ) );
		
	}
	
	/**
	 * Used to include the CSS and JS on the front-end.
	 *
	 * Called manually by the shortcode, or automatically by the block
	 * @see shortcode_zm_order_form_list()
	 * 
	 * @return void
	 */
	public function enqueue_public_scripts() {
		if ( $this->public_assets_enqueued ) return;
		else $this->public_assets_enqueued = true;
		
		// Front-end styles and scripts
		wp_enqueue_script( 'zm-order-forms', ZMOF_URL . '/assets/zm-order-forms.js', array('jquery'), ZMOF_VERSION );
		
		/*
		// Include data for zm-order-forms.js
		$data = array(
			'zmof_settings' => $this->get_js_settings(),
		);
		
		wp_localize_script( 'zm-order-forms', 'zmof_settings', $data);
		*/
		
		// Ensuring block styles are registered
		$this->register_block_styles();
		
		// Enqueue the block styles. This is automatic for the gutenberg block, but not the shortcode
		wp_enqueue_style( 'zm-order-forms-block-styles' );
		
	}
	
	/**
	 * Registers (but does not enqueue) block styles for the download list block
	 * 
	 * @return void
	 */
	public function register_block_styles() {
		wp_register_style( 'zm-order-forms-block-styles', ZMOF_URL . '/assets/block-styles.css', array(), ZMOF_VERSION );
	}
	
	/**
	 * Register CSS and JS on the dashboard, order form settings page.
	 *
	 * @return void
	 */
	public function enqueue_admin_scripts() {
		// Only include admin assets on the settings page
		if ( ! acf_is_screen('settings_page_zmof-settings') ) return;
		
		// wp_enqueue_style( 'zm-order-forms-admin', ZMOF_URL . '/assets/admin.css', array(), ZMOF_VERSION );
		wp_enqueue_script( 'zm-order-forms-admin', ZMOF_URL . '/assets/admin.js', array( 'jquery', 'acf', 'acf-input' ), ZMOF_VERSION );
	}
	
	/**
	 * Register block editor scripts on the editor page
	 *
	 * @return void
	 */
	public function enqueue_block_editor_scripts() {
		wp_register_script( 'zm-order-forms-block-editor', ZMOF_URL . '/assets/block-editor.js', array('wp-element', 'wp-hooks'), ZMOF_VERSION );
	}
	
	/**
	 * Get an array of settings to be passed to zm-order-forms.js via wp_localize_script()
	 * 
	 * @return array
	 */
	public function get_js_settings() {
		$settings = array();
		
		return $settings;
	}
	
}

return new ZMOF_Enqueue();