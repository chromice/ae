Zepto(function() {
	window.opener.consoleOpened();
	$.each(window.opener.logs(), function(i, l) {
		$('<pre />').text(l.log).appendTo('body');
	});
	$(window).on('unload', function() {
		window.opener.consoleClosed();
	});
});