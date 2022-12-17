<?php

namespace DebugBar\Panel;

class Environment extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_title = 'Server';
	public $_icon = 'fa fa-server';
	public $_panel_id;
	public $_capability = 'manage_options';

	public function render ()
	{
		$this->addTab( 'Details', [ $this, 'getDetails' ] );
		$this->addTab( 'WP Constants', [ $this, 'getWPConstants' ] );
		$this->addTab( 'PHP Extensions', [ $this, 'getExtensions' ] );
		$this->addTab( 'Error Reporting', [ $this, 'getErrorReporting' ] );
		$this->showTabs( $this->_panel_id );
	}

	protected function getAbsolutePath ( $path )
	{
		if ( !empty( $abs_path = realpath( $path ) ) ) {
			return $abs_path;
		}

		if ( !empty( $abs_path = realpath( dirname( $_SERVER['SCRIPT_FILENAME'] ) . '/' . $path ) ) ) {
			return $abs_path;
		}

		return $path;
	}

	protected function getDetails ()
	{
		$cards = [
			'PHP Setup'  => 2,
			'INI Config' => 2,
			'Database'   => 2,
			'Web Server' => 2,
			'INI Dirs'   => strlen( php_ini_scanned_files() ) ? 2 : FALSE,
		];

		?>
		<h3>Server Panel</h3>
		<?php
		foreach ( $cards as $card => $size ) {
			if ( $size !== FALSE ) {
				$cardId = str_replace( ' ', '_', strtolower( $card ) );
				$this->addCard( $card, [ $this, "get_{$cardId}_card" ], $size );
			}
		}
		$this->showCards();
	}

	protected function get_php_setup_card ()
	{
		$php = [];

		$php['WP Version']     = '<a href="https://wordpress.org/about/history/" target="_blank">' . get_bloginfo( 'version' ) . '</a>';
		$php['WP Environment'] = wp_get_environment_type();
		$php['WP Timezone']    = wp_timezone_string();

		$majorMinor          = explode( '.', $versionNumber = phpversion() );
		$majorMinor          = $majorMinor[0] . $majorMinor[1];
		$versionNumber       = \esc_html( $versionNumber );
		$versionLink         = "<a href=\"https://www.php.net/manual/en/migration{$majorMinor}.new-features.php\" target=\"_blank\">{$versionNumber}</a>";
		$php['PHP Version']  = $versionLink;
		$php['PHP Timezone'] = date( 'e' );
		if ( date( 'e' ) !== date( 'T' ) ) {
			$php['PHP Timezone'] .= ' (' . date( 'T' ) . ')';
		}

		$php['Server API'] = php_sapi_name();

		$php['Thread Safe'] = defined( 'ZEND_THREAD_SAFE' ) && ZEND_THREAD_SAFE ? 'Yes' : 'No';

		$php['Process Control'] = extension_loaded( 'pcntl' ) ? 'Yes' : 'No';

		if ( !empty( $opcache_status = opcache_get_status() ) ) {
			$php['OPCache JIT']    = ( $opcache_status['jit']['enabled'] ?? FALSE ) ? 'Yes' : 'No';
			$php['OPCache Memory'] = ( $opmem = ( $opcache_status['memory_usage']['used_memory'] ?? FALSE ) ) ? round( $opmem / MB_IN_BYTES ) . ' MB' : '0 MB';
			if ( !empty( $opconfig = opcache_get_configuration() ) ) {
				$php['OPCache Version'] = ( $opconfig['version']['version'] ?? FALSE ) ? $opconfig['version']['version'] : 'Unknown';
			}
		}
		else {
			$php['OPCache Version'] = 'Not Installed';
		}

		$php['xDebug Version'] = extension_loaded( 'xdebug' ) ? phpversion( 'xdebug' ) : 'Not Installed';

		$php['Imagick Version'] = extension_loaded( 'imagick' ) ? phpversion( 'imagick' ) : 'Not Installed';

		$php['Zend Version'] = zend_version();

		if ( extension_loaded( 'curl' ) ) {
			$curl = curl_version();

			$php['cURL Version'] = $curl['version'];
			$php['SSL  Version'] = $curl['ssl_version'];
		}
		else {
			$php['cURL Version'] = 'Not Installed';
		}

		$this->outputTable( $php );

		echo '<hr>';

		$locations = [];

		$locations['INI File'] = $this->getFileLinkTag( [ 'file' => php_ini_loaded_file() ] );

		if ( !empty( $extension_dir = ini_get( 'extension_dir' ) ) ) {
			$locations['Ext Dir'] = $this->getFileLinkTag( [ 'file' => $this->getAbsolutePath( $extension_dir ), 'text' => $extension_dir ] );
		}
		else {
			$locations['Ext Dir'] = 'None';
		}

		if ( !empty( $error_log = ini_get( 'error_log' ) ) ) {
			$locations['Error Log'] = $this->getFileLinkTag( [ 'file' => $this->getAbsolutePath( $error_log ), 'text' => $error_log ] );
		}
		else {
			$locations['Error Log'] = 'None';
		}

		$this->outputTable( $locations );
	}

	public static function get_mysql_var ( $mysql_var )
	{
		global $wpdb;

		$result = $wpdb->get_row( $wpdb->prepare( 'SHOW VARIABLES LIKE %s', $mysql_var ), ARRAY_A );

		if ( !empty( $result ) && array_key_exists( 'Value', $result ) ) {
			return $result['Value'];
		}

		return NULL;
	}

	protected function get_database_card ()
	{
		global $wpdb;

		$db = [];

		if ( is_resource( $wpdb->dbh ) ) {
			$db['Extension'] = 'mysql';
		}
		elseif ( is_object( $wpdb->dbh ) ) {
			$db['Extension'] = get_class( $wpdb->dbh );
		}

		$db['Server Version'] = $wpdb->get_var( 'SELECT VERSION()' );

		if ( isset( $wpdb->use_mysqli ) && $wpdb->use_mysqli ) {
			$db['Client Version'] = $wpdb->dbh->client_info;
		}
		elseif ( preg_match( '|[0-9]{1,2}\.[0-9]{1,2}\.[0-9]{1,2}|', mysql_get_client_info(), $matches ) ) {
			$db['Client Version'] = $matches[0];
		}

		$db['User']     = $wpdb->dbuser;
		$db['Host']     = $wpdb->dbhost;
		$db['Database'] = $wpdb->dbname;
		$db['Prefix']   = $wpdb->prefix;
		$db['Charset']  = $wpdb->charset;

		$db['Key Buffer size']         = round( static::get_mysql_var( 'key_buffer_size' ) / MB_IN_BYTES ) . ' MB';
		$db['Innodb Buffer size']      = round( static::get_mysql_var( 'innodb_buffer_pool_size' ) / MB_IN_BYTES ) . ' MB';
		$db['Max allowed packet size'] = round( static::get_mysql_var( 'max_allowed_packet' ) / MB_IN_BYTES ) . ' MB';
		$db['Max connections number']  = static::get_mysql_var( 'max_connections' );

		$this->outputTable( $db );
	}

	protected function get_web_server_card ()
	{
		$server = [];

		if ( array_key_exists( 'SERVER_SOFTWARE', $_SERVER ) && !empty( $_SERVER['SERVER_SOFTWARE'] ) ) {
			[ $webServer, $software ] = explode( '/', $_SERVER['SERVER_SOFTWARE'], 2 );
			[ $serverVersion, $software ] = explode( ' ', $software, 2 );
			$server['Web Server']            = $webServer;
			$server[$webServer . ' Version'] = $serverVersion;
		}

		$server['User'] = get_current_user();

		if ( function_exists( 'php_uname' ) ) {
			$server['Host']      = php_uname( 'n' );
			$server['OS']        = php_uname( 's' ) . ' ' . php_uname( 'r' );
			$server['Processor'] = php_uname( 'm' );
		}

		$server['IP Address'] = $this->get_public_ip() ?: 'Unknown';

		$server['Peak Memory'] = round( memory_get_peak_usage() / MB_IN_BYTES ) . ' MB';
		$server['Last Memory'] = round( memory_get_usage() / MB_IN_BYTES ) . ' MB';

		$this->outputTable( $server );
	}

	protected function get_public_ip ()
	{
		$keys = [
			'HTTP_CLIENT_IP',
			'HTTP_X_FORWARDED_FOR',
			'HTTP_X_FORWARDED',
			'HTTP_FORWARDED_FOR',
			'HTTP_FORWARDED',
			'REMOTE_ADDR',
		];

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $_SERVER ) && !empty( $_SERVER[$key] ) ) {
				return $_SERVER[$key];
			}
		}

		return FALSE;
	}

	protected function get_ini_dirs_card ()
	{
		if ( empty( $files = php_ini_scanned_files() ) ) {
			return;
		}

		$files = explode( ',', $files );

		foreach ( $files as &$file ) {
			$file = $this->getFileLinkTag( [ 'file' => trim( $file ) ] );
		}

		echo implode( '<br>', $files );
	}

	protected function get_ini_config_card ()
	{
		$ini = [];

		$ini['memory_limit']       = ini_get( 'memory_limit' );
		$ini['max_execution_time'] = ini_get( 'max_execution_time' ) . ' seconds';
		$ini['max_input_time']     = ini_get( 'max_input_time' ) . ' seconds';

		$ini['post_max_size']       = ini_get( 'post_max_size' );
		$ini['upload_max_filesize'] = ini_get( 'upload_max_filesize' );
		$ini['max_file_uploads']    = ini_get( 'max_file_uploads' );
		$ini['output_buffering']    = ini_get( 'output_buffering' );
		$ini['implicit_flush']      = ini_get( 'implicit_flush' ) ?: 'No';

		$ini['log_errors']     = ini_get( 'log_errors' ) ? 'Yes' : 'No';
		$ini['display_errors'] = ini_get( 'display_errors' ) ? 'Yes' : 'No';

		$this->outputTable( $ini );
	}

	protected function getWPConstants ()
	{
		global $wpdb;

		$absPath    = defined( 'ABSPATH' ) ? ABSPATH : NULL;
		$contentDir = $absPath . 'wp-content';
		$siteurl    = get_option( 'siteurl' ) ?? NULL;
		$home       = get_option( 'home' ) ?? NULL;
		$hasLangDir = file_exists( $contentDir . '/languages' ) && @is_dir( $contentDir . '/languages' ) || !@is_dir( $absPath . 'wp-includes/languages' );

		$constants = [
			'Admin'          => [
				'AUTOSAVE_INTERVAL' => MINUTE_IN_SECONDS,
				'EMPTY_TRASH_DAYS'  => 30,
				'MEDIA_TRASH'       => FALSE,
				'WP_POST_REVISIONS' => TRUE,
			],
			'Cache'          => [
				'WP_CACHE' => FALSE,
			],
			'Cron'           => [
				'ALTERNATE_WP_CRON'    => FALSE,
				'DISABLE_WP_CRON'      => FALSE,
				'WP_CRON_LOCK_TIMEOUT' => MINUTE_IN_SECONDS,
			],
			'Debug'          => [
				'SAVEQUERIES'         => FALSE,
				'DIEONDBERROR'        => FALSE,
				'WP_DEBUG'            => FALSE,
				'WP_DEBUG_DISPLAY'    => TRUE,
				'WP_DEBUG_LOG'        => FALSE,
				'WP_ENVIRONMENT_TYPE' => 'production',
				'WP_START_TIMESTAMP'  => NULL,
			],
			'Memory'         => [
				'WP_MAX_MEMORY_LIMIT' => '256M',
				'WP_MEMORY_LIMIT'     => is_multisite() ? '64M' : '40M',
			],
			'Styles/Scripts' => [
				'COMPRESS_CSS'        => TRUE,
				'COMPRESS_SCRIPTS'    => TRUE,
				'CONCATENATE_SCRIPTS' => TRUE,
				'SCRIPT_DEBUG'        => FALSE,
			],
			'Paths'          => [
				'ABSPATH'         => $absPath,
				'BLOCKS_PATH'     => $absPath . 'wp-includes/blocks/',
				'WP_LANG_DIR'     => ( $hasLangDir ? $contentDir : $absPath . 'wp-includes' ) . '/languages',
				'LANGDIR'         => ( $hasLangDir ? 'wp-content' : 'wp-includes' ) . '/languages',
				'MUPLUGINDIR'     => 'wp-content/mu-plugins',
				'PLUGINDIR'       => 'wp-content/plugins',
				'WPINC'           => 'wp-includes',
				'STYLESHEETPATH'  => $contentDir . '/themes/' . ( get_option( 'stylesheet' ) ?? '' ),
				'TEMPLATEPATH'    => $contentDir . '/themes/' . ( get_option( 'template' ) ?? '' ),
				'WP_CONTENT_DIR'  => $contentDir,
				'WP_PLUGIN_DIR'   => $contentDir . '/plugins',
				'WPMU_PLUGIN_DIR' => $contentDir . '/mu-plugins',
			],
			'Urls'           => [
				'ADMIN_COOKIE_PATH'   => preg_replace( '|https?://[^/]+|i', '', $siteurl . '/' ) . 'wp-admin',
				'COOKIEPATH'          => preg_replace( '|https?://[^/]+|i', '', $home . '/' ),
				'COOKIE_DOMAIN'       => FALSE,
				'FORCE_SSL_ADMIN'     => FALSE,
				'FORCE_SSL_LOGIN'     => FALSE,
				'LANGDIR'             => ( $hasLangDir ? 'wp-content' : 'wp-includes' ) . '/languages',
				'MUPLUGINDIR'         => 'wp-content/mu-plugins',
				'PLUGINDIR'           => 'wp-content/plugins',
				'PLUGINS_COOKIE_PATH' => preg_replace( '|https?://[^/]+|i', '', $siteurl . '/wp-content/plugins' ),
				'SITECOOKIEPATH'      => preg_replace( '|https?://[^/]+|i', '', $siteurl . '/' ),
				'WPINC'               => 'wp-includes',
				'WP_CONTENT_URL'      => $siteurl . '/wp-content',
				'WP_PLUGIN_URL'       => $siteurl . '/wp-content/plugins',
				'WPMU_PLUGIN_URL'     => $siteurl . '/wp-content/mu-plugins',
				'WP_HOME'             => $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'WP_HOME' ) ),
				'WP_SITEURL'          => $wpdb->get_var( $wpdb->prepare( "SELECT option_value FROM $wpdb->options WHERE option_name = %s", 'WP_SITEURL' ) ),
			],
		];

		$wp_constants = [];

		foreach ( $constants as $group => $keys ) {
			foreach ( $keys as $key => $default ) {
				$value          = defined( $key ) ? constant( $key ) : NULL;
				$wp_constants[] = [
					'name'      => $key,
					'value'     => $this->formatValue( $value, [ 'maxStrLength' => 500 ] ),
					'default'   => $this->formatValue( $default, [ 'maxStrLength' => 500 ] ),
					'isDefault' => is_null( $value ) || $value === $default || in_array( $key, [ 'WP_START_TIMESTAMP' ] ),
					'type'      => gettype( !is_null( $value ) ? $value : $default ),
					'group'     => $group,
				];
			}
		}
		?>

		<h3>WordPress Constants</h3>
		<div id="wp-constants-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var wpConstants = <?= json_encode( array_values( $wp_constants ?? [] ) ) ?>;

				if (wpConstants.length) {
					T.Create("#wp-constants-table", {
						data: wpConstants,
						layout: 'fitDataStretch',
						columns: [
							{title: 'Group', field: 'group', formatter: 'list'},
							{title: 'Name', field: 'name', formatterParams: {type: 'string'}, formatter: 'args'},
							{title: 'Value', field: 'value', formatter: 'args'},
							{title: 'Default?', field: 'isDefault', formatter: 'boolean'},
							{title: 'Default', field: 'default', formatter: 'args'},
							{title: 'Type', field: 'type', formatter: 'list'},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function getExtensions ()
	{
		?>
		<h3>PHP Extensions</h3>
		<?php
		if ( !function_exists( 'get_loaded_extensions' ) ) {
			return '<p><b>Cannot get extensions</b></p>';
		}

		$exts = [];

		if ( !empty( $extensions = get_loaded_extensions() ) ) {
			sort( $extensions, SORT_STRING | SORT_FLAG_CASE );
			foreach ( $extensions as $extension ) {
				$exts[] = [ 'name' => $extension, 'version' => trim( phpversion( $extension ) ) ];
			}
		}
		?>
		<div id="php-extensions-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var phpExtensions = <?= json_encode( $exts ) ?>;

				if (phpExtensions.length) {
					T.Create("#php-extensions-table", {
						data: phpExtensions,
						columns: [
							{title: 'Name', field: 'name', formatter: 'string'},
							{title: 'Version', field: 'version', formatter: 'string'},
						],
					});
				}
			});
		</script>
		<?php

	}

	protected function getErrorReporting ()
	{
		$error_constants = [
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_CORE_ERROR',
			'E_CORE_WARNING',
			'E_COMPILE_ERROR',
			'E_COMPILE_WARNING',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_STRICT',
			'E_RECOVERABLE_ERROR',
			'E_DEPRECATED',
			'E_USER_DEPRECATED',
			'E_ALL',
		];

		?>
		<h3>Error Reporting</h3>
		<?php

		$error_reporting = error_reporting();
		$errorReporting  = [];

		foreach ( $error_constants as $error_constant ) {
			$errorReporting[] = [ 'level' => $error_constant, 'value' => constant( $error_constant ), 'show' => ( defined( $error_constant ) && ( $error_reporting & constant( $error_constant ) ) ) ];
		}
		?>
		<div id="error-reporting-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var errorReporting = <?= json_encode( $errorReporting ) ?>;

				if (errorReporting.length) {
					T.Create("#error-reporting-table", {
						data: errorReporting,
						columns: [
							{title: 'Show', field: 'show', formatter: 'boolean'},
							{title: 'Level', field: 'level', formatter: 'string'},
							{title: 'Value', field: 'value', formatter: 'minMax'},
						],
					});
				}
			});
		</script>
		<?php

	}
}


/*

SERVER

DOCUMENT_ROOT
HTTP2
HTTPS
HTTP_HOST
PHP_SELF
REMOTE_ADDR
REMOTE_HOST
REMOTE_PORT
REMOTE_USER
REQUEST_URI
SCRIPT_FILENAME
SCRIPT_NAME
SERVER_ADDR
SERVER_NAME
SERVER_PORT
SERVER_PROTOCOL
SERVER_SOFTWARE

*/