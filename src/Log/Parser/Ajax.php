<?php

namespace DebugBar\Log\Parser;

use Kint\CallFinder;
use Kint\Object\BasicObject;

class Ajax extends \Kint\Parser\Plugin
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
		if ( wp_doing_ajax() ) {
			$o->hints[] = 'ajax';
		}
	}
}
