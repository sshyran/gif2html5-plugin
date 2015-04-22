<?php

class Gif2Html5Test extends WP_UnitTestCase {

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
}

