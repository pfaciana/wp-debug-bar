<?php

namespace DebugBar\Panel;

class Routine extends \Debug_Bar_Panel
{
	protected $output;

	public function setRenderCallback ( $callback )
	{
		$this->output = $callback;
	}

	public function render ()
	{
		is_callable( $this->output ) && ( $this->output )();
	}
}