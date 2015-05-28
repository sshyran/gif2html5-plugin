(function($) {
	$(document).ready(function() {
		var videos = $('video.gif2html5-video');
		var videoHandler = VideoHandler(videos);

		videoHandler.handleError();
		videoHandler.handleMobile();
	});
})(jQuery);
