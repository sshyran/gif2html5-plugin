<?php

class Test_Gif2Html5 extends WP_UnitTestCase {

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
		Gif2Html5()->unset_conversion_response_pending( $this->gif_id );
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

	function test_gif2html5_function_returns_correct_class() {
		$this->assertTrue( 'Gif2Html5' === get_class( Gif2Html5() ) );
	}

	function test_gif2html5_function_returns_singleton() {
		$obj1 = Gif2Html5();
		$obj2 = Gif2Html5();
		$this->assertTrue( $obj1 === $obj2 );
	}

	function test_gif2html5_function_returns_get_instance() {
		$obj1 = Gif2Html5::get_instance();
		$obj2 = Gif2Html5();
		$this->assertTrue( $obj1 === $obj2 );
	}

	function test_mime_type_check_for_gif_is_true() {
		$this->assertTrue( Gif2Html5()->mime_type_check( $this->gif_id ) );
	}

	function test_mime_type_check_for_png_is_false() {
		$this->assertFalse( Gif2Html5()->mime_type_check( $this->png_id ) );
	}

	function test_request_url_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->request_url );
	}

	function test_request_url_correct_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( $this->api_url, $this->request_url );
	}

	function test_request_args_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->api_url, $this->request_r );
	}

	function test_request_method_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( 'POST', $this->request_r['method'] );
	}

	function test_blocking_false_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r['blocking'] );
	}

	function test_content_type_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertEquals( 'application/json', $this->request_r['headers']['Content-Type'] );
	}

	function test_source_url_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$this->assertEquals( $data['url'], wp_get_attachment_url( $this->gif_id ) );
	}

	function test_webhook_base_url_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$this->assertEquals( strtok( $webhook, '?' ), admin_url( 'admin-post.php' ) );
	}

	function test_webhook_params_length_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 3, count( $params ) );
	}

	function test_webhook_action_param_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 'gif2html5_convert_cb', $params['action'] );
	}

	function test_webhook_attachment_id_param_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( $this->gif_id, $params['attachment_id'] );
	}

	function test_webhook_code_param_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( wp_hash( $this->gif_id ), $params['code'] );
	}

	function test_conversion_response_pending_set_on_gif_add() {
		do_action( 'add_attachment', $this->gif_id );
		$this->assertTrue( Gif2Html5()->conversion_response_pending( $this->gif_id ) );
	}

	function test_no_request_when_conversion_response_pending() {
		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r || $this->request_url );
	}

	function test_request_url_not_empty_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->request_url );
	}

	function test_request_url_correct_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( $this->api_url, $this->request_url );
	}

	function test_request_args_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertNotEmpty( $this->api_url, $this->request_r );
	}

	function test_request_method_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( 'POST', $this->request_r['method'] );
	}

	function test_blocking_false_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r['blocking'] );
	}

	function test_content_type_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertEquals( 'application/json', $this->request_r['headers']['Content-Type'] );
	}

	function test_source_url_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$this->assertEquals( $data['url'], wp_get_attachment_url( $this->gif_id ) );
	}

	function test_webhook_base_url_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$this->assertEquals( strtok( $webhook, '?' ), admin_url( 'admin-post.php' ) );
	}

	function test_webhook_params_length_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 3, count( $params ) );
	}

	function test_webhook_action_param_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( 'gif2html5_convert_cb', $params['action'] );
	}

	function test_webhook_attachment_id_param_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( $this->gif_id, $params['attachment_id'] );
	}

	function test_webhook_code_param_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$webhook = $data['webhook'];
		$params = array();
		parse_str( parse_url( $webhook, PHP_URL_QUERY ), $params );
		$this->assertEquals( wp_hash( $this->gif_id ), $params['code'] );
	}

	function test_conversion_response_pending_set_on_gif_edit() {
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertTrue( Gif2Html5()->conversion_response_pending( $this->gif_id ) );
	}

	function test_request_url_empty_on_png_add() {
		do_action( 'add_attachment', $this->png_id );
		$this->assertEmpty( $this->request_url );
	}

	function test_conversion_response_pending_false_on_png_add() {
		do_action( 'add_attachment', $this->png_id );
		$this->assertFalse( Gif2Html5()->conversion_response_pending( $this->png_id ) );
	}

	function test_request_url_empty_on_png_edit() {
		do_action( 'edit_attachment', $this->png_id );
		$this->assertEmpty( $this->request_url );
	}

	function test_request_args_empty_on_png_add() {
		do_action( 'add_attachment', $this->png_id );
		$this->assertEmpty( $this->request_r );
	}

	function test_request_args_empty_on_png_edit() {
		do_action( 'edit_attachment', $this->png_id );
		$this->assertEmpty( $this->request_r );
	}

	function test_conversion_response_pending_false_on_png_edit() {
		do_action( 'edit_attachment', $this->png_id );
		$this->assertFalse( Gif2Html5()->conversion_response_pending( $this->png_id ) );
	}

	function test_webhook_callback_sets_mp4_url() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
	}

	function test_webhook_callback_sets_snapshot_url() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://example.com/snapshot.png' );
	}

	function test_webhook_callback_unsets_pending_conversion_flag() {

		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$this->assertFalse( Gif2Html5()->conversion_response_pending( $this->gif_id ) );
	}

	function test_webhook_callback_fails_on_bad_code() {
		$_GET['code'] = wp_hash( $this->gif_id ) . 'x';
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEmpty( $mp4 );
	}

	function test_webhook_callback_fails_on_no_snapshot() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEmpty( $mp4 );
	}

	function test_webhook_callback_fails_on_no_mp4() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEmpty( $snapshot );
	}

	function test_webhook_checks_mime_type() {
		$_GET['code'] = wp_hash( $this->png_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->png_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->png_id );
		$this->assertEmpty( $mp4 );
	}

	function test_webhook_callback_sets_mp4_url_nopriv() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_nopriv_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
	}

	function test_get_attachment_image_src() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		$image = wp_get_attachment_image( $this->gif_id );
		$this->assertContains( 'src="http://example.com/test.mp4"', $image );
	}

	function get_submitbox_misc_actions_html( $attachment_id ) {
		global $post;
		$post = get_post( $attachment_id );
		setup_postdata( $post );
		ob_start();
		do_action( 'attachment_submitbox_misc_actions' );
		return ob_get_clean();
	}

	function test_action_attachment_submitbox_contains_mp4_label() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		$this->assertContains( 'Mp4 URL:', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_mp4_url() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		$this->assertContains( 'http://example.com/test.mp4', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_snapshot_label() {
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/test.png' );
		$this->assertContains( 'Snapshot URL:', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_snapshot_url() {
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/test.png' );
		$this->assertContains( 'http://example.com/test.png', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

}
