<?php

class Scrubber {

	public static function init() {
		$data = Scrubber::get_data();

		foreach( $data as $property => $value ) {
			Scrubber::trigger_scrub_on( $property );
		}
	}
	/**
	 * Schedule deletion of a transient on event hooks
	 *
	 * @author Matthew Eppelsheimer
	 *
	 * @param $key    string  Key string of a transient to schedule deletion for
	 * @param $hooks  array   Array of WordPress action hook tags
	 * @todo prune any duplicate values in the $hooks array for performance
	 *
	 * @return void
	 */
	public static function schedule_deletion( $key, $hooks ) {
		foreach ( (array) $hooks as $hook ) {
			Scrubber::schedule_deletion_on_hook( $key, $hook );
			Scrubber::trigger_scrub_on( $hook );
		}
	}

	// Get data
	private static function get_data() {
		$data = get_option( '_scrubber_data');

		// If it doesn't exist, create it for the first time.
		if ( ! $data ) {
			$data = new StdClass;
		}

		return $data;
	}

	// Save data
	private static function save_data( $data ) {
		return update_option( '_scrubber_data', $data, true );
	}

	// Add a transient to our schedule
	private static function schedule_deletion_on_hook( $key, $hook ){
		if ( ! is_string( $hook ) || ! is_string( $key ) ) {
			return new WP_Error( 'scrubber', '`Scrubber::schedule_deletion_on_hook()` called with invalid parameters.' );
		}

		$data = Scrubber::get_data();

		// Add the key
		if ( property_exists( $data, $hook ) ) {
			if ( ! in_array( $key, $data->$hook ) ) {
				array_push( $data->$hook, $key );
			}
		} else {
			$data->$hook = array( $key );
		}

		Scrubber::save_data( $data );
	}

	// Remove a transient from the deletion schedule
	private static function unschedule_deletion_on_hook( $key, $hook ) {
		if ( ! is_string( $hook ) || ! is_string( $key ) ) {
			return new WP_Error( 'scrubber', '`Scrubber::unschedule_deletion_on_hook()` called with invalid parameters.' );
		}

		$data = Scrubber::get_data();

		// Bail if what we are unscheduling is not scheduled
		if ( ! property_exists( $data, $hook ) ) {
			return;
		}

		// Remove the key
		if ( in_array( $key, $data->$hook ) ) {
			$data->$hook = array_merge( array_diff( $data->$hook, array( $key ) ) );

		}

		Scrubber::save_data( $data );
	}

	// Register an action $hook for transient scrubbing
	private static function trigger_scrub_on( $hook ) {
		add_action( $hook, array( 'Scrubber', 'scrub' ) );
	}

	// Scrub
	public static function scrub() {
		$data = Scrubber::get_data();

		// Find out which action we're doing
		$hook = current_action();

		// Bail if we don't have any transients to clear for this action
		if ( ! property_exists( $data, $hook ) ) {
			return;
		}

		foreach ( $data->$hook as $key ) {
			delete_transient( $key );
			Scrubber::unschedule_deletion_on_hook( $key, $hook );
		}
	}
}