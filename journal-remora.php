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

		// Scripts and Styles
		wp_enqueue_script('remora-iframe', plugin_dir_path( __FILE__) . 'assets/js/remora-iframe.js', array());
	}
}

add_action( 'plugins_loaded', array('Journal_Remora', 'load_resources' ) );

function vox($utterance){
	echo "<pre>".__($utterance)."</pre>";
}
?>
