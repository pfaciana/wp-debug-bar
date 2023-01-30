<?php

namespace DebugBar;

class Hooks
{
	use \DebugBar\Traits\FormatTrait;

	protected $panel;
	protected $active = TRUE;
	protected $start_time;
	protected $end_time;
	protected $hooks = [];
	protected $actions = [];
	protected $trackers = [];
	protected $tracking = [];
	protected $ajaxKey = 'Hooks';

	function __construct ()
	{
		add_action( 'plugins_loaded', [ $this, 'init' ], -9e9 );
		add_filter( 'debug_bar_panels', function ( $panels ) {
			$this->panel = new \DebugBar\Panel\Hooks( 'Hooks' );
			$this->panel->setRenderCallback( [ $this, 'render' ] );
			$panels[] = $this->panel;

			return $panels;
		}, 11 );
	}

	public function init ()
	{
		global $wp_actions;

		if ( ( !is_admin_bar_showing() && DebugBar::running_for_ajax() === FALSE ) || in_array( 'DebugBar_Panel_Hooks_Hooks', \DebugBar\DebugBar::get_disabled_panels() ) ) {
			return ( $this->active = FALSE );
		}

		$this->actions    = $wp_actions;
		$this->start_time = WP_START_TIMESTAMP ?: microtime( TRUE );
		add_action( 'all', [ $this, 'all' ] );
		add_filter( 'rdb/ajax/response', [ $this, 'ajaxRender' ] );
	}

	public function all ()
	{
		if ( DebugBar::wp_doing_ajax() && !DebugBar::running_for_ajax() ) {
			$this->active = FALSE;
		}

		if ( !$this->active ) {
			return $this->hooks = [];
		}

		global $wp_current_filter;

		$bt     = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 4 );
		$caller = $bt[3]['function'];

		$args      = func_get_args();
		$hook_name = array_shift( $args );
		$args      = strpos( $caller, '_ref_array' ) !== FALSE ? $args[0] : $args;
		$type      = strpos( $caller, 'action' ) !== FALSE ? 'action' : 'filter';

		if ( !empty( $args ) ) {
			if ( $hook_name === 'debugbar/watch' ) {
				add_action( $hook_name, [ $this, 'log' ], 9e9, 1 );
				$this->trackers[$args[0]] = TRUE;

				if ( count( $args ) > 1 && ( is_callable( $args[1] ) || function_exists( $args[1] ) ) ) {
					$args[1]();
					$this->trackers[$args[0]] = FALSE;
				}

				return;
			}
			elseif ( $hook_name === 'debugbar/unwatch' ) {
				return $this->trackers[$args[0]] = FALSE;
			}
		}

		if ( !array_key_exists( $hook_name, $this->hooks ) ) {
			$this->hooks[$hook_name] = [
				'type'  => $type,
				'calls' => [],
			];

			add_action( $hook_name, [ $this, 'log' ], 9e9, 1 );
		}

		$tmp = $wp_current_filter;
		array_pop( $tmp );
		if ( ( $parent = array_pop( $tmp ) ) === 'debugbar/watch' ) {
			$parent = array_pop( $tmp );
		}
		$hook_call                          = [
			'file'   => $bt[3]['file'],
			'line'   => $bt[3]['line'],
			'time'   => $this->diff(),
			'args'   => $args,
			'level'  => count( $tmp ),
			'parent' => $parent,
		];
		$this->hooks[$hook_name]['calls'][] = $hook_call;
	}

	public function log ( $value = NULL )
	{
		if ( !$this->active ) {
			$this->hooks = [];

			return $value;
		}

		$hook_name = current_filter();

		if ( $hook_name === 'debugbar/watch' ) {
			return function () use ( $value ) {
				$this->trackers[$value] = FALSE;

				return $value;
			};
		}

		$call = array_pop( $this->hooks[$hook_name]['calls'] );

		$call['duration'] = $this->diffCall( $call );
		$call['value']    = $value;

		array_push( $this->hooks[$hook_name]['calls'], $call );

		if ( $active_trackers = array_filter( $this->trackers ) ) {
			$this->tracking[] = [ 'trackers' => array_keys( $active_trackers ), 'name' => $hook_name, 'type' => $this->hooks[$hook_name]['type'] ] + $call;
		}

		return $value;
	}

	public function diffCall ( $call )
	{
		return microtime( TRUE ) - ( $this->start_time + $call['time'] );
	}

	public function diff ( $end = NULL, $start = NULL )
	{
		$start = $start ?? $this->start_time;
		$end   = $end ?? microtime( TRUE );

		return $end - $start;
	}

	public function formatHookData ( $data, $name = NULL )
	{
		isset( $name ) && ( $data['name'] = $name );

		$calls = $data['calls'];
		unset( $data['calls'] );

		$last = ( $data['count'] = count( $calls ) ) - 1;

		$data['start'] = floor( $calls[0]['time'] * 1e5 ) / 1e2;
		if ( !empty( $durations = array_column( $calls, 'duration' ) ) ) {
			$data['end']   = ceil( ( $calls[$last]['time'] + ( $calls[$last]['duration'] ?? 0 ) ) * 1e5 ) / 1e2;
			$data['max']   = ceil( max( $durations ) * 1e5 ) / 1e2;
			$data['total'] = ceil( array_sum( $durations ) * 1e5 ) / 1e2;
		}
		else {
			$data['end'] = $data['start'];
			$data['max'] = $data['total'] = 0;
		}
		$data['level'] = max( array_column( $calls, 'level' ) );

		foreach ( $data['subscribers'] as &$subscriber ) {
			$subscriber['text'] .= " [{$subscriber['priority']}]";
			if ( array_key_exists( 'count', $subscriber ) && $subscriber['count'] > 1 ) {
				$subscriber['text'] .= " (x{$subscriber['count']})";
			}
		}
		$data['subscribers'] = array_values( $data['subscribers'] );

		$publishers = [];
		foreach ( $calls as $call ) {
			$publishers[] = $call['file'] . '::' . $call['line'];
		}
		$data['publishers'] = array_keys( array_flip( $publishers ) );
		sort( $data['publishers'] );

		foreach ( $data['publishers'] as &$fileWithLine ) {
			[ $filename, $line ] = explode( '::', $fileWithLine );
			$fileWithLine = $this->getFileLinkArray( $filename, $line );;
		}

		return $data;
	}

	public function formatHook ( $hook )
	{
		$hook['input'] = NULL;
		if ( $hook['type'] === 'filter' ) {
			$hook['input'] = !empty( $hook['args'] ) ? array_shift( $hook['args'] ) : NULL;
			$hook['input'] = $hook['input'] === $hook['value'] ? [ 'type' => 'same' ] : $this->formatValue( $hook['input'] );
			$hook['value'] = $this->formatValue( $hook['value'] );
		}
		else {
			$hook['value'] = NULL;
		}
		foreach ( $hook['args'] as &$arg ) {
			$arg = $this->formatValue( $arg );
		}

		$hook['time']     = floor( $hook['time'] * 1e5 ) / 1e2;
		$hook['duration'] = floor( $hook['duration'] * 1e5 ) / 1e2;

		$hook['publishers'] = [ $this->getFileLinkArray( $hook['file'], $hook['line'] ) ];

		unset( $hook['level'] );
		unset( $hook['file'] );
		unset( $hook['line'] );

		return $hook;
	}

	protected function addToSubscribers ( $subscribers, $fileLink, $priority = NULL )
	{
		$text = $fileLink['text'];

		if ( !array_key_exists( $text, $subscribers ) ) {
			$fileLink['priority'] = $priority == PHP_INT_MIN ? 'PHP_INT_MIN' : ( $priority == PHP_INT_MAX ? 'PHP_INT_MAX' : $priority );
			$fileLink['count']    = 1;
			$subscribers[$text]   = $fileLink;
		}
		else {
			$subscribers[$text]['count']++;
		}

		return $subscribers;
	}

	public function getResults ()
	{
		global $wp_filter;

		$diff = $this->diff( $this->end_time = microtime( TRUE ) );

		$hooks = [];

		foreach ( $this->hooks as $name => $data ) {
			$data['subscribers'] = [];
			if ( array_key_exists( $name, $wp_filter ) && property_exists( $wp_filter[$name], 'callbacks' ) && !empty( $wp_filter[$name]->callbacks ) ) {
				foreach ( $wp_filter[$name]->callbacks as $priority => $callback ) {
					foreach ( $callback as $id => $args ) {
						if ( !array_key_exists( 'function', $args ) ) {
							continue;
						}
						[ $file, $line ] = $this->getFileLine( $args['function'] );
						if ( !empty( $file ) ) {
							if ( !str_starts_with( $file, __DIR__ ) ) {
								$data['subscribers'] = $this->addToSubscribers( $data['subscribers'], $this->getFileLinkArray( $file, $line ), $priority );
							}
						}
						else {
							$data['subscribers'] = $this->addToSubscribers( $data['subscribers'], [ 'url' => FALSE, 'text' => $args['function'] ], $priority );
						}
					}
				}
			}
			$hooks[] = $this->formatHookData( $data, $name );
		}

		$tracking = [];

		$subscribers = array_column( $hooks, 'subscribers', 'name' );
		foreach ( $this->tracking as $hook ) {
			$hook['subscribers'] = $subscribers[$hook['name']] ?? [];
			if ( apply_filters( 'debugbar/watch/filter', TRUE, $hook = $this->formatHook( $hook ) ) ) {
				$tracking[] = $hook;
			}
		}

		return [ $hooks, $tracking ];
	}

	public function ajaxRender ( $response = [] )
	{
		remove_action( 'all', [ $this, 'all' ] );

		[ $hooks, $tracking ] = $this->getResults();

		if ( !empty( $hooks ) || !empty( $tracking ) ) {
			$response[$this->ajaxKey] = [
				'hooks'    => $hooks,
				'tracking' => $tracking,
			];
		}

		return $response;
	}

	public function render ( $panel_class = '' )
	{
		remove_action( 'all', [ $this, 'all' ] );

		[ $hooks, $tracking ] = $this->getResults();

		$actions = [];
		foreach ( $this->actions as $name => $count ) {
			$actions[] = "{$name} ({$count}x)";
		}
		$actions = implode( ', ', $actions );

		echo '<div class="hooks-tables"></div>';

		?>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var storageKey = 'rwdAdditionalHooks';
				var ajaxHooksPrefix = 'hooks-table-';
				var ajaxTrackingPrefix = 'tracking-table-';
				var $container = $('.<?=$panel_class?> .hooks-tables');

				function createAccordion() {
					$container.accordion({
						animate: false,
						header: 'h3',
						heightStyle: 'content',
					});
				}

				function updateAccordion() {
					$container.accordion('refresh');
				}

				function createTrackingTable(heading, id, data, config = {}) {
					$container.append(`<h3>${heading}</h3><div><div id="${id}"></div></div>`)
					T.Create(`#${id}`, {
						data: data,
						layout: 'fitDataStretch',
						columns: [
							{title: 'Tracking ID(s)', field: 'trackers', hozAlign: 'left', formatter: 'list[]'},
							{title: "Hook Type", field: 'type', formatter: 'list'},
							{title: 'Hook Name', field: 'name', formatter: 'text'},
							{title: 'Output', field: 'value', maxWidth: 250, formatter: 'args'},
							{title: 'Input', field: 'input', maxWidth: 250, formatter: 'args'},
							{title: 'Arguments', field: 'args', maxWidth: 250, formatter: 'args'},
							{title: 'Run Time', field: 'duration', headerSortStartingDir: 'desc', formatter: 'timeMs', formatterParams: {bottomSum: true}, bottomCalcParams: {suffix: ' ms'}},
							{title: 'Start Time', field: 'time', formatter: 'timeMs',},
							{title: 'Parent Hook', field: 'parent', headerFilter: 'input'},
							{title: 'Subscribers', field: 'subscribers', formatter: 'subscribers'},
							{title: 'Publisher', field: 'publishers', formatter: 'files'},
						],
						...config,
					});
				}

				function createHooksTable(heading, id, data, config = {}) {
					$container.append(`<h3>${heading}</h3><div><div id="${id}"></div></div>`)
					T.Create(`#${id}`, {
						data: data,
						layout: 'fitDataStretch',
						columns: [
							{title: 'Hook Name', field: 'name', hozAlign: 'left', headerFilter: 'input', headerFilterFunc: T.filters.advanced},
							{title: "Hook Type", field: 'type', formatter: 'list'},
							{title: 'Run Count', field: 'count', formatter: 'minMax', formatterParams: {bottomSum: true}},
							{title: 'Total Run', field: 'total', headerSortStartingDir: 'desc', formatter: 'timeMs', formatterParams: {bottomSum: true}, bottomCalcParams: {suffix: ' ms'}},
							{title: 'Slowest Run', field: 'max', headerSortStartingDir: 'desc', formatter: 'timeMs',},
							{title: 'Start Time', field: 'start', formatter: 'timeMs',},
							{title: 'End Time', field: 'end', headerSortStartingDir: 'desc', formatter: 'timeMs'},
							{title: 'Subscribers', field: 'subscribers', formatter: 'subscribers'},
							{title: 'Publishers', field: 'publishers', formatter: 'files'},
						],
						...config,
					});
				}

				var tracking = <?= json_encode( $tracking ?? [] ) ?>;
				if (tracking.length) {
					createTrackingTable('User Tracking', 'tracking-table', tracking);
				}

				var hooks = <?= json_encode( $hooks ?? [] ) ?>;
				if (hooks.length) {
					createHooksTable('Hooks (after plugins_loaded)', 'hooks-table', hooks);
				}

				var actions = '<?= $actions ?? '' ?>';
				if (actions.length) {
					$container.append(`<h3>Actions (prior to plugins_loaded)</h3><div><div style="padding: 15px;">${actions}</div></div>`)
				}

				createAccordion();

				/** Support for Ajax Calls **/

				function displayAjaxTables(response, localtime) {
					if (response.tracking.length) {
						createTrackingTable(`Ajax User Tracking (${rdb.formatLocalTime(localtime)})`, `${ajaxTrackingPrefix}${localtime}`, response.tracking);
					}
					if (response.hooks.length) {
						createHooksTable(`Ajax Hooks (${rdb.formatLocalTime(localtime)})`, `${ajaxHooksPrefix}${localtime}`, response.hooks);
					}
					updateAccordion();
				}

				function scrollToBottom() {
					setTimeout(() => $container.closest('[data-panel]').animate({scrollTop: $container.height()}, 1000), 1);
				}

				if (!rdb.isCapturingAjaxPersistent()) {
					localStorage.removeItem(storageKey);
				} else {
					let hooks = localStorage.getItem(storageKey);
					if (hooks) {
						hooks = JSON.parse(hooks);
						$.each(hooks, function (localtime, response) {
							displayAjaxTables(response, localtime);
						});
					}
				}

				$.subscribe('rdb/capture-ajax/change', function (state) {
					!state && localStorage.removeItem(storageKey);
				});

				$.subscribe('rdb/capture-ajax/response/<?=$this->ajaxKey?>', function (response, localtime, persist) {
					displayAjaxTables(response, localtime);
					if (response.tracking.length || response.hooks.length) {
						scrollToBottom();
						if (persist) {
							let hooks = localStorage.getItem(storageKey);
							hooks = hooks ? JSON.parse(hooks) : {};
							hooks[localtime] = response;
							localStorage.setItem(storageKey, JSON.stringify(hooks));
						}
					}
				});

				$.subscribe('tabulator-table-delete', function ($table) {
					if ($table && $table instanceof jQuery) {
						let type, localtime = null, tableId = $table.attr('id');
						if (tableId.startsWith(ajaxHooksPrefix)) {
							localtime = $table.attr('id').replace(ajaxHooksPrefix, '');
							type = 'hooks';
						} else if (tableId.startsWith(ajaxTrackingPrefix)) {
							localtime = $table.attr('id').replace(ajaxTrackingPrefix, '');
							type = 'tracking';
						} else {
							return;
						}
						let hooks = localStorage.getItem(storageKey);
						if (hooks) {
							hooks = JSON.parse(hooks);
							if (localtime in hooks) {
								if (type in hooks[localtime]) {
									delete hooks[localtime][type];
									if (!Object.keys(hooks[localtime]).length) {
										delete hooks[localtime];
									}
									localStorage.setItem(storageKey, JSON.stringify(hooks));
								}
							}
						}
					}
				});
			});
		</script>
		<?php
	}
}