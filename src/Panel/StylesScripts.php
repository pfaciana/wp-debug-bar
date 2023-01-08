<?php

namespace DebugBar\Panel;

class StylesScripts extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_icon = 'dashicons-editor-code';
	public $_panel_id;

	public function render ()
	{
		$this->addTab( 'Styles', [ $this, 'setStyles' ] );
		$this->addTab( 'Scripts', [ $this, 'setScripts' ] );
		$this->showTabs( $this->_panel_id );
	}

	protected function stringify ( $input )
	{
		if ( is_null( $input ) ) {
			return 'NULL';
		}

		if ( $input === FALSE ) {
			return 'FALSE';
		}

		if ( $input === TRUE ) {
			return 'TRUE';
		}

		return (string) $input;
	}

	protected function setStyles ()
	{
		global $wp_styles;

		$registered = $wp_styles->registered;
		$loaded     = array_unique( array_merge( $wp_styles->queue, $wp_styles->done ) );
		sort( $loaded );

		$styles = [];

		/** @var \_WP_Dependency $dependency */
		foreach ( $registered as $dependency ) {
			$styles[] = [
				'loaded' => in_array( $dependency->handle, $loaded ),
				'handle' => $dependency->handle,
				'src'    => is_string( $dependency->src ) ? $dependency->src : '',
				'deps'   => (array) $dependency->deps,
				'ver'    => $this->stringify( $dependency->ver ),
				'args'   => $this->stringify( $dependency->args ),
				'extra'  => (array) $dependency->extra,
			];
		}

		?>
		<h3>WP Styles</h3>
		<div id="wp-styles-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var wpStyles = <?= json_encode( array_values( $styles ?? [] ) ) ?>;

				if (wpStyles.length) {
					T.Create("#wp-styles-table", {
						data: wpStyles,
						layout: 'fitDataFill',
						columns: [
							{title: 'Loaded', field: 'loaded', formatter: 'boolean'},
							{title: 'Handle', field: 'handle', formatter: 'string'},
							{title: 'Location', field: 'src', minWidth: 375, formatter: 'url',},
							{title: 'Dependencies', field: 'deps', hozAlign: 'center', formatter: 'object'},
							{title: 'Version', field: 'ver', formatter: 'string'},
							{title: 'Property', field: 'args', formatter: 'string'},
							{title: 'Extra', field: 'extra', formatter: 'object', formatterParams: {showKeys: true}},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function setScripts ()
	{
		global $wp_scripts;

		$registered = $wp_scripts->registered;
		$loaded     = array_unique( array_merge( $wp_scripts->queue, $wp_scripts->done ) );
		sort( $loaded );

		$scripts = [];

		/** @var \_WP_Dependency $dependency */
		foreach ( $registered as $dependency ) {
			$scripts[] = [
				'loaded'    => in_array( $dependency->handle, $loaded ),
				'in_footer' => in_array( $dependency->handle, $wp_scripts->in_footer ),
				'handle'    => $dependency->handle,
				'src'       => is_string( $dependency->src ) ? $dependency->src : '',
				'deps'      => (array) $dependency->deps,
				'ver'       => $this->stringify( $dependency->ver ),
				'args'      => $this->stringify( $dependency->args ),
				'extra'     => (array) $dependency->extra,
			];
		}

		?>
		<h3>WP Scripts</h3>
		<div id="wp-scripts-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var wpScripts = <?= json_encode( array_values( $scripts ?? [] ) ) ?>;

				if (wpScripts.length) {
					T.Create("#wp-scripts-table", {
						data: wpScripts,
						layout: 'fitDataFill',
						columns: [
							{title: 'Loaded', field: 'loaded', formatter: 'boolean'},
							{title: 'Footer', field: 'in_footer', formatter: 'boolean'},
							{title: 'Handle', field: 'handle', hozAlign: 'left', formatter: 'string'},
							{title: 'Location', field: 'src', formatter: 'url'},
							{title: 'Dependencies', field: 'deps', formatter: 'object'},
							{title: 'Version', field: 'ver', formatter: 'string'},
							{title: 'Property', field: 'args', formatter: 'string'},
							{title: 'Extra', field: 'extra', formatter: 'object', formatterParams: {showKeys: true}},
						],
					});
				}
			});
		</script>
		<?php
	}
}