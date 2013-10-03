<?php
/**
 * @package Journal_Remora
 * @version 1.6
 */
/*
Plugin Name: Journal Remora
Plugin URI: http://cdrs.columbia.edu/
Description: Enables retrieving data from an OJS journal and outputting it
Author: Christopher Anderton
Version: 0.1
Author URI: http://cdrs.columbia.edu
*/
class Journal_Remora {

	function __construct(){
		self::load_resources();
	}

	/**
	 * Loads plugin resources
	 */
	function load_resources() {
		// Required classes
		require_once(plugin_dir_path( __FILE__).'/classes/Remora_OJS_Core.php');
		if(include_once(plugin_dir_path( __FILE__).'/classes/Remora_OJS_Widget.php')) {
			add_action( 'widgets_init', array('Remora_OJS_Widget', 'register_widget') );
		}
	}
	/**
	 * Loads plugin assets (css, js, etc.)
	 */
	function load_assets(){
		wp_enqueue_style('remora', plugin_dir_url( __FILE__) . 'assets/css/remora.css', false);
		wp_enqueue_script('remora-iframe', plugin_dir_url( __FILE__) . 'assets/js/remora-iframe.js', array('jquery'));
	}
}

add_action( 'plugins_loaded', array('Journal_Remora', 'load_resources' ) );
add_action( 'wp_enqueue_scripts', array('Journal_Remora', 'load_assets') );

function vox($utterance){
	echo "<pre>".__($utterance)."</pre>";
}
?>
