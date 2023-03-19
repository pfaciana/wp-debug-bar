<?php

namespace DebugBar\Log\Parser;

use Kint\CallFinder;
use Kint\Object\BasicObject;

class Hooks extends \Kint\Parser\Plugin
{
	protected $wp_hooks = [
		'do_action',
		'apply_filters',
		'do_action_ref_array',
		'apply_filters_ref_array',
	];
	protected $aliases = [
		[ 'console', 'log' ],
	];

	public function getTypes ()
	{
		return [ 'null', 'boolean', 'string', 'integer', 'double', 'array', 'object', 'resource' ];
	}

	public function getTriggers ()
	{
		return \Kint\Parser\Parser::TRIGGER_SUCCESS;
	}

	public function parse ( &$var, BasicObject &$o, $trigger )
	{
		if ( ( !property_exists( $o, 'name' ) || !property_exists( $o, 'access_path' ) || //
			$o->name !== NULL || empty( $o->access_path ) || $o->access_path[0] !== '$' ) ) {
			return;
		}

		$found  = FALSE;
		$frame  = [];
		$called = NULL;
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $frame ) {
			$called = strtolower( $frame['function'] );
			if ( isset( $frame['class'] ) ) {
				$called = [ strtolower( $frame['class'] ), $called ];
			}
			if ( $found && in_array( $called, $this->wp_hooks ) ) {
				break;
			}
			if ( in_array( $called, $this->aliases, TRUE ) ) {
				$found = TRUE;
			}
		}

		if ( substr( $called, -10 ) === '_ref_array' ) {
			return;
		}

		if ( empty( $called ) || empty( $found ) || empty( $frame ) || !is_readable( $frame['file'] ) || //
			empty( $calls = CallFinder::getFunctionCalls( file_get_contents( $frame['file'] ), $frame['line'], $called ) ) || //
			!is_array( $calls ) || !array_key_exists( 'parameters', $call = $calls[0] ) || empty( $params = $call['parameters'] ) || //
			!ctype_digit( $pos = ltrim( $o->access_path, '$' ) ) || !array_key_exists( ++$pos, $params ) ) {
			return;
		};

		$o->name        = $params[$pos]['name'];
		$o->access_path = "\${$pos}";
	}
}
