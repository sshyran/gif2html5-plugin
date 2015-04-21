<?php
class Gif2Html5 {

	private $plugin_dir;
	private $plugin_url;
	private static $instance = null;

	private $mp4_url_meta_key = 'gif2html5_mp4_url';
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
		return $this->convert_attachment( $attachment_id );
	}

	public function action_edit_attachment( $attachment_id ) {
		return $this->convert_attachment( $attachment_id );
	}

	/**
     * Convert the attachment.
     *
     * @param int $attachment_id   Attachment ID.
     */
	public function convert_attachment( $attachment_id ) {
		$mime_type = get_post_mime_type( $attachment_id );
		if ( 'image/gif' !== $mime_type ) {
			return;
		}
		$attachment_url = wp_get_attachment_url( $attachment_id );
		if ( ! $attachment_url ) {
			return;
		}
		// TODO: Make this async
		$convert_result = $this->convert( $attachment_url );
		if ( ! $convert_result || is_wp_error( $convert_result ) ) {
			return;
		}
		if ( isset( $convert_result['mp4'] ) && isset( $convert_result['snapshot'] ) ) {
			 update_post_meta( $attachment_id, $this->mp4_url_meta_key, $convert_result['mp4'] );
			 update_post_meta( $attachment_id, $this->snapshot_url_meta_key, $convert_result['snapshot'] );
		}
	}

	private function get_api_settings() {
		return get_option( 'gif2html5_options_api' );
	}

	/**
	 * Convert the image from .gif to mp4.
	 *
	 * @param string $source_url   The URL of the source image.
	 * @return array an array with conversion results. Includes:
	 *     mp4: URL to mp4 file.
	 *     snapshot: URL to snapshot image.
	 */
	public function convert( $source_url ) {
		$api_settings = $this->get_api_settings();
		if ( ! isset( $api_settings['base_url'] ) ) {
			return;
		}
		$api_url = trailingslashit( $api_settings['base_url'] ) . 'convert';
		$args = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body' => json_encode( array(
				'url' => $source_url,
				) ),
			);
		$response = wp_remote_post( $api_url, $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );
		if ( is_wp_error( $response_body ) ) {
			return $response_body;
		}
		if ( ! in_array( $response_code, array( 200, 201 ) ) ) {
			return new WP_Error(
				'gif2html5-invalid-api-response',
				__( 'Received invalid response code: ' . $response_code, 'gif2html5' )
				);
		}

		$result = null;
		try {
			$result = json_decode( $response_body, true );
		} catch ( Exception $e ) {
			return new WP_Error(
				'gif2html5-invalid-api-response',
				__( 'Error decoding response: ' . $e->getMessage(), 'gif2html5' )
				);
		}
		return $result;
	}
}
