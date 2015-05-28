var VideoHandler = (function($) {
	var Handler = function(videos) {
		return {
			handleError : function() {	
				videos.each(function(index, video) {
					var sources = video.querySelectorAll('source'),
					object = video.querySelector('object'),
					lastsource = sources[sources.length - 1];

					$(lastsource).on('error', function () {
						var gif = $('<img></img>');
						gif.attr('alt', object.getAttribute('alt'));
						gif.attr('src', object.getAttribute('data-gif'));
						gif.attr('srcset', object.getAttribute('srcset'));	
						gif.html(video.innerHTML);

						video.parentNode.replaceChild(gif.get(0), video);
					});

				});
			}
		}
	};

	return Handler;
})(jQuery);
