<?php

class Test_Gif2Html5 extends WP_UnitTestCase {

	private $gif_id;
	private $png_id;
	private $request_r;
	private $request_url;

	private $api_url = 'http://example-api.com/convert';
	private $api_key = 'abc123';

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

	function test_has_video_w_no_video_is_false() {
		$this->assertFalse( Gif2Html5()->has_video( $this->gif_id ) );
	}

	function test_has_video_w_mp4_is_true() {
		$this->assertFalse( Gif2Html5()->has_video( $this->gif_id ) );
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		$this->assertTrue( Gif2Html5()->has_video( $this->gif_id ) );
	}

	function test_has_video_w_ogg_is_true() {
		$this->assertFalse( Gif2Html5()->has_video( $this->gif_id ) );
		Gif2Html5()->set_ogg_url( $this->gif_id, 'http://example.com/ogg.ogg' );
		$this->assertTrue( Gif2Html5()->has_video( $this->gif_id ) );
	}

	function test_has_video_w_webm_is_true() {
		$this->assertFalse( Gif2Html5()->has_video( $this->gif_id ) );
		Gif2Html5()->set_webm_url( $this->gif_id, 'http://example.com/webm.webm' );
		$this->assertTrue( Gif2Html5()->has_video( $this->gif_id ) );
	}

	function test_no_request_when_mp4_exists_on_add() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r || $this->request_url );
	}

	function test_no_request_when_ogg_exists_on_add() {
		Gif2Html5()->set_ogg_url( $this->gif_id, 'http://example.com/ogg.ogg' );
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r || $this->request_url );
	}

	function test_no_request_when_webm_exists_on_add() {
		Gif2Html5()->set_webm_url( $this->gif_id, 'http://example.com/webm.webm' );
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r || $this->request_url );
	}

	function test_request_when_urls_exist_and_force_on_add() {
		$_POST['gif2html5_force_conversion'] = true;
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		do_action( 'add_attachment', $this->gif_id );
		$this->assertTrue( $this->request_r && $this->request_url );
	}

	function test_pending_flag_unset_on_add() {
		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		$_POST['gif2html5_unset_conversion_response_pending'] = true;
		do_action( 'add_attachment', $this->gif_id );
		$this->assertFalse( Gif2Html5()->conversion_response_pending( $this->gif_id ) );
	}

	function test_pending_flag_unset_on_edit() {
		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		$_POST['gif2html5_unset_conversion_response_pending'] = true;
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertFalse( Gif2Html5()->conversion_response_pending( $this->gif_id ) );
	}

	function test_no_request_when_urls_exist_on_edit() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertFalse( $this->request_r || $this->request_url );
	}

	function test_request_when_urls_exist_and_force_on_edit() {
		$_POST['gif2html5_force_conversion'] = true;
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		do_action( 'edit_attachment', $this->gif_id );
		$this->assertTrue( $this->request_r && $this->request_url );
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

	function test_webhook_callback_sets_appropriate_urls() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['ogv'] = 'http://example.com/ogg.ogg';
		$_POST['webm'] = 'http://example.com/webm.webm';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
		$ogg = Gif2Html5()->get_ogg_url( $this->gif_id );
		$this->assertEquals( $ogg, 'http://example.com/ogg.ogg' );
		$webm = Gif2Html5()->get_webm_url( $this->gif_id );
		$this->assertEquals( $webm , 'http://example.com/webm.webm' );
		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://example.com/snapshot.png' );
	}

	function test_mp4_url_filter() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://s3.amazon.com/bucket/folder/mp4.mp4' );
		add_filter('gif2html5_mp4_url', function($url) {
			return 'http://assets.cloudfront.net/folder/mp4.mp4';
		}, 10 ,1);
		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://assets.cloudfront.net/folder/mp4.mp4' );
	}

	function test_snapshot_url_filter() {
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/snapshot.png' );
		add_filter('gif2html5_snapshot_url', function($url) {
			return 'http://assets.cloudfront.net/folder/snapshot.png';
		}, 10 ,1);
		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://assets.cloudfront.net/folder/snapshot.png' );
	}

	function test_webhook_callback_unsets_pending_conversion_flag() {

		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['ogv'] = 'http://example.com/ogg.ogg';
		$_POST['webm'] = 'http://example.com/webm.webm';
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

	function test_webhook_callback_succeeds_on_no_snapshot() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['ogv'] = 'http://example.com/ogg.ogg';
		$_POST['webm'] = 'http://example.com/webm.webm';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
		$ogg = Gif2Html5()->get_ogg_url( $this->gif_id );
		$this->assertEquals( $ogg, 'http://example.com/ogg.ogg' );
		$webm = Gif2Html5()->get_webm_url( $this->gif_id );
		$this->assertEquals( $webm , 'http://example.com/webm.webm' );
		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEmpty( $snapshot );
	}

	function test_webhook_callback_succeeds_on_no_mp4() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['snapshot'] = 'http://example.com/snapshot.png';
		$_POST['ogv'] = 'http://example.com/ogg.ogg';
		$_POST['webm'] = 'http://example.com/webm.webm';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEmpty( $mp4 );
		$ogg = Gif2Html5()->get_ogg_url( $this->gif_id );
		$this->assertEquals( $ogg, 'http://example.com/ogg.ogg' );
		$webm = Gif2Html5()->get_webm_url( $this->gif_id );
		$this->assertEquals( $webm , 'http://example.com/webm.webm' );
		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://example.com/snapshot.png' );
	}

	function test_webhook_callback_succeeds_on_no_ogg() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['webm'] = 'http://example.com/webm.webm';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
		$ogg = Gif2Html5()->get_ogg_url( $this->gif_id );
		$this->assertEmpty( $ogg );
		$webm = Gif2Html5()->get_webm_url( $this->gif_id );
		$this->assertEquals( $webm , 'http://example.com/webm.webm' );
		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://example.com/snapshot.png' );

	}

	function test_webhook_callback_succeeds_on_no_webm() {
		$_GET['code'] = wp_hash( $this->gif_id );
		$_GET['action'] = 'gif2html5_convert_cb';
		$_POST['attachment_id'] = $this->gif_id;
		$_POST['mp4'] = 'http://example.com/mp4.mp4';
		$_POST['snapshot'] = 'http://example.com/snapshot.png';
		$_POST['ogv'] = 'http://example.com/ogg.ogg';

		do_action( 'admin_post_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
		$ogg = Gif2Html5()->get_ogg_url( $this->gif_id );
		$this->assertEquals( $ogg, 'http://example.com/ogg.ogg' );
		$webm = Gif2Html5()->get_webm_url( $this->gif_id );
		$this->assertEmpty( $webm );
		$snapshot = Gif2Html5()->get_snapshot_url( $this->gif_id );
		$this->assertEquals( $snapshot, 'http://example.com/snapshot.png' );
	}

	function test_webhook_callback_fails_on_no_video() {
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
		$_POST['ogv'] = 'http://example.com/ogg.ogg';
		$_POST['webm'] = 'http://example.com/webm.webm';

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
		$_POST['ogv'] = 'http://example.com/ogg.ogg';
		$_POST['webm'] = 'http://example.com/webm.webm';

		do_action( 'admin_post_nopriv_gif2html5_convert_cb' );

		$mp4 = Gif2Html5()->get_mp4_url( $this->gif_id );
		$this->assertEquals( $mp4, 'http://example.com/mp4.mp4' );
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
		$this->assertContains( 'MP4 URL:', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_mp4_url() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		$this->assertContains( 'http://example.com/test.mp4', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_snapshot_label() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/test.png' );
		$this->assertContains( 'Snapshot URL:', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_snapshot_url() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/test.png' );
		$this->assertContains( 'http://example.com/test.png', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_force_conversion_button() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/test.mp4' );
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/snapshot.png' );
		$this->assertContains( 'Regenerate Video', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_pending_message() {
		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		$this->assertContains( 'Video conversion pending...', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_stop_waiting_button() {
		Gif2Html5()->set_conversion_response_pending( $this->gif_id );
		$this->assertContains( 'Stop waiting for video conversion', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_action_attachment_submitbox_contains_generate_video_button() {
		$this->assertContains( 'Generate Video', $this->get_submitbox_misc_actions_html( $this->gif_id ) );
	}

	function test_api_key_empty_without_option_set() {
		Gif2Html5()->send_conversion_request( $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$this->assertFalse( array_key_exists( 'api_key', $data ) );
	}

	function test_api_key_correct_with_option_set() {
		update_option( 'gif2html5_api_key', $this->api_key );
		Gif2Html5()->send_conversion_request( $this->gif_id );
		$data = json_decode( $this->request_r['body'], true );
		$this->assertEquals( $data['api_key'], $this->api_key );
	}

	function get_img_to_video_html() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		Gif2Html5()->set_ogg_url( $this->gif_id, 'http://example.com/ogg.ogg' );
		Gif2Html5()->set_webm_url( $this->gif_id, 'http://example.com/webm.webm' );
		Gif2Html5()->set_snapshot_url( $this->gif_id, 'http://example.com/snapshot.png' );
		$html = '<p>This is a test <img class="alignnone size-full wp-image-' . $this->gif_id . '"'
		. ' src="' . esc_attr( wp_get_attachment_url( $this->gif_id ) ) . '"'
		. ' alt="Test GIF" width="100" height="200" /> and done.</p>';
		$new_html = Gif2Html5()->img_to_video( $html );
		return $new_html;
	}

	function test_img_to_video_persists_alignone_class() {
		$html = $this->get_img_to_video_html();
		$this->assertRegExp( '/<video [^>]*class="[^"]*alignnone[" ]/', $html );
	}

	function test_img_to_video_persists_size_full_class() {
		$html = $this->get_img_to_video_html();
		$this->assertRegExp( '/<video [^>]*class="[^"]*size-full[" ]/', $html );
	}

	function test_img_to_video_persists_wp_image_class() {
		$html = $this->get_img_to_video_html();
		$this->assertRegExp( '/<video [^>]*class="[^"]*wp-image-' . $this->gif_id . '[" ]/', $html );
	}

	function test_img_to_video_contains_video_tag() {
		$html = $this->get_img_to_video_html();
		$this->assertContains( '<video', $html );
	}

	function test_img_to_video_contains_video_urls() {
		$html = $this->get_img_to_video_html();
		$this->assertRegExp( '|<source [^>]*src="http://example.com/mp4.mp4"|', $html );
		$this->assertRegExp( '|<source [^>]*src="http://example.com/ogg.ogg"|', $html );
		$this->assertRegExp( '|<source [^>]*src="http://example.com/webm.webm"|', $html );
	}

	function test_img_to_video_contains_width() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<video [^>]*width="100"/', $html );
	}

	function test_img_to_video_contains_width_var_1() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		$html = '<p>This is a test <img class="alignnone size-full wp-image-' . $this->gif_id . '"'
		. ' src="' . esc_attr( wp_get_attachment_url( $this->gif_id ) ) . '"'
		. ' alt="Test GIF" width=\'100\' height="200" /> and done.</p>';
		$new_html = Gif2Html5()->img_to_video( $html );
		$this->assertRegexp( '/<video [^>]*width="100"/', $new_html );
	}

	function test_img_to_video_contains_width_var_2() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		$html = '<p>This is a test <img class="alignnone size-full wp-image-' . $this->gif_id . '"'
		. ' src="' . esc_attr( wp_get_attachment_url( $this->gif_id ) ) . '"'
		. ' alt="Test GIF" width=100 height="200" /> and done.</p>';
		$new_html = Gif2Html5()->img_to_video( $html );
		$this->assertRegexp( '/<video [^>]*width="100"/', $new_html );
	}

	function test_img_to_video_contains_height() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<video [^>]*height="200"/', $html );
	}

	function test_img_to_video_contains_height_var_1() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		$html = '<p>This is a test <img class="alignnone size-full wp-image-' . $this->gif_id . '"'
		. ' src="' . esc_attr( wp_get_attachment_url( $this->gif_id ) ) . '"'
		. ' alt="Test GIF" width="100" height=\'200\' /> and done.</p>';
		$new_html = Gif2Html5()->img_to_video( $html );
		$this->assertRegexp( '/<video [^>]*height="200"/', $new_html );
	}

	function test_img_to_video_contains_height_var_2() {
		Gif2Html5()->set_mp4_url( $this->gif_id, 'http://example.com/mp4.mp4' );
		$html = '<p>This is a test <img class="alignnone size-full wp-image-' . $this->gif_id . '"'
		. ' src="' . esc_attr( wp_get_attachment_url( $this->gif_id ) ) . '"'
		. ' alt="Test GIF" width="100" height=200 /> and done.</p>';
		$new_html = Gif2Html5()->img_to_video( $html );
		$this->assertRegexp( '/<video [^>]*height="200"/', $new_html );
	}

	function test_img_to_video_contains_snapshot() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '|<video [^>]*poster="http://example.com/snapshot.png"|', $html );
	}

	function test_img_to_video_contains_gif2html5_video_class() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<video [^>]*class="[^"]*gif2html5-video[ "]/', $html );
	}

	function test_img_to_video_contains_gif2html5_video_id_class() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<video [^>]*class="[^"]*gif2html5-video-' . $this->gif_id . '[ "]/', $html );
	}

	function test_img_to_video_is_autoplay() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<video [^>]* autoplay[ >]/', $html );
	}

	function test_img_to_video_is_loop() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<video [^>]* loop[ >]/', $html );
	}

	function test_img_to_video_contains_original_img_tag() {
		$html = $this->get_img_to_video_html();
		$this->assertRegexp( '/<img [^>]*class="[^"]*wp-image-' . $this->gif_id . '[ "]/', $html );
	}

}
