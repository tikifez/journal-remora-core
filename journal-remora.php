<?php
/**
 * @package Hello_Dolly
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
		add_action( 'plugins_loaded', array($this, 'load_resources' ), 1 );
	}

	// This just echoes the chosen line, we'll position it later
	function load_resources() {
		require_once(plugin_dir_path( __FILE__).'/classes/Remora_OJS_Core.php');
		if(include_once(plugin_dir_path( __FILE__).'/classes/Remora_OJS_Widget.php')) {
			add_action( 'widgets_init', array('Remora_OJS_Widget', 'register_widget') );
		}
	}
}
// Now we set that function up to execute when the admin_notices action is called
$journal_remora = new Journal_Remora();

function vox($utterance){
	echo "<pre>".__($utterance)."</pre>";
}
?>
