<?php

namespace DebugBar\Panel;

class Template extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_title = 'Template';
	public $_icon = 'dashicons-admin-appearance';
	public $init_only_if_active = TRUE;

	protected $template_hierarchy = [];
	protected $template_file;
	protected $body_classes;

	public function init ()
	{
		add_filter( 'template_include', [ $this, 'template_include' ], PHP_INT_MAX );
		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_filter( 'body_class', [ $this, 'body_class' ], PHP_INT_MAX );
	}

	public function template_include ( $template )
	{
		return $this->template_file = wp_normalize_path( $template );
	}

	public function template_redirect ()
	{
		$templates = [
			'embed'             => 'is_embed',
			'404'               => 'is_404',
			'search'            => 'is_search',
			'front_page'        => 'is_front_page',
			'home'              => 'is_home',
			'post_type_archive' => 'is_post_type_archive',
			'taxonomy'          => 'is_tax',
			'attachment'        => 'is_attachment',
			'single'            => 'is_single',
			'page'              => 'is_page',
			'singular'          => 'is_singular',
			'category'          => 'is_category',
			'tag'               => 'is_tag',
			'author'            => 'is_author',
			'date'              => 'is_date',
			'archive'           => 'is_archive',
			'index'             => '__return_true',
		];

		foreach ( $templates as $template => $conditional ) {
			$get_template = "get_{$template}_template";

			if ( !function_exists( $conditional ) || !function_exists( $get_template ) || !call_user_func( $conditional ) ) {
				continue;
			}

			$filter = str_replace( '_', '', $template );
			add_filter( "{$filter}_template_hierarchy", [ $this, 'template_hierarchy' ], PHP_INT_MAX );
			$get_template();
			remove_filter( "{$filter}_template_hierarchy", [ $this, 'template_hierarchy' ], PHP_INT_MAX );
		}

	}

	public function template_hierarchy ( array $templates )
	{
		$this->template_hierarchy = array_merge( $this->template_hierarchy, $templates );

		return $templates;
	}

	public function body_class ( array $classes )
	{
		$copy = $classes;
		sort( $copy );
		$this->body_classes = $copy;

		return $classes;
	}

	protected function get_theme_card ()
	{
		?>
		<p><?= $this->getFileLinkTag( [ 'file' => get_stylesheet_directory() . '/functions.php' ] ) ?></p>
		<?php
		$is_child_theme = get_stylesheet() !== get_template();
		if ( $is_child_theme ) : ?>
			<h3>Parent Theme</h3>
			<p><?= get_template() ?></p>
		<?php
		endif;
	}

	protected function get_template_file_card ()
	{
		?>
		<p><?= $this->getFileLinkTag( [ 'file' => $this->template_file ] ) ?></p>
		<?php
	}

	protected function get_template_hierarchy_card ()
	{
		?>
		<ul class="debug-bar-reset-list debug-bar-template-hierarchy">
			<?php foreach ( $this->template_hierarchy as $filename ) :
				$active = basename( $this->template_file ) === $filename;
				$filepath = locate_template( $filename );
				$text = ( $active ? '&raquo;&nbsp;' : '' ) . $filename;
				$text = $filepath ? $this->getFileLinkTag( [ 'file' => $filepath, 'text' => $text ] ) : $text;
				?>
				<li style="font-weight: <?= $active ? 'bold' : 'normal' ?>"><?= $text ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	protected function get_body_classes_card ()
	{
		?>
		<ul class="debug-bar-reset-list">
			<?php foreach ( ( $this->body_classes ?? [] ) as $class ) : ?>
				<li><?= esc_html( $class ) ?></li>
			<?php endforeach; ?>
		</ul>
		<?php
	}

	protected function getThemeFeatures ()
	{
		global $_wp_theme_features, $_wp_registered_theme_features;

		$_theme_features = array_keys( $_wp_theme_features );

		$themeFeatures = $_wp_registered_theme_features;

		foreach ( $_wp_theme_features as $name => $value ) {
			if ( !array_key_exists( $name, $themeFeatures ) ) {
				$themeFeatures[$name] = [
					'type'         => $this->formatValue( $value )['type'],
					'variadic'     => FALSE,
					'description'  => '',
					'show_in_rest' => FALSE,
				];
			}
		}

		ksort( $themeFeatures );

		foreach ( $themeFeatures as $name => $themeFeature ) {
			$themeFeatures[$name]['name']    = $name;
			$themeFeatures[$name]['enabled'] = in_array( $name, $_theme_features );
			unset( $themeFeatures[$name]['show_in_rest'] );
		}

		$themeFeatures = array_values( $themeFeatures );

		$themeScalars        = [
			'name'        => 'string',
			'type'        => 'list',
			'enabled'     => 'boolean',
			'variadic'    => 'boolean',
			'description' => 'string',
		];
		$themeFeaturesConfig = [];
		foreach ( $themeScalars as $field => $type ) {
			$themeFeatureConfig = [ 'title' => str_replace( '_', ' ', $field ), 'field' => $field, ] + $this->tabulatorConfigs[$type];
			if ( in_array( $field, [ 'name' ] ) ) {
				$themeFeatureConfig += $this->tabulatorConfigs['frozen'];
			}
			$themeFeaturesConfig[] = $themeFeatureConfig;
		}

		?>

		<h3>Theme Features</h3>
		<div id="theme-features-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var themeFeatures = <?= json_encode( array_values( $themeFeatures ?? [] ) ) ?>;

				if (themeFeatures.length) {
					T.Create("#theme-features-table", {
						data: themeFeatures,
						layout: 'fitDataStretch',
						columns: <?= json_encode( $themeFeaturesConfig ?? [] ) ?>,
					});
				}
			});
		</script>
		<?php
	}

	protected function getShortcodes ()
	{
		global $shortcode_tags;

		$shortcodes = [];

		foreach ( $shortcode_tags as $tag => $callback ) {
			[ $file, $line ] = $this->getFileLine( $callback );
			$shortcodes[] = [ 'tag' => "[{$tag}]", 'file' => [ $this->getFileLinkArray( $file, $line ) ] ];
		}

		?>

		<h3>Shortcodes</h3>
		<div id="shortcodes-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var shortcodes = <?= json_encode( array_values( $shortcodes ?? [] ) ) ?>;

				if (shortcodes.length) {
					T.Create("#shortcodes-table", {
						data: shortcodes,
						paginationSize: 5,
						layout: 'fitDataStretch',
						columns: [
							{title: 'Tag', field: 'tag', formatter: 'string'},
							{title: 'Render Callback', field: 'file', formatter: 'file'},
						],
					});
				}
			});
		</script>
		<?php
	}

	public function render ()
	{
		$cards = [
			'theme'              => NULL,
			'template_file'      => NULL,
			'template_hierarchy' => NULL,
			'body_classes'       => NULL,
		];

		?>
		<h3>Templates Panel</h3>
		<?php
		foreach ( $cards as $card => $size ) {
			$this->addCard( $this->humanize( $card ), [ $this, "get_{$card}_card" ], $size );
		}
		$this->showCards();
		$this->getShortcodes();
		$this->getThemeFeatures();
	}
}
