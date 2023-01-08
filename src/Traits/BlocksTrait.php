<?php

namespace DebugBar\Traits;

trait BlocksTrait
{
	use FormatTrait;

	protected $registeredBlocks = [];
	protected $activeBlocks = [];
	protected $blockCurrentIndex = -1;

	public function setupBlockHooks ()
	{
		static $hasSetup = FALSE;

		if ( $hasSetup ) {
			return;
		}

		$hasSetup = TRUE;
		add_filter( 'pre_render_block', [ $this, 'pre_render_block_pre' ], PHP_INT_MIN, 3 );
		add_filter( 'pre_render_block', [ $this, 'pre_render_block_post' ], PHP_INT_MAX, 3 );
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

	protected function addBlock ( $block, $parentIndex = NULL )
	{
		$levels = $this->getLevelsFromParentIndex( $parentIndex );

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

		$this->activeBlocks[] = [
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
			'start_time'      => NULL,
			'end_time'        => NULL,
			'total_time'      => NULL,
			'time'            => NULL,
			'supports'        => $matchingBlock['supports'] ?? NULL,
			'providesContext' => $matchingBlock['providesContext'] ?? NULL,
			'usesContext'     => $matchingBlock['usesContext'] ?? NULL,
			'renderCallback'  => $matchingBlock['renderCallback'] ?? NULL,
			'initiator'       => $fileLink,
			'initiatorLabel'  => $fileLink['text'],
		];

		if ( !is_null( $parentIndex ) ) {
			$this->activeBlocks[$parentIndex]['children'][] = $index;
		}

		return $index;
	}


	protected function addBlocks ( $blocks, $parentIndex = NULL )
	{
		foreach ( $blocks as $block ) {
			$thisIndex = $this->addBlock( $block, $parentIndex );
			if ( array_key_exists( 'innerBlocks', $block ) && !empty( $block['innerBlocks'] ) ) {
				$this->addBlocks( $block['innerBlocks'], $thisIndex );
			}
		}
	}

	protected function addToBlock ( $data, $index = NULL )
	{
		$this->activeBlocks[$index = $index ?? $this->blockCurrentIndex] = array_merge( $this->activeBlocks[$index], $data );
	}

	protected function blockStart ( $index = NULL )
	{
		$this->addToBlock( [
			'start_time' => microtime( TRUE ),
		], $index );
	}

	protected function blockCompleted ( $index = NULL )
	{
		$this->addToBlock( [
			'end_time'   => microtime( TRUE ),
			'total_time' => round( microtime( TRUE ) - $this->activeBlocks[$index]['start_time'], 5 ) * 1e3,
		], $index );
	}

	protected function isNullBlock ( $block )
	{
		return empty( $block['blockName'] ) && empty( trim( $block['innerHTML'] ) );
	}

	protected function getCurrentRootIndex ()
	{
		$currentRootIndex = count( $this->activeBlocks );
		foreach ( $this->activeBlocks as $index => $block ) {
			if ( empty( $block['total_time'] ) ) {
				$currentRootIndex = $index;
			}
		}

		return $currentRootIndex;
	}

	public function pre_render_block_pre ( $pre_render, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->blockCurrentIndex++;
			if ( empty( $parent_block ) ) {
				$currentRootIndex = $this->getCurrentRootIndex();
				$this->addBlocks( [ $parsed_block ], $currentRootIndex == count( $this->activeBlocks ) ? NULL : $currentRootIndex );
			}
		}

		return $pre_render;
	}

	public function pre_render_block_post ( $pre_render, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->blockStart();
			if ( !is_null( $pre_render ) ) {
				$this->addToBlock( [ 'content' => $pre_render, ] );
				$this->blockCompleted();
			}
		}

		return $pre_render;
	}

	public function render_block_data ( $parsed_block, $source_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$parsed_block['_debug_bar'] = $this->blockCurrentIndex;
		}

		return $parsed_block;
	}

	public function render_block_context ( $context, $parsed_block, $parent_block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->addToBlock( [ 'context' => $context, ] );
		}

		return $context;
	}

	public function render_block ( $block_content, $parsed_block, $WP_Block )
	{
		if ( !$this->isNullBlock( $parsed_block ) ) {
			$this->addToBlock( [ 'content' => $block_content, ], $parsed_block["_debug_bar"] ?? NULL );
			$this->blockCompleted( $parsed_block["_debug_bar"] ?? NULL );
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