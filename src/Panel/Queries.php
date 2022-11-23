<?php

namespace DebugBar\Panel;

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

class Queries extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;

	public $_title = 'Queries';
	protected $ignoreFiles = [ 'class-wp.php', 'class-wpdb.php', 'class-wp-query.php' ];
	protected $query_args = NULL;

	public function init ()
	{
		ini_set( "precision", 14 );
		ini_set( "serialize_precision", -1 );
		add_action( 'pre_get_posts', [ $this, 'pre_get_posts' ], 10, 1 );
		add_filter( 'log_query_custom_data', [ $this, 'log_query_custom_data' ], 10, 5 );
	}

	public function pre_get_posts ( $WP_Query )
	{
		$this->query_args = $WP_Query->query;
	}

	public function log_query_custom_data ( $query_data, $query, $query_time, $query_callstack, $query_start )
	{
		$found            = FALSE;
		$query_args       = $this->query_args;
		$this->query_args = NULL;

		$bt = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS );
		foreach ( $bt as $data ) {
			foreach ( $this->ignoreFiles as $ignoreFile ) {
				if ( array_key_exists( 'file', $data ) && str_ends_with( $data['file'], $ignoreFile ) ) {
					$found = TRUE;
					continue 2;
				}
			}
			if ( $found ) {
				if ( is_array( $query_args ) ) {
					$data += [ 'args' => $query_args ];
				}

				return $query_data + $data;
			}
		}

		return $query_data;
	}

	public function render ()
	{
		global $wpdb;

		$queries   = [];
		$startTime = NULL;
		foreach ( $wpdb->queries as $query ) {
			$created  = $query[0];
			$parser   = new PHPSQLParser();
			$parsed   = $parser->parse( $created );
			$keywords = array_keys( $parsed );
			try { // big fix: PHPSQLCreator
				$creator = @new PHPSQLCreator( $parser->parsed );
				$created = $creator->created;
			}
			catch ( \Exception $e ) {
				$created = $query[0];
			}
			$statements = explode( "\n", trim( str_replace( $keywords, "\n", $created ) ) );
			if ( count( $statements ) === count( $keywords ) ) {
				foreach ( $statements as $index => &$statement ) {
					$statement = $keywords[$index] . ' ' . trim( $statement );
				}
			}
			else {
				$statements = $query[0];
			}

			if ( !array_key_exists( $created, $queries ) ) {
				if ( empty( $startTime ) ) {
					$startTime = $query[3];
				}
				$queries[$created] = [
					'type'      => $keywords[0],
					'caller'    => !empty( $query[4] ) ? ( $query[4]['class'] === 'WP_Query' ? 'get_posts' : $query[4]['function'] ) : NULL,
					'args'      => !empty( $query[4] ) && array_key_exists( 'args', $query[4] ) ? $query[4]['args'] : NULL,
					'sql'       => htmlspecialchars( is_array( $statements ) ? implode( "\n", $statements ) : $statements ),
					'runTime'   => round( $query[1], 5 ),
					'startTime' => round( $query[3] - $startTime, 3 ),
					'endTime'   => round( $query[3] - $startTime + $query[1], 3 ),
					'source'    => [ $this->getFileLinkArray( $query[4]['file'] ?? NULL, $query[4]['line'] ?? NULL ) ],
					'count'     => 1,
				];
			}
			else {
				$queries[$created]['count']++;
				$queries[$created]['endTime']  = round( $query[3] - $startTime + $query[1], 3 );
				$queries[$created]['source'][] = $this->getFileLinkArray( $query[4]['file'] ?? NULL, $query[4]['line'] ?? NULL );
			}
		}
		?>

		<h3>Queries</h3>
		<div id="queries-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var queries = <?= json_encode( array_values( $queries ?? [] ) ) ?>;

				if (queries.length) {
					new Tabulator("#queries-table", {
						data: queries,
						pagination: 'local',
						paginationSize: 5,
						paginationSizeSelector: [5, 10, 20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						layout: 'fitDataStretch',
						columns: [
							{title: 'Type', field: 'type', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{title: 'Function', field: 'caller', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{title: 'Args', field: 'args', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', ...T.common.listArray, formatterParams: {space: 4, join: "<br>"},},
							{
								maxWidth: 575,
								title: 'SQL', field: 'sql', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input', formatter: function (cell, formatterParams, onRendered) {
									if (cell.getValue() === null) {
										return '';
									}
									return '<div style="white-space: normal">' + cell.getValue().split("\n").join('<br>') + '</div>';
								},
							},
							T.filters.minMax(queries, {title: 'Time', field: 'runTime', formatter: Tabulator.formatters.timeMs, vertAlign: 'middle', hozAlign: 'right', headerHozAlign: 'center', headerSortStartingDir: 'desc',}),
							T.filters.minMax(queries, {title: 'Start', field: 'startTime', formatter: Tabulator.formatters.timeMs, vertAlign: 'middle', hozAlign: 'right', headerHozAlign: 'center',}),
							{title: 'Run', field: 'count', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'list', headerFilterParams: {sort: 'asc', valuesLookup: true, clearable: true},},
							{title: 'Source', field: 'source', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', ...Tabulator.common.filesArray,},
						],
					});
				}
			});
		</script>
		<?php
	}
}