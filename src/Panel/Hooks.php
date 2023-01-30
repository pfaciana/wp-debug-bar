<?php

namespace DebugBar\Panel;

class Hooks extends \Debug_Bar_Panel
{
	public $_icon = 'fa fa-filter';

	/**
	 * @var callable
	 */
	protected $output;

	public function setRenderCallback ( $callback )
	{
		$this->output = $callback;
	}

	public function render ()
	{
		is_callable( $this->output ) && ( $this->output )( $this->get_panel_class() );
	}
}