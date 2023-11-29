<?php
/*
Plugin Name: ZingMap Order Forms
Description: Adds configurable order forms to the site which can be filled out and emailed to multiple recipients. The order form can only be filled out by users with the included "Office Manager" role, or users with "edit_pages" permissions.
Version: 1.2.0
Author: Radley Sustaire, ZingMap
Author URI: https://radleysustaire.com/
Plugin URI: https://zingmap.com/
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly.

define( 'ZMOF_VERSION', '1.2.0' );
define( 'ZMOF_URL', untrailingslashit(plugin_dir_url( __FILE__ )) );
define( 'ZMOF_PATH', dirname(__FILE__) );

class ZMOF_Plugin {
	
	public ZMOF_Enqueue  $Enqueue;
	public ZMOF_Blocks   $Blocks;
	public ZMOF_Forms    $Forms;
	
	public function __construct() {
		
		// Load the rest of the plugin when other plugins have finished loading.
		add_action( 'plugins_loaded', array( $this, 'load_plugin' ) );
		
		// Add a link to the settings page on the plugin screen
		add_filter('plugin_action_links_' . plugin_basename(__FILE__), array( $this, 'add_plugin_settings_links' ) );
		
		// On plugin activation install custom role called Office Manager
		register_activation_hook( __FILE__, array( $this, 'install_roles' ) );
		
	}
	
	public function load_plugin() {
		
		// Check if ACF exists
		if ( ! class_exists('acf') ) {
			add_action( 'admin_notices', array( $this, 'acf_not_found_notice' ) );
			return;
		}
		
		// Classes
		// - For usage outside of this plugin: ZM_Order_Forms()->Enqueue->something();
		$this->Enqueue = include( ZMOF_PATH . '/classes/enqueue.php' );
		$this->Blocks = include( ZMOF_PATH . '/classes/blocks.php' );
		$this->Forms = include( ZMOF_PATH . '/classes/forms.php' );
		
		// Shortcodes
		include( ZMOF_PATH . '/shortcodes/zm_order_form.php' );
		
		// ACF fields and settings pages
//		include( ZMOF_PATH . '/acf/fields.php' );
//		include( ZMOF_PATH . '/acf/options.php' );
		
	}
	
	/**
	 * Add a link to the settings page on the plugin screen
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function add_plugin_settings_links( $links ) {
		$settings_link = '<a href="' . admin_url('options-general.php?page=zmof-settings') . '">Settings</a>';
		
		// Add the link to the beginning of the list
		array_unshift($links, $settings_link);
		
		return $links;
	}
	
	public function install_roles() {
		// Create a custom role for "Office Manager", based on the subscriber role.
		if ( get_role('office_manager') === null ) {
			$capabilities = array(
				'read' => true,
				'level_0' => true,
			);
			add_role( 'office_manager', 'Office Manager', $capabilities );
		}
	}
	
	/**
	 * Display a warning on the dashboard if ACF is not loaded
	 *
	 * @return void
	 */
	public function acf_not_found_notice() {
		?>
		<div class="notice notice-error">
			<p><strong>ZingMap Order Forms:</strong> The plugin <a href="https://www.advancedcustomfields.com/" target="_blank">Advanced Custom Fields Pro</a> is required for this plugin to function.</p>
		</div>
		<?php
	}
	
}

/**
 * Get the Forms object, which handles order form validation and processing.
 *
 * @return ZMOF_Forms
 */
function ZMOF_Forms() {
	global $ZMOF_Plugin;
	return $ZMOF_Plugin->Forms;
}

// Initialize the plugin
global $ZMOF_Plugin;
$ZMOF_Plugin = new ZMOF_Plugin();