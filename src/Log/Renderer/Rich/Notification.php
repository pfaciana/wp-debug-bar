<?php

namespace DebugBar\Log\Renderer\Rich;

use Kint\Object\BasicObject;

class Notification extends \Kint\Renderer\Rich\Plugin implements \Kint\Renderer\Rich\ObjectPluginInterface
{
	public function renderObject ( BasicObject $o )
	{
		/** @var \DebugBar\Log\Notification $renderer */
		if ( !property_exists( $o, 'value' ) || gettype( $renderer = $o->value ) !== 'object' || get_class( $renderer ) !== 'DebugBar\Log\Notification' ) {
			return '<dl>' . $this->renderer->renderChildren( $o ) . $this->renderer->renderHeader( $o ) . '</dl>';
		}

		$classPrefix = 'kint-';
		if ( !empty( $header = $renderer->renderHeader() ) ) {
			$header = '<div class="' . $renderer->getStateClass( $classPrefix ) . '">' . $header . '</div>';
		}
		$children = $renderer->renderChildren();

		return '<dl class="' . $renderer->getParentClasses( $classPrefix ) . '">' . $header . $children . '</dl>';
	}
}
