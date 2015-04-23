<?php
class Gif2Html5 {

	private static $instance = null;

	private $api_url_option = 'gif2html5_api_url';
	private $convert_action = 'gif2html5_convert_cb';
	private $mp4_url_meta_key = 'gif2html5_mp4_url';
	private $snapshot_url_meta_key = 'gif2html5_snapshot_url';

	public static function get_instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
			self::$instance->setup_actions();
		}
		return self::$instance;
	}

	private function setup_actions() {
		add_action( 'add_attachment', array( $this, 'action_add_attachment' ) );
		add_action( 'edit_attachment', array( $this, 'action_edit_attachment' ) );
		add_action( 'admin_post_' . $this->convert_action, array( $this, 'action_admin_post_convert_cb' ) );
		add_action( 'admin_post_nopriv_' . $this->convert_action, array( $this, 'action_admin_post_convert_cb' ) );
		add_filter( 'wp_get_attachment_image_attributes', array( $this, 'filter_wp_get_attachment_image_attributes' ), 10, 2 );
		add_action( 'attachment_submitbox_misc_actions', array( $this, 'action_attachment_submitbox_misc_actions' ), 20 );
	}

	public function action_attachment_submitbox_misc_actions() {
		$attachment_id = get_the_id();
		if ( $mp4 = $this->get_mp4_url( $attachment_id ) ) {
			?>
			<div class="misc-pub-section misc-pub-gif2html5-mp4-url">
				<label for="gif2html5_mp4_url"><?php _e( 'Mp4 URL', 'gif2html5' ) ?>:</label>
				<input type="text" class="widefat urlfield" readonly="readonly" id="gif2html5_mp4_url" value="<?php esc_attr_e( $mp4 ) ?>"/>
			</div><?php
		}
		if ( $snapshot = $this->get_snapshot_url( $attachment_id ) ) {
			?>
			<div class="misc-pub-section misc-pub-gif2html5-snapshot-url">
				<label for="gif2html5_snapshot_url"><?php _e( 'Snapshot URL', 'gif2html5' ) ?>:</label>
				<input type="text" class="widefat urlfield" readonly="readonly" id="gif2html5_snapshot_url" value="<?php esc_attr_e( $snapshot ) ?>"/>
			</div><?php
		}

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
				'code' => wp_hash( $attachment_id ),
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

	/**
	 * Handle the response from the conversion API.
	 */
	public function action_admin_post_convert_cb() {

		$attachment_id = absint( $_POST['attachment_id'] );
		if ( ! $attachment_id ) {
			return;
		}

		$code = sanitize_text_field( $_GET['code'] );
		if ( ! $code || $code !== wp_hash( $attachment_id ) ) {
			return;
		}

		if ( ! isset( $_POST['mp4'] ) || ! isset( $_POST['snapshot'] ) ) {
			return;
		}

		if ( ! $this->mime_type_check( $attachment_id ) ) {
			return;
		}

		$mp4_url = esc_url_raw( $_POST['mp4'] );
		$snapshot_url = esc_url_raw( $_POST['snapshot'] );
		if ( $mp4_url && $snapshot_url ) {
			$this->set_mp4_url( $attachment_id, $mp4_url );
			$this->set_snapshot_url( $attachment_id, $snapshot_url );
		}
	}

	/**
	 * Get the mp4 URL for the given attachment.
	 */
	public function get_mp4_url( $attachment_id ) {
		return get_post_meta( $attachment_id, $this->mp4_url_meta_key, true );
	}

	/**
	 * Set the mp4 URL for the given attachment.
	 */
	public function set_mp4_url( $attachment_id, $mp4_url ) {
		return update_post_meta( $attachment_id, $this->mp4_url_meta_key, $mp4_url );
	}


	/**
	 * Get the snapshot URL for the given attachment.
	 */
	public function get_snapshot_url( $attachment_id ) {
		return get_post_meta( $attachment_id, $this->snapshot_url_meta_key, true );
	}

	/**
	 * Set the snapshot URL for the given attachment.
	 */
	public function set_snapshot_url( $attachment_id, $snapshot_url ) {
		return update_post_meta( $attachment_id, $this->snapshot_url_meta_key, $snapshot_url );
	}

	/**
	 * Set the image src attribute to the mp4 URL if one exists.
	 */
	public function filter_wp_get_attachment_image_attributes( $attr, $attachment ) {
		if ( $mp4_url = $this->get_mp4_url( $attachment->ID ) ) {
			$attr['src'] = $mp4_url;
		}
		return $attr;
	}

}
