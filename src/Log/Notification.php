<?php

namespace DebugBar\Log;

class Notification
{
	protected $header;
	protected $children;
	protected $state = 'log';
	protected $statePrefix = FALSE;
	protected $dimCallStack = TRUE;
	protected $classes = [
		'dimCallStack' => 'dim-call-stack',
	];

	const LOG = 'log';
	const INFO = 'info';
	const DEBUG = 'debug';
	const NOTICE = 'notice';
	const WARN = 'warning';
	const ERROR = 'error';
	const ALERT = 'alert';
	const CRITICAL = 'critical';
	const EMERGENCY = 'emergency';

	public function __construct ( $config = [] )
	{
		$this->set( ...func_get_args() );
	}

	/**
	 * @param string|array|callable $config
	 * @return void
	 */
	public function set ( $config = [] )
	{
		if ( is_string( $config ) || is_callable( $config ) ) {
			$config = [ 'header' => $config ];
		}
		if ( func_num_args() > 1 ) {
			$config['state'] = func_get_arg( 1 );
		}

		if ( empty( $config ) || !is_array( $config ) ) {
			return;
		}

		$properties = [ 'header', 'children', 'state', 'statePrefix', 'dimCallStack', ];

		foreach ( $config as $key => $value ) {
			if ( in_array( $key, $properties ) ) {
				$this->$key = $value;
			}
		}
	}

	public function renderHeader ( $default = '' )
	{
		return ( is_callable( $this->header ) && !is_string( $this->header ) ? ( $this->header )() : $this->header ) ?: $default;
	}

	public function renderChildren ( $default = '' )
	{
		return ( is_callable( $this->children ) && !is_string( $this->children ) ? ( $this->children )() : $this->children ) ?: $default;
	}

	public function getStateClass ( $prefix = '' )
	{
		return $prefix . $this->state . ( $this->statePrefix ? '-with-prefix' : '' );
	}

	public function getParentClasses ( $prefix = '', $implode = TRUE )
	{
		$classes = array_map( function ( $key ) use ( $prefix ) {
			if ( !array_key_exists( $key, $this->classes ) || empty( $this->$key ) ) {
				return '';
			}

			return $prefix . $this->classes[$key];
		}, array_keys( $this->classes ) );

		return $implode ? trim( implode( ' ', $classes ) ) : $classes;
	}
}