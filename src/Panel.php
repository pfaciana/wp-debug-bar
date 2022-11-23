<?php

namespace DebugBar;

/**
 * Debug_Bar_Panel
 *
 * Extended functionality of the Debug Bar Panel
 */
abstract class Panel
{
	/**
	 * @var string unique panel id
	 */
	protected $_panel_id;

	/**
	 * @var string user-friendly panel name
	 */
	public $_title = '';

	/**
	 * Determines if an active panel is visible to the user
	 * This affects the top and sidebar menu links and the panel rendering
	 * The `render` method won't execute if this is `false`,
	 * but the `prerender` method will still execute if the panel is active.
	 *
	 * Common use case is if a panel should only be visible
	 * if there is an error or only if certain event(s) occurred.
	 *
	 * @var bool panel visibility
	 */
	public $_visible = TRUE;

	/**
	 * This is slightly different from `$_visible`.
	 * This determines if the `prerender` method gets executed
	 * (The `$_visible` property will not affect the `prerender` method)
	 * This does not determine if the menu links are visible.
	 * (The `$_visible` property determines if the menu links are visible)
	 * `$_active` and `$_visible` work together in that the both must be true for the `render` method to execute
	 *
	 * @var bool whether the panel is active or not
	 */
	protected $_active;

	/**
	 * @var bool prevent a user from disabling this panel
	 */
	protected $_can_disable = TRUE;

	/**
	 * This is just an alternative to writing...
	 *
	 *     if ( property_exists( $this, 'active' ) && !$this->_active ) {
	 *         return;
	 *     }
	 *
	 * ...in the `init` method
	 *
	 * @var bool for added performance prevent the init from running if disabled
	 */
	public $init_only_if_active = FALSE;

	/**
	 * @var string the WordPress capability to see the menu item in the DebugBar menu
	 */
	public $_capability = NULL;

	/**
	 * Arrays are joined by a space
	 * Strings should be space delimited
	 *
	 * @var string|array classes for the anchor link and wrapping li tag in the DebugBar menu
	 */
	public $_classes;

	/**
	 * @var string html id attribute for the wrapping li tag in the DebugBar menu
	 */
	public $_li_id;

	/**
	 * A string that starts with `dashicons-` will be a dashicon
	 * A string that starts with `fa fa-` will be a font awesome icon from version 4.7
	 * A string that starts with `data:image/svg+xml` will be displayed as a background image
	 * All other strings will be displayed as straight html
	 *
	 * @var string the type of icon for the DebugBar menu
	 */
	public $_icon = 'dashicons-code-standards';

	/**
	 * list of links for panel submenu
	 *
	 * @var array[] {
	 * @type string $title       title for link
	 * @type string $capability  user capability to see the link
	 * @type array  $attrs       key/value pairs of html attributes for the anchor tag
	 * @type array  $attrs_li    key/value pairs of html attributes for the wrapping li tag
	 *                           }
	 */
	public $_submenu = [];

	public function __construct ( $title = '' )
	{
		if ( !isset( $this->_capability ) ) {
			$this->_capability = get_option( 'rwd_debug_bar_min_role', 'edit_posts' );
		}

		if ( in_array( $this->_capability, [ '*', 'any', 'all' ] ) ) {
			$this->_capability = '';
		}

		if ( is_object( $title ) ) {
			if ( property_exists( $title, 'title' ) ) {
				$title = $title->title;
			}
			elseif ( method_exists( $title, 'title' ) ) {
				$title = $title->title();
			}
		}

		$this->title( $title );

		$this->_panel_id = preg_replace( '/[^a-zA-Z0-9_:.]/', '_', get_class( $this ) . '_' . $title );

		$disabled_panels = \DebugBar\DebugBar::get_disabled_panels();

		if ( empty( $has_access = ( empty( $this->_capability ) || current_user_can( $this->_capability ) ) ) ) {
			$this->set_visible( FALSE );
		}

		$this->_active = ( !in_array( $this->_panel_id, $disabled_panels ) || !$this->_can_disable ) && $has_access;

		if ( !$this->init_only_if_active || $this->_active ) {
			if ( $this->init() === FALSE ) {
				$this->set_visible( FALSE );
			}
		}

		if ( !$this->_active ) {
			return do_action( "debug_bar_panel_{$this->_panel_id}_inactive", $this ) ?? FALSE;
		}
		do_action( "debug_bar_panel_{$this->_panel_id}_active", $this );

		add_filter( 'debug_bar_classes', [ $this, 'debug_bar_classes' ] );
	}

	/**
	 * Initialize method for panel
	 *
	 * Makes a Panel active, but hidden, if returns `false`
	 * If anything but `false` is returned (including void), then the panel is active and visible by default
	 *
	 * @return void|false
	 */
	public function init () { }

	/**
	 * Preflight checks prior to executing the `render` method
	 *
	 * Typically used to determine panel visibility
	 *
	 * @return void
	 */
	public function prerender () { }

	/**
	 * Code the executes to render the panel UI
	 *
	 * @return void
	 */
	public function render () { }

	/**
	 * Active getter
	 *
	 * @return bool
	 */
	public function is_active ( $check_disableable = FALSE )
	{
		if ( $check_disableable ) {
			return $this->_active && !$this->_can_disable;
		}

		return $this->_active;
	}

	/**
	 * Visibility getter
	 *
	 * @return bool
	 */
	public function is_visible ()
	{
		return $this->_visible;
	}

	/**
	 * Visibility setter
	 *
	 * @param bool $visible panel visibility
	 *
	 * @return void
	 */
	public function set_visible ( $visible )
	{
		$this->_visible = $visible;
	}

	/**
	 * Title getter/setter
	 *
	 * @param string $title panel title
	 *
	 * @return string
	 */
	public function title ( $title = NULL )
	{
		if ( empty( $title ) ) {
			return $this->_title;
		}

		return $this->_title = $title;
	}

	/**
	 * Panel ID getter
	 *
	 * @return string
	 */
	public function get_panel_id ()
	{
		return $this->_panel_id;
	}

	/**
	 * HTML class(es) added to the main header navigation for the li wrapper of the Debug Bar link
	 *
	 * @param array $classes
	 *
	 * @return array
	 */
	public function debug_bar_classes ( $classes )
	{
		return $classes;
	}
}