var VideoHandler = (function($) {
	var Handler = function(video) {
		return {
			handleError : function() {				
				var sources = video.find('source'),
					object = video.find('object'),
					lastsource = sources[sources.length - 1];
				
				$(lastsource).on('error', function () {
					var gif = $('<img></img>');
					gif.attr('alt', object.attr('alt'));
					gif.attr('src', object.attr('data'));
					gif.attr('srcset', object.attr('srcset'));	
					gif.html(video.innerHTML);

					video.replaceWith(gif);
				});
			}
		}
	};

	return Handler;
})(jQuery);
