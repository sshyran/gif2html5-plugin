<?php

class Gif2Html5Test extends WP_UnitTestCase {

	private $gif_id;
	private $png_id;
	private $request_r;
	private $request_url;

	private $api_url = 'http://example-api.com/convert';

	function setUp() {
		parent::setUp();

		update_option( 'gif2html5_api_url', $this->api_url );

		add_filter( 'pre_http_request', array( $this, 'filter_pre_http_request' ), 10, 3 );

		$this->gif_id = $this->factory->attachment->create_object(
			'test.gif',
			0,
			array( 'post_mime_type' => 'image/gif' )
			);
		$this->png_id = $this->factory->attachment->create_object(
			'test.png',
			0,
			array( 'post_mime_type' => 'image/png' )
			);

		$this->request_r = null;
		$this->request_url = null;
	}

	function tearDown() {
		parent::tearDown();
		delete_option( 'gif2html5_api_url' );
		remove_filter( 'pre_http_request', array( $this, 'filter_pre_http_request' ) );
		$this->gif_id = null;
		$this->png_id = null;
		$this->request_r = null;
		$this->request_url = null;
	}


	function filter_pre_http_request( $pre, $r, $url ) {
		$this->request_r = $r;
		$this->request_url = $url;
		return true;
	}

	function testGif2Html5FunctionReturnsCorrectClass() {
		$this->assertTrue( 'Gif2Html5' === get_class( Gif2Html5() ) );
	}

	function testGif2Html5FunctionReturnsSingleton() {
		$obj1 = Gif2Html5();
		$obj2 = Gif2Html5();
		$this->assertTrue( $obj1 === $obj2 );
	}

	function testGif2Html5FunctionReturnsGetInstance() {
		$obj1 = Gif2Html5::get_instance();
		$obj2 = Gif2Html5();
		$this->assertTrue( $obj1 === $obj2 );
	}

	function testMimeTypeCheckForGifIsTrue() {
		$this->assertTrue( Gif2Html5()->mime_type_check( $this->gif_id ) );
	}

	function testMimeTypeCheckForPngIsFalse() {
		$this->assertFalse( Gif2Html5()->mime_type_check( $this->png_id ) );
	}

	function testRequestUrlNotEmptyOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->request_url );
	}

	function testRequestUrlCorrectOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( $this->api_url, $this->request_url );
	}

	function testRequestUrlNotEmptyOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->request_url );
	}

	function testRequestUrlCorrectOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( $this->api_url, $this->request_url );
	}

	function testRequestUrlEmptyOnPngAdd() {
		do_action( 'add_attachment', $this->png_id );
		$this->assertEmpty( $this->request_url );
	}

	function testRequestUrlEmptyOnPngEdit() {
		do_action( 'edit_attachment', $this->png_id );
		$this->assertEmpty( $this->request_url );
	}

}
