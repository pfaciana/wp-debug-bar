<?php

namespace DebugBar\Log;

class Counters
{
	protected static $counters = [];
	protected static $message = NULL;

	public static function count ( $label = 'default' )
	{
		static::clearMessages();

		if ( !array_key_exists( $label, static::$counters ) ) {
			static::$counters[$label] = 1;
		}
		else {
			static::$counters[$label] += 1;
		}

		static::$message = "Counter '{$label}': " . static::$counters[$label] . ".";

		return static::$counters[$label];
	}

	public static function reset ( $label = 'default' )
	{
		static::clearMessages();

		unset( static::$counters[$label] );

		static::$message = "Counter '{$label}': Reset.";

		return 0;
	}

	protected static function clearMessages ()
	{
		static::$message = NULL;
	}

	public static function getLastMessage ()
	{
		return static::$message;
	}
}