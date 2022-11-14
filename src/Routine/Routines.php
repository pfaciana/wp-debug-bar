<?php

namespace DebugBar\Routine;

class Routines
{
	/**
	 * @var Page[]
	 */
	protected $pages = [];

	public static function get_instance ()
	{
		static $instance;

		if ( !$instance instanceof static ) {
			$instance = new static;
		}

		return $instance;
	}

	protected function __construct ()
	{
		if ( wp_doing_ajax() ) {
			$hook_name = 'admin_init';
		}
		elseif ( is_admin() ) {
			$hook_name = '_admin_menu';
		}
		else {
			$hook_name = 'admin_bar_init';
		}

		add_action( $hook_name, function () { $this->admin_bar_init(); }, PHP_INT_MIN );
	}

	protected function admin_bar_init ()
	{
		do_action( 'debug_bar_routines', $this );
		foreach ( $this->pages as $page ) {
			$page->setUpAdminPage();
			$page->setUpDebugPanel();
			$page->registerAjax();
		}
	}

	public function addPage ( $config )
	{
		if ( empty( $config ) ) {
			$config = [
				'menu_slug'       => ( $menu_slug = Page::DEFAULT_SLUG ),
				'admin_menu_page' => TRUE,
				'debug_bar_panel' => TRUE,
			];
		}

		if ( gettype( $config ) === 'object' && method_exists( $config, 'get' ) ) {
			$menu_slug = $config->get( 'menu_slug' );
		}

		if ( is_array( $config ) ) {
			$menu_slug = $config['menu_slug'];
		}

		if ( is_string( $config ) && !empty( $config ) ) {
			$config = [ 'menu_slug' => ( $menu_slug = $config ) ];
		}

		if ( !isset( $menu_slug ) || empty( $menu_slug ) ) {
			return FALSE;
		}

		if ( array_key_exists( $menu_slug, $this->pages ) ) {
			$this->pages[$menu_slug]->set( $config );
		}
		else {
			$this->pages[$menu_slug] = new Page( $config );
		}

		return $this->pages[$menu_slug];
	}

	public function addTasks ( $tasks )
	{
		$page = $this->addPage( $tasks->getPage() );
		$page->addTasks( $tasks );

		return $tasks;
	}

	public function addTask ( $config = [] )
	{
		$task = new Task( ...func_get_args() );

		$page = $this->addPage( $task->getPage() );
		$page->addTask( $task );

		return $task;
	}
}