<?php

namespace DebugBar\Panel;

use DebugBar\Traits\FormatTrait;
use DebugBar\Traits\LayoutTrait;

class Blocks extends \Debug_Bar_Panel
{
	use FormatTrait;
	use LayoutTrait;

	public $_icon = 'dashicons-block-default';
	public $_capability = 'edit_others_posts';

	protected $registeredBlocks = [];

	protected function getRegisteredBlocks ()
	{
		if ( !empty( $this->registeredBlocks ) ) {
			return $this->registeredBlocks;
		}

		$parentBlocks = [];
		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as &$block ) {
			/** @var \WP_Block_Type $block */
			if ( !empty( $block->parent ) ) {
				$parentBlocks = array_merge( $parentBlocks, is_array( $block->parent ) ? $block->parent : [ $block->parent ] );
			}
		}

		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as &$block ) {
			/** @var \WP_Block_Type $block */
			$this->registeredBlocks[$block->name] = [
				'name'            => $block->name,
				'title'           => $block->title,
				'description'     => $block->description,
				'category'        => $block->category,
				'attributes'      => $block->attributes,
				'variations'      => $block->variations,
				'parent'          => $block->parent,
				'supports'        => $block->supports,
				'providesContext' => $block->provides_context,
				'usesContext'     => $block->uses_context,
				'renderCallback'  => $block->is_dynamic() ? $this->getFileLinkArray( ...$this->getFileLine( $block->render_callback ) ) : FALSE,
				'isDynamic'       => $block->is_dynamic(),
				'isParent'        => in_array( $block->name, $parentBlocks ),
				'isChild'         => !empty( $block->parent ),
				'apiVersion'      => $block->api_version,
			];
		}

		return $this->registeredBlocks;
	}

	protected function getRegisteredBlock ( $name, $prop = NULL, $default = NULL )
	{
		$registeredBlocks = $this->getRegisteredBlocks();

		if ( !array_key_exists( $name, $registeredBlocks ) || ( isset( $prop ) && !array_key_exists( $prop, $registeredBlocks[$name] ) ) ) {
			return $default;
		}

		return isset( $prop ) ? $registeredBlocks[$name][$prop] : $registeredBlocks[$name];
	}


	/* --- Generator --- */

	/**
	 * @var \Generator
	 */
	protected $innerBlocksGenerator = NULL;
	protected $innerBlocksCounter = 0; // Starts at 0 because we only iterate through children
	protected $innerBlocksIndex = 0;

	protected function generateInnerBlocks ( &$blocks )
	{
		foreach ( $blocks as &$block ) {
			$localScopeCounter = ++$this->innerBlocksCounter;
			if ( array_key_exists( 'innerBlocks', $block ) && !empty( $block['innerBlocks'] ) ) {
				yield from $this->generateInnerBlocks( $block['innerBlocks'] );
			}
			yield $localScopeCounter;
		}
	}

	/* --- Process Blocks --- */

	protected $blocks = [];
	protected $blockCurrentIndex = -1;
	protected $currentRootIndex = -1;
	protected $currentRootCounter = -1;

	protected function removeACF ( $attrs )
	{
		foreach ( $attrs as $key => &$value ) {
			if ( is_string( $value ) ) {
				if ( $key[0] === '_' && str_starts_with( $value, 'field_' ) ) {
					unset( $attrs[$key] );
				}
			}
			elseif ( is_array( $value ) ) {
				$value = $this->removeACF( $value );
			}
		}

		return $attrs;
	}

	protected function addBlockToList ( $block, $levels )
	{
		$this->blocks[] = [
			'index'           => count( $this->blocks ),
			'level'           => implode( '.', $levels ) . '.',
			'levels'          => $levels,
			'title'           => $this->getRegisteredBlock( $block['blockName'], 'title' ),
			'name'            => $block['blockName'],
			'attrs'           => $this->removeACF( $block['attrs'] ),
			'isDynamic'       => !!$this->getRegisteredBlock( $block['blockName'], 'renderCallback' ),
			'parent'          => NULL,
			'children'        => [],
			'html'            => $block['innerHTML'],
			'total_time'      => NULL,
			'time'            => NULL,
			'supports'        => $this->getRegisteredBlock( $block['blockName'], 'supports' ),
			'providesContext' => $this->getRegisteredBlock( $block['blockName'], 'providesContext' ),
			'usesContext'     => $this->getRegisteredBlock( $block['blockName'], 'usesContext' ),
			'renderCallback'  => $this->getRegisteredBlock( $block['blockName'], 'renderCallback' ),
		];
	}

	protected function addBlocksRecursively ( $blocks, $levels, $isRoot = TRUE )
	{
		!$isRoot && ( $levels[] = 0 );
		foreach ( $blocks as $index => $block ) {
			!$isRoot && ( $levels[array_key_last( $levels )] = $index + 1 );
			$this->addBlockToList( $block, $levels );
			if ( array_key_exists( 'innerBlocks', $block ) && !empty( $block['innerBlocks'] ) ) {
				$this->addBlocksRecursively( $block['innerBlocks'], $levels, FALSE );
			}
		}
	}

	protected function addDataToBlock ( $data, $index = NULL )
	{
		$this->blocks[$index = $index ?? $this->blockCurrentIndex] = array_merge( $this->blocks[$index], $data );
	}

	protected function blockStart ( $index = NULL )
	{
		$this->addDataToBlock( [ 'total_time' => microtime( TRUE ), ], $index );
	}

	protected function blockCompleted ( $index = NULL )
	{
		$this->addDataToBlock( [
			'total_time' => round( microtime( TRUE ) - ( $this->blocks[$index]['total_time'] ?? microtime( TRUE ) ), 5 ),
		], $index );
	}

	protected function isNullBlock ( $block )
	{
		return empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ) );
	}

	/* --- Block Hooks --- */

	public function init ()
	{
		add_filter( 'pre_render_block', [ $this, 'pre_render_block_pre' ], PHP_INT_MIN, 3 );
		add_filter( 'pre_render_block', [ $this, 'pre_render_block_post' ], PHP_INT_MAX, 3 );
		add_filter( 'render_block_context', [ $this, 'render_block_context' ], PHP_INT_MAX, 3 );
		add_filter( 'render_block', [ $this, 'render_block' ], PHP_INT_MAX, 3 );
	}

	public function pre_render_block_pre ( $pre_render, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->blockCurrentIndex++;
			if ( empty( $parent_block ) ) {
				$this->currentRootCounter++;
				$this->currentRootIndex = $this->blockCurrentIndex;
				$this->addBlocksRecursively( [ $parsed_block ], [ $this->currentRootCounter + 1 ] );
				if ( !empty( $parsed_block['innerBlocks'] ) ) {
					$this->innerBlocksGenerator = $this->generateInnerBlocks( $parsed_block['innerBlocks'] );
					$this->innerBlocksCounter   = 0;
					$this->innerBlocksIndex     = 0;
				}
			}
		}

		return $pre_render;
	}

	public function pre_render_block_post ( $pre_render, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->blockStart();
			if ( !is_null( $pre_render ) ) {
				$this->addDataToBlock( [ 'content' => $pre_render, ] );
				$this->blockCompleted();
			}
		}

		return $pre_render;
	}

	public function render_block_context ( $context, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->addDataToBlock( [ 'context' => $context, ] );
		}

		return $context;
	}

	public function render_block ( $block_content, $parsed_block, $WP_Block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			if ( !empty( $this->innerBlocksGenerator ) ) {
				if ( $this->innerBlocksGenerator->valid() ) {
					$this->innerBlocksIndex = $this->innerBlocksGenerator->current();
					$this->innerBlocksGenerator->next();
				}
				else { // reset and run the root parent node
					$this->innerBlocksGenerator = NULL;
					$this->innerBlocksCounter   = 0;
					$this->innerBlocksIndex     = 0;
				}
			}

			$index = $this->currentRootIndex + $this->innerBlocksIndex;
			$this->addDataToBlock( [ 'content' => $block_content, ], $index );
			$this->blockCompleted( $index );
		}

		return $block_content;
	}


	/* ------- */


	public function render ()
	{
		$this->addTab( 'Current Blocks', [ $this, 'getCurrentBlocks' ] );
		$this->addTab( 'Block Types', [ $this, 'getBlockTypes' ] );
		$this->addTab( 'Block Patterns', [ $this, 'getBlockPatterns' ] );
		$this->addTab( 'Block Categories', [ $this, 'getBlockCategories' ] );
		if ( \WP_Theme_JSON_Resolver::theme_has_support() ) {
			$wp_theme = \WP_Theme_JSON_Resolver::get_merged_data();
			$this->addTab( 'Theme JSON', function () use ( $wp_theme ) { $this->getThemeJson( $wp_theme ); } );
			$this->addTab( 'Theme JSON CSS', function () use ( $wp_theme ) { $this->getThemeStyles( $wp_theme ); } );
		}
		$this->showTabs( $this->_panel_id );
	}

	public function getCurrentBlocks ()
	{
		$lookup = [];
		foreach ( $this->blocks as $index => &$block ) {
			$lookup[$block['level']] = $index;
			if ( count( $block['levels'] ) > 1 ) {
				$block['parent'] = ( $parent = $lookup[implode( '.', array_slice( $block['levels'], 0, -1 ) ) . '.'] );
				( $this->blocks[$parent]['children'][] = $index );
			}
		}

		foreach ( $this->blocks as &$block ) {
			$children_time = 0;
			if ( !empty( $block['children'] ) ) {
				foreach ( $block['children'] as $child_index ) {
					$children_time += $this->blocks[$child_index]['total_time'];
				}
			}
			$block['time'] = $block['total_time'] - $children_time;
		}

		?>
		<h3>Current Page Blocks</h3>
		<div id="current-blocks-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var blocks = <?= json_encode( $this->blocks ?? [] ) ?>;
				if (blocks.length) {
					T.Create("#current-blocks-table", {
						data: blocks,
						paginationSize: 100,
						columns: [
							{
								title: 'Pos', field: 'level', formatter: 'string', headerFilterFunc: T.filters.regex,
								sorter: function (a, b, aRow, bRow, column, dir, sorterParams) {
									return aRow.getData().index - bRow.getData().index;
								}
							},
							{title: 'Block Title', field: 'title', hozAlign: 'left', formatter: 'string'},
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
							{title: 'Block Time', field: 'time', formatter: 'timeMs'},
							{title: 'Block HTML', field: 'html', formatter: 'string', formatterParams: {textLimit: 25, htmlChars: true}},
							{title: 'Total Time', field: 'total_time', formatter: 'timeMs'},
							{title: 'Final Content', field: 'content', formatter: 'string', formatterParams: {textLimit: 25, htmlChars: true}},
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
					$('#available-blocks-table').before('<p><b>NOTE: This is only partial data. Some data is only accessible when the block editor is active on the page.</b></p>');
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
							{title: 'description', field: 'description', hozAlign: 'left', formatter: 'string'},
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
							{title: 'description', field: 'description', hozAlign: 'left', formatter: 'string'},
						],
					});
				}
			});
		</script>
		<?php
	}

	public function getBlockCategories ()
	{
		?>
		<h3>Categories</h3>
		<div id="block-categories-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var categories = <?= json_encode( \WP_Block_Pattern_Categories_Registry::get_instance()->get_all_registered() ?? [] ) ?>;

				if (categories.length) {
					T.Create("#block-categories-table", {
						data: categories,
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