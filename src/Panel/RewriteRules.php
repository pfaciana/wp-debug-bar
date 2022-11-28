<?php

namespace DebugBar\Panel;

use DebugBar\Traits\FormatTrait;
use DebugBar\Traits\LayoutTrait;

class RewriteRules extends \Debug_Bar_Panel
{
	use FormatTrait;
	use LayoutTrait;

	public $_icon = 'dashicons-editor-ol';
	public $_panel_id;
	public $_capability = 'edit_others_posts';

	public function render ()
	{
		$this->addTab( 'Rules', [ $this, 'setRewriteRules' ] );
		$this->addTab( 'Tags', [ $this, 'setRewriteTags' ] );
		$this->addTab( 'Matched Query', [ $this, 'setMatchedQuery' ] );
		$this->addTab( 'GET/POST', [ $this, 'setRequestQuery' ] );
		$this->showTabs( $this->_panel_id );
	}

	protected function setRewriteRules ()
	{
		global $wp_rewrite;

		$rules = $wp_rewrite->wp_rewrite_rules();

		$rewriteRules = [];
		$count        = 0;
		foreach ( $rules as $regex => $query ) {
			$rewriteRules[] = [ 'position' => ++$count, 'regex' => $regex, 'query' => $query ];
		}

		?>
		<h3>Rewrite Rules</h3>
		<div id="rewrite-rules-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var rewriteRules = <?= json_encode( array_values( $rewriteRules ?? [] ) ) ?>;

				if (rewriteRules.length) {
					new Tabulator("#rewrite-rules-table", {
						data: rewriteRules,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						columns: [
							{title: 'Pos', field: 'position', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{
								title: 'Regex', field: 'regex', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								headerFilterFunc: function (userValue, fieldValue, rowData, filterParams) {
									return (new RegExp(fieldValue)).test(userValue);
								}
							},
							{title: 'Query', field: 'query', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function setRewriteTags ()
	{
		global $wp_rewrite;

		$rewritecodes    = $wp_rewrite->rewritecode;
		$rewritereplaces = $wp_rewrite->rewritereplace;
		$queryreplaces   = $wp_rewrite->queryreplace;

		$rewriteTags = [];
		foreach ( $rewritecodes as $index => $rewritecode ) {
			$rewriteTags[] = [ 'tag' => $rewritecodes[$index], 'regex' => $rewritereplaces[$index], 'query' => $queryreplaces[$index] ];
		}

		?>
		<h3>Rewrite Tags</h3>
		<div id="rewrite-tags-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var rewriteTags = <?= json_encode( array_values( $rewriteTags ?? [] ) ) ?>;

				if (rewriteTags.length) {
					new Tabulator("#rewrite-tags-table", {
						data: rewriteTags,
						pagination: 'local',
						paginationSize: 20,
						paginationSizeSelector: [20, 50, 100, true],
						paginationButtonCount: 15,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						columns: [
							{title: 'Tag', field: 'tag', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{
								title: 'Regex', field: 'regex', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',
								headerFilterFunc: function (userValue, fieldValue, rowData, filterParams) {
									return (new RegExp(fieldValue)).test(userValue);
								}
							},
							{title: 'Query', field: 'query', vertAlign: 'middle', hozAlign: 'left', headerHozAlign: 'center', headerFilter: 'input',},
						],
					});
				}
			});
		</script>
		<?php
	}

	protected function setMatchedQuery ()
	{
		$cards = [
			'Matched Request' => 2,
			'Query Vars'      => 2,
		];

		?>
		<h3>Matched Query</h3>
		<?php
		foreach ( $cards as $card => $size ) {
			if ( $size !== FALSE ) {
				$cardId = str_replace( ' ', '_', strtolower( $card ) );
				$this->addCard( $card, [ $this, "get_{$cardId}_card" ], $size );
			}
		}
		$this->showCards();
	}

	protected function get_matched_request_card ()
	{
		global $wp;

		$this->outputTable( [
			'request'       => $wp->request,
			'matched_rule'  => $wp->matched_rule,
			'matched_query' => $wp->matched_query,
		] );
	}

	protected function get_query_vars_card ()
	{
		global $wp;

		$this->outputTable( $wp->query_vars );
	}

	protected function setRequestQuery ()
	{
		$requestQuery = [];

		foreach ( $_REQUEST ?? [] as $key => $value ) {
			$requestQuery[] = [ 'name' => $key, 'value' => $value, 'get' => in_array( $key, array_keys( $_GET ?? [] ) ), 'post' => in_array( $key, array_keys( $_POST ?? [] ) ), ];
		}
		?>
		<h3>Request Query</h3>
		<div id="request-query-table"></div>

		<script type="application/javascript">
			jQuery(function ($) {
				var T = window.Tabulator;
				var requestQuery = <?= json_encode( array_values( $requestQuery ?? [] ) ) ?>;

				if (requestQuery.length) {
					new Tabulator("#request-query-table", {
						data: requestQuery,
						footerElement: '<button class="clear-all-table-filters tabulator-page">Clear Filters</button>',
						columns: [
							{title: 'Field', field: 'name', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							{title: 'Value', field: 'value', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', headerFilter: 'input',},
							T.filters.boolean({title: 'GET', field: 'get', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', width: 75}),
							T.filters.boolean({title: 'POST', field: 'post', vertAlign: 'middle', hozAlign: 'center', headerHozAlign: 'center', width: 75}),
						],
					});
				}
			});
		</script>
		<?php

		if ( empty( $requestQuery ) ) {
			echo '<p><b>No Request parameters were found.</b></p>';
		}
	}
}