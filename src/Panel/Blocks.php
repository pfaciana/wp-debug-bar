<?php

namespace DebugBar\Panel;

class Blocks extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\BlocksTrait;
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_icon = 'dashicons-block-default';
	public $_capability = 'edit_others_posts';
	protected $_panel_id;

	public function init ()
	{
		$this->setupBlockHooks();
	}

	public function render ()
	{
		$this->addTab( 'Current Blocks', [ $this, 'getCurrentBlocks' ] );
		$this->addTab( 'Block Types', [ $this, 'getBlockTypes' ] );
		$this->addTab( 'Block Categories', [ $this, 'getBlockCategories' ] );
		$this->addTab( 'Block Patterns', [ $this, 'getBlockPatterns' ] );
		$this->addTab( 'Block Pattern Categories', [ $this, 'getBlockPatternCategories' ] );
		if ( \WP_Theme_JSON_Resolver::theme_has_support() ) {
			$wp_theme = \WP_Theme_JSON_Resolver::get_merged_data();
			$this->addTab( 'Theme JSON', function () use ( $wp_theme ) { $this->getThemeJson( $wp_theme ); } );
			$this->addTab( 'Theme JSON CSS', function () use ( $wp_theme ) { $this->getThemeStyles( $wp_theme ); } );
		}
		$this->showTabs( $this->_panel_id );
	}

	public function getCurrentBlocks ()
	{
		$activeBlocks = $this->getActiveBlocks();

		?>
		<h3>Current Page Blocks</h3>
		<div id="current-blocks-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var blocks = <?= json_encode( $activeBlocks ?? [] ) ?>;
				if (blocks.length) {
					T.Create("#current-blocks-table", {
						data: blocks,
						paginationSize: 100,
						columns: [
							{
								title: 'Pos', field: 'level', formatter: 'string', headerFilterFunc: T.filters.regex, hozAlign: 'left',
								sorter: function (a, b, aRow, bRow, column, dir, sorterParams) {
									return aRow.getData().index - bRow.getData().index;
								}
							},
							{
								title: 'Block Title', field: 'title', hozAlign: 'left', headerFilter: 'input',
								formatter: function (cell, formatterParams, onRendered) {
									var content = cell.getValue() || '';
									var row = cell.getRow().getData();

									if (row.children && !row.parent) {
										content = '<span style="text-decoration: underline;">' + content + '</span>';
									}

									if (row.levels.length > 1) {
										content = '<span style="opacity: .33;">' + "&ndash; ".repeat(row.levels.length - 1) + '</span>&nbsp;' + content;
									}

									return content;
								},
							},
							{
								title: 'Block Name', field: 'name', formatter: 'string',
								cellClick: function (e, cell) {
									$('[data-tab-id="debugbar_panel_blocks_blocks_block-types"]').trigger('click');
									T.findTable('#available-blocks-table')[0].setHeaderFilterValue('name', cell.getValue());
								},
							},
							{title: 'Attrs', field: 'attrs', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'Context', field: 'context', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'Uses', field: 'usesContext', headerTooltip: 'Uses Context', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'Provides', field: 'providesContext', headerTooltip: 'Provides Context', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'Supports', field: 'supports', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'Dynamic?', field: 'isDynamic', headerTooltip: 'Is Dynamic?', formatter: 'boolean'},
							{
								title: 'Parent?', field: 'children', headerTooltip: 'Is Parent?', formatter: 'boolean',
								mutator: function (value, data, type, params, component) {
									return !!value.length;
								}
							},
							{
								title: 'Child?', field: 'parent', headerTooltip: 'Is Child?', formatter: 'boolean',
								mutator: function (value, data, type, params, component) {
									return value != null;
								}
							},
							{
								title: 'Block Time', field: 'time', formatter: 'timeMs',
								bottomCalc: function (values, data) {
									return Math.round(values.reduce(function (a, b) {
										return a + b;
									}, 0)) + ' ms';
								}
							},
							{title: 'Static HTML', field: 'html', formatter: 'html', formatterParams: {textLimit: 25, showPopup: true}},
							{title: 'Total Time', field: 'total_time', formatter: 'timeMs'},
							{title: 'Final Content', field: 'content', formatter: 'html', formatterParams: {textLimit: 25, showPopup: true}},
							{
								title: 'Initiator', field: 'initiator', formatter: 'files', hozAlign: 'left', headerFilter: 'list',
								headerFilterParams: {
									clearable: true,
									sort: 'asc',
									valuesLookup: function (cell, filterTerm) {
										var options = [...new Set(T.helpers.arrayColumn(T.helpers.arrayColumn(blocks, 'initiator'), 'text'))];
										return options.map(function (option) {
											return {value: option, label: option};
										});
									},
								},
								headerFilterFunc: function (filterTerm, rowValue, rowData, filterParams) {
									if (filterTerm == null || filterTerm == '') {
										return true;
									}
									return 'text' in rowValue ? filterTerm == rowValue.text : false;
								}
							},
							{title: 'Callback File', field: 'renderCallback', formatter: 'files', hozAlign: 'left'},
						],
					});
				} else {
					$('#current-blocks-table').before('<p><b>NOTE: There were no blocks found on this page.</b></p>');
				}
			});
		</script>
		<?php
	}

	public function getBlockTypes ()
	{
		?>
		<h3>Available Blocks</h3>
		<div id="available-blocks-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var blocks = [];
				var blockDetails = <?= json_encode( $this->getRegisteredBlocks() ?? [] ) ?>;

				if ('wp' in window && 'blocks' in window.wp && 'getBlockTypes' in wp.blocks && wp.blocks.getBlockTypes().length) {
					$.each(wp.blocks.getBlockTypes(), function () {
						blocks.push({
							name: this.name,
							title: this.title,
							description: this.description,
							category: this.category,
							attributes: this.attributes,
							variations: this.variations,
							parent: (this.parent && this.parent.length) ? this.parent : null,
							supports: (this.supports && Object.keys(this.supports).length) ? this.supports : null,
							providesContext: (this.providesContext && Object.keys(this.providesContext).length) ? this.providesContext : null,
							usesContext: (this.usesContext && this.usesContext.length) ? this.usesContext : null,
							renderCallback: this.name in blockDetails && blockDetails[this.name].renderCallback ? blockDetails[this.name].renderCallback : false,
							isDynamic: !!(this.name in blockDetails && blockDetails[this.name].renderCallback),
							isParent: !!(this.name in blockDetails && blockDetails[this.name].isParent),
							isChild: !!(this.parent && this.parent.length),
							apiVersion: this.apiVersion,
						});
					});
				} else {
					$('#available-blocks-table').before('<p><b>NOTE: This is only partial data. Some data is only available when the block editor is active on the page.</b></p>');
					blocks = Object.values(blockDetails);
				}

				if (blocks.length) {
					T.Create("#available-blocks-table", {
						data: blocks,
						layout: 'fitDataStretch',
						columns: [
							{title: 'title', field: 'title', hozAlign: 'left', frozen: true, formatter: 'string'},
							{title: 'name', field: 'name', frozen: true, formatter: 'string'},
							{title: 'category', field: 'category', formatter: 'list'},
							{title: 'Dynamic?', field: 'isDynamic', headerTooltip: 'Is Dynamic?', formatter: 'boolean'},
							{title: 'Parent?', field: 'isParent', headerTooltip: 'Is Parent?', formatter: 'boolean'},
							{title: 'Child?', field: 'isChild', headerTooltip: 'Is Child?', formatter: 'boolean'},
							{title: 'parent', field: 'parent', hozAlign: 'left', formatter: 'object'},
							{title: 'attributes', field: 'attributes', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'variations', field: 'variations', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'supports', field: 'supports', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'providesContext', field: 'providesContext', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'usesContext', field: 'usesContext', formatter: 'object', formatterParams: {showKeys: true}},
							{title: 'renderCallback', field: 'renderCallback', formatter: 'files'},
							{title: 'description', field: 'description', hozAlign: 'left', formatter: 'text'},
							{title: 'apiVersion', field: 'apiVersion', formatter: 'minMax'},
						],
					});
				}
			});
		</script>
		<?php
	}

	public function getBlockPatterns ()
	{
		$patternDirs     = [];
		$default_headers = [
			'title'         => 'Title',
			'slug'          => 'Slug',
			'description'   => 'Description',
			'viewportWidth' => 'Viewport Width',
			'categories'    => 'Categories',
			'keywords'      => 'Keywords',
			'blockTypes'    => 'Block Types',
			'inserter'      => 'Inserter',
			'file'          => 'File',
		];
		$header_keys     = array_keys( $default_headers );
		$bySlug          = [];
		$byTitle         = [];

		$stylesheet = get_stylesheet();
		$template   = get_template();
		if ( $stylesheet !== $template ) {
			$patternDirs['stylesheet'] = wp_get_theme( $stylesheet )->get_stylesheet_directory() . '/patterns/';
		}
		$patternDirs['template'] = wp_get_theme( $template )->get_template_directory() . '/patterns/';
		if ( defined( 'ABSPATH' ) && defined( 'WPINC' ) ) {
			$patternDirs['core'] = wp_normalize_path( ABSPATH . '/' . WPINC . '/block-patterns/' );
		}

		foreach ( $patternDirs as $type => $dirpath ) {
			if ( is_dir( $dirpath ) && is_readable( $dirpath ) ) {
				if ( $files = glob( $dirpath . '*.php' ) ) {
					foreach ( $files as $file ) {
						$file = wp_normalize_path( $file );
						if ( $type === 'core' ) {
							$pattern_data = ( require $file );
						}
						else {
							$pattern_data = get_file_data( $file, $default_headers );
						}
						if ( array_key_exists( 'slug', $pattern_data ) && !empty( $pattern_data['slug'] ) ) {
							$bySlug[$pattern_data['slug']] = $this->getFileLinkArray( $file );
						}
						elseif ( array_key_exists( 'title', $pattern_data ) && !empty( $pattern_data['title'] ) ) {
							$byTitle[$pattern_data['title']] = $this->getFileLinkArray( $file );
						}
					}
				}
			}
		}

		$patterns = \WP_Block_Patterns_Registry::get_instance()->get_all_registered();
		foreach ( $patterns as &$pattern ) {
			foreach ( $pattern as $key => &$value ) {
				if ( empty( $value ) || !in_array( $key, $header_keys ) ) {
					unset( $pattern[$key] );
				}
			}
			$pattern = wp_parse_args( $pattern, [
				'title'         => '', // string
				'slug'          => '', // string
				'description'   => '', // string
				'categories'    => [], // array
				'keywords'      => [], // array,
				'blockTypes'    => [], // array,
				'viewportWidth' => NULL, // int
				'inserter'      => FALSE, // boolean
				'file'          => FALSE, // string
			] );
			foreach ( $pattern as $key => &$value ) {
				if ( is_array( $value ) ) {
					$value = implode( ' - ', $value );
				}
				if ( $key === 'file' ) {
					if ( !empty( $pattern['slug'] ) && array_key_exists( $pattern['slug'], $bySlug ) ) {
						$value = $bySlug[$pattern['slug']];
					}
					elseif ( !empty( $pattern['title'] ) && array_key_exists( $pattern['title'], $byTitle ) ) {
						$value = $byTitle[$pattern['title']];
					}
				}
			}

		}

		?>
		<h3>Registered Patterns</h3>
		<div id="block-patterns-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var patterns = <?= json_encode( $patterns ?? [] ) ?>;
				if (patterns.length) {
					T.Create("#block-patterns-table", {
						data: patterns,
						columns: [
							{title: 'title', field: 'title', hozAlign: 'left', frozen: true, formatter: 'string'},
							{title: 'slug', field: 'slug', frozen: true, formatter: 'string'},
							{title: 'categories', field: 'categories', formatter: 'list'},
							{title: 'keywords', field: 'keywords', hozAlign: 'left', formatter: 'string'},
							{title: 'blockTypes', field: 'blockTypes', hozAlign: 'left', formatter: 'string'},
							{title: 'viewportWidth', field: 'viewportWidth', formatter: 'minMax'},
							{title: 'inserter', field: 'inserter', formatter: 'boolean'},
							{title: 'file', field: 'file', formatter: 'file'},
							{title: 'description', field: 'description', formatter: 'text'},
						],
					});
				}
			});
		</script>
		<?php
	}

	public function getBlockCategories ()
	{
		global $post;
		?>
		<h3>Block Categories</h3>
		<div id="block-categories-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var blockCategories = <?= json_encode( get_block_categories( $post ) ?? [] ) ?>;

				if (blockCategories.length) {
					T.Create("#block-categories-table", {
						data: blockCategories,
						columns: [
							{title: 'slug', field: 'slug', frozen: true, formatter: 'string'},
							{title: 'title', field: 'title', hozAlign: 'left', frozen: true, formatter: 'string'},
						],
					});
				}
			});
		</script>
		<?php
	}

	public function getBlockPatternCategories ()
	{
		global $post;
		?>
		<h3>Block Pattern Categories</h3>
		<div id="block-pattern-categories-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var patternCategories = <?= json_encode( \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered() ?? [] ) ?>;

				if (patternCategories.length) {
					T.Create("#block-pattern-categories-table", {
						data: patternCategories,
						columns: [
							{title: 'name', field: 'name', frozen: true, formatter: 'string'},
							{title: 'label', field: 'label', hozAlign: 'left', frozen: true, formatter: 'string'},
						],
					});
				}
			});
		</script>
		<?php
	}

	public function getThemeJson ( $wp_theme )
	{
		$json = $wp_theme->get_raw_data();

		?>
		<pre style="padding: 20px; font-weight:bold;"><?= wp_unslash( json_encode( $json, JSON_PRETTY_PRINT ) ) ?></pre>
		<?php
	}

	public function getThemeStyles ( $wp_theme )
	{
		$css = str_replace( [ '{', ';', ' }', "\t}", ], [ " {\n\t", ";\n\t", '}', "}\n\n" ], $wp_theme->get_stylesheet() );

		?>
		<pre style="padding: 20px; font-weight:bold;"><?= $css ?></pre>
		<?php
	}
}