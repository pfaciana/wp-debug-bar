<?php

namespace DebugBar\Log\Parser;

use Kint\Object\BasicObject;

class Unpack extends \Kint\Parser\Plugin
{
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
		if ( ( !property_exists( $o, 'name' ) || !property_exists( $o, 'access_path' ) || $o->name !== $o->access_path ) && //
			( str_starts_with( $o->name, 'reset(' ) || str_starts_with( $o->name, 'array_values(' ) ) ) {
			return;
		}

		if ( str_starts_with( $o->name, 'reset(' ) && str_ends_with( $o->name, ')' ) ) {
			$o->name = $o->access_path = $this->str_replace_first( 'reset(', '...', $o->name );
			$o->name = $o->access_path = $this->str_replace_first( ')', '[0]', $o->name );
		}

		if ( str_starts_with( $o->name, 'array_values(' ) && str_ends_with( $o->name, ']' ) ) {
			$o->name = $o->access_path = $this->str_replace_first( 'array_values(', '...', $o->name );
			$o->name = $o->access_path = $this->str_replace_first( ')', '', $o->name );
		}
	}

	public function str_replace_first ( $find, $replace, $context )
	{
		if ( ( $pos = strpos( $context, $find ) ) !== FALSE ) {
			return substr_replace( $context, $replace, $pos, strlen( $find ) );
		}

		return $context;
	}
}
