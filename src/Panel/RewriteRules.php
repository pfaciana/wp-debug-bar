<?php

namespace DebugBar\Panel;

class RewriteRules extends \Debug_Bar_Panel
{
	use \DebugBar\Traits\FormatTrait;
	use \DebugBar\Traits\LayoutTrait;

	public $_icon = 'dashicons-editor-ol';
	public $_panel_id;
	public $_capability = 'edit_others_posts';

	public function render ()
	{
		$this->addTab( 'Matched Query', [ $this, 'setMatchedQuery' ] );
		$this->addTab( 'Rules', [ $this, 'setRewriteRules' ] );
		$this->addTab( 'Tags', [ $this, 'setRewriteTags' ] );
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
						T.Create("#rewrite-rules-table", {
							data: rewriteRules,
							columns: [
								{title: 'Pos', field: 'position', formatter: 'string'},
								{title: 'Regex', field: 'regex', formatter: 'regex'},
								{title: 'Query', field: 'query', formatter: 'text'},
							],
						});
					}
				}
			)
			;
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
					T.Create("#rewrite-tags-table", {
						data: rewriteTags,
						columns: [
							{title: 'Tag', field: 'tag', formatter: 'string'},
							{title: 'Regex', field: 'regex', formatter: 'regex'},
							{title: 'Query', field: 'query', hozAlign: 'left', formatter: 'string'},
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

		$this->setRequestQuery();
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
					T.Create("#request-query-table", {
						data: requestQuery,
						columns: [
							{title: 'Field', field: 'name', formatter: 'string'},
							{title: 'Value', field: 'value', formatter: 'string'},
							{title: 'GET', field: 'get', formatter: 'boolean'},
							{title: 'POST', field: 'post', formatter: 'boolean'},
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