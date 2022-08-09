<?php
/**
 * Plugin Name: Chatrix
 * Description: Embed the Chatrix Matrix client into WordPress pages.
 * Author: Automattic
 * Author URI: https://automattic.com/
 * Plugin URI: https://github.com/Automattic/chatrix
 * Version: 1.0
 */

namespace Chatrix;

function assetUrl( $assetPath ): string {
	return plugins_url( "frontend/$assetPath", __FILE__ );
}

// Declare all instances of chatrix.
add_filter( "chatrix_instances", function () {
	return array(
		"core"      => array(
			"homeserver"              => "https://orbit-sandbox.ems.host",
			"login_methods"           => array( "sso" ),
			"welcome_message_heading" => "Welcome to chatrix!",
			"welcome_message_text"    => "To start chatting, log in with one of the options below.",
			"auto_join_room"          => "!IhfwGaWMASGLWVjkWJ:orbit-sandbox.ems.host",
		),
		"polyglots" => array(
			"homeserver"              => "https://orbit-sandbox.ems.host",
			"login_methods"           => array( "sso" ),
			"welcome_message_heading" => "Welcome to chatrix!",
			"welcome_message_text"    => "To start chatting, log in with one of the options below.",
			"auto_join_room"          => "!TGeMnSOaImTtdkfeLq:orbit-sandbox.ems.host",
		),
	);
}, 0 );

// Declare rest routes for the config of each chatrix instance.
add_action( 'rest_api_init', function () {
	$instances = apply_filters( "chatrix_instances", false );
	foreach ( $instances as $instance_id => $instance ) {
		register_rest_route( 'chatrix', "config/$instance_id", array(
			'methods'  => 'GET',
			'callback' => function () use ( $instances, $instance_id ) {
				return $instances[ $instance_id ];
			}
		) );
	}
} );

// Return the configuration for the current page, if any.
add_filter( "chatrix_configuration", function () {
	$instances = apply_filters( "chatrix_instances", false );
	$page_uri  = str_replace( home_url() . '/', '', get_permalink() );

	// The instance id is the $uri without the trailing '/'.
	$instance_id = rtrim( $page_uri, '/' );

	if ( ! array_key_exists( $instance_id, $instances ) ) {
		return null;
	}

	return array(
		"url"    => rest_url( "chatrix/config/$instance_id" ),
		"config" => $instances[ $instance_id ],
	);
} );
function chatrix_config() {
	return apply_filters( 'chatrix_configuration', false );
}

// Chatterbox accepts some configuration through properties on the window object.
// Ideally we would use wp_localize_script() but it cannot write to the `window` object.
// So instead we hook to wp_head and set the properties explicitly.
add_action( 'wp_head', function () {
	if ( $config = chatrix_config() ) {
		?>
        <script type="text/javascript">
            window.CHATTERBOX_CONFIG_LOCATION = "<?php echo $config["url"] ?>";
        </script>
		<?php
	}
} );

// Enqueue the script only when chatrix_configuration filter is set.
add_action( 'wp_enqueue_scripts', function () {
	if ( chatrix_config() ) {
		wp_enqueue_script( 'chatrix-script', assetUrl( 'assets/parent.js' ), array(), null, true );
	}
} );

// Output the script tag in the format expected by chatterbox.
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( $handle === "chatrix-script" ) {
		$tag = '<script id="chatterbox-script" type="module" src="' . esc_url( $src ) . '"></script>';
	}

	return $tag;
}, 10, 3 );
