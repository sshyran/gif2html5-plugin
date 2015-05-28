describe('Gif2HTML5', function() {
	beforeEach(function() {
		jasmine.getFixtures().fixturesPath = 'js-tests/fixtures';
	});
	
	it('should swap broken video with gif image', function() {
		loadFixtures('video.html');

		var video = $('video.gif2html5');
	
		spyOn(video, 'replaceWith');
		var sourceEvent = spyOnEvent('source', 'error');
		
		VideoHandler(video).handleError();

		$('source').trigger('error');

		expect(sourceEvent).toHaveBeenTriggered();
		expect(video.replaceWith).toHaveBeenCalled();

		var expectedImg = video.replaceWith.calls.argsFor(0)[0][0];

		expect(expectedImg.src).toMatch('some_gif.gif');
	});

	it('should not swap broken video with gif image', function() {
		loadFixtures('video.html');

		var video = $('video.gif2html5');
	
		spyOn(video, 'replaceWith');
		var sourceEvent = spyOnEvent('source', 'error');
		
		VideoHandler(video).handleError();

		expect(sourceEvent).not.toHaveBeenTriggered();
		expect(video.replaceWith).not.toHaveBeenCalled();
	});
});
