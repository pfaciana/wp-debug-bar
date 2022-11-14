<?php

namespace DebugBar\Routine;

abstract class Tasks
{
	protected $page = NULL;
	protected $group;
	protected $priority = 10;
	protected $taskPrefix = 'wp_ajax_';
	protected $titles = [];
	protected $crons = [];
	protected $cronActionPrefix = 'routine_tasks_';
	protected $disablePre = FALSE;
	protected $disablePost = FALSE;

	public function __construct ( $config = [] )
	{
		$this->preInit( $config );

		foreach ( $config as $key => $value ) {
			$this->{$key} = $value;
		}

		if ( empty( $this->group ) ) {
			$this->group = ( new \ReflectionClass( $this ) )->getShortName();
		}

		$this->setupFilters();

		$this->scheduleCronEvents();

		$this->init( $config );
	}

	protected function preInit ( $config ) { }

	protected function init ( $config ) { }

	protected function setupFilters ()
	{

		foreach ( get_class_methods( $this ) as $method ) {
			$title  = array_key_exists( $method, $this->titles ) ? $this->titles[$method] : ucwords( str_replace( '_', ' ', $method ) );
			$action = str_replace( '-', '_', sanitize_title( $this->group ) ) . '_' . str_replace( '-', '_', sanitize_title( $title ) );
			$this->disablePre && add_filter( "{$action}_pre", '__return_false' );
			$this->disablePost && add_filter( "{$action}_post", '__return_false' );
		}

	}

	protected function scheduleCronEvents ()
	{
		if ( is_array( $this->crons ) && !empty( $this->crons ) ) {
			foreach ( $this->crons as $schedule => $actions ) {
				foreach ( $actions as $method => $priorities ) {
					$cronAction = $this->cronActionPrefix . $method;
					!wp_next_scheduled( $cronAction ) && wp_schedule_event( time(), $schedule, $cronAction );
					foreach ( (array) $priorities as $priority ) {
						add_action( $cronAction, [ $this, $method ], $priority );
					}
				}
			}
		}
	}

	public function getGroup ( $default = NULL )
	{
		return $this->group ?: $default;
	}

	public function getPage ( $default = NULL )
	{
		return $this->page ?: $default;
	}

	public function getCallbacks ()
	{
		$callbacks = [];

		foreach ( get_class_methods( $this ) as $method ) {
			if ( str_starts_with( $method, $this->taskPrefix ) ) {
				$title = array_key_exists( $method, $this->titles ) ? $this->titles[$method] : ucwords( str_replace( '_', ' ', $method ) );

				$callbacks[$title] = [ $this, $method ];
			}
		}

		return $callbacks;
	}

	public function getPriority ( $default = 10 )
	{
		return $this->priority ?: $default;
	}
}