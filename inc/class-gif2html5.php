<?php
/**
 * Convert GIFs to MP4s
 *
 * Converts GIFS to MP4s and replaces GIF HTML img elements with MP4 video elements.
 */
class Gif2Html5 {

	private static $instance = null;

	private $api_key_option = 'gif2html5_api_key';
	private $api_url_option = 'gif2html5_api_url';
	private $convert_action = 'gif2html5_convert_cb';
	private $conversion_response_pending_meta_key = 'gif2html5_conversion_response_pending';
	private $mp4_url_meta_key = 'gif2html5_mp4_url';
	private $snapshot_url_meta_key = 'gif2html5_snapshot_url';

	/**
	 * Returns an instance of this class.
	 *
	 * @return Gif2Html5
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
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
		add_action( 'attachment_submitbox_misc_actions', array( $this, 'action_attachment_submitbox_misc_actions' ), 20 );
		add_filter( 'the_content', array( $this, 'filter_the_content_img_to_video' ), 99 );
	}

	/**
	 * Customize the attachments editor submitbox.
	 */
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

	/**
	 * Send request for GIF conversion on attachment add, where applicable.
	 *
	 * @param int $attachment_id Attachment ID
	 */
	public function action_add_attachment( $attachment_id ) {
		$this->handle_save( $attachment_id );
	}

	/**
	 * Send request for GIF conversion on attachment edit, where applicable.
	 *
	 * @param int $attachment_id Attachment ID
	 */
	public function action_edit_attachment( $attachment_id ) {
		$this->handle_save( $attachment_id );
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
	 * Indicate whether the attachment can be converted to MP4.
	 *
	 * @param int $attachment_id the ID of the attachment.
	 * @return bool true if the attachment is of the proper type, false otherwise.
	 */
	public function mime_type_check( $attachment_id ) {
		return 'image/gif' === get_post_mime_type( $attachment_id );
	}

	/**
	 * Send the request to convert the image from GIF to MP4.
	 *
	 * @param int $attachment_id   The unique ID of the attachment.
	 * @return array an array with conversion results. Includes:
	 *     mp4: URL to MP4 file.
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
				'code' => wp_hash( 'gif2html5-' . $attachment_id ),
				),
			admin_url( 'admin-post.php' )
		);

		$data = array(
			'url' => $attachment_url,
			'webhook' => $webhook_url,
		);

		$api_key = get_option( $this->api_key_option );
		if ( ! empty( $api_key ) ) {
			$data['api_key'] = $api_key;
		}

		$args = array(
			'headers' => array( 'Content-Type' => 'application/json' ),
			'blocking' => false,
			'body' => json_encode( $data ),
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
		if ( ! $code || wp_hash( 'gif2html5-' . $attachment_id ) !== $code ) {
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
	 * Get the MP4 URL for the given attachment.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return string The MP4 URL or, an empty string if no MP4 URL exists.
	 */
	public function get_mp4_url( $attachment_id ) {
		/**
		 * Filter the MP4 URL.
		 *
		 * @param string MP4 URL
		 * @param int Attachment ID
		 */
		return apply_filters( 'gif2html5_mp4_url', get_post_meta( $attachment_id, $this->mp4_url_meta_key, true ), $attachment_id );
	}

	/**
	 * Set the MP4 URL for the given attachment.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @param string $mp4_url The MP4 URL.
	 */
	public function set_mp4_url( $attachment_id, $mp4_url ) {
		update_post_meta( $attachment_id, $this->mp4_url_meta_key, $mp4_url );
	}


	/**
	 * Get the snapshot URL for the given attachment.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return string The snapshot URL, or an empty string if no snapshot URL exists.
	 */
	public function get_snapshot_url( $attachment_id ) {
		/**
		 * Filter the snapshot URL.
		 *
		 * @param string snapshot URL
		 * @param int Attachment ID
		 */
		return apply_filters( 'gif2html5_snapshot_url', get_post_meta( $attachment_id, $this->snapshot_url_meta_key, true ), $attachment_id );
	}

	/**
	 * Set the snapshot URL for the given attachment.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @param string $snapshot_url The URL of the snapshot.
	 */
	public function set_snapshot_url( $attachment_id, $snapshot_url ) {
		update_post_meta( $attachment_id, $this->snapshot_url_meta_key, $snapshot_url );
	}

	/**
	 * Indicate whether the conversion response is still pending.
	 *
	 * @param int $attachment_id The attachment ID.
	 * @return bool True if conversion response is still pending, False otherwise.
	 */
	public function conversion_response_pending( $attachment_id ) {
		return (bool) get_post_meta( $attachment_id, $this->conversion_response_pending_meta_key, true );
	}

	/**
	 * Turn on the conversion response pending flag.
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public function set_conversion_response_pending( $attachment_id ) {
		update_post_meta( $attachment_id, $this->conversion_response_pending_meta_key, true );
	}

	/**
	 * Turn off the conversion response pending flag.
	 *
	 * @param int $attachment_id The attachment ID.
	 */
	public function unset_conversion_response_pending( $attachment_id ) {
		delete_post_meta( $attachment_id, $this->conversion_response_pending_meta_key );
	}

	/**
	 * Replace img with video elements in post content.
	 *
	 * @param string $html the HTML with img elements to replace.
	 * @return string the HTML with img elements replaced by corresponding video elements.
	 */
	public function filter_the_content_img_to_video( $html ) {
		return $this->img_to_video( $html );
	}

	/**
	 * Replace img elements with video elements in the specified HTML.
	 *
	 * @param string $html The input HTML.
	 * @return string The HTML with img elements replaced by video elements.
	 */
	public function img_to_video( $html ) {
		$matches = array();
		preg_match_all(
			'/<img [^>]*class="[^"]*wp-image-(\d+)[^>]*>/i',
			$html,
			$matches
		);
		if ( empty( $matches[1] ) ) {
			return $html;
		}
		$post_ids = array();
		foreach ( $matches[1] as $post_id ) {
			$post_ids[] = absint( $post_id );
		}
		$post_ids = array_filter( array_unique( $post_ids ) );
		if ( empty( $post_ids ) ) {
			return $html;
		}
		// This query should cache post data,
		// so that we don't have to fetch posts individually.
		new WP_Query( array(
			'post__in' => $post_ids,
			'post_type' => 'attachment',
			'posts_per_page' => count( $post_ids ),
		) );
		$replaced = array();
		for ( $i = 0; $i < count( $matches[0] ); $i++ ) {
			$post_id = absint( $matches[1][ $i ] );
			if ( empty( $post_id ) ) {
				continue;
			}
			if ( ! $this->get_mp4_url( $post_id ) ) {
				continue;
			}
			$img_element = $matches[0][ $i ];
			if ( in_array( $img_element, $replaced ) ) {
				continue;
			}
			$video_element = $this->img_to_video_element( $post_id, $img_element );
			if ( empty( $video_element ) ) {
				continue;
			}
			$html = str_replace( $img_element, $video_element, $html );
			$replaced[] = $img_element;
		}
		return $html;
	}

	/**
	 * Returns the video HTML element that replaces the img element for the specified attachment.
	 *
	 * @param int $id the attachment ID.
	 * @param string $img_element the img element HTML.
	 * @return string the video element HTML.
	 */
	public function img_to_video_element( $id, $img_element ) {
		$attributes = $this->get_element_attributes(
			$img_element,
			array( 'width', 'height', 'class' )
		);

		return $this->get_video_element(
			$id,
			array( 'attributes' => $attributes, 'fallback' => $img_element )
		);
	}

	/**
	 * Returns the video element for an attachment.
	 *
	 *
	 * @param int $id the attachment ID.
	 * @param array $options An array of display options.
	 *                       Options may include:
	 *                       'attributes': attributes used for the video element.
	 *                       'fallback':   any HTML that should be placed at the end of the video element
	 *                       as a fallback for browsers that do not support the video element.
	 * @return string|bool the HTML of the video element, or False if the video element could
	 *                     not be created for the given attachment.
	 */
	public function get_video_element( $id, $options = array() ) {
		$mp4_url = $this->get_mp4_url( $id );
		if ( empty( $mp4_url ) ) {
			return false;
		}
		$attributes = $this->get_video_element_attributes(
			$id,
			isset( $options['attributes'] ) ? $options['attributes'] : array()
		);
		$fallback = isset( $options['fallback'] ) ? $options['fallback'] : '';
		return '<video '
		. trim( $this->attributes_string( $attributes ) . ' autoplay loop' )
		. '><source src="' . esc_url( $mp4_url ) . '" type="video/mp4">' . $fallback . '</video>';
	}

	private function get_element_attributes( $element_html, $att_names ) {
		$html = '<html><body>' . $element_html . '</body></html>';
		$doc = new DomDocument();
		$doc->loadHtml( $html );
		$body = $doc->getElementsByTagName( 'body' )->item( 0 );
		if ( empty( $body ) ) {
			return array();
		}
		$element = $body->firstChild;
		if ( empty( $element ) || ! method_exists( $element, 'getAttribute' ) ) {
			return array();
		}
		$attributes = array();
		foreach ( $att_names as $att_name ) {
			$att_value = $element->getAttribute( $att_name );
			if ( ! empty( $att_value ) ) {
				$attributes[ $att_name ] = $att_value;
			}
		}
		return $attributes;
	}

	private function attributes_string( $attributes ) {
		$parts = array();
		foreach ( $attributes as $k => $v ) {
			$parts[] = sanitize_key( $k ) . '="' . esc_attr( $v ) . '"';
		}
		return join( ' ', $parts );
	}

	private function get_video_element_attributes( $id, $attributes = array() ) {
		if ( ! isset( $attributes['poster'] ) ) {
			$snapshot_url = $this->get_snapshot_url( $id );
			if ( ! empty( $snapshot_url ) ) {
				$attributes['poster'] = esc_url( $snapshot_url );
			}
		}
		$attributes['class'] = trim(
			( isset( $attributes['class'] ) ? $attributes['class'] : '' )
			. ' gif2html5-video gif2html5-video-' . $id
		);
		return $attributes;
	}

}
