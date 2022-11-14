<?php

use \AspectMock\Test as test;
use DebugBar\Traits\MenuTrait;

class MenuTraitTest extends \Codeception\Test\Unit
{
	/**
	 * @var \UnitTester
	 */
	protected $tester;

	protected function _before ()
	{
	}

	protected function _after ()
	{
		test::clean();
	}

	// tests
	public function testGetHtmlAttrs ()
	{
		/** @var MenuTrait $mock */
		$mock = $this->getMockForTrait( MenuTrait::class );

		$html = $mock->getHtmlAttrs();
		$this->assertEquals( '', $html, 'empty input' );

		$attrs = [ 'data-test' => 1 ];
		$html  = $mock->getHtmlAttrs( $attrs );
		$this->assertEquals( 'data-test="1"', $html, 'single attr' );

		$attrs = [
			'id'         => 'some_id',
			'class'      => (object) [ 'class-a', 'class-b' ],
			'data-true'  => TRUE,
			'data-false' => FALSE,
			'data-null'  => NULL,
			'data-json'  => '{"a":"some \'quoted\' word"}',
		];
		$html  = $mock->getHtmlAttrs( $attrs );
		$this->assertEquals( 'id="some_id" class="class-a class-b" data-true="1" data-false="0" data-null="" data-json="{\"a\":\"some \'quoted\' word\"}"', $html, 'multi-attrs' );

		$attrs = 'short-hand for-class';
		$html  = $mock->getHtmlAttrs( $attrs );
		$this->assertEquals( 'class="short-hand for-class"', $html, 'shorthand for class' );

		$attrs = 'id="some_id" class="class-a class-b"';
		$html  = $mock->getHtmlAttrs( $attrs );
		$this->assertEquals( $attrs, $html, 'pre-parsed, just return' );
	}

	public function testGetMenu ()
	{
		function esc_attr ()
		{
			return func_get_arg( 0 );
		}

		function wptexturize ()
		{
			return func_get_arg( 0 );
		}

		function esc_url ()
		{
			return func_get_arg( 0 );
		}

		function sanitize_html_class ()
		{
			return func_get_arg( 0 );
		}

		function is_user_logged_in ()
		{
			return TRUE;
		}

		function current_user_can ( $role )
		{
			return $role !== 'guest';
		}

		function clean_up ( $html )
		{
			return str_replace( [ "\n", "\t" ], [ '' ], $html );
		}

		/** @var MenuTrait $mock */
		$mock = $this->getMockForTrait( MenuTrait::class );

		$args = $mock->getMenuArgs();
		$this->assertEquals( [ [], [] ], $args, 'empty input' );

		$debugMenu = [
			[
				'title' => 'Some Panel Item',
				'key'   => 'panel_633a3f6ed1db9',
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [];
		$this->assertEquals( [ $menu, $submenu ], $args, 'single item args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>' );
		$this->assertEquals( $expected, $html, 'single item output' );

		$debugMenu = [
			[
				'title' => 'Some Panel Item #1',
				'key'   => 'panel_633a3f6ed1db9',
			],
			[
				'classes' => [ 'some-other-class', 'wp-menu-separator' ],
				'li_id'   => 'divider_1',
			],
			[
				'title'   => 'Some Panel Item #2',
				'key'     => 'panel_633a43b8997df',
				'classes' => 'some-random-class',
			],
			[
				'title'   => 'Some Panel Item #3',
				'key'     => 'panel_633a43b964b7c',
				'classes' => [ 'class-a', 'class-b' ],
				'li_id'   => 'item_3',
				'icon'    => '',
			],
			[
				'title' => 'Key missing, should be ignored',
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item #1',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'',
				TRUE,
				FALSE,
			],
			[
				'',
				'',
				'',
				'',
				'some-other-class wp-menu-separator',
				'divider_1',
				'',
			],
			[
				'Some Panel Item #2',
				'',
				'panel_633a43b8997df',
				'',
				'some-random-class',
				'',
				'',
				TRUE,
				FALSE,
			],
			[
				'Some Panel Item #3',
				'',
				'panel_633a43b964b7c',
				'',
				'class-a class-b',
				'item_3',
				'',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [];
		$this->assertEquals( [ $menu, $submenu ], $args, 'multi-items args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item #1</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>
					<li id="rwd-debug-menu-link-" class="wp-not-current-submenu menu-top some-other-class wp-menu-separator" aria-hidden="true">
						<div class="separator"></div>
					</li>
					<li id="rwd-debug-menu-link-panel_633a43b8997df" class="wp-not-current-submenu menu-top some-random-class" data-panel="panel_633a43b8997df">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top some-random-class">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item #2</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>
					<li id="rwd-debug-menu-link-panel_633a43b964b7c" class="wp-not-current-submenu menu-top class-a class-b" data-panel="panel_633a43b964b7c">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top class-a class-b">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item #3</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>' );
		$this->assertEquals( $expected, $html, 'multi-items output' );

		$debugMenu = [
			[
				'title'   => 'Some Panel Item',
				'key'     => 'panel_633a3f6ed1db9',
				'submenu' => [
					[
						'title' => 'Panel Sub Menu Item',
					],
					[
						'ignore' => 'Missing Title',
					],
				],
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [
			'panel_633a3f6ed1db9' => [
				[
					'Panel Sub Menu Item',
					'',
					'',
					'',
					'',
					'',
				],
			],
		];
		$this->assertEquals( [ $menu, $submenu ], $args, 'with simple submenu args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-has-submenu wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-has-submenu wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
						<ul class="wp-submenu wp-submenu-wrap">
							<li class="wp-submenu-head" aria-hidden="true">Some Panel Item</li>
							<li><a href="javascript:void(0);">Panel Sub Menu Item</a></li>
						</ul>
					</li>' );
		$this->assertEquals( $expected, $html, 'with simple submenu output' );

		$submenu_callback = function () {
			return '<li>Not a Link</li><li>Other Text</li>';
		};
		$debugMenu        = [
			[
				'title'   => 'Some Panel Item',
				'key'     => 'panel_633a3f6ed1db9',
				'submenu' => $submenu_callback,
			],
		];
		$args             = $mock->getMenuArgs( $debugMenu );
		$menu             = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'',
				TRUE,
				FALSE,
			],
		];
		$submenu          = [
			'panel_633a3f6ed1db9' => $submenu_callback,
		];
		$this->assertEquals( [ $menu, $submenu ], $args, 'with submenu callback args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-has-submenu wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-has-submenu wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
						<ul class="wp-submenu wp-submenu-wrap">
							<li class="wp-submenu-head" aria-hidden="true">Some Panel Item</li>
							<li>Not a Link</li>
							<li>Other Text</li>
						</ul>
					</li>' );
		$this->assertEquals( $expected, $html, 'with submenu callback output' );

		$debugMenu = [
			[
				'title'   => 'Some Panel Item',
				'key'     => 'panel_633a3f6ed1db9',
				'submenu' => [
					[
						'title'    => 'Panel Sub Menu Item #1',
						'attrs'    => [
							'class'     => (object) [ 'class-a', 'class-b' ],
							'data-true' => TRUE,
						],
						'attrs_li' => 'id="some_id" data-false="0"',
					],
					[
						'title'      => 'Panel Sub Menu Item #2',
						'capability' => 'read',
						'attrs'      => 'class-y class-z',
					],
					[
						'title'      => 'Panel Sub Menu Item #3',
						'capability' => 'guest',
						'classes'    => [ 'this-is-not-accessible' ],
					],
				],
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [
			'panel_633a3f6ed1db9' => [
				[
					'Panel Sub Menu Item #1',
					'',
					'',
					'',
					'class="class-a class-b" data-true="1"',
					'id="some_id" data-false="0"',
				],
				[
					'Panel Sub Menu Item #2',
					'read',
					'',
					'',
					'class="class-y class-z"',
					'',
				],
				[
					'Panel Sub Menu Item #3',
					'guest',
					'',
					'',
					'class="this-is-not-accessible"',
					'',
				],
			],
		];

		$this->assertEquals( [ $menu, $submenu ], $args, 'with simple submenu args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-has-submenu wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-has-submenu wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
						<ul class="wp-submenu wp-submenu-wrap">
							<li class="wp-submenu-head" aria-hidden="true">Some Panel Item</li>
							<li id="some_id" data-false="0">
								<a href="javascript:void(0);" class="class-a class-b" data-true="1">Panel Sub Menu Item #1</a>
							</li>
							<li>
								<a href="javascript:void(0);" class="class-y class-z">Panel Sub Menu Item #2</a>
							</li>
						</ul>
					</li>' );
		$this->assertEquals( $expected, $html, 'with simple submenu output' );

		$debugMenu = [
			[
				'title' => 'Some Panel Item',
				'key'   => 'panel_633a3f6ed1db9',
				'icon'  => 'none',
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'none',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [];
		$this->assertEquals( [ $menu, $submenu ], $args, 'without icon args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before" aria-hidden="true"><br /></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>' );
		$this->assertEquals( $expected, $html, 'without icon output' );

		$debugMenu = [
			[
				'title' => 'Some Panel Item',
				'key'   => 'panel_633a3f6ed1db9',
				'icon'  => 'dashicons-performance',
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'dashicons-performance',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [];
		$this->assertEquals( [ $menu, $submenu ], $args, 'with dash icon args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image dashicons-before dashicons-performance" aria-hidden="true"><br /></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>' );
		$this->assertEquals( $expected, $html, 'with dash icon output' );

		$debugMenu = [
			[
				'title' => 'Some Panel Item',
				'key'   => 'panel_633a3f6ed1db9',
				'icon'  => 'data:image/svg+xml;base64,abc123',
			],
		];
		$args      = $mock->getMenuArgs( $debugMenu );
		$menu      = [
			[
				'Some Panel Item',
				'',
				'panel_633a3f6ed1db9',
				'',
				'',
				'',
				'data:image/svg+xml;base64,abc123',
				TRUE,
				FALSE,
			],
		];
		$submenu   = [];
		$this->assertEquals( [ $menu, $submenu ], $args, 'with svg icon args' );
		$html     = clean_up( $mock->getMenuOutput( $debugMenu ) );
		$expected = clean_up( '
					<li id="rwd-debug-menu-link-panel_633a3f6ed1db9" class="wp-not-current-submenu menu-top" data-panel="panel_633a3f6ed1db9">
						<a href="javascript:void(0);" class="rwd-debug-menu-link wp-not-current-submenu menu-top">
							<div class="wp-menu-arrow"><div></div></div>
							<div class="wp-menu-image svg" style="background-image:url(\'data:image/svg+xml;base64,abc123\')" aria-hidden="true"><br /></div>
							<div class="wp-menu-name">Some Panel Item</div>
						</a>
						<button class="rwd-debug-panel-action" data-activate="0"><i class="fa fa-toggle-on" aria-hidden="true"></i></button>
					</li>' );
		$this->assertEquals( $expected, $html, 'with svg icon output' );
	}
}