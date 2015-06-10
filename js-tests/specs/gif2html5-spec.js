describe('Gif2HTML5', function() {
	var videos, sourceEvent, domVideo;
	
	beforeEach(function() {
		jasmine.getFixtures().fixturesPath = 'js-tests/fixtures';
		loadFixtures('video.html');

		videos = $('video.gif2html5');
		domVideo = videos.get(0);
		spyOn(domVideo, 'parentNode');
		spyOn(domVideo.parentNode, 'replaceChild');

		sourceEvent = spyOnEvent('source', 'error');

		VideoHandler(videos).handleError();

	});
	
	it('should swap broken video with gif image', function() {
		$('source').trigger('error');

		expect(sourceEvent).toHaveBeenTriggered();
		expect(domVideo.parentNode.replaceChild).toHaveBeenCalled();

		var expectedImg = domVideo.parentNode.replaceChild.calls.argsFor(0)[0];

		expect(expectedImg.src).toMatch('some_gif.gif');
	});

	it('should not swap broken video with gif image', function() {
		expect(sourceEvent).not.toHaveBeenTriggered();
		expect(domVideo.parentNode.replaceChild).not.toHaveBeenCalled();
	});
});
