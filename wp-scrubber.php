<?php
/*
Plugin Name: Scrubber
Version: 0.1.0
Description: Schedule Transients for deletion on action hooks
Author: Matthew Eppelsheimer
Author URI: https://rocketlift.com
Plugin URI: https://rocketlift.com
*/

if ( ! class_exists( 'Scrubber' ) ) {
	require_once( 'inc/class.scrubber.php' );
}

add_action( 'init', array( 'Scrubber', 'init' ) );
