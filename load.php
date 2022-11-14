<?php

if ( function_exists( 'add_action' ) ) {

	!defined( 'WP_START_TIMESTAMP' ) && define( 'WP_START_TIMESTAMP', $GLOBALS['timestart'] ?? NULL );
	!defined( 'SAVEQUERIES' ) && define( "SAVEQUERIES", TRUE );
	define( 'RWD_DEBUG_BAR_PLUGIN_DIR', ( __DIR__ ) );
	define( 'RWD_DEBUG_BAR_PLUGIN_FILE', ( __FILE__ ) );
	define( 'KINT_SKIP_HELPERS', !class_exists( 'console' ) );

	new DebugBar\Hooks();
	DebugBar\Routine\Routines::get_instance();

	$GLOBALS['rwd_debug_bar'] = new DebugBar\DebugBar();

	$definedVars = get_defined_vars();
	add_action( 'plugins_loaded', function () use ( $definedVars ) {
		$queryPanel   = new DebugBar\Panel\Queries( 'Queries' );
		$globalsPanel = new DebugBar\Panel\Globals( 'Globals' );
		$globalsPanel->setDefinedVars( $definedVars );
		add_filter( 'debug_bar_panels', function ( $panels ) use ( $queryPanel, $globalsPanel ) {
			$panels[] = $globalsPanel;
			$panels[] = new DebugBar\Panel\PostTypes( 'Post Types' );
			$panels[] = new DebugBar\Panel\Template( 'Templating' );
			$panels[] = new DebugBar\Panel\UserRoles( 'User Roles' );
			$panels[] = $queryPanel;

			return $panels;
		}, );

		add_filter( 'debug_bar_panels', function ( $panels ) {
			$panels[] = new DebugBar\Panel\Kint( 'Kint' );
			$panels[] = new DebugBar\Panel\Settings( 'Settings' );

			return $panels;
		}, PHP_INT_MAX );
	}, PHP_INT_MIN );

}