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
						video.parentNode.replaceChild(gif.get(0), video);
					});

				});
			},
			handleMobile : function() {

				var $window = $(window),
					$videos = $('.gif2html5-video-container'),
					_buffer = null;

				$window.on('scroll resize load', function ( e ) {
					if ( !_buffer && $videos.length ) {
						_buffer = setTimeout(function () {
							checkInView( e );
							_buffer = null;
						}, 300);
					}
				});

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
					if (!$videoContainer.hasClass('played')) {
						$videoContainer.addClass('played');
						var $video = $videoContainer.find('video');

						if (navigator.userAgent.match(/(iPad|iPhone|iPod)/g)) {
							var $object = $videoContainer.find('object');
							var gif = $('<img></img>');

							gif.attr('alt',    $object.attr('alt'));
							gif.attr('src',    $object.data('gif'));
							gif.attr('srcset', $object.data('srcset'));
							gif.insertBefore($video);
							$video.remove();
						} else {
							$video.get(0).play();
						}
					}
				}
			},
		}
	};
	return Handler;
})(jQuery);
