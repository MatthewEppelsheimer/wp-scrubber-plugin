<?php

class Scrubber {

	/**
	 * Setup Scrubber during WordPress application initialization
	 *
	 * This makes the transient deletion schedule saved in scrubber data actionable by looping through its
	 * action hooks saved in Scrubber's data that need "scrubbing".
	 *
	 * @return void
	 */
	public static function init() {
		$data = Scrubber::get_data();

		foreach( $data as $property => $value ) {
			Scrubber::trigger_scrub_on( $property );
		}
	}

	/**
	 * Schedule deletion of a transient on event hooks
	 *
	 * Coder "users" of Scrubber should use this method to register a transient for deletion.
	 *
	 * @todo prune any duplicate values in the $hooks array for performance
	 *
	 * @param $key    string  Key string of a transient to schedule deletion for
	 * @param $hooks  array   Array of WordPress action hook tags
	 *
	 * @return void
	 */
	public static function schedule_deletion( $key, $hooks ) {
		foreach ( (array) $hooks as $hook ) {
			Scrubber::schedule_deletion_on_hook( $key, $hook );
			Scrubber::trigger_scrub_on( $hook );
		}
	}

	/**
	 * Get stored Scrubber data
	 *
	 * @return mixed|StdClass|boolean previously-saved data, or an empty object if nothing's been saved yet
	 */
	private static function get_data() {
		$data = get_option( '_scrubber_data');

		// If it doesn't exist, create it for the first time.
		if ( ! $data ) {
			$data = new StdClass;
			
			add_option( '_scrubber_data', $data, false, true );
		}

		return $data;
	}

	/**
	 * Save Scrubber data
	 *
	 * @param $data mixed Data to save
	 *
	 * @return bool 'true' if data was successfully saved, otherwise 'false'
	 */
	private static function save_data( $data ) {
		return update_option( '_scrubber_data', $data, true );
	}

	/**
	 * Add a transient to Scrubber's deletion schedule
	 *
	 * @todo return true on success
	 *
	 * @param $key string unique transient key (required)
	 * @param $hook string hook tag to schedule transient deletion on (required)
	 *
	 * @return void|WP_Error WP_Error if not passed two strings as parameters, otherwise void
	 */
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

	/**
	 * Remove a transient from Scrubber's deletion schedule
	 *
	 * @todo return true on success
	 *
	 * @param $key string unique transient key (required)
	 * @param $hook string hook tag transient deletion should be un-scheduled from (required)
	 *
	 * @return void|WP_Error WP_Error if not passed two strings as parameters, otherwise void
	 */
	private static function unschedule_deletion_on_hook( $key, $hook ) {
		if ( ! is_string( $hook ) || ! is_string( $key ) ) {
			return new WP_Error( 'scrubber', '`Scrubber::unschedule_deletion_on_hook()` called with invalid parameters.' );
		}

		$data = Scrubber::get_data();

		// Bail if what we are un-scheduling is not scheduled
		if ( ! property_exists( $data, $hook ) ) {
			return;
		}

		// Remove the key
		if ( in_array( $key, $data->$hook ) ) {
			$data->$hook = array_merge( array_diff( $data->$hook, array( $key ) ) );

		}

		Scrubber::save_data( $data );
	}

	/**
	 * Register transient scrubbing for an action hook
	 *
	 * @todo return output of `add_action()` so failures can be handled
	 *
	 * @param $hook string An action hook to register the `scrub` method to (required)
	 *
	 * @return void
	 */
	private static function trigger_scrub_on( $hook ) {
		add_action( $hook, array( 'Scrubber', 'scrub' ) );
	}

	/**
	 * "Scrub" a transient
	 *
	 * This does the actual work of removing transients that have been scheduled for deletion for the action hook that
	 * is currently running.
	 */
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
