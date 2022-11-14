<?php

namespace DebugBar\Traits;

trait MenuTrait
{
	public function getHtmlAttrs ( $attrs = NULL )
	{
		if ( empty( $attrs ) ) {
			return '';
		}

		if ( is_string( $attrs ) ) {
			return strpos( $attrs, '=' ) === FALSE ? "class=\"{$attrs}\"" : $attrs;
		}

		$items = (array) $attrs;
		$attrs = [];
		foreach ( $items as $key => $value ) {
			if ( is_array( $value ) || is_object( $value ) ) {
				$value = implode( ' ', (array) $value );
			}
			elseif ( is_bool( $value ) ) {
				$value = $value ? '1' : '0';
			}
			$key     = is_numeric( $key ) ? 'class' : $key;
			$value   = addcslashes( (string) $value, '"' );
			$attrs[] = "{$key}=\"{$value}\"";
		}

		return implode( ' ', $attrs );
	}

	public function getMenuArgs ( $debugMenu = [] )
	{
		$menu = $submenu = [];

		foreach ( $debugMenu as $priority => $item ) {
			if ( is_array( $classes = $item['classes'] ?? '' ) ) {
				$classes = implode( ' ', $classes );
			}
			$li_id = $item['li_id'] ?? '';
			if ( strpos( $classes, 'wp-menu-separator' ) !== FALSE ) {
				$menu[] = [ '', '', '', '', $classes, $li_id, '' ];
				continue;
			}
			if ( empty( $menu_title = $item['title'] ?? FALSE ) || empty( $key = $item['key'] ?? FALSE ) ) {
				continue;
			}
			$icon = $item['icon'] ?? '';

			$menu[] = [ $menu_title, '', $key, '', $classes, $li_id, $icon, $item['active'] ?? TRUE, $item['force'] ?? FALSE ];

			if ( array_key_exists( 'submenu', $item ) && !empty( $sub_items = $item['submenu'] ) ) {
				if ( is_callable( $sub_items ) ) {
					$submenu[$key] = $sub_items;
				}
				else {
					foreach ( $sub_items as $sub_item ) {
						if ( empty( $sub_title = $sub_item['title'] ?? FALSE ) ) {
							continue;
						}
						$sub_capability  = $sub_item['capability'] ?? '';
						$attrs           = $this->getHtmlAttrs( $sub_item['attrs'] ?? $sub_item['classes'] ?? '' );
						$attrs_li        = $this->getHtmlAttrs( $sub_item['attrs_li'] ?? $sub_item['classes_li'] ?? '' );
						$submenu[$key][] = [ $sub_title, $sub_capability, '', '', $attrs, $attrs_li, ];
					}
				}
			}
		}

		return [ $menu, $submenu ];
	}

	public function getMenuOutput ( $debugMenu, $submenu_as_parent = TRUE )
	{
		ob_start();

		[ $menu, $submenu ] = $this->getMenuArgs( $debugMenu );
		$this->_wp_menu_output( $menu, $submenu, $submenu_as_parent );

		return ob_get_clean();
	}

	protected function _wp_menu_output ( $menu, $submenu )
	{
		foreach ( $menu as $priority => $item ) {
			$id    = ' id="rwd-debug-menu-link-' . $item[2] . '"';
			$class = 'wp-not-current-submenu menu-top ' . \esc_attr( $item[4] ?? '' );

			if ( FALSE !== strpos( $class, 'wp-menu-separator' ) ) {
				$class = $class ? ' class="' . $class . '"' : '';
				echo "\n\t<li$id$class aria-hidden=\"true\"><div class=\"separator\"></div></li>";
				continue;
			}

			$submenu_items = [];
			if ( !empty( $submenu[$item[2]] ) ) {
				$class         = "wp-has-submenu $class";
				$submenu_items = $submenu[$item[2]];
			}
			$li_class = $class ? ' class="' . trim( $class ) . '"' : '';

			echo "\n\t<li$id$li_class data-panel=\"$item[2]\">";

			$img       = '';
			$img_style = '';
			$img_class = ' dashicons-before';

			if ( !empty( $item[6] ) ) {
				$img = $item[6];

				if ( 'none' === $item[6] || 'div' === $item[6] ) {
					$img = '<br />';
				}
				elseif ( 0 === strpos( $item[6], 'data:image/svg+xml;base64,' ) ) {
					$img       = '<br />';
					$img_style = ' style="background-image:url(\'' . \esc_attr( $item[6] ) . '\')"';
					$img_class = ' svg';
				}
				elseif ( 0 === strpos( $item[6], 'dashicons-' ) ) {
					$img       = '<br />';
					$img_class = ' dashicons-before ' . sanitize_html_class( $item[6] );
				}
				elseif ( 0 === strpos( $item[6], 'fa fa-' ) ) {
					$img = '<i class="' . $item[6] . '" aria-hidden="true"></i>';
				}
			}

			$class = $class ? ' class="' . trim( 'rwd-debug-menu-link ' . $class ) . '"' : '';

			echo "\n\t<a href=\"javascript:void(0);\"$class><div class=\"wp-menu-arrow\"><div></div></div><div class=\"wp-menu-image$img_class\"$img_style aria-hidden=\"true\">$img</div><div class=\"wp-menu-name\">" . wptexturize( $item[0] ) . "</div></a>";

			if ( empty( $item[8] ) && is_user_logged_in() ) {
				echo "\n\t<button class=\"rwd-debug-panel-action\" data-activate=\"" . ( $item[7] ? '0' : '1' ) . "\"><i class=\"fa fa-toggle-" . ( $item[7] ? 'on' : 'off' ) . "\" aria-hidden=\"true\"></i></button>";
			}

			if ( !empty( $submenu_items ) ) {
				echo "\n\t<ul class=\"wp-submenu wp-submenu-wrap\">";
				echo "<li class=\"wp-submenu-head\" aria-hidden=\"true\">{$item[0]}</li>";
				if ( is_array( $submenu_items ) ) {
					foreach ( $submenu_items as $sub_priority => $sub_item ) {
						if ( !empty( $sub_item[1] && !current_user_can( $sub_item[1] ) ) ) {
							continue;
						}
						$attrs    = empty( $attrs = $this->getHtmlAttrs( $sub_item[4] ) ) ? '' : ' ' . $attrs;
						$attrs_li = empty( $attrs_li = $this->getHtmlAttrs( $sub_item[5] ) ) ? '' : ' ' . $attrs_li;
						echo "<li$attrs_li><a href=\"javascript:void(0);\"$attrs>" . wptexturize( $sub_item[0] ) . "</a></li>";
					}
				}
				elseif ( is_callable( $submenu_items ) ) {
					echo $submenu_items();
				}
				echo '</ul>';
			}

			echo '</li>';
		}

	}
}