<?php

namespace DebugBar\Panel;

class Kint extends \Debug_Bar_Panel
{
	public $_title = 'Kint';
	public $_icon = 'fa fa-bug';
	public $init_only_if_active = TRUE;
	protected $ajaxKey = 'Kint';

	function __construct ()
	{
		parent::__construct();
	}

	public function init ()
	{
		add_filter( 'rdb/ajax/response', [ $this, 'ajaxRender' ] );
	}

	public function ajaxRender ( $response = [] )
	{
		$king_buffer = $GLOBALS['kint_buffer'] ?? '';

		if ( !empty( $king_buffer ) ) {
			$response[$this->ajaxKey] = $king_buffer;
		}

		return $response;
	}

	public function render ()
	{
		echo $GLOBALS['kint_buffer'] ?? '';
	}
}