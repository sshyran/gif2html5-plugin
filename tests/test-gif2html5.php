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

	function testRequestUrlOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->request_url );
	}

	function testRequestUrlCorrectOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( $this->api_url, $this->request_url );
	}

	function testRequestArgsOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->api_url, $this->request_r );
	}

	function testRequestMethodOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( 'POST', $this->request_r['method'] );
	}

	function testBlockingFalseOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r['blocking'] );
	}

	function testContentTypeOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( 'application/json', $this->request_r['headers']['Content-Type'] );
	}

	function testSourceUrlOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$this->assertEquals( $data['url'], wp_get_attachment_url( $this->gif_id ) );
	}

	function testWebhookBaseUrlOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$this->assertEquals( strtok( $webhook, '?' ), admin_url( 'admin-post.php' ) );
	}

	function testWebhookParamsLengthOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 3, count( $params ) );
	}

	function testWebhookActionParamOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 'gif2html5_convert_cb', $params['action'] );
	}

	function testWebhookAttachmentIdParamOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( $this->gif_id, $params['attachment_id'] );
	}

	function testWebhookCodeParamOnGifAdd() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( wp_hash( $this->gif_id ), $params['code'] );
	}

	function testRequestUrlNotEmptyOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->request_url );
	}

	function testRequestUrlCorrectOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( $this->api_url, $this->request_url );
	}

	function testRequestArgsOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->api_url, $this->request_r );
	}

	function testRequestMethodOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( 'POST', $this->request_r['method'] );
	}

	function testBlockingFalseOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r['blocking'] );
	}

	function testContentTypeOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( 'application/json', $this->request_r['headers']['Content-Type'] );
	}

	function testSourceUrlOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$this->assertEquals( $data['url'], wp_get_attachment_url( $this->gif_id ) );
	}

	function testWebhookBaseUrlOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$this->assertEquals( strtok( $webhook, '?' ), admin_url( 'admin-post.php' ) );
	}

	function testWebhookParamsLengthOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 3, count( $params ) );
	}

	function testWebhookActionParamOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 'gif2html5_convert_cb', $params['action'] );
	}

	function testWebhookAttachmentIdParamOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( $this->gif_id, $params['attachment_id'] );
	}

	function testWebhookCodeParamOnGifEdit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( wp_hash( $this->gif_id ), $params['code'] );
	}

	function testRequestUrlEmptyOnPngAdd() {
		do_action( 'add_attachment', $this->png_id );
		$this->assertEmpty( $this->request_url );
	}

	function testRequestUrlEmptyOnPngEdit() {
		do_action( 'edit_attachment', $this->png_id );
		$this->assertEmpty( $this->request_url );
	}

	function testRequestArgsEmptyOnPngAdd() {
		do_action( 'add_attachment', $this->png_id );
		$this->assertEmpty( $this->request_r );
	}

	function testRequestArgsEmptyOnPngEdit() {
		do_action( 'edit_attachment', $this->png_id );
		$this->assertEmpty( $this->request_r );
	}

	function testWebhookCallbackSetsMp4Url() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
	}

	function testWebhookCallbackSetsSnapshotUrl() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://example.com/snapshot.png' );
	}

	function testWebhookCallbackFailsOnBadCode() {
		$_GET['code'] = wp_hash( $this->gif_id ) . 'x';
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEmpty( $mp4 );
	}

}
