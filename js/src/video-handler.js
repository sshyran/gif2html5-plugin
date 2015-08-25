var VideoHandler = (function($) {
	var Handler = function(videos) {
		var $window = $(window),
			$videos = $('.gif2html5-video-container');

		function fallbackGif(video) {
			var $video = $(video),
				$object = $video.find('object');

			var gif = $('<img></img>')
				.attr({
					'class':  $video.attr('class'),
					'width':  $video.attr('width'),
					'height': $video.attr('height'),
					'alt':    $object.attr('alt'),
					'src':    $object.data('gif'),
					'srcset': $object.data('srcset'),
				});
			gif.insertBefore($video);
			$video.remove();
		};

		function checkInView() {
			var windowBottom = $window.scrollTop() + $window.height();

			$.each($videos, function () {
				var elemTop = $(this).offset().top;

				if (elemTop < windowBottom) {
					$videos = $videos.not($(this));
					startPlaying($(this));
				}
			});
		}

		function startPlaying($videoContainer) {
			if (!$videoContainer.hasClass('loaded')) {
				$videoContainer.addClass('loaded');
				var $video = $videoContainer.find('video');

				if ($video.hasClass('gif2html5-extremely-large-gif')) {
					$videoContainer.addClass('playable')
						.on('click', function(evt) {
							evt.stopPropagation();
							fallbackGif($video);
						}
					);
				} else {
					fallbackGif($video);
				}
			}
		}

		return {
			handleError : function() {
				videos.each(function(index, video) {
					var sources = video.querySelectorAll('source'),
					object = video.querySelector('object'),
					lastsource = sources[sources.length - 1];

					$(lastsource).on('error', function () {
						fallbackGif(video);
					});
				});
			},
			handleMobile : function() {
				var _buffer = null;
				$window.on('scroll resize load', function ( e ) {
					if ( !_buffer && $videos.length ) {
						_buffer = setTimeout(function () {
							checkInView( e );
							_buffer = null;
						}, 300);
					}
				});
			},
		}
	};
	return Handler;
})(jQuery);
