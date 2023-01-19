<?php

namespace DebugBar\Traits;

trait BlocksTrait
{
	use FormatTrait;

	protected $registeredBlocks = [];
	protected $activeBlocks = [];

	public function setupBlockHooks ()
	{
		static $hasSetup = FALSE;

		if ( $hasSetup ) {
			return;
		}

		$hasSetup = TRUE;
		add_filter( 'pre_render_block', [ $this, 'pre_render_block' ], PHP_INT_MAX, 3 );
		add_filter( 'render_block_data', [ $this, 'render_block_data' ], PHP_INT_MAX, 3 );
		add_filter( 'render_block_context', [ $this, 'render_block_context' ], PHP_INT_MAX, 3 );
		add_filter( 'render_block', [ $this, 'render_block' ], PHP_INT_MAX, 3 );
	}

	protected function removeACF ( $attrs )
	{
		foreach ( $attrs as $key => &$value ) {
			if ( is_string( $key ) && is_string( $value ) ) {
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

	protected function getLevelsFromParentIndex ( $parentIndex = NULL )
	{
		if ( is_null( $parentIndex ) ) {
			$rootBlocks = 0;
			foreach ( $this->activeBlocks as $block ) {
				if ( count( $block['levels'] ) === 1 ) {
					$rootBlocks++;
				}
			}

			return [ $rootBlocks + 1 ];
		}

		$levels = $this->activeBlocks[$parentIndex]['levels'];

		$siblings = 0;
		for ( $i = $parentIndex + 1; $i < count( $this->activeBlocks ); $i++ ) {
			if ( count( $this->activeBlocks[$i]['levels'] ) == count( $levels ) + 1 ) {
				$siblings++;
			}
		}

		$levels[] = $siblings + 1;

		return $levels;
	}

	protected function getCurrentParentIndex ()
	{
		$currentParentIndex = NULL;

		foreach ( $this->activeBlocks as $index => $block ) {
			if ( !empty( $block['start_time'] ) && empty( $block['end_time'] ) ) {
				$currentParentIndex = $index;
			}
		}

		return $currentParentIndex;
	}

	protected function blockStart ( $block, $data = [] )
	{
		foreach ( debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS ) as $data ) {
			if ( ( ( !isset( $data['class'] ) || !str_starts_with( $data['class'], 'DebugBar' ) ) //
					&& ( isset( $data['file'] ) && !str_starts_with( $this->getWordPressPathText( $data['file'] ), 'WP Core' ) ) ) //
				|| ( isset( $data['class'] ) && str_starts_with( $data['class'], 'WP_REST_' ) ) ) {
				break;
			}
		}

		$fileLink = $this->getFileLinkArray( $data['file'], $data['line'] );

		$matchingBlock = $this->getRegisteredBlock( $block['blockName'] ) ?? [];

		$title = $matchingBlock['title'] ?? '';

		if ( $block['blockName'] === 'core/block' && array_key_exists( 'ref', $block['attrs'] ) && ( $reusable_block = get_post( $block['attrs']['ref'] ) ) ) {
			$title = '<a href="' . get_edit_post_link( $reusable_block ) . '" target="_blank">' . $reusable_block->post_title . '</a>&nbsp;(' . $title . ')';
		}

		$parentIndex = $this->getCurrentParentIndex();
		$levels      = $this->getLevelsFromParentIndex( $parentIndex );

		$this->activeBlocks[] = array_merge( [
			'index'           => ( $index = count( $this->activeBlocks ) ),
			'level'           => implode( '.', $levels ),
			'levels'          => $levels,
			'title'           => $title,
			'name'            => $block['blockName'],
			'attrs'           => $this->removeACF( $block['attrs'] ),
			'isDynamic'       => !!( $matchingBlock['renderCallback'] ?? FALSE ),
			'parent'          => $parentIndex,
			'children'        => [],
			'html'            => $block['innerHTML'],
			'start_time'      => microtime( TRUE ),
			'end_time'        => NULL,
			'total_time'      => NULL,
			'time'            => NULL,
			'supports'        => $matchingBlock['supports'] ?? NULL,
			'providesContext' => $matchingBlock['providesContext'] ?? NULL,
			'usesContext'     => $matchingBlock['usesContext'] ?? NULL,
			'renderCallback'  => $matchingBlock['renderCallback'] ?? NULL,
			'initiator'       => $fileLink,
			'initiatorLabel'  => $fileLink['text'],
		], $data );


		if ( !is_null( $parentIndex ) ) {
			$this->activeBlocks[$parentIndex]['children'][] = $index;
		}

		return $index;
	}

	protected function addToBlock ( $index = NULL, $data = [] )
	{
		if ( is_null( $index ) || !array_key_exists( $index, $this->activeBlocks ) ) {
			return;
		}

		$this->activeBlocks[$index] = array_merge( $this->activeBlocks[$index], $data );

		return;
	}

	protected function blockCompleted ( $index = NULL, $data = [] )
	{
		if ( is_null( $index ) || !array_key_exists( $index, $this->activeBlocks ) ) {
			return;
		}

		$data += [
			'end_time'   => microtime( TRUE ),
			'total_time' => round( microtime( TRUE ) - $this->activeBlocks[$index]['start_time'], 5 ) * 1e3,
		];

		$this->addToBlock( $index, $data );

		return;
	}

	protected function isNullBlock ( $block )
	{
		return empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ) );
	}

	public function pre_render_block ( $pre_render, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			if ( !is_null( $pre_render ) ) {
				$this->blockCompleted( $this->blockStart( $parsed_block, [ 'content' => $pre_render, ] ) );
			}
		}

		return $pre_render;
	}

	public function render_block_data ( $parsed_block, $source_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$parsed_block['_debug_bar'] = $this->blockStart( $parsed_block );
		}

		return $parsed_block;
	}

	public function render_block_context ( $context, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->addToBlock( $parsed_block["_debug_bar"] ?? NULL, [ 'context' => $context, ] );
		}

		return $context;
	}

	public function render_block ( $block_content, $parsed_block, $WP_Block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->blockCompleted( $parsed_block["_debug_bar"] ?? NULL, [ 'content' => $block_content, ] );
		}

		return $block_content;
	}


	/* ------- */


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

	protected function getActiveBlocks ( $forceProcessing = FALSE )
	{
		static $completedProcessing = FALSE;

		if ( $completedProcessing && !$forceProcessing ) {
			return $this->activeBlocks ?? [];
		}

		foreach ( $this->activeBlocks as &$block ) {
			$children_time = 0;
			if ( !empty( $block['children'] ) ) {
				foreach ( $block['children'] as $child_index ) {
					$children_time += $this->activeBlocks[$child_index]['total_time'];
				}
			}
			$block['time'] = round( $block['total_time'] - $children_time, 5 );
		}

		$completedProcessing = TRUE;

		return $this->activeBlocks ?? [];
	}
}