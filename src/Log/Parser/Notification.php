<?php

namespace DebugBar\Log\Parser;

use Kint\Object\BasicObject;

class Notification extends \Kint\Parser\Plugin
{
	public function getTypes ()
	{
		return [ 'object' ];
	}

	public function getTriggers ()
	{
		return \Kint\Parser\Parser::TRIGGER_SUCCESS;
	}

	public function parse ( &$var, BasicObject &$o, $trigger )
	{
		if ( !property_exists( $o, 'classname' ) || $o->classname !== 'DebugBar\Log\Notification' ) {
			return;
		}

		$o->hints[] = 'notification';
		$o->value   = $var;
	}
}
