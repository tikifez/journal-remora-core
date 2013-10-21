jQuery(function($) {
	
	$( document ).ready(function() {

		// Initial frame fix
		$('iframe.remora-frame').load(function() { 
			// Add parent styles to frame
			$('link[rel=stylesheet]').clone().prependTo($("iframe.remora-frame").contents().find("head"));

			// Now that we're done potentially changing the height of things, 
			// change height of frame to match height of contents
			resizeFrame($(this));
		});

		// Make sure it stays the same size when the window is resized
		$(window).resize(function(){resizeFrame($('iframe.remora-frame')); });

	});

	function resizeFrame(){
		$('iframe.remora-frame').height( $('iframe.remora-frame').contents().height() );
	}
});

