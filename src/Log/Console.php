<?php

use DebugBar\Log\Timers;
use DebugBar\Log\Counters;
use DebugBar\Log\Notification;

if ( !class_exists( 'console' ) ) {

	class console
	{
		protected static $count = 0;

		protected static $icons = [
			'pass'      => 'check',
			'fail'      => 'times',
			'assert'    => 'exclamation',
			'timer'     => 'clock-o',
			'counter'   => 'calculator',
			'debug'     => 'bug',
			'info'      => 'info-circle',
			'notice'    => 'flag',
			'warning'   => 'exclamation-triangle',
			'error'     => 'minus-circle',
			'alert'     => 'exclamation-circle',
			'critical'  => 'flask',
			'emergency' => 'medkit',
		];

		public static function aliases ()
		{
			\Kint\Kint::$aliases[] = [ 'console', 'log' ];
			\Kint\Kint::$aliases[] = [ 'console', 'trace' ];
		}

		public static function hooks ()
		{
			$prefixes = [ 'console.', 'console::' ];
			$methods  = get_class_methods( 'console' );

			foreach ( $methods as $method ) {
				$callback = function () use ( $method ) {
					if ( func_num_args() === 1 && func_get_arg( 0 ) === '' ) {
						return static::$method();
					}

					return static::$method( ...func_get_args() );
				};
				if ( function_exists( 'add_filter' ) ) {
					foreach ( $prefixes as $prefix ) {
						add_filter( $prefix . $method, $callback, 10, 9e9 );
					}
				}
			}
		}

		protected static function shift_args ( $args, $count = 1 )
		{
			return array_slice( $args, $count );
		}

		protected static function call ( $method, $args )
		{
			global $kint_buffer;
			static $buffer_full = FALSE, $final_message = FALSE;

			if ( $buffer_full ) {
				return FALSE;
			}

			ob_start();
			call_user_func_array( [ 'Kint', $method ], $args );
			$buffer = ob_get_contents();
			ob_end_clean();

			$kint_buffer .= $buffer;

			if ( wp_doing_ajax() && !headers_sent() ) {
				$buffer_size = +ini_get( 'output_buffering' );
				$new_header  = 'RWD-Debug-Bar-Kint-' . static::$count++ . ': ' . rawurlencode( $buffer );
				$header_size = strlen( implode( '', headers_list() ) . $new_header );
				if ( !$buffer_full && ( empty( $buffer_size ) || ( $header_size + ( !$final_message ? 750 : 0 ) < $buffer_size ) ) ) {
					header( $new_header );
				}
				else {
					static::$count--;
					$final_message = TRUE;
					static::error( 'Buffer Size Full. Cannot add Kint debugging.' );
					$buffer_full = TRUE;
				}
			}

			return $buffer;
		}

		public static function log ()
		{
			static::call( 'dump', func_get_args() );

			if ( is_string( func_get_arg( 0 ) ) ) {
				return trim( strip_tags( func_get_arg( 0 ) ) );
			}

			return func_get_arg( 0 );
		}

		public static function trace ( $debug_backtrace_args = NULL )
		{
			if ( wp_doing_ajax() ) {
				static::warn( 'You can run BACKTRACE while doing ajax' );

				return [];
			}

			static::call( 'trace', func_get_args() );

			if ( $debug_backtrace_args === TRUE ) {
				return debug_backtrace();
			}

			if ( is_array( $debug_backtrace_args ) ) {
				return debug_backtrace( ...$debug_backtrace_args );
			}

			return $debug_backtrace_args;
		}

		public static function notification ( $message = '' )
		{
			$message = (array) $message;
			$context = static::shift_args( func_get_args() );

			return console::log( new Notification( ...$message ), ...$context );
		}

		public static function message ( $message = '', $warning = '', $context = [] )
		{
			$message = (array) $message;

			if ( empty( $context ) && is_array( $warning ) ) {
				$context = $warning;
				$warning = '';
			}

			if ( !empty( $warning ) ) {
				return console::log( new Notification( static::getIcon( Notification::WARN, ' ' . $warning ), Notification::WARN ), new Notification( ...$message ), ...$context );
			}

			return console::log( new Notification( ...$message ), ...$context );
		}

		public static function timeStart ( $label = 'default' )
		{
			return static::time( ...func_get_args() );
		}

		public static function time ( $label = 'default' )
		{
			$duration = Timers::start( $label ?? 'default' );
			$context  = static::shift_args( func_get_args() );

			static::message( static::getIcon( 'timer', ' ' . Timers::getLastMessage() ), Timers::getLastWarning(), $context );

			// Reset the timer because the previous lines of cost a small amount of time
			if ( empty( $duration ) ) {
				Timers::reset( $label ?? 'default' );
			}

			return 0;
		}

		public static function timeLog ( $label = 'default' )
		{
			$duration = Timers::get( $label ?? 'default' );
			$context  = static::shift_args( func_get_args() );

			static::message( static::getIcon( 'timer', ' ' . Timers::getLastMessage() ), Timers::getLastWarning(), $context );

			return $duration;
		}

		public static function timeEnd ( $label = 'default' )
		{
			$duration = Timers::stop( $label ?? 'default' );
			$context  = static::shift_args( func_get_args() );

			static::message( static::getIcon( 'timer', ' ' . Timers::getLastMessage() ), Timers::getLastWarning(), $context );

			return $duration;
		}

		public static function count ( $label = 'default' )
		{
			$count   = Counters::count( $label ?? 'default' );
			$context = static::shift_args( func_get_args() );

			static::message( static::getIcon( 'counter', ' ' . Counters::getLastMessage() ), NULL, $context );

			return $count;
		}

		public static function countReset ( $label = 'default' )
		{
			$count   = Counters::reset( $label ?? 'default' );
			$context = static::shift_args( func_get_args() );

			static::message( static::getIcon( 'counter', ' ' . Counters::getLastMessage() ), NULL, $context );

			return $count;
		}

		public static function test ( $condition )
		{
			$context = static::shift_args( func_get_args() );

			$message = $condition ? static::getIcon( 'pass', ' Test Passed' ) : static::getIcon( 'fail', ' Test failed' );

			if ( count( $context ) && is_string( $context[0] ) ) {
				$message .= ': ' . array_shift( $context );
			}
			else {
				$message .= '!';
			}

			static::message( [ $message, $condition ? Notification::DEBUG : Notification::ERROR ], NULL, $context );

			return $condition;
		}

		public static function assert ( $condition )
		{
			if ( $condition ) {
				return TRUE;
			}

			$context = static::shift_args( func_get_args() );
			$message = static::getIcon( 'assert', ' Assertion failed' );

			if ( count( $context ) && is_string( $context[0] ) ) {
				$message .= ': ' . array_shift( $context );
			}
			else {
				$message .= '!';
			}

			static::message( [ $message, Notification::ERROR ], NULL, $context );

			return $condition;
		}

		public static function getIcon ( $level, $suffix = '', $prefix = '' )
		{
			return $prefix . '<i class="fa fa-' . static::$icons[$level] . '" aria-hidden="true"></i>' . $suffix;
		}

		public static function level ( $level )
		{
			$context = static::shift_args( func_get_args() );
			$message = static::getIcon( $level, ' ' . ucfirst( $level ) );

			if ( count( $context ) && is_string( $context[0] ) ) {
				$message .= ': ' . array_shift( $context );
			}

			static::message( [ $message, $level ], NULL, $context );

			return trim( strip_tags( $message ) );
		}

		public static function info ()
		{
			return static::level( Notification::INFO, ...func_get_args() );
		}

		public static function debug ()
		{
			return static::level( Notification::DEBUG, ...func_get_args() );
		}

		public static function notice ()
		{
			return static::level( Notification::NOTICE, ...func_get_args() );
		}

		public static function warn ()
		{
			return static::level( Notification::WARN, ...func_get_args() );
		}

		public static function error ()
		{
			return static::level( Notification::ERROR, ...func_get_args() );
		}

		public static function alert ()
		{
			return static::level( Notification::ALERT, ...func_get_args() );
		}

		public static function critical ()
		{
			return static::level( Notification::CRITICAL, ...func_get_args() );
		}

		public static function emergency ()
		{
			return static::level( Notification::EMERGENCY, ...func_get_args() );
		}

		public static function memory ()
		{
			$memory = round( memory_get_usage( TRUE ) / 1048576 );

			static::message( "Current memory usage: {$memory} MB.", NULL, func_get_args() );

			return $memory;
		}

		public static function memoryPeak ()
		{
			$peak_memory = round( memory_get_peak_usage( TRUE ) / 1048576 );

			static::message( "Peak memory usage: {$peak_memory} MB.", NULL, func_get_args() );

			return $peak_memory;
		}

		public static function memoryBoth ()
		{
			$memory      = round( memory_get_usage( TRUE ) / 1048576 );
			$peak_memory = round( memory_get_peak_usage( TRUE ) / 1048576 );

			static::message( "Current memory usage: {$memory} MB.<br>Peak memory usage: {$peak_memory} MB.", NULL, func_get_args() );

			return [ $memory, $peak_memory ];
		}

		public static function memoryReset ()
		{
			if ( !function_exists( 'memory_reset_peak_usage' ) ) {
				static::message( 'Did Not Reset Peak Memory.', 'memory_reset_peak_usage() does not exist.', func_get_args() );

				return 0;
			}

			memory_reset_peak_usage();

			static::message( 'Reset Peak Memory.', NULL, func_get_args() );

			return 0;
		}
	}

}