jQuery(function($) {
	$( document ).ready(function() {
		// Add all styles into iframes
		$("link[type='text/css']").clone().prependTo($("iframe.remora-frame").contents().find("head"));
		$('iframe.remora-frame').load(function() { 
			console.log(this);
			//console.log($(this).contents().prop('scrollHeight'));
			console.log( $(this).contents().height() );
			$(this).height( $(this).contents().height() );
		});
	});
	
});

