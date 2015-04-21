<?php
class Gif2Html5 {

	private $plugin_dir;
	private $plugin_url;
	private static $instance = null;

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
	}

}
