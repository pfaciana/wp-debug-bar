<?php

if ( class_exists( 'console' ) ) {
	if ( !function_exists( 'd' ) && method_exists( 'console', 'log' ) ) {
		function d ()
		{
			return call_user_func_array( [ 'console', 'log' ], func_get_args() );
		}

		\Kint\Kint::$aliases[] = 'd';
	}

	if ( !function_exists( 's' ) && method_exists( 'console', 'log' ) ) {
		function s ()
		{
			if ( !Kint\Kint::$enabled_mode ) {
				return 0;
			}

			$stash = Kint\Kint::$enabled_mode;

			if ( Kint\Kint::MODE_TEXT !== Kint\Kint::$enabled_mode ) {
				Kint\Kint::$enabled_mode = Kint\Kint::MODE_PLAIN;
				if ( PHP_SAPI === 'cli' && TRUE === Kint\Kint::$cli_detection ) {
					Kint\Kint::$enabled_mode = Kint\Kint::$mode_default_cli;
				}
			}

			$buffer = call_user_func_array( [ 'console', 'log' ], func_get_args() );

			Kint\Kint::$enabled_mode = $stash;

			return $buffer;
		}

		\Kint\Kint::$aliases[] = 's';
	}

	if ( !function_exists( 't' ) && method_exists( 'console', 'trace' ) ) {
		function t ()
		{
			return call_user_func_array( [ 'console', 'trace' ], func_get_args() );
		}

		\Kint\Kint::$aliases[] = 't';
	}

	method_exists( 'console', 'aliases' ) && console::aliases();
	method_exists( 'console', 'hooks' ) && console::hooks();
}
if ( !function_exists( 't' ) ) {
	function t ()
	{
		return call_user_func_array( [ '\Kint\Kint', 'trace' ], func_get_args() );
	}

	Kint::$aliases[] = 't';
}

if ( function_exists( 'has_filter' ) && !has_filter( 'console.log' ) ) {
	$aliases = [
		'd' => [ 'log', 'info', 'debug', 'notice', 'warn', 'error', 'alert', 'critical', 'emergency', 'message' ],
		't' => [ 'trace' ],
	];

	foreach ( $aliases as $alias => $methods ) {
		foreach ( $methods as $method ) {
			foreach ( [ '.', '::' ] as $join ) {
				add_filter( 'console' . $join . $method, $alias, 10, 9e9 );
			}
		}
	}
}

\Kint\Kint::$plugins[] = '\\Kint\\Parser\\DOMDocumentPlugin';
\Kint\Kint::$plugins[] = '\\Kint\\Parser\\SerializePlugin';
\Kint\Kint::$plugins[] = '\\Kint\\Parser\\MysqliPlugin';

\Kint\Kint::$plugins[] = '\\DebugBar\\Log\\Parser\\Ajax';
\Kint\Kint::$plugins[] = '\\DebugBar\\Log\\Parser\\Notification';
\Kint\Kint::$plugins[] = '\\DebugBar\\Log\\Parser\\Unpack';
\Kint\Kint::$plugins[] = '\\DebugBar\\Log\\Parser\\Hooks';

\Kint\Kint::$renderers[\Kint\Kint::MODE_RICH] = '\\DebugBar\\Log\\Renderer\\RichRenderer';

\Kint\Renderer\RichRenderer::$object_plugins['notification'] = '\\DebugBar\\Log\\Renderer\\Rich\\Notification';

if ( defined( 'RWD_DEBUG_BAR_PLUGIN_DIR' ) ) {
	\Kint\Renderer\RichRenderer::$theme = RWD_DEBUG_BAR_PLUGIN_DIR . '/css/dist/kint-dark-theme.css';
}

if ( function_exists( 'wp_doing_ajax' ) && !wp_doing_ajax() ) {
	$GLOBALS['kint_buffer'] = str_replace( '<div class="kint-rich kint-file"></div>', '', ( new \Kint\Renderer\RichRenderer() )->preRender() . '</div>' );
}
\Kint\Renderer\RichRenderer::$needs_pre_render = FALSE;
