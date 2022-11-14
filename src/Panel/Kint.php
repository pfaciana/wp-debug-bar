<?php

namespace DebugBar\Panel;

class Kint extends \Debug_Bar_Panel
{
	public $_title = 'Kint';
	public $_icon = 'fa fa-bug';

	public function render ()
	{
		echo $GLOBALS['kint_buffer'] ?? '';
	}
}