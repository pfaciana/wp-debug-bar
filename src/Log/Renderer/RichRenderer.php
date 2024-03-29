<?php

namespace DebugBar\Log\Renderer;

class RichRenderer extends \Kint\Renderer\RichRenderer
{
	use \DebugBar\Traits\FormatTrait;

	public function postRender ()
	{
		if ( !\DebugBar\DebugBar::wp_doing_ajax() || \DebugBar\DebugBar::running_for_ajax() ) {
			return parent::postRender();
		}

		/*
		// TODO: make a setting to turn this on/off to save on ajax payload size, for now leaving off
		// NOTE: Using the `running_for_ajax` check now to as the setting for simple or details responses

		$frame  = [];
		$frames = array_reverse( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) );
		foreach ( $frames as $frame ) {
			$called = strtolower( $frame['function'] );
			if ( isset( $frame['class'] ) ) {
				$called = [ strtolower( $frame['class'] ), $called ];
			}
			if ( in_array( $called, \Kint\Kint::$aliases, TRUE ) ) {
				break;
			}
		}

		if ( empty( $frame ) ) {
			return '</div>';
		}

		$frame['link_class'] = 'kint-ide-link';

		$func = ( $frame['class'] ?? '' ) . ( $frame['type'] ?? '' ) . $frame['function'] . '()';

		return '<footer class="kint-show"> Called from ' . $this->getFileLinkTag( $frame ) . ' ' . $func . '</footer></div>';
		*/

		return '</div>';
	}
}
