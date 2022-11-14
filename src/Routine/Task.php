<?php

namespace DebugBar\Routine;

class Task
{
	protected $title;
	protected $page;
	protected $group;
	protected $callback;
	protected $priority = 10;

	public function __construct ( $config = [], $callback = NULL )
	{
		if ( is_callable( $config ) ) {
			$config = [ 'callback' => $config ];
		}

		if ( is_callable( $callback ) ) {
			if ( is_string( $config ) ) {
				$config = [ 'title' => $config ];
			}
			$config['callback'] = $callback;
		}

		foreach ( $config as $key => $value ) {
			$this->{$key} = $value;
		}
	}

	public function getTitle ( $default = NULL )
	{
		return $this->title ?: $default;
	}

	public function getGroup ( $default = NULL )
	{
		return $this->group ?: $default;
	}

	public function getPage ( $default = NULL )
	{
		return $this->page ?: $default;
	}

	public function getCallback ( $default = NULL )
	{
		return $this->callback ?: $default;
	}

	public function getPriority ( $default = 10 )
	{
		return $this->priority ?: $default;
	}
}