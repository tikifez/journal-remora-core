jQuery(function($) {
	$( document ).ready(function() {
		// Get styles for inserting into the frame later
		var parent_styles = $('link[rel=stylesheet]').clone();

		// Add all styles into iframes
		$("link[type='text/css']").clone().prependTo($("iframe.remora-frame").contents().find("head"));
		$('iframe.remora-frame').load(function() { 

			// Add styles
			$(this).contents().find('head').append(parent_styles);
			//$(this).contents().prepend('<h1>wha?</h1>');

			// Now that we're done potentially changing the height of things, 
			// change height of frame to match height of contents
			$(this).height( $(this).contents().height() );


		});
	});
	
});

