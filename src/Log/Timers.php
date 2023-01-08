<?php

namespace DebugBar\Log;

class Timers
{
	protected static $timers = [];
	protected static $message = NULL;
	protected static $warning = NULL;

	public static function start ( $label = 'default' )
	{
		if ( array_key_exists( $label, static::$timers ) ) {
			$duration = static::get( $label );

			static::$warning = "Timer '{$label}': Already exists.";

			return $duration;
		}

		static::clearMessages();

		static::$message = "Timer '{$label}': Started.";

		static::$timers[$label] = microtime( TRUE );

		return 0;
	}

	public static function get ( $label = 'default' )
	{
		if ( !array_key_exists( $label, static::$timers ) ) {
			$duration = static::start( $label );

			static::$warning = "Timer '{$label}': Does not exist.";

			return $duration;
		}

		static::clearMessages();

		$duration = microtime( TRUE ) - static::$timers[$label];

		static::$message = "Timer '{$label}': " . static::formatTime( $duration * 1000 ) . ".";

		return $duration;
	}

	public static function stop ( $label = 'default' )
	{
		$duration = static::get( $label );

		static::$message = "Timer '{$label}': " . static::formatTime( $duration * 1000 ) . " - Ended.";

		unset( static::$timers[$label] );

		return $duration;
	}

	public static function reset ( $label = 'default' )
	{
		if ( array_key_exists( $label, static::$timers ) ) {
			static::$timers[$label] = microtime( TRUE );
		}
	}

	public static function formatTime ( $duration, $decimals = NULL, $unit = 'ms' )
	{
		if ( is_null( $decimals ) ) {
			if ( $duration >= 100 ) {
				$decimals = 0;
			}
			elseif ( $duration >= 10 ) {
				$decimals = 1;
			}
			else {
				$decimals = 2;
			}
		}

		return trim( number_format( $duration, $decimals ) . ' ' . ( $unit ?? '' ) );
	}

	protected static function clearMessages ()
	{
		static::$message = NULL;
		static::$warning = NULL;
	}

	public static function getLastMessage ()
	{
		return static::$message;
	}

	public static function getLastWarning ()
	{
		return static::$warning;
	}
}