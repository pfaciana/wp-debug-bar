<?php

namespace DebugBar\Panel;

use DebugBar\Traits\FormatTrait;
use DebugBar\Traits\LayoutTrait;

class Globals extends \Debug_Bar_Panel
{
	use FormatTrait;
	use LayoutTrait;

	public $_icon = 'fa fa-globe';
	public $_panel_id;
	public $_capability = 'manage_options';
	protected $ignore = [
		'str_starts_with' => [ 'EP_', 'DB_', "Sodium\\", 'SODIUM_', 'KINT_' ],
		'str_ends_with'   => [ '_COOKIE', '_KEY', '_SALT', 'HASH' ],
	];
	protected $groups = [
		'constants' => [
			'Time'     => [
				'str_ends_with' => [ '_IN_SECONDS', 'TIMESTAMP' ],
			],
			'Storage'  => [
				'str_ends_with' => [ '_IN_BYTES' ],
			],
			'Path'     => [
				'str_ends_with' => [ 'PATH', 'DIR', 'FILE', 'WPINC' ],
			],
			'Url'      => [
				'str_ends_with' => [ 'URL', 'DOMAIN', 'WP_HOME' ],
			],
			'Template' => [
				'str_starts_with' => [ 'WP_TEMPLATE_' ],
				'str_ends_with'   => [ '_THEME' ],
			],
			'Debug'    => [
				'str_starts_with' => [ 'WP_DEBUG', 'WP_CACHE', 'FORCE_SSL_ADMIN', 'MULTISITE', 'WP_', 'REST_API_' ],
				'str_ends_with'   => [ '_DEBUG', '_MEMORY_LIMIT', 'SAVEQUERIES', 'MEDIA_TRASH', 'SHORTINIT', 'AUTOSAVE_INTERVAL', 'EMPTY_TRASH_DAYS' ],
			],
		],
		'globals'   => [
			'Post'           => [
				'equals' => [ 'authordata', 'currentday', 'currentmonth', 'id', 'more', 'multipage', 'numpages', 'page', 'pages', 'post', 'post_id', 'previousday', 'query_string', ],
			],
			'Database'       => [
				'equals' => [ 'wpdb', 'table_prefix' ],
			],
			'Query'          => [
				'equals'        => [
					'request',
					'blog_id',
					'attachment',
					'attachment_id',
					'author',
					'author__in',
					'author__not_in',
					'author_name',
					'cache_results',
					'calendar',
					'cat',
					'category__and',
					'category__in',
					'category__not_in',
					'category_name',
					'comments_per_page',
					'cpage',
					'day',
					'embed',
					'error',
					'exact',
					'favicon',
					'feed',
					'fields',
					'hour',
					'ignore_sticky_posts',
					'lazy_load_term_meta',
					'm',
					'menu_order',
					'meta_key',
					'meta_value',
					'minute',
					'monthnum',
					'name',
					'no_found_rows',
					'nopaging',
					'offset',
					'order',
					'orderby',
					'p',
					'page',
					'page_id',
					'paged',
					'pagename',
					'pb',
					'perm',
					'post__in',
					'post__not_in',
					'post_mime_type',
					'post_name__in',
					'post_parent',
					'post_parent__in',
					'post_parent__not_in',
					'post_status',
					'posts',
					'posts_per_archive_page',
					'posts_per_page',
					'preview',
					'robots',
					's',
					'search',
					'second',
					'sentence',
					'showposts',
					'subpost',
					'subpost_id',
					'suppress_filters',
					'tag',
					'tag__and',
					'tag__in',
					'tag__not_in',
					'tag_id',
					'tag_slug__and',
					'tag_slug__in',
					'taxonomy',
					'tb',
					'term',
					'title',
					'update_menu_item_cache',
					'update_post_meta_cache',
					'update_post_term_cache',
					'w',
					'withcomments',
					'withoutcomments',
					'year',
				],
				'str_ends_with' => [ '_query' ],
			],
			'Env'            => [
				'str_starts_with' => [ 'is_' ],
			],
			'User'           => [
				'str_starts_with' => [ 'user', ],
				'str_ends_with'   => [ '_user', '_roles', '_cap', '_caps' ],
			],
			'Hooks'          => [
				'str_starts_with' => [ 'hook_' ],
				'str_ends_with'   => [ '_actions', '_filters', '_filter', '_hook' ],
			],
			'Screen'         => [
				'equals'          => [ 'screen', 'mode', 'pagenum', 'parent_file', 'per_page', 'post_new_file', 'self', 'typenow', 'action', 'autosave', 'editing', 'doaction', ],
				'str_starts_with' => [ 'screen_', 'editor_', 'block_editor_', ],
				'str_ends_with'   => [ '_screen' ],
			],
			'Menu'           => [
				'equals'          => [],
				'str_starts_with' => [ 'menu', '_wp_menu', '_wp_submenu', 'core_menu', 'submenu', ],
				'str_ends_with'   => [ 'menu' ],
			],
			'Version'        => [
				'str_starts_with' => [ 'version_' ],
				'str_ends_with'   => [ 'version' ],
			],
			'Allow'          => [
				'str_starts_with' => [ 'allowed', 'pass_allowed_' ],
			],
			'Admin'          => [
				'equals'       => [ 'pagenow', 'post_type', 'plugin_page', ],
				'str_contains' => [ 'admin' ],
			],
			'Date/Time'      => [
				'str_starts_with' => [ 'time_', 'timezone_' ],
				'str_ends_with'   => [ 'timestart', 'date_format', 'time_format' ],
			],
			'Styles/Scripts' => [
				'equals'        => [ 'script', 'compress_css' ],
				'str_ends_with' => [ '_styles', '_scripts', '_script' ],
			],
			'Theme'          => [
				'str_contains' => [ 'template', 'theme' ],
			],
			'Widget'         => [
				'str_contains'  => [ 'widget' ],
				'str_ends_with' => [ '_sidebars' ],
			],
			'Comments'       => [
				'str_starts_with' => [ 'awaiting_mod_' ],
			],
			'Smilies'        => [
				'str_contains' => [ 'smilies' ],
			],
			'Locale'         => [
				'str_starts_with' => [ 'weekday', 'month', 'wp_locale' ],
				'str_ends_with'   => [ 'locale' ],
			],
			'WP Core'        => [
				'equals'          => [ 'wp', 'taxnow', 'types', 'shortcode_tags', ],
				'str_starts_with' => [ 'wp_', '_wp_' ],
			],
		],
	];
	protected $definedVars = [];
	protected $superGlobals = [
		'GLOBALS'  => [],
		'_SERVER'  => [],
		'_GET'     => [],
		'_POST'    => [],
		'_FILES'   => [],
		'_COOKIE'  => [],
		'_SESSION' => [],
		'_REQUEST' => [],
		'_ENV'     => [],
	];

	public function render ()
	{
		$php_defined_constants  = get_defined_constants( TRUE );
		$user_defined_constants = $php_defined_constants['user'];
		unset( $php_defined_constants['user'] );

		$this->addTab( 'User Constants', function () use ( $user_defined_constants ) { $this->getUserConstants( $user_defined_constants ); } );
		$this->addTab( 'WP Globals', [ $this, 'getGlobals' ] );
		$this->addTab( 'WP Conditionals', [ $this, 'getConditionals' ] );
		$this->addTab( 'Class Constants', [ $this, 'getClassInfo' ] );
		$this->addTab( 'PHP Constants', function () use ( $php_defined_constants ) { $this->getPHPConstants( $php_defined_constants ); } );
		$this->showTabs( $this->_panel_id );
	}

	protected function getUserConstants ( $user_defined_constants )
	{
		$user_constants = [];
		foreach ( $user_defined_constants as $key => $value ) {
			foreach ( $this->ignore as $func => $searches ) {
				foreach ( $searches as $search ) {
					if ( $func( $key, $search ) ) {
						continue 3;
					}
				}
			}
			$user_constants[] = [ 'name' => $key, 'value' => $this->formatValue( $value, [ 'maxStrLength' => 500 ] ), 'type' => gettype( $value ), 'group' => $this->getGroup( 'constants', $key, 'Other' ) ];
		}
		?>

		<h3>WordPress Constants</h3>
		<div id="wp-constants-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var userConstants = <?= json_encode( array_values( $user_constants ?? [] ) ) ?>;

				if (userConstants.length) {
					new Tabulator("#wp-constants-table", {
						data: userConstants,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [5, 10, 20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							{title: 'Group', field: 'group', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{
								title: 'Name', field: 'name', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null) {
										return '';
									}

									return `<span data-type="string">${cell.getValue()}</span>`;
								}
							},
							{
								title: 'Value', field: 'value', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center',
								headerFilter: 'input', headerFilterFunc: T.filters.args,
								formatter: T.formatters.args, sorter: T.sorter.args,
							},
							{title: 'Type', field: 'type', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function getGlobals ()
	{
		$superGlobalKeys = array_keys( $this->superGlobals );

		$wp_globals = [];
		foreach ( $GLOBALS as $key => $value ) {
			if ( !in_array( $key, $superGlobalKeys ) ) {
				$wp_globals[] = [ 'name' => $key, 'value' => $this->formatValue( $value, [ 'maxStrLength' => 100 ] ), 'type' => gettype( $value ), 'group' => $this->getGroup( 'globals', $key, 'Other' ) ];
			}
		}
		?>

		<h3>WordPress Globals</h3>
		<div id="wp-globals-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var wpGlobals = <?= json_encode( $wp_globals ?? [] ) ?>;

				if (wpGlobals.length) {
					new Tabulator("#wp-globals-table", {
						data: wpGlobals,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [5, 10, 20, 50, 100],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							{title: 'Group', field: 'group', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{
								title: 'Name', field: 'name', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null) {
										return '';
									}

									return `<span data-type="string">${cell.getValue()}</span>`;
								}
							},
							{
								title: 'Value', field: 'value', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center',
								headerFilter: 'input', headerFilterFunc: T.filters.args,
								formatter: T.formatters.args, sorter: T.sorter.args,
							},
							{title: 'Type', field: 'type', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function getClassInfo ()
	{
		$class_constants = [];
		$class_statics   = [];

		foreach ( get_declared_classes() as $class ) {
			$reflector = new \ReflectionClass( $class );
			foreach ( $reflector->getConstants() as $key => $value ) {
				$class_constants[] = [ 'name' => $key, 'value' => $this->formatValue( $value, [ 'maxStrLength' => 500 ] ), 'type' => gettype( $value ), 'group' => $class ];
			}
			$is_public  = $reflector->getProperties( \ReflectionProperty::IS_PUBLIC );
			$is_static  = $reflector->getProperties( \ReflectionProperty::IS_STATIC );
			$properties = array_uintersect( $is_public, $is_static, function ( $a, $b ) {
				return strcmp( $a->name . $a->class, $b->name . $b->class );
			} );
			foreach ( $properties as $property ) {
				if ( !str_starts_with( $property->class, "Composer\\" ) ) {
					$value           = $reflector->getStaticPropertyValue( $property->name );
					$class_statics[] = [ 'name' => $property->name, 'value' => $this->formatValue( $value, [ 'maxStrLength' => 500 ] ), 'type' => gettype( $value ), 'group' => $property->class ];
				}
			}
		}

		?>

		<h3>Class Constants</h3>
		<div id="class-constants-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var classConstants = <?= json_encode( $class_constants ?? [] ) ?>;

				if (classConstants.length) {
					new Tabulator("#class-constants-table", {
						data: classConstants,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [5, 10, 20, 50, 100],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							{title: 'Group', field: 'group', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{
								title: 'Name', field: 'name', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null) {
										return '';
									}

									return `<span data-type="string">${cell.getValue()}</span>`;
								}
							},
							{
								title: 'Value', field: 'value', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center',
								headerFilter: 'input', headerFilterFunc: T.filters.args,
								formatter: T.formatters.args, sorter: T.sorter.args,
							},
							{title: 'Type', field: 'type', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
						],
					});
				}
			});
		</script>

		<h3>Class Statics</h3>
		<div id="class-statics-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var classStatics = <?= json_encode( $class_statics ?? [] ) ?>;

				if (classStatics.length) {
					new Tabulator("#class-statics-table", {
						data: classStatics,
						pagination: 'local',
						paginationSize: 10,
						paginationSizeSelector: [5, 10, 20, 50, 100],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							{title: 'Group', field: 'group', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{
								title: 'Name', field: 'name', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null) {
										return '';
									}

									return `<span data-type="string">${cell.getValue()}</span>`;
								}
							},
							{
								title: 'Value', field: 'value', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center',
								headerFilter: 'input', headerFilterFunc: T.filters.args,
								formatter: T.formatters.args, sorter: T.sorter.args,
							},
							{title: 'Type', field: 'type', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function getPHPConstants ( $php_defined_constants )
	{

		$php_constants = [];
		foreach ( $php_defined_constants as $group => $constants ) {
			foreach ( $constants as $key => $value ) {
				$php_constants[] = [ 'name' => $key, 'value' => $this->formatValue( $value, [ 'maxStrLength' => 500 ] ), 'type' => gettype( $value ), 'group' => $group ];
			}
		}
		?>

		<h3>PHP Constants</h3>
		<div id="php-constants-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var phpConstants = <?= json_encode( $php_constants ?? [] ) ?>;

				if (phpConstants.length) {
					new Tabulator("#php-constants-table", {
						data: phpConstants,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [5, 10, 20, 50, 100],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							{title: 'Group', field: 'group', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{
								title: 'Name', field: 'name', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null) {
										return '';
									}

									return `<span data-type="string">${cell.getValue()}</span>`;
								}
							},
							{
								title: 'Value', field: 'value', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center',
								headerFilter: 'input', headerFilterFunc: T.filters.args,
								formatter: T.formatters.args, sorter: T.sorter.args,
							},
							{title: 'Type', field: 'type', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
						],
					});
				}
			});
		</script>
		<?php
	}


	protected function getConditionals ()
	{
		$boolean_functions = [];
		$functions         = get_defined_functions( TRUE );

		sort( $functions['user'] );

		foreach ( $functions['user'] as $func ) {
			if ( stripos( $func, 'is_' ) === 0 || stripos( $func, 'wp_is_' ) === 0 || stripos( $func, 'wp_doing_' ) === 0 ) {
				$rf = new \ReflectionFunction( $func );
				if ( $rf->getNumberOfRequiredParameters() == 0 ) {
					$filename     = wp_normalize_path( $rf->getFileName() );
					$line         = $rf->getStartLine();
					$rel_pathinfo = pathinfo( str_replace( wp_normalize_path( ABSPATH ), '', $filename ) );
					$groups       = explode( '/', $rel_pathinfo['dirname'] . '/' . $rel_pathinfo['filename'] );
					$group        = $groups[min( 2, count( $groups ) - 1 )];

					$boolean_functions[$filename . '_' . $func] = [
						'file'   => [ $this->getFileLinkArray( $filename, $line ) ],
						'group'  => $group,
						'name'   => $func,
						'return' => !empty( $func() ),
					];
				}
			}
		}

		ksort( $boolean_functions );

		$boolean_functions = array_values( $boolean_functions );

		?>
		<h3>WordPress Conditionals</h3>
		<div id="bools-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var bools = <?= json_encode( $boolean_functions ?? [] ) ?>;

				if (bools.length) {
					new Tabulator("#bools-table", {
						data: bools,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [10, 20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							T.filters.boolean({title: 'Yes/No', field: 'return', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center',}),
							{
								title: 'Name', field: 'name', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input', formatter: function (cell, formatterParams, onRendered) {
									return '<span style="font-weight: bold; color: ' + (cell.getData().return ? '#090' : '#C00') + '">' + cell.getValue() + '</span>';
								},
							},
							{title: 'Group', field: 'group', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{title: 'File', field: 'file', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', ...Tabulator.common.filesArray,},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function getGroup ( $type, $key, $default = '' )
	{
		foreach ( $this->groups[$type] as $group => $conditions ) {
			foreach ( $conditions as $func => $compares ) {
				if ( $func === 'equals' ) {
					if ( in_array( $key, $compares ) ) {
						return $group;
					}
				}
				else {
					foreach ( $compares as $compare ) {
						if ( $func( $key, $compare ) ) {
							return $group;
						}
					}
				}
			}
		}

		return $default;
	}

}