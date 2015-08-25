Schedule WordPress transients for deletion on action hooks.

## How to Use Scrubber

```php
<?php
// Say you've generated some data from WordPress, like a query with a custom post type.
$my_query = WP_Query( array( 'post_type' => 'my_cpt' ) );

// For performance, you want to cache that for regular use.
set_transient( 'my_cached_cpt_query', $my_query );

// Now you can re-use that, rather than running the query every time.
// This will be false if the transient doesn't exist
$my_query = get_transient( 'my_cached_cpt_query' ) ) {

if ( ! $my_query ) {
	// we'll rebuild it only if the cached version doesn't exist
	$my_query = WP_Query( array( 'post_type' => 'my_cpt' ) );
}

// Great! But, what about when your data changes?
// For instance, say we add a new post...
$new_post = array(
	'post_type' => 'my_cpt',
	'post_title' => 'CO2 Scrubbers',
	'post_content' => 'A carbon dioxide scrubber is a device which absorbs carbon dioxide (CO2).

It is used to treat exhaust gases from industrial plants or from exhaled air in life support systems such as rebreathers or in spacecraft, submersible craft or airtight chambers.'
);

wp_insert_post( $new_post );

// Oh no! Now our cached query of `my_cpt` posts is stale!

// This is where Scrubber comes in. Use it when creating the transient
// to schedule it for deletion during events that will make it stale. 
//
// The `Scrubber::schedule()` method takes two arguments:
//     $key is a string matching your transient name
//     $hooks is an array of WordPress action hook tags

Scrubber::schedule(
	'my_cached_cpt_query',
	array(
		'save_post_my_cpt'
	)
);

// Now, Scrubber will make sure whenever the `save_post_my_cpt` action hook runs,
// our transient `my_cached_cpt_query` will be deleted.
// It persists this data using WordPress options.

// To set up the complete working code example below, let's make
// our transient storage example above a little more complicated.

// @todo demonstrate hashing the args array

// Here is a complete working code example

// @todo demonstrate use in situ
```

## Roadmap

Scrubber is available for testing and feedback, but **we don't recommend using it in production environments yet**.  When we believe it is stable and secure, we'll publish it to the [wordpress.org](https://wordpress.org/) plugin repository. 

## Contribute

Feel free to open a Github issue to discuss feedback. Pull requests encouraged!
