describe('Gif2HTML5', function() {
	it('should swap gif image with broker video', function() {
		var video = $('<video>');
		var source = $('<source>');
		source.attr('src', 'mp4.mp4');

		var object = $('<object>');
		object.attr('data', 'path_to_gif.gif');

		video.append(source, object);

		VideoHandler(video).handleError();

		video.trigger('error');

		expect(video.html()).toBe('');
	});
});
