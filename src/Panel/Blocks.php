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

	public function render ()
	{
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

	public function getBlockTypes ()
	{
		$blocks = [];

		foreach ( \WP_Block_Type_Registry::get_instance()->get_all_registered() as &$registered_block ) {
			/** @var \WP_Block_Type $registered_block */
			$block = [ 'render_callback' => FALSE ];
			if ( $registered_block->is_dynamic() ) {
				$block['render_callback'] = $this->getFileLinkArray( ...$this->getFileLine( $registered_block->render_callback ) );
			}
			$blocks[$registered_block->name] = $block;
		}

		?>
		<h3>Available Blocks</h3>
		<div id="available-blocks-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var blockDetails = <?= json_encode( $blocks ?? [] ) ?>;

				if (!('wp' in window) || !('blocks' in window.wp) || !('getBlockTypes' in wp.blocks)) {
					return $('#available-blocks-table').after('<b>Blocks are only available on a Block Editor page.</b>');
				}

				if (!wp.blocks.getBlockTypes().length) {
					return $('#available-blocks-table').after('<b>No blocks have been loaded.</b>');
				}

				var blocks = [], separator = ' - ', parents = new Set();

				$.each(wp.blocks.getBlockTypes(), function (i, block) {
					if (block.parent && block.parent.length) {
						$.each(block.parent, function (i, parent) {
							parents.add(parent);
						});
					}
				});

				$.each(wp.blocks.getBlockTypes(), function () {
					blocks.push({
						name: this.name,
						title: this.title,
						description: this.description,
						category: this.category,
						attributes: Object.keys(this.attributes).length,
						variations: this.variations.length,
						parent: (this.parent && this.parent.length) ? this.parent.join(separator) : null,
						supports: (this.supports && Object.keys(this.supports).length) ? Object.keys(this.supports).join(separator) : null,
						providesContext: (this.providesContext && Object.keys(this.providesContext).length) ? Object.values(this.providesContext).join(separator) : null,
						usesContext: (this.usesContext && this.usesContext.length) ? this.usesContext.join(separator) : null,
						renderCallback: this.name in blockDetails && blockDetails[this.name].render_callback ? [blockDetails[this.name].render_callback] : false,
						isDynamic: !!(this.name in blockDetails && blockDetails[this.name].render_callback),
						isParent: parents.has(this.name),
						isChild: !!(this.parent && this.parent.length),
						apiVersion: this.apiVersion,
					});
				});

				if (blocks.length) {
					T.Create("#available-blocks-table", {
						data: blocks,
						layout: 'fitDataStretch',
						columns: [
							{title: 'title', field: 'title', hozAlign: 'left', frozen: true, formatter: 'string'},
							{title: 'name', field: 'name', frozen: true, formatter: 'string'},
							{title: 'category', field: 'category', formatter: 'list'},
							{title: 'Dynamic?', field: 'isDynamic', formatter: 'boolean'},
							{title: 'attributes', field: 'attributes', formatter: 'minMax'},
							{title: 'variations', field: 'variations', formatter: 'minMax'},
							{title: 'Parent?', field: 'isParent', formatter: 'boolean'},
							{title: 'Child?', field: 'isChild', formatter: 'boolean'},
							{title: 'parent', field: 'parent', hozAlign: 'left', formatter: 'string'},
							{title: 'supports', field: 'supports', hozAlign: 'left', formatter: 'string'},
							{title: 'providesContext', field: 'providesContext', hozAlign: 'left', formatter: 'string'},
							{title: 'usesContext', field: 'usesContext', hozAlign: 'left', formatter: 'string'},
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
							{title: 'categories', field: 'categories', formatter: 'string'},
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