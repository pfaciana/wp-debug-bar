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

		static::$message = "Timer '{$label}': " . static::formatTime( $duration ) . ".";

		return $duration;
	}

	public static function stop ( $label = 'default' )
	{
		$duration = static::get( $label );

		static::$message = "Timer '{$label}': " . static::formatTime( $duration ) . " - Ended.";

		unset( static::$timers[$label] );

		return $duration;
	}

	public static function formatTime ( $duration )
	{
		return round( $duration, 3 ) . ' ms';
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