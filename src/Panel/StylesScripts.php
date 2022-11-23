<?php

namespace DebugBar\Panel;

use DebugBar\Traits\FormatTrait;
use DebugBar\Traits\LayoutTrait;

class StylesScripts extends \Debug_Bar_Panel
{
	use FormatTrait;
	use LayoutTrait;

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
					new Tabulator("#wp-styles-table", {
						data: wpStyles,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [5, 10, 20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							T.filters.boolean({title: 'Loaded', field: 'loaded', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center',}),
							{title: 'Handle', field: 'handle', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',},
							{
								title: 'Location', field: 'src', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								minWidth: 375, formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null || cell.getValue() === '') {
										return '';
									}
									return `<a href="${cell.getValue()}" target="_blank">${cell.getValue()}</a>`;
								},
							},
							{title: 'Deps', field: 'deps', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', ...T.common.listArray},
							{title: 'Ver', field: 'ver', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{title: 'Args', field: 'args', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{title: 'Extra', field: 'extra', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', ...T.common.listArray},
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
					new Tabulator("#wp-scripts-table", {
						data: wpScripts,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [5, 10, 20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							T.filters.boolean({title: 'Loaded', field: 'loaded', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center',}),
							T.filters.boolean({title: 'Footer', field: 'in_footer', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center',}),
							{title: 'Handle', field: 'handle', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',},
							{
								title: 'Location', field: 'src', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								minWidth: 375, formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null || cell.getValue() === '') {
										return '';
									}
									return `<a href="${cell.getValue()}" target="_blank">${cell.getValue()}</a>`;
								},
							},
							{title: 'Deps', field: 'deps', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', ...T.common.listArray},
							{title: 'Ver', field: 'ver', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{title: 'Args', field: 'args', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{title: 'Extra', field: 'extra', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', ...T.common.listArray},
						],
					});
				}
			});
		</script>
		<?php
	}
}