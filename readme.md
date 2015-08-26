Schedule WordPress transients for deletion on action hooks.

**Important: Scrubber is not ready for prime time.** See [Roadmap](#roadmap), below.

## Install Scrubber

This is a WordPress plugin. If you don't know how to take this package and install and use one, this probably isn't for you.

The `Scrubber` class is segregated into its own file, so it's easy to incorporate directly into your own project. Not recommended because you'll miss out on any future development, but free is free. This class does 100% of the work except for calling `Scrubber::init()` on the `init` action, so you'll need to do that.

## Using Scrubber — tl;dr

```php
// The `Scrubber::schedule_deletion()` method will clear your transient
// by name on an array of action hooks that you specify. It takes two arguments:
//     $key is a string matching your transient name
//     $hooks is an array of WordPress action hook tags

Scrubber::schedule_deletion(
	'my_cached_cpt_query',
	array(
		'save_post_my_cpt',
		'delete_post'
	)
);
```

## How to Use Scrubber

```php
<?php
// Say you've generated some data from WordPress, like a query with a custom post type.
$my_query = WP_Query( array( 'post_type' => 'my_cpt' ) );

// For performance, you want to cache that for regular use.
set_transient( 'my_cached_cpt_query', $my_query );

// Now you can re-use that, rather than running the query every time.
// Building on the example code above...

// $my_query will be false if the transient doesn't exist
// We can test for that and either use the transient, or build it first then use it
$my_query = get_transient( 'my_cached_cpt_query' ) ) {
if ( ! $my_query ) {
	$my_query = WP_Query( array( 'post_type' => 'my_cpt' ) );
	set_transient( 'my_cached_cpt_query', $my_query );
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
// It doesn't include the new post we added.

// This is where Scrubber comes in. Use it when creating the transient
// to schedule it for deletion during events that will make the transient stale. 
//
// The `Scrubber::schedule_deletion()` method takes two arguments:
//     $key is a string matching your transient name
//     $hooks is an array of WordPress action hook tags

Scrubber::schedule_deletion(
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
// In this example, we'll use PHP's `sha1()` function to hash our 
// array of query arguments. 

$query_args = array( 'post_type' => 'my_cpt' );
$transient_key = 'handy-prefix-' . sha1( serialize( $query_args ) );

echo $transient_key;

// That will output 'handy-prefix-194ae22745199913eaebe502628c334a5f9c535e'
//
// Why'd we do all that? Well, now our transient key has a prefix useful for us
// humans to use in debugging, and a hash suffix useful for our server to keep
// track of the unique data for us.
//
// We'll use it to set the transient like this:
$my_query = WP_Query( $query_args );
set_transient( $transient_key, $my_query );

// and then later retrieve it:
$my_query = get_transient( $transient_key );

// Here is a complete working code example
// We'll schedule the transient for deletion on both the 'save_post_my_cpt'
// action hook and the 'delete_post' action hook. (There is not yet a 
// `delete_post_{$post->post_type}` action hook in WordPress. Better to clear
// the cache more often than necessary than less often than necessary, so we
// use the `delete_post` which covers our bases.)

$query_args = array( 'post_type' => 'my_cpt' );
$transient_key = 'handy-prefix-' . sha1( serialize( $query_args ) );

$my_query = WP_Query( $query_args );
set_transient( $transient_key, $my_query );

if ( ! $my_query = WP_Query( $query_args ) ) {
	set_transient( $transient_key, $my_query );
	Scrubber::schedule_deletion(
		$transient_key,
		array(
			'save_post_my_cpt',
			'delete_post'
		)
	);
}

// Now do something with $my_query, such as looping over it in a theme's page 
// template, confident you're dealing with fresh data.
```

## <a id="roadmap"></a>Roadmap

Scrubber is available for testing and feedback, but **this is only a proof of concept. It isn't ready for prime time yet**.  

When I can attest to its stablity, performance, and security, I'll publish it to the [wordpress.org](https://wordpress.org/) plugin repository. 

Immediate next steps for this:

* Try and break things, to expose and deal with stoopid bugs
* Add unit tests

## Contribute

Feel free to send me feedback through [Twitter](https://twitter.com/mattepp), or [open an issue](https://github.com/MatthewEppelsheimer/wp-scrubber-plugin/issues) to discuss ideas. 

Pull requests welcome!

## License

GLP v2.0