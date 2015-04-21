<?php
class Gif2Html5 {

	private $plugin_dir;
	private $plugin_url;
	private static $instance = null;

	private $api_url_option = 'gif2html5_api_url';
	private $mp4_url_meta_key = 'gif2html5_mp4_url';
	private $convert_action = 'gif2html5_convert_cb';
	private $snapshot_url_meta_key = 'gif2html5_snapshot_url';

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->plugin_version = GIF2HTML5_VERSION;
		$this->plugin_dir     = plugin_dir_path( dirname( __FILE__ ) );
		$this->plugin_url     = plugin_dir_url( dirname( __FILE__ ) );
	}

	private function setup_actions() {
		add_action( 'add_attachment', array( $this, 'action_add_attachment' ) );
		add_action( 'edit_attachment', array( $this, 'action_edit_attachment' ) );
	}

	public function action_add_attachment( $attachment_id ) {
		return $this->send_conversion_request( $attachment_id );
	}

	public function action_edit_attachment( $attachment_id ) {
		return $this->send_conversion_request( $attachment_id );
	}

	/**
	 * Indicate whether the attachment can be converted.
	 *
	 * @param int $attachment_id the ID of the attachment.
	 * @return boolean true if the attachment is of the proper type, false otherwise.
	 */
	public function mime_type_check( $attachment_id ) {
		return 'image/gif' === get_post_mime_type( $attachment_id );
	}

	/**
	 * Send the request to convert the image from .gif to mp4.
	 *
	 * @param int $attachment_id   The unique ID of the attachment.
	 * @return array an array with conversion results. Includes:
	 *     mp4: URL to mp4 file.
	 *     snapshot: URL to snapshot image.
	 */
	public function send_conversion_request( $attachment_id ) {

		if ( ! $this->mime_type_check( $attachment_id ) ) {
			return;
		}

		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( ! $attachment_url ) {
			return;
		}

		$api_url = get_option( $this->api_url_option );

		if ( ! $api_url ) {
			return;
		}

		$webhook_url = add_query_arg(
			array(
				'action' => $this->convert_action,
				'attachment_id' => $attachment_id,
				'attachment_hash' => wp_hash( $attachment_id ),
				),
			admin_url( 'admin-post.php' )
			);

		$args = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'blocking' => false,
			'body' => json_encode( array(
				'url' => $attachment_url,
				'webhook' => $webhook_url,
				) ),
			);
		return wp_remote_post( $api_url, $args );
	}
}
