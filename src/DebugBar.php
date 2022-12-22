<?php

namespace DebugBar;

/**
 * DebugBar
 *
 * Extended functionality of the Debug Bar plugin class (https://wordpress.org/plugins/debug-bar/)
 *
 * Events order
 * 1) plugins_loaded (action - via WordPress)
 * 2) debug_bar_pre_init (action)
 * 3) debug_bar_post_init (action)
 * 4) debug_bar_panels (filter)
 * *) Panels initialized
 * 5) debug_bar_post_panels (action)
 * *) Headers are sent by PHP
 */
class DebugBar
{
	use Traits\MenuTrait;

	/**
	 * @var Panel[] array of Debug Bar Panels
	 */
	public $panels = [];

	/**
	 * @var bool does the original Debug_Bar exist
	 */
	protected $orig_debug_bar = FALSE;

	function __construct ()
	{
		add_action( 'plugins_loaded', function () { $this->set_alias(); }, -9e9 );
		add_action( 'plugins_loaded', function () { $this->pre_init(); } );
		add_action( 'init', function () { $this->init(); } );
		add_action( wp_doing_ajax() ? 'admin_init' : 'admin_bar_init', function () { $this->admin_bar_init(); } );
		add_action( 'activate_debug-bar/debug-bar.php', function () { $this->orig_debug_bar_activated(); } );
	}

	protected function init ()
	{
		if ( empty( $min_user_role = get_option( 'rwd_debug_bar_min_role', 'edit_posts' ) ) || in_array( $min_user_role, [ '*', 'any', 'all' ] ) || current_user_can( $min_user_role ) ) {
			add_filter( 'show_admin_bar', '__return_true', 1000 );
		}
	}

	protected function admin_bar_init ()
	{
		if ( $this->enable_debug_bar() ) {

			if ( !wp_doing_ajax() ) {
				add_action( 'wp_before_admin_bar_render', function () { $this->wp_before_admin_bar_render(); }, PHP_INT_MAX );

				add_action( 'wp_enqueue_scripts', function () { $this->enqueue_scripts(); } );
				add_action( 'admin_enqueue_scripts', function () { $this->enqueue_scripts(); } );

				add_action( 'shutdown', function () { $this->render(); }, 999 );

				add_action( 'rdb/top-menu', function () { $this->top_menu(); }, 999 );
				add_action( 'rdb/top-secondary-menu', function () { $this->top_secondary_menu(); }, 999 );
				add_action( 'rdb/side-menu', function () { $this->side_menu(); } );
				add_action( 'rdb/footer', function () { $this->footer(); } );
			}

			$this->init_panels();
		}
		elseif ( $this->orig_debug_bar && !wp_doing_ajax() ) {
			add_action( 'wp_enqueue_scripts', function () { $this->enqueue_scripts(); } );
			add_action( 'admin_enqueue_scripts', function () { $this->enqueue_scripts(); } );
		}
	}

	protected function enable_debug_bar ( $enable = FALSE )
	{
		if ( $this->orig_debug_bar ) {
			return FALSE;
		}

		if ( !$this->is_wp_login() && !is_favicon() && ( is_admin_bar_showing() || wp_doing_ajax() ) ) {
			if ( empty( $min_user_role = get_option( 'rwd_debug_bar_min_role', 'edit_posts' ) ) || in_array( $min_user_role, [ '*', 'any', 'all' ] ) || current_user_can( $min_user_role ) ) {
				$enable = TRUE;
			}
		}

		$enable = apply_filters( 'debug_bar_enable', $enable, is_admin_bar_showing(), is_user_logged_in(), is_super_admin(), wp_doing_ajax(), $this->is_wp_login() );

		do_action( 'debug_bar_post_init', $enable, is_admin_bar_showing(), is_user_logged_in(), is_super_admin(), wp_doing_ajax(), $this->is_wp_login() );

		return $enable;
	}

	public function is_wp_login ()
	{
		return 'wp-login.php' == basename( $_SERVER['SCRIPT_NAME'] );
	}

	protected function orig_debug_bar_activated ()
	{
		$this->orig_debug_bar = TRUE;
	}

	protected function set_alias ()
	{
		global $pagenow;

		if ( is_favicon() || stripos( $_SERVER["REQUEST_URI"], 'favicon.ico' ) !== FALSE ) {
			return;
		}

		if ( array_key_exists( 'debug_bar', $GLOBALS ) ) {
			return $this->orig_debug_bar = TRUE;
		}

		if ( is_admin() && $pagenow === 'plugins.php' && array_key_exists( 'activate', $_REQUEST ) && array_key_exists( 'plugin', $_REQUEST ) ) {
			if ( ( $_REQUEST['action'] === 'activate' && $_REQUEST['plugin'] === 'debug-bar/debug-bar.php' ) || //
				( $_REQUEST['action'] === 'activate-selected' && in_array( 'debug-bar/debug-bar.php', $_REQUEST['checked'] ?? [] ) ) ) {
				return $this->orig_debug_bar = TRUE;
			}
		}

		class_alias( 'DebugBar\DebugBar', 'Debug_Bar' );
		class_alias( 'DebugBar\Panel', 'Debug_Bar_Panel' );
		$GLOBALS['debug_bar'] = $GLOBALS['rwd_debug_bar'];
	}


	protected function pre_init ()
	{
		do_action( 'debug_bar_pre_init', is_admin_bar_showing(), is_user_logged_in(), is_super_admin(), wp_doing_ajax(), $this->is_wp_login() );
	}

	public static function get_disabled_panels ()
	{
		static $debug_bar_disabled_panels = NULL;

		if ( is_null( $debug_bar_disabled_panels ) ) {
			$debug_bar_disabled_panels = get_current_user_id() ? json_decode( get_user_meta( get_current_user_id(), 'rwd_debug_bar_disabled_panels', TRUE ) ?: '[]' ) : [];
		}

		return $debug_bar_disabled_panels;
	}

	protected function init_panels ()
	{
		$this->panels = apply_filters( 'debug_bar_panels', $this->panels ?? [] );
		do_action( 'debug_bar_post_panels', $this->panels );
	}

	protected function wp_before_admin_bar_render ()
	{
		global $wp_admin_bar;

		$args = [
			'id'     => 'rwd-debug-bar',
			'parent' => 'top-secondary',
			'title'  => apply_filters( 'debug_bar_title', '<i class="fa fa-bug" aria-hidden="true"></i> <span>&nbsp;RWD Debug Bar</span>' ),
			'href'   => '#',
			'meta'   => [ 'class' => implode( ' ', apply_filters( 'debug_bar_classes', [] ) ) ],
		];

		$wp_admin_bar->add_menu( $args );

		foreach ( $this->panels as $panel_key => $panel ) {
			$panel->is_active() && $panel->prerender();
			if ( !$panel->is_visible() ) {
				continue;
			}

			$panel_id = $panel->get_panel_id();

			$args = [
				'id'     => $panel_id,
				'title'  => $panel->title(),
				'parent' => 'rwd-debug-bar',
				'href'   => '#' . $panel_id,
				'meta'   => [
					'class' => 'rwd-debug-admin-bar-link ' . ( $panel->is_active() ? 'rwd-debug-panel-active' : 'rwd-debug-panel-inactive' ),
				],
			];

			$wp_admin_bar->add_menu( $args );
		}
	}

	protected function enqueue_scripts ()
	{
		$prefix = isset( $GLOBALS['debug_bar'] ) ? 'rwd-' : '';
		$suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style( 'jquery-ui', "//code.jquery.com/ui/1.13.2/themes/base/jquery-ui{$suffix}.css", [], NULL );

		wp_enqueue_style( 'tabulator', "https://unpkg.com/tabulator-tables@5.4.2/dist/css/tabulator{$suffix}.css", [], NULL );
		wp_enqueue_script( 'tabulator', "https://unpkg.com/tabulator-tables@5.4.2/dist/js/tabulator{$suffix}.js", [ 'jquery' ], NULL, TRUE );
		wp_enqueue_script( 'jquery-small-pubsub', "https://unpkg.com/jquery-small-pubsub@0.1.0/dist/pubsub{$suffix}.js", [ 'jquery' ], NULL, TRUE );
		wp_enqueue_script( 'tabulator-modules', "https://unpkg.com/tabulator-modules@0.0.11/dist/tabulator-modules{$suffix}.js", [ 'jquery', 'tabulator' ], NULL, TRUE );

		wp_enqueue_style( $prefix . 'debug-bar', plugins_url( "/css/dist/styles{$suffix}.css", RWD_DEBUG_BAR_PLUGIN_FILE ), [ 'admin-menu' ], filemtime( RWD_DEBUG_BAR_PLUGIN_DIR . "/css/dist/styles{$suffix}.css" ) );
		wp_enqueue_script( $prefix . 'debug-bar', plugins_url( "/js/dist/scripts{$suffix}.js", RWD_DEBUG_BAR_PLUGIN_FILE ), [ 'jquery-ui-resizable', 'common' ], filemtime( RWD_DEBUG_BAR_PLUGIN_DIR . "/js/dist/scripts{$suffix}.js" ), TRUE );

		wp_enqueue_script( 'beautify-js', "https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.9.0-beta5/beautify{$suffix}.js" );

		wp_enqueue_style( 'fontawesome', "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome{$suffix}.css", [], '4.7.0' );

		echo '<script> var output_buffering = ', intval( ini_get( 'output_buffering' ) ?? 0 ), ';</script>', PHP_EOL;
	}

	protected function top_menu ()
	{
		?>
		<li id="rwd-debug-bar-flip">
			<button><span class="dashicons dashicons-image-rotate-left"></span></button>
		</li>
		<li id="rwd-debug-bar-window-size"></li>
		<?php
	}

	protected function top_secondary_menu ()
	{
		?>
		<li id="rwd-debug-bar-minimize">
			<button><i class="fa fa-window-minimize" aria-hidden="true"></i></button>
		</li>
		<li id="rwd-debug-bar-restore">
			<button><i class="fa fa-window-restore" aria-hidden="true"></i></button>
		</li>
		<li id="rwd-debug-bar-maximize">
			<button><i class="fa fa-window-maximize" aria-hidden="true"></i></button>
		</li>
		<li id="rwd-debug-bar-close">
			<button><i class="fa fa-times" aria-hidden="true"></i>&nbsp;</button>
		</li>
		<?php
	}

	protected function side_menu ()
	{
		$debugMenu = [];
		$panelKeys = [ '_title', '_capability', '_classes', '_li_id', '_icon', '_submenu' ];

		foreach ( $this->panels as $panel ) {
			if ( !$panel->is_visible() ) {
				continue;
			}

			$panelMenu = [ 'key' => $panel->get_panel_id(), 'active' => $panel->is_active(), 'force' => $panel->is_active( TRUE ) ];

			foreach ( $panelKeys as $panelKey ) {
				if ( property_exists( $panel, $panelKey ) && ( !empty( $panel->$panelKey ) || in_array( $panelKey, [ '_capability' ] ) ) ) {
					$panelMenu[ltrim( $panelKey, '_' )] = $panel->$panelKey;
				}
			}

			$debugMenu[] = $panelMenu;
		}

		echo $this->getMenuOutput( $debugMenu );

		return $debugMenu;
	}

	protected function render_panels ()
	{
		foreach ( $this->panels as $panel ) :
			if ( !$panel->is_visible() ) {
				continue;
			}

			$panel_id = $panel->get_panel_id(); ?>
			<section id="debug-menu-target-<?= $panel_id ?>" class="rwd-debug-menu-target" data-panel="<?= $panel_id ?>">
				<div>
					<?php
					if ( $panel->is_active() ) :
						$panel->render();
					else : ?>
						<b>
							Howdy,
							<br><br>
							The <?= $panel->title() ?> panel is currently disabled. Please enable it from the side menu and then refresh the page.
							<br><br>
							Thanks!
						</b>
					<?php endif; ?>
				</div>
			</section>
		<?php
		endforeach;
	}

	protected function render ()
	{
		foreach ( $this->panels as $panel_key => $panel ) {
			if ( !$panel->is_visible() ) {
				unset( $this->panels[$panel_key] );
			}
		}

		?>
		<div id="rwd-debug-bar-wrap" class="rwd-debug-bar-wrap" style="display: none;" data-ajax-url="<?= admin_url( 'admin-ajax.php' ) ?>">
			<header id="rwd-debug-bar-header" class="rwd-debug-bar-header">
				<nav id="rwd-debug-bar-top-menu" class="rwd-debug-bar-top-menu">
					<ul>
						<?php do_action( 'rdb/top-menu' ); ?>
					</ul>
				</nav>
				<nav id="rwd-debug-bar-top-secondary-menu" class="rwd-debug-bar-top-secondary-menu">
					<ul>
						<?php do_action( 'rdb/top-secondary-menu' ); ?>
					</ul>
				</nav>
			</header>
			<div id="rwd-debug-bar-body" class="rwd-debug-bar-body">
				<nav id="rwd-debug-bar-side-menu" class="rwd-debug-bar-side-menu">
					<ul id="adminmenu">
						<?php do_action( 'rdb/side-menu' ); ?>
					</ul>
				</nav>
				<div id="rwd-debug-bar-main" class="rwd-debug-bar-main">
					<div id="rwd-debug-bar-content" class="rwd-debug-bar-content">
						<?php $this->render_panels(); ?>
						<?php do_action( 'debug_bar' ); ?>
					</div>
					<footer id="rwd-debug-bar-footer" class="rwd-debug-bar-footer">
						<?php do_action( 'rdb/footer' ); ?>
					</footer>
				</div>
			</div>
		</div>
		<?php
	}

	protected function footer ()
	{
		echo '<em>Brought to you by Render Dev.</em>';
	}
}
