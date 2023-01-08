<?php

namespace DebugBar\Panel;

use PHPSQLParser\PHPSQLCreator;
use PHPSQLParser\PHPSQLParser;

class Queries extends \DebugBar\Panel
{
	use \DebugBar\Traits\FormatTrait;

	public $_icon = 'dashicons-database-view';
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

		$queries = [];
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
				$queries[$created] = [
					'type'      => $keywords[0],
					'caller'    => !empty( $query[4] ) ? ( $query[4]['class'] === 'WP_Query' ? 'get_posts' : $query[4]['function'] ) : NULL,
					'args'      => !empty( $query[4] ) && array_key_exists( 'args', $query[4] ) ? $query[4]['args'] : NULL,
					'sql'       => htmlspecialchars( is_array( $statements ) ? implode( "\n", $statements ) : $statements ),
					'runTime'   => round( $query[1] * 1e3, 5 ),
					'startTime' => round( ( $query[3] - WP_START_TIMESTAMP ) * 1e3, 3 ),
					'endTime'   => round( ( $query[3] - WP_START_TIMESTAMP + $query[1] ) * 1e3, 3 ),
					'source'    => [ $this->getFileLinkArray( $query[4]['file'] ?? NULL, $query[4]['line'] ?? NULL ) ],
					'count'     => 1,
				];
			}
			else {
				$queries[$created]['count']++;
				$queries[$created]['endTime']  = round( ( $query[3] - WP_START_TIMESTAMP + $query[1] ) * 1e3, 3 );
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
					T.Create("#queries-table", {
						data: queries,
						paginationSize: 5,
						layout: 'fitDataStretch',
						columns: [
							{title: 'Type', field: 'type', formatter: 'list'},
							{title: 'Function', field: 'caller', formatter: 'list'},
							{title: 'Args', field: 'args', formatterParams: {space: 4, textLimit: T.helpers.indexOf.nl(7)}, formatter: 'object'},
							{
								title: 'SQL', field: 'sql', formatter: 'text', maxWidth: 575,
								formatterParams: {
									prefix: '<div style="white-space: normal">', suffix: '</div>',
									textLimit: T.helpers.indexOf.fn('<br>', 5),
									modify: function (content, params) {
										return content.split("\n").join('<br>');
									},
									showPopup: true,
								},
							},
							{
								title: 'Time', field: 'runTime', headerSortStartingDir: 'desc', formatter: 'timeMs',
								bottomCalc: function (values, data, params) {
									return Math.round(data.reduce(function (accumulator, row) {
										return accumulator + (row.runTime * row.count);
									}, 0)) + ' ms';
								}
							},
							{title: 'Start', field: 'startTime', formatter: 'timeMs'},
							{title: 'Run', field: 'count', formatter: 'minMax'},
							{title: 'Source', field: 'source', formatter: 'file'},
						],
					});
				}
			});
		</script>
		<?php
	}
}