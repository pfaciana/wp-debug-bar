<?php

if ( isset( $GLOBALS['wp_version'] ) && !isset( $_GET['_wp-find-template'] ) //
	&& !str_ends_with( explode( '?', $_SERVER['REQUEST_URI'] )[0], 'favicon.ico' ) && !defined( 'RWD_DEBUG_BAR_PLUGIN_FILE' ) ) {

	!defined( 'WP_START_TIMESTAMP' ) && define( 'WP_START_TIMESTAMP', $GLOBALS['timestart'] ?? NULL );
	!defined( 'SAVEQUERIES' ) && define( "SAVEQUERIES", TRUE );
	define( 'RWD_DEBUG_BAR_PLUGIN_DIR', ( __DIR__ ) );
	define( 'RWD_DEBUG_BAR_PLUGIN_FILE', ( __FILE__ ) );
	define( 'KINT_SKIP_HELPERS', !class_exists( 'console' ) );

	require __DIR__ . '/src/Log/Kint.php';

	new DebugBar\Hooks();
	WP_Routines\Routines::get_instance();

	$GLOBALS['rwd_debug_bar'] = new DebugBar\DebugBar();

	add_action( 'plugins_loaded', function () {
		$queryPanel   = new DebugBar\Panel\Queries( 'Queries' );
		add_filter( 'debug_bar_panels', function ( $panels ) use ( $queryPanel ) {
			$panels[] = new DebugBar\Panel\Environment( 'Environment' );
			$panels[] = new DebugBar\Panel\Globals( 'Globals' );
			$panels[] = new DebugBar\Panel\Template( 'Templating' );
			$panels[] = new DebugBar\Panel\Blocks( 'Blocks' );
			$panels[] = new DebugBar\Panel\PostTypes( 'Post Types' );
			$panels[] = new DebugBar\Panel\UserRoles( 'User Roles' );
			$panels[] = new DebugBar\Panel\StylesScripts( 'Styles & Scripts' );
			$panels[] = new DebugBar\Panel\RewriteRules( 'Rewrite Rules' );
			$panels[] = $queryPanel;

			return $panels;
		} );

		add_filter( 'debug_bar_panels', function ( $panels ) {
			$panels[] = new DebugBar\Panel\Kint( 'Kint' );
			$panels[] = new DebugBar\Panel\Settings( 'Settings' );

			return $panels;
		}, PHP_INT_MAX );
	}, -9e3 );

}