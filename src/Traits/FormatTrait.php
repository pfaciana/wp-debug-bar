<?php

namespace DebugBar\Traits;

trait FormatTrait
{
	protected $fileLinkFormat;
	protected $plugins = [];
	protected $mu_plugins = [];
	protected $prefixes = [];

	protected $tabulatorConfigs = [
		'boolean' => [
			'formatter' => 'boolean',
			'width'     => 115,
		],
		'string'  => [
			'formatter' => 'string',
		],
		'integer' => [
			'formatter' => 'number',
			'width'     => 100,
		],
		'list'    => [
			'formatter' => 'list',
		],
		'frozen'  => [
			'frozen' => TRUE,
			'width'  => 175,
		],
	];

	public function formatValue ( $value, $options = [] )
	{
		$options = wp_parse_args( $options, [
			'maxStrLength'   => 25,
			'floatPrecision' => 5,
		] );

		if ( is_string( $value ) ) {
			return [ 'type' => 'string', 'text' => htmlspecialchars( trim( strlen( $value ) > $options['maxStrLength'] ? substr( $value, 0, $options['maxStrLength'] ) . '...' : $value ) ) ];
		}

		if ( is_float( $value ) ) {
			if ( is_nan( $value ) ) {
				return [ 'type' => 'float', 'text' => 'NaN' ];
			}
			if ( is_infinite( $value ) ) {
				return [ 'type' => 'float', 'text' => 'INF' ];
			}

			return [ 'type' => 'float', 'text' => round( $value, $options['floatPrecision'] ) ];
		}

		if ( !isset( $value ) || is_scalar( $value ) ) {
			return [ 'type' => strtolower( gettype( $value ) ), 'text' => $value ];
		}

		if ( is_array( $value ) ) {
			$size = count( $value );
			$type = ( $size - 1 ) === array_key_last( $value ) || empty( $value ) ? '' : 'assoc_';

			return [ 'type' => 'array', 'text' => "{$type}array[{$size}]" ];
		}

		if ( is_object( $value ) ) {
			if ( get_class( $value ) === 'Closure' ) {
				return [ 'type' => 'callable', 'text' => 'Closure()' ];
			}

			return [ 'type' => 'object', 'text' => get_class( $value ) . "{" . count( get_object_vars( $value ) ) . "}" ];
		}

		if ( is_resource( $value ) ) {
			return [ 'type' => 'resource', 'text' => 'Resource' ];
		}

		if ( is_callable( $value ) ) {
			return [ 'type' => 'callable', 'text' => 'Function()' ];
		}

		return [ 'type' => strtolower( gettype( $value ) ), 'text' => gettype( $value ) ];
	}

	public function camelcase_to_underscores ( $str )
	{
		$chars = str_split( $str );
		array_walk( $chars, function ( &$char ) {
			if ( ctype_upper( $char ) ) {
				$char = '_' . strtolower( $char );
			}
		} );

		return implode( '', $chars );
	}

	public function humanize ( $str )
	{
		$str = $this->camelcase_to_underscores( $str );

		return preg_replace( '/\s+/', ' ', ucwords( trim( str_replace( [ '_', '-' ], ' ', $str ) ) ) );
	}

	public function setFileLinkFormat ( $format )
	{
		return $this->fileLinkFormat = $format;
	}

	public function getFileLinkFormat ( $format = FALSE, $saveAsDefaultFormat = FALSE )
	{
		if ( $format === FALSE && isset( $this->fileLinkFormat ) ) {
			return $this->fileLinkFormat;
		}

		$fileLinkFormat = is_string( $format ) ? $format : ini_get( 'xdebug.file_link_format' );

		if ( empty( $fileLinkFormat ) ) {
			return FALSE;
		}

		$fileLinkFormat = str_replace( [ '%f', '%l' ], [ '%1$s', '%2$d' ], $fileLinkFormat );

		if ( !isset( $this->fileLinkFormat ) || $saveAsDefaultFormat ) {
			$this->setFileLinkFormat( $fileLinkFormat );
		}

		return $fileLinkFormat;
	}

	public function setMuPluginNames ()
	{
		foreach ( wp_get_mu_plugins() as $mu_plugin_file ) {
			$mu_plugin_name = get_file_data( $mu_plugin_file, [ 'name' => 'Plugin Name' ], 'plugin' )['name'];

			$this->mu_plugins[wp_normalize_path( $mu_plugin_file )] = $mu_plugin_name;
		}

		return $this->mu_plugins;
	}

	public function getMuPluginNames ()
	{
		return empty( $this->mu_plugins ) ? $this->setMuPluginNames() : $this->mu_plugins;
	}

	public function setPluginNames ()
	{
		foreach ( get_option( 'active_plugins' ) as $plugin_file ) {
			$plugin_name       = get_file_data( WP_PLUGIN_DIR . '/' . $plugin_file, [ 'name' => 'Plugin Name' ], 'plugin' )['name'];
			$plugin_file_parts = explode( '/', $plugin_file );

			$this->plugins[array_shift( $plugin_file_parts )] = $plugin_name;
		}

		return $this->plugins;
	}

	public function getPluginNames ()
	{
		return empty( $this->plugins ) ? $this->setPluginNames() : $this->plugins;
	}

	public function setPrefixNames ()
	{
		$wpDir        = wp_normalize_path( ABSPATH . '/' );
		$wpContentDir = wp_normalize_path( WP_CONTENT_DIR . '/' );
		$wpThemeDir   = $wpContentDir . 'themes/';
		$wpThemeChild = wp_normalize_path( get_stylesheet_directory() );
		$wpTheme      = wp_normalize_path( get_template_directory() );
		$wpPluginDir  = wp_normalize_path( WP_PLUGIN_DIR . '/' );

		foreach ( $this->getPluginNames() as $path => $name ) {
			$this->prefixes[$wpPluginDir . $path . '/'] = "Plugin: {$name}";
		}
		$this->prefixes[$wpPluginDir] = 'Plugin';
		foreach ( $this->getMuPluginNames() as $path => $name ) {
			$this->prefixes[$path] = "MU Plugin: {$name}";
		}
		$this->prefixes[$wpContentDir . 'mu-plugins/'] = 'MU Plugin';
		$this->prefixes[$wpThemeChild . '/']           = 'Child Theme: ' . wp_get_theme( str_replace( $wpThemeDir, '', $wpThemeChild ) )->get( 'Name' );
		$this->prefixes[$wpTheme . '/']                = 'Theme: ' . wp_get_theme( str_replace( $wpThemeDir, '', $wpTheme ) )->get( 'Name' );
		$this->prefixes[$wpThemeDir]                   = 'Theme';
		$this->prefixes[$wpDir . 'wp-admin/']          = 'WP Admin';
		$this->prefixes[$wpDir . 'wp-includes/']       = 'WP Core';
		$this->prefixes[$wpDir]                        = 'Root';

		return $this->prefixes;
	}

	public function getPrefixNames ()
	{
		return empty( $this->prefixes ) ? $this->setPrefixNames() : $this->prefixes;
	}

	public function getWordPressPathText ( $filename, $separator = ' > ' )
	{
		$filename = wp_normalize_path( $filename );

		foreach ( $this->getPrefixNames() as $prefixPath => $prefix ) {
			if ( stripos( $filename, $prefixPath ) !== FALSE ) {
				return $prefix . $separator . str_replace( $prefixPath, '', $filename );
			}
		}

		return $filename;
	}

	public function getFileLinkUrl ( $filename, $line = 1, $format = FALSE )
	{
		if ( function_exists( 'wp_normalize_path' ) ) {
			$filename = wp_normalize_path( $filename );
		}

		if ( !file_exists( $filename ) || empty( $fileLinkFormat = $this->getFileLinkFormat( $format ) ) ) {
			return FALSE;
		}

		$line = ( $line = intval( $line ) ) > 0 ? $line : 1;

		return esc_attr( sprintf( $fileLinkFormat, $filename, $line ) );
	}

	public function getFileLinkTag ( $config = [] )
	{
		$config = wp_parse_args( $config, [
			'file'          => NULL,
			'line'          => 1,
			'text'          => NULL,
			'url'           => NULL,
			'format'        => FALSE,
			'link_class'    => 'debug-bar-ide-link',
			'no_link_class' => 'debug-bar-ide-file',
		] );

		if ( empty( $url = $config['url'] ) ) {
			[ 'url' => $url, 'text' => $text ] = $this->getFileLinkArray( $config['file'], $config['line'], $config['format'] );
		}

		if ( !empty( $config['text'] ) ) {
			$text = $config['text'];
		}

		if ( !isset( $text ) ) {
			$text = esc_html( $text ?? basename( $config['file'] ) );
		}

		if ( stripos( $url, 'http' ) !== 0 ) {
			return '<span class="' . $config['no_link_class'] . '">' . $text . '</span>';
		}

		return '<a href="' . $url . '" class="' . $config['link_class'] . '">' . $text . '</a>';
	}

	public function getFileLine ( $callback, $onlyFile = FALSE )
	{
		try {
			if ( gettype( $callback ) === 'array' ) {
				$ref = new \ReflectionMethod( is_object( $callback[0] ) ? get_class( $callback[0] ) : $callback[0], $callback[1] );
			}
			elseif ( is_string( $callback ) || get_class( $callback ) === 'Closure' ) {
				$ref = new \ReflectionFunction( $callback );

			}
			elseif ( gettype( $callback ) === 'object' ) {
				$ref = new \ReflectionClass( $callback );
			}
			else {
				return $onlyFile ? '' : [ '', '' ];
			}
			$file = $ref->getFileName();
			$line = $ref->getStartLine();
		}
		catch ( \Exception $e ) {
			return $onlyFile ? '' : [ '', '' ];
		}

		return $onlyFile ? $file : [ $file, $line ];
	}

	public function getFileLinkArray ( $file, $line = 0, $format = FALSE )
	{
		if ( empty( $file ) ) {
			return FALSE;
		}

		if ( $line === FALSE ) {
			[ $file, $line ] = $this->getFileLine( $file );
		}

		return [
			'url'  => $this->getFileLinkUrl( $file, $line, $format ) ?: ( $file . ':' . $line ),
			'text' => $this->getWordPressPathText( $file ) . ( $line > 1 ? ':' . $line : '' ),
		];
	}
}