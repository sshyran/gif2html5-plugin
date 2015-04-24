<?php
class Gif2Html5 {

	private static $instance = null;

	private $api_url_option = 'gif2html5_api_url';
	private $convert_action = 'gif2html5_convert_cb';
	private $conversion_response_pending_meta_key = 'gif2html5_conversion_response_pending';
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

		if ( ! $this->mime_type_check( $attachment_id ) ) {
			return;
		}

		if ( $this->conversion_response_pending( $attachment_id ) ) {
			?>
			<div class="misc-pub-section misc-pub-gif2html5-conversion-response-pending">
				<p><?php esc_html_e( 'MP4 conversion pending...', 'gif2html5' ) ?></p>
				<input type="submit" name="gif2html5_unset_conversion_response_pending" value="<?php esc_attr_e( 'Stop waiting for MP4 conversion', 'gif2html5' ) ?>" class="button"/>
			</div><?php
			return;
		}

		$mp4 = $this->get_mp4_url( $attachment_id );
		$snapshot = $this->get_snapshot_url( $attachment_id );

		if ( ! $mp4 || ! $snapshot ) {
			?>
			<div class="misc-pub-section misc-pub-gif2html5-generate-mp4">
				<input type="submit" name="gif2html5_generate_mp4" value="<?php esc_attr_e( 'Generate MP4', 'gif2html5' ) ?>" class="button button-primary"/>
			</div><?php
			return;
		}

		?>
		<div class="misc-pub-section misc-pub-gif2html5-mp4-url">
			<label for="gif2html5_mp4_url"><?php esc_html_e( 'Mp4 URL', 'gif2html5' ) ?>:</label>
			<input type="text" class="widefat urlfield" readonly="readonly" id="gif2html5_mp4_url" value="<?php echo esc_attr( $mp4 ) ?>"/>
		</div>
		<div class="misc-pub-section misc-pub-gif2html5-snapshot-url">
			<label for="gif2html5_snapshot_url"><?php esc_html_e( 'Snapshot URL', 'gif2html5' ) ?>:</label>
			<input type="text" class="widefat urlfield" readonly="readonly" id="gif2html5_snapshot_url" value="<?php echo esc_attr( $snapshot ) ?>"/>
		</div>
		<div class="misc-pub-section misc-pub-gif2html5-force-conversion">
			<input type="submit" name="gif2html5_force_conversion" class="button button-primary" value="<?php esc_attr_e( 'Regenerate MP4', 'gif2html5' ) ?>"/>
		</div>
		<?php
	}

	public function action_add_attachment( $attachment_id ) {
		return $this->handle_save( $attachment_id );
	}

	public function action_edit_attachment( $attachment_id ) {
		return $this->handle_save( $attachment_id );
	}

	private function handle_save( $attachment_id ) {

		if ( ! $this->mime_type_check( $attachment_id ) ) {
			return;
		}

		if ( ! empty( $_POST['gif2html5_unset_conversion_response_pending'] ) ) {
			$this->unset_conversion_response_pending( $attachment_id );
			return;
		}

		return $this->send_conversion_request( $attachment_id, ! empty( $_POST['gif2html5_force_conversion'] ) );
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
	public function send_conversion_request( $attachment_id, $force_conversion = false ) {

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

		if ( $this->conversion_response_pending( $attachment_id ) ) {
			return;
		}

		if ( ! $force_conversion && $this->get_mp4_url( $attachment_id ) && $this->get_snapshot_url( $attachment_id ) ) {
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
		$this->set_conversion_response_pending( $attachment_id );
		return wp_remote_post( esc_url_raw( $api_url ), $args );
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
		if ( ! $code || wp_hash( $attachment_id ) !== $code ) {
			return;
		}

		if ( ! isset( $_POST['mp4'] ) || ! isset( $_POST['snapshot'] ) ) {
			return;
		}

		if ( ! $this->mime_type_check( $attachment_id ) ) {
			return;
		}

		$this->unset_conversion_response_pending( $attachment_id );

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
	 * Indicate whether the conversion response is still pending for the specified attachment.
	 */
	public function conversion_response_pending( $attachment_id ) {
		return (bool) get_post_meta( $attachment_id, $this->conversion_response_pending_meta_key, true );
	}

	/**
	 * Turn on the conversion response pending flag.
	 */
	public function set_conversion_response_pending( $attachment_id ) {
		return update_post_meta( $attachment_id, $this->conversion_response_pending_meta_key, true );
	}

	/**
	 * Turn off the conversion response pending flag.
	 */
	public function unset_conversion_response_pending( $attachment_id ) {
		return delete_post_meta( $attachment_id, $this->conversion_response_pending_meta_key );
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
