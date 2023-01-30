# WP Debug Bar

A Debug Bar for WordPress inspired by (and compatible with) Debug Bar by wordpressdotorg with my own opinionated take on usability and features

This repo is designed to work with and support existing Debug Bar plugins.

## Getting Started

Install as a composer package in the same directory as your file with your plugin [header comments](https://developer.wordpress.org/plugins/plugin-basics/header-requirements/).

```shell
composer require pfaciana/wp-debug-bar
```

And if you're not already using other composer packages, then add the autoload.php right after your plugin header comments.

```php
/**
 * Plugin Name: YOUR PLUGIN NAME
 */
 
require __DIR__ . '/vendor/autoload.php';
```

You can install WP Debug Bar in all your plugins, but only the first instance run will get loaded. The rest will be ignored.

A couple differences between the original Debug Bar by wordpressdotorg and this, is the option to quickly disable a Panel without having to disable the entire plugin. You just click on the toggle icon next to the Panel name, and that code won't run on next page load. This is extremely helpful since some Panels are resource intensive, and you may not want to be constantly enabling and disabling plugins while debugging a stubborn issue.

This project also allows for the ability to easily change which User Roles can see a Panel. Sometimes (on staging) you may want to debug something only a Guest sees. You can temporarily change that on the Settings Panel. By default, Panels can decide the minimum capability required for a Panel to be visible. This is usually because a Panel might display sensitive data that should not be accessed by a guest or user with lesser capabilities. If not explicitly set by the Panel, a Panel defaults to `edit_posts`. However, if you're absolutely sure you want to expose that data in a controlled environment, you can override this with the `debug_bar_panel_capability` filter, shown here...

```php
// This example will allow visibility to ALL Panels to ALL site visitors,
// excepts Panels titled `Environment` or `Globals`
// $panel is the Panel class object
add_filter( 'debug_bar_panel_capability', function ( string $capability, string $title, Panel $panel ) {
    if ( !in_array( $title, [ 'Environment', 'Globals' ] ) ) {
        return ''; // Setting a $capability to '', '*', 'any' or 'all' shows to panel to all site visitors
    }

    return $capability;
}, 10, 3 );
```

A full list of panels that come with WP Debug can be seen at the [bottom of this documentation](#built-in-panels).

The next few sections will detail user defined code that can interact with specific Debug Panels

## Kint Debugger & Console Class

WP Debug Bar comes with a custom Kint Debugger Panel for output from [Kint Debugger](https://kint-php.github.io/kint/)

> NOTE: Both the Kint Panel and the Kint Debugger are bundled with this install

While the following default usage of `Kint` still works...

```php
// Properties
Kint::$expanded = true;
Kint::$enabled_mode = FALSE;
Kint::$max_depth = 3;

// Methods
Kint::dump($_SERVER); // Dump any number of variables
Kint::trace(); // Dump a backtrace

// Shorthand
d($_SERVER); // d() is a shortcut for Kint::dump()
t(); // t() is a shortcut for Kint::trace()
```

...it is recommended to use the new `console` instance created by WP Debug Bar over the `Kint` instance for methods, because there are a few enhancements that improve the experience.

### WP Debug Bar `console`

`console` is a replacement for the `Kint` methods `dump` and `trace`, along with additional methods designed to act and function similar to the `console` [in the browser](https://developer.mozilla.org/en-US/docs/Web/API/console). So `Kint::dump()` becomes `console::log()` and `Kint::trace()` becomes `console::trace()`. And if you're familiar with `console.log()` or `console.assert()`, etc, this will function in a similar way. In addition to displaying content to the Kint Panel, these methods also return relevant data to be used in your code. Here are rest of the methods available...

```php
// `console::log` is same as `Kint::dump`, accepts unlimited arguments and dumps them to the Panel
// Each call to log groups all the arguments together in one section
// Returns the value of the first agrument passed
console::log( ...$args ) : mixed

// `console::trace` is same as `Kint::trace`, accept the same signature as php's debug_backtrace
// If $options is true, then response is debug_backtrace() with the default arguments
// If $options and/or $limit is defined, then the response is debug_backtrace($options, $limit)
// Returns the backtrace array, or NULL if no arguments are defined
console::trace( true|int $options = NULL, int $limit = 0 ) : null|array
```

The following methods all accept additional variables, as $context arguments, to be appended to the bottom of the group for extra context. The $context arguments are optional.

#### Log Levels

Similar to the [PSR-3: Logger Interface](https://www.php-fig.org/psr/psr-3/)

```php
// These methods work the same as `console::log` except they add a relevant icon and header text with the level
// Returns the $message prefixed with the message level
console::emergency( string $message, ...$context ) : string  // Header flashes red quickly
console::critical( string $message, ...$context ) : string  // Header flashes red 
console::alert( string $message, ...$context ) : string  // Header flashes red slowly
console::error( string $message, ...$context ) : string  // Header is red
console::warn( string $message, ...$context ) : string  // Header is orange
console::notice( string $message, ...$context ) : string  // Header is yellow
console::debug( string $message, ...$context ) : string  // Header is green
console::info( string $message, ...$context ) : string // Header is white

// Usage
console.warn("This is a Warning!")
// Outputs
// `⚠ Warning: This is a Warning!` (in orange color)
```

#### Testing conditions

```php
// Checks a condition and displays pass or fail to the panel
// $message is an optional text to display after pass/fail header
// Returns the result of the $condition
console::test( bool $condition, string $message, ...$context ) : bool

// Checks a condition and displays it to the panel ONLY if it fails
// $error_message is an optional text to display after fail header
// Returns the result of the $condition
console::assert( bool $condition, string $error_message, ...$context ) : bool

// Usage
console::test( $large_number > $small_number, 'Checking to make sure large > small', $large_number, $small_number);
console::assert( $large_number > $small_number, 'Large is not greater than small', $large_number, $small_number);
```

#### Timers

You can manage multiple timers by defining the `$label`. Otherwise, all timer methods apply to the same group by default.

```php
// Start a Timer
// $label is the name of the timer group, defaults to 'default'
// Alias: console::timeStart()
console::time( string $label = 'default', ...$context ) : float

// Display the current duration of a Timer
// $label is the name of the timer group, defaults to 'default'
// Returns the number of seconds the timer has been running
console::timeLog( string $label = 'default', ...$context ) : float

// Stop a Timer
// $label is the name of the timer group, defaults to 'default'
// Returns the duration of the timer, in seconds
console::timeEnd( string $label = 'default', ...$context ) : float

// Usage
console::time();
// some code to time
console::timeEnd();
```

#### Counters

You can manage multiple counters by defining the `$label`. Otherwise, all timer methods apply to the same group by default.

```php
// Start a Counter
// $label is the name of the counter group, defaults to 'default'
// Return the number of times the counter group has been called
console::count( string $label = 'default', ...$context ) : int

// Reset a Counter to zero
// $label is the name of the counter group, defaults to 'default'
console::countReset( string $label = 'default', ...$context ) : int

// Usage
console::count(); // do something...
console::count(); // do something...
console::countReset();  // start over...
console::count(); // do something...
```

#### Memory checking

```php
// Display and return the current memory being used (in MB)
console::memory( ...$context ) : float
// Display and return the peak memory usage (in MB)
console::memoryPeak( ...$context ) : float
// Display and return (as an array) both the current memory used the peak memory usage (in MB)
console::memoryBoth( ...$context ) : float[]
// Reset the peak memory usage, if `memory_reset_peak_usage` function exists in php
console::memoryReset( ...$context ) : float
```

#### WordPress Hooks

While it is not recommended to use debugging tools like this on production, you may want to use it on a dev, staging or testing environment. And there may be concerns of pushing code with `console` to different environments where WP Debug Bar is not installed. If that were the case, then the missing `console` would throw an error. So, if you are concerned about this, you can use WordPress Hooks to output to the Kint Panel. In that scenario, even if that code gets pushed accidentally, the hook will simply do nothing, avoiding a production error. Hook names accept both `::` and `.` concatenation for classes and methods. So BOTH `console::info` and `console.log` work. I find it easier and quicker to type dot notion, so I prefer that.

```php
// Usage
do_action( 'console.log', $a, $b, $c );
do_action_ref_array( 'console::log', [ &$a, $b, $c ] );
$error = apply_filters( 'console::error', 'This is an ERROR' )
```

WP Debug Bar also publishes actions for specific `console` events that may be useful for notifying third party tools.

```php
# Level Logging (converts string level to RFC 5424 and Monolog integer versions as well) 
do_action( 'console/level', string $level, string $message, mixed[] $context );
do_action( 'console/level/rfc5424', int $rfc5424, string $message, mixed[] $context );
do_action( 'console/level/monolog', int $monolog, string $message, mixed[] $context );
do_action( "console/level/{$level}", string $message, mixed[] $context );
do_action( "console/level/rfc5424/{$rfc5424}", string $message, mixed[] $context );
do_action( "console/level/monolog/{$monolog}", string $message, mixed[] $context );

# Conditions
do_action( 'console/test', bool $condition, string $message, mixed[] $context );
do_action( 'console/assert', bool $condition, string $message, mixed[] $context );
do_action( 'console/condition', 'test'|'assert' $type, bool $condition, string $message, mixed[] $context );

# Timers (after a Timer has ended)
do_action( 'console/time', string $label, float $duration, mixed[] $context );
do_action( "console/time/{$label}", float $duration, mixed[] $context );

// Usage
add_action( 'console/level/monolog', function ( int $level, string $message, array $context ) {
    if( $level >= 400 ) {
        // The last item in the $context array is the string name of the $level
        $level_name = (string) array_pop( $context );
        notify_developer("There was a(n) {$level_name}");
    }
}, 10, 3 );

add_action( 'console/level/rfc5424', function ( int $level, string $message, array $context ) {
    if( $level === 7 ) {
        write_to_debug_log($message, $context);
    }
    
    if( $level >= 4 && $level <= 5 ) {
        notify_team_lead($message, $context);
    }
}, 10, 3 );

add_action( 'console/condition', function ( string $type, bool $condition, string $message,  array $context ) {
    if( $type === 'assert' && !$condition ) {
        notify_current_user($message, $context);
    }
}, 10, 4 );
```

## Tracking Hooks that fire in a section of code

Sometimes you may want to know what hooks are fired along with their inputs and output. For example, there is third party code that outputs something to the buffer, and you'd like to modify that text. The third party documentation may be lacking, and trying to set breakpoint to debug may take a very long time if there is a lot of nested code. It would be ideal to find out what filters get run on that function call to see if you can modify it with a hook in your plugin or theme. That's where hook tracking comes in. It shows up on the Hooks Panel in WP Debug Bar whenever a watcher is set.

### How to use

```php
// In some third party code you might see...
echo some_function();

// You change that to...
do_action( 'debugbar/watch', (string) $label );
echo some_function();
do_action( 'debugbar/unwatch', (string) $label );
// NOTE: $label is the tracker label and used to identify and filter rows in the Panel UI.
// $label is arbitrary and should be a string.
```

When you do that, a new Tracking section will show up in the Hooks Panel. It will only show the hooks fired between the `debugbar/watch` and `debugbar/unwatch` actions. Now you can see if a relevant filter exists and hook into to modify the output.

#### Alternative use

These do the same thing, but may be a preferable alternative.

```php
// Alternate Option #1
$unwatch = apply_filters( 'debugbar/watch', 'Some Watcher Name' );
echo do_something();
$unwatch();

// Alternate Option #2
do_action( 'debugbar/watch', 'Do Something', function() {
    echo do_something();
});
```

#### Multiple trackers

```php
do_action( 'debugbar/watch', 'watcher #1' );
echo some_function();
do_action( 'debugbar/watch', 'watcher #2' );
echo some_other_function();
do_action( 'debugbar/unwatch', 'watcher #1' );
do_action( 'debugbar/unwatch', 'watcher #2' );
```

#### Filtering trackers

Sometimes there may be dozens or hundreds of filters that show up in the tracker table. So you can also shrink this list by using the `debugbar/watch/filter` filter.

```php
// In this example, we'll only show tracker hooks where the filter's return value is a non-empty string
// $show is the boolean value to show or hide the row for that specific tracker
// $hook is the array of data for that specific tracker
add_filter( 'debugbar/watch/filter', function ( bool $show, array $hook ) {
    if ( !empty( $hook['value'] ) && $hook['value']['type'] === 'string' ) {
        return $show;
    }
    return FALSE;
}, 10, 2 );

// Here is an example of a $hook variable passed as the second argument
// This matches what you see in the Hooks Panel table
$hook = [
    // Array of tracker names watching this hook
    'trackers' => ['watcher #1', 'watcher #2'],
    
    // Hook Type
    'type'     => 'filter', // or action
    
    // Name of the Hook
    'name'     => 'hook/name/called',
    
    // This is the value returned after the filter is complete (the Output column in the UI)
    'value'    => [ // An $arg array
          'text' => NULL, // A representation of the return value (what you see in the Hook Table)
          'type' => 'null', // The variable type of the returned value
    ],
    
    // This is the initial value of the variable to be filtered
    'input'    => [ // An $arg array
        'type' => 'same', // the filtered value did not change as a result of the filter
    ],
    
    // This is additional context sent to the variable to be fitlred (argument 2+ in the hook)
    'args'     => [ // An array of $arg arrays
        [
            'text' => 'some text',
            'type' => 'string',
        ],
        [
            'text' => FALSE,
            'type' => 'boolean',
        ],
    ],
    
    // How many milliseconds it took to complete the code being tracked  
    'duration' => 0.15,
    
    // How many millisecond since the script was started
    'time'     => 191.01,
    
    // Name of the parent Hook (if applicable)
    'parent'   => 'parent/hook/name',
    
    // An array of $file arrays
    'subscribers' => [
        [
            'text'     => 'Plugin: Some Plugin > includes.php:218 [10] x2',
            'priority' => 10,
            'count'    => 2,
        ],
    ],
    
    // An array of $file arrays
    'publishers'  => [ 
        [
            'text' => 'Plugin: Some Other Plugin > includes/helpers.php:2474',
        ],
    ],
]
```

## Routines

This also bundles another project of mine called [WP Routines](https://github.com/pfaciana/wp-routines). You can check out that project along with [its documentation](https://github.com/pfaciana/wp-routines/wiki) for more info. WP Routines is a standalone project that does not need WP Debug Bar to work, but WP Debug Bar extends its functionality to work with Debug Bar Panels.

## Built-in Panels

### Environment

* Server Details
    * PHP and WordPress Specs (most important items)
    * PHP INI Config (most important items)
    * Database Specs
    * Web Server Specs
* WordPress Constants
    * An opinionated list of the most important constants defined in WordPress, and comparison to their default values
* PHP Extensions
    * active extensions available
* Error Reporting
    * based on `error_reporting` from php.ini

### Globals

* User Constants
    * All constants defined
* WordPress Globals
    * All global variable
* WordPress Conditionals
    * boolean conditional functions that describe the type of the current request or current WordPress instance
* Class Constants and Statics
    * All constant and static variables defined inside of classes
* PHP Constants
    * All constant defined by PHP itself

### Templating

* Current Theme info
* Current Template file for the current page
* Template Hierarchy for template file for the current page
* CSS Classes on the body tag for the current page
* Available Shortcodes
* Theme Features Registered
    * both enabled and disabled features

### Blocks

* Gutenberg Blocks on the current page with contextual information
* All Gutenberg Blocks in this WordPress install with contextual information
* Block Categories
* Block Patterns in this WordPress install
* Block Pattern Categories

### Post Types

* Post Types
* Taxonomies
* Taxonomies paired to Post Types
* Post Statuses
* Image Sizes

### User Roles & Capabilities

* User Roles
* Capabilities

### Styles & Scripts

* Registered Styles
* Registered Scripts

### Rewrite Rules

* Active Page Query
    * Matched Url Query
    * Query Vars
    * Request (GET/POST) Query on the page
* All Registered Rewrite Rules
* All Registered Rewrite Tags

### SQL Queries

* All SQL Queries run on the current page

### WordPress Hooks

* All Hooks (after plugins_loaded)
    * Optional, user defined, Hook Debugging

### Kint Debugger

* A Panel for Kint debugging output
    * [See Documentation](https://kint-php.github.io/kint/)

### WP Routines

* User defined code that streams an output to Debug Bar Panels in real-time
    * [See Documentation](https://github.com/pfaciana/wp-routines)

---

## Special Thanks

To [Tabulator](https://tabulator.info/), [Kint](https://kint-php.github.io/kint/), any open source software that provided inspiration, and of course [WordPress](https://wordpress.org/)
