<?php

class Gif2Html5Test extends WP_UnitTestCase {

	private $gif_id;
	private $png_id;

	function setUp() {
		parent::setUp();
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

}
