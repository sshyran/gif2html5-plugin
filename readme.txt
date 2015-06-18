=== GIF2HTML5 ===
Contributors: fusionengineering, mpatek, noppanit, danielbachhuber, davisshaver
Tags: images, GIF, videos, performance
Requires at least: 4.2
Tested up to: 4.2
Stable tag: 0.1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

GIF2HTML5 transforms animated GIF attachments to HTML5 video, using a separate application, and displays the HTML5 video in place of the original GIF.

== Description ==

When a user adds a new GIF to the Media library, or updates an existing GIF, GIF2HTML5 will start the image transformation process.

This plugin does not handle the actual file transformation routine. The transformation is handled by a [standalone web application](https://github.com/fusioneng/gif2html5-app). GIF2HTML5 sends the URL of the GIF to the web application, and the web application responds asynchronously with a POST handled via `admin_post`.

When the GIF2HTML5 plugin receives the POST from the web application, it receives MP4, OGG, and WEBM video URLs, and a poster image URL, and saves those as post meta fields of the GIF attachment.

The GIF2HTML5 plugin filters post content, and replaces `img` elements with `video` elements when the video file replacements are available.

== Installation ==

The [separate, standalone web application](https://github.com/fusioneng/gif2html5-app) is a requirement. In order to set up the web application, you should clone it from the GitHub repository, and follow the setup instructions.

Once you have your web application running. You will need to set the `gif2html5_api_url` option to the `/convert` endpoint of your web application.

If you have your web application running on Heroku, setting the `gif2html_api_url` option might look like this:

```PHP
set_option( 'gif2html5_api_url', 'https://my-web-app.herokuapp.com/convert' );
```

If you've chosen to secure your web application with an API key (the GIF2HTML5_API_KEY setting described [here](https://github.com/fusioneng/gif2html5-app#configuration)), then you will also need to set the `gif2html5_api_key` option.

```PHP
set_option( 'gif2html_api_key', 'secret-api-key' );
```

Instead of storing these options directly in the database, it might be preferable to set them via a runtime `pre_option_` filter like this:

```PHP
define( 'MYSITE_GIF2HTML5_API_URL', 'https://my-web-app.herokuapp.com/convert' );
define( 'MYSITE_GIF2HTML5_API_KEY', 'secret-api-key' );
...
add_filter( 'pre_option_gif2html5_api_url', function() { return MYSITE_GIF2HTML5_API_URL } );
add_filter( 'pre_option_gif2html5_api_key', function() { return MYSITE_GIF2HTML5_API_KEY } );
```

Now you are ready to use the plugin.

= Filters =

The plugin defines four filters for altering the URLs of assets `gif2html5_mp4_url`, `gif2html5_ogv_url`, `gif2html5_webm_url`, `gif2html5_snapshot_url`. These filters can be used to modify the URLs returned from `Gif2Html5::get_mp4_url`, `Gif2Html5::get_ogg_url`, `Gif2Html5::get_webm_url`, `Gif2Html5::get_snapshot_url` respectively. Each filter is provided with the asset URL as the first argument, and the attachment ID as the second argument.

You might want to use these filters if you've configured a DNS name as an alias for the S3 bucket used by the image conversion service. Here's how you might handle that situation:

```PHP
define( 'MYSITE_GIF2HTML5_URL_DOMAIN', 'assets.mysite.com' );
...
function replace_gif2html5_url( $url ) {
	$parsed_url = parse_url($url);
	$paths = explode( '/', $parsed_url['path'] );
	return $parsed_url['scheme'] . '://' . MYSITE_GIF2HTML5_URL_DOMAIN . '/' . implode( '/', array_slice( $paths, 2 ) );
}
add_filter( 'gif2html5_mp4_url', 'replace_gif2html5_url' );
add_filter( 'gif2html5_ogv_url', 'replace_gif2html5_url' );
add_filter( 'gif2html5_webm_url', 'replace_gif2html5_url' );
add_filter( 'gif2html5_snapshot_url', 'replace_gif2html5_url' );
```
