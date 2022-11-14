<?php

namespace DebugBar\Routine;

class Page
{
	const DEFAULT_SLUG = 'routines_console';
	protected $config = [
		'menu_slug'       => 'routines_console',
		'parent_slug'     => FALSE,
		'menu_title'      => 'Routines',
		'page_title'      => 'Routines Console',
		'capability'      => 'manage_options',
		'icon_url'        => 'dashicons-embed-generic',
		'position'        => FALSE,
		'admin_menu_page' => FALSE,
		'debug_bar_panel' => FALSE,
	];
	protected $panel;

	protected $tasks = [ 'Main' => [] ];

	public function __construct ( $config = [] )
	{
		$this->config['callback'] = [ $this, 'render' ];
		$this->set( $config );
		add_filter( 'debug_bar_panels', [ $this, 'debug_bar_panels' ], );
	}

	public function debug_bar_panels ( $panels )
	{
		if ( $this->config['debug_bar_panel'] ) {
			$this->panel = new \DebugBar\Panel\Routine( $this->config['menu_title'] );
			$this->panel->setRenderCallback( [ $this, 'render' ] );
			$this->panel->_icon = $this->config['icon_url'];

			$panels[] = $this->panel;
		}

		return $panels;
	}

	/**
	 * @param Page|array $config
	 * @return array|mixed
	 */
	public function set ( $config = [] )
	{
		if ( gettype( $config ) === 'object' && method_exists( $config, 'get' ) ) {
			$config = $config->get();
		}

		if ( array_key_exists( 'menu_slug', $config ) && !empty( $config['menu_slug'] ) ) {
			$this->config = array_filter( $config, function ( $x ) { return $x !== FALSE; } ) + $this->config;
		}

		return $this->get();
	}

	/**
	 * @param string|null $key
	 * @param mixed|null  $default
	 * @return array|mixed
	 */
	public function get ( $key = NULL, $default = NULL )
	{
		if ( !empty( $key ) ) {
			return array_key_exists( $key, $this->config ) ? $this->config[$key] : $default;
		}

		return $this->config;
	}

	public function setUpAdminPage ()
	{
		if ( empty( $this->get( 'admin_menu_page' ) ) ) {
			return FALSE;
		}

		is_callable( $this->config['callback'] ) && add_action( 'admin_menu', function () {
			$args = $this->config;
			if ( !empty( $args['parent_slug'] ) ) {
				add_submenu_page( $args['parent_slug'], $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['callback'], $args['position'] );
			}
			else {
				add_menu_page( $args['page_title'], $args['menu_title'], $args['capability'], $args['menu_slug'], $args['callback'], $args['icon_url'], $args['position'] );
			}
		} );
	}

	public function setUpDebugPanel ()
	{
		static $completed = FALSE;

		if ( $completed ) {
			return NULL;
		}

		$completed = TRUE;

		if ( empty( $this->get( 'debug_bar_panel' ) ) ) {
			return FALSE;
		}
	}

	/**
	 * @param Tasks $tasks
	 * @return void
	 */
	public function addTasks ( $tasks )
	{
		if ( !array_key_exists( $group = $tasks->getGroup( 'Main' ), $this->tasks ) ) {
			$this->tasks[$group] = [];
		}

		if ( !array_key_exists( $priority = $tasks->getPriority(), $this->tasks ) ) {
			$this->tasks[$group][$priority] = [];
		}

		foreach ( $tasks->getCallbacks() as $title => $callback ) {
			$this->tasks[$group][$priority][$title] = $callback;
		}
	}

	/**
	 * @param Task $task
	 * @return void
	 */
	public function addTask ( $task )
	{
		if ( !array_key_exists( $group = $task->getGroup( 'Main' ), $this->tasks ) ) {
			$this->tasks[$group] = [];
		}

		if ( !array_key_exists( $priority = $task->getPriority(), $this->tasks[$group] ) ) {
			$this->tasks[$group][$priority] = [];
		}

		if ( empty( $title = $task->getTitle() ) ) {
			$title = 'Task #' . ( count( $this->tasks[$group][$priority] ) + 1 );
		}

		$this->tasks[$group][$priority][$title] = $task->getCallback();
	}

	protected function renderAction ( $callback, $action )
	{
		$execTime    = apply_filters( "global_exec_time", 720, (int) ini_get( 'max_execution_time' ) );
		$memoryLimit = apply_filters( "global_memory_limit", '512M', ini_get( 'memory_limit' ) );

		$execTime    = apply_filters( "{$action}_exec_time", $execTime, (int) ini_get( 'max_execution_time' ) );
		$memoryLimit = apply_filters( "{$action}_memory_limit", $memoryLimit, ini_get( 'memory_limit' ) );

		$stream = new Stream( $execTime, $memoryLimit );
		apply_filters( "{$action}_pre", TRUE, $stream ) && $stream->start();
		$callback( $stream );
		apply_filters( "{$action}_post", TRUE, $stream ) && $stream->stop();
	}

	public function registerAjax ()
	{
		foreach ( $this->tasks as $group => $priorities ) {
			foreach ( $priorities as $priority => $tasks ) {
				foreach ( $tasks as $title => $callback ) {
					if ( $title !== FALSE ) {
						$action = str_replace( '-', '_', sanitize_title( $group ) ) . '_' . str_replace( '-', '_', sanitize_title( $title ) );
						add_action( "wp_ajax_{$action}", function () use ( $callback, $action ) {
							$this->renderAction( $callback, $action );
						}, $priority );
					}
				}
			}
		}
	}

	protected function getActionLinks ()
	{
		$request = filter_input_array( INPUT_POST ) ?? filter_input_array( INPUT_GET ) ?: [];

		$groups = [];

		foreach ( $this->tasks as $group => $priorities ) {
			ksort( $priorities );
			$links = [];
			foreach ( $priorities as $priority => $tasks ) {
				foreach ( $tasks as $title => $callback ) {
					if ( $title !== FALSE ) {
						$action  = str_replace( '-', '_', sanitize_title( $group ) ) . '_' . str_replace( '-', '_', sanitize_title( $title ) );
						$args    = array_key_exists( $action, $request ) ? " <b> ( $request[$action] ) </b>" : '';
						$links[] = '<a data-action="' . $action . '">' . $title . '</a>' . $args;
					}
				}
			}
			if ( !empty( $links ) ) {
				$groups[] = '<b>' . $group . '</b>: &nbsp; ' . implode( ' &nbsp; | &nbsp; ', $links );
			}
		}

		return '<hr>' . implode( '<hr>', $groups ) . '<hr>';
	}

	public function render ()
	{
		?>
		<div class="wrap" data-routine-id="<?= $this->config['menu_slug'] ?>" data-ajax-url="<?= admin_url( 'admin-ajax.php' ) ?>">
			<h1 style="font-size: 24px; font-weight: bold; margin: 1em 0"><?= $this->config['page_title'] ?></h1>
			<div><?= $this->getActionLinks() ?></div>
			<div class="routine-output-buffer"></div>
			<div><a class="routine-auto-scroll">Scroll To End</a> | <a class="routine-pause-scroll">Pause Scroll</a> | <a class="routine-abort-xhr">Stop Request</a></div>
		</div>
		<?php
	}
}