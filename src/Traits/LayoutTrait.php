<?php

namespace DebugBar\Traits;

trait LayoutTrait
{
	protected $debugBarCards = [];

	protected function addCard ( $header, $content, $size = NULL )
	{
		$this->debugBarCards[] = [
			'header'  => $header,
			'content' => $content,
			'size'    => $size,
		];
	}

	protected function showCards ( $size = 4, $echo = TRUE )
	{
		ob_start()
		?>
		<ul class="debug-bar-cards debug-bar-cards-<?= $size ?>">
			<?php foreach ( $this->debugBarCards as $card ) : ?>
				<li class="debug-bar-card-<?= $card['size'] ?? 'initial' ?>">
					<section>
						<h3><?= $card['header'] ?></h3>
						<div><?= is_callable( $card['content'] ) ? $card['content']() : $card['content'] ?></div>
					</section>
				</li>
			<?php endforeach; ?>
		</ul>
		<?php
		$content = ob_get_clean();

		return $echo ? print $content : $content;
	}

	protected $debugBarTabs = [];

	protected function addTab ( $header, $content )
	{
		$this->debugBarTabs[$header] = $content;
	}

	protected function showTabs ( $panel_id, $echo = TRUE )
	{
		$tab_ids = [];
		foreach ( $this->debugBarTabs as $header => $content ) {
			$tab_ids[$header] = sanitize_title( $panel_id . '_' . $header );
		}

		ob_start()
		?>
		<ul class="debug-bar-tabs">
			<?php foreach ( $this->debugBarTabs as $header => $content ) : ?>
				<li><a tabindex="0" data-tab-id="<?= $tab_ids[$header] ?>"><?= $header ?></a></li>
			<?php endforeach; ?>
		</ul>
		<div class="debug-bar-tabs-content" data-group-id="<?= $panel_id ?>">
			<?php foreach ( $this->debugBarTabs as $header => $content ) : ?>
				<section id="<?= $tab_ids[$header] ?>"><?= is_callable( $content ) ? $content() : $content ?></section>
			<?php endforeach; ?>
		</div>
		<?php
		$content = ob_get_clean();

		return $echo ? print $content : $content;
	}

	protected function outputTable ( $rows )
	{
		?>
		<table><?php
			foreach ( $rows as $header => $value ) : ?>
				<tr>
					<th><?= $header ?> &nbsp;</th>
					<td><?= $value ?></td>
				</tr>
			<?php endforeach; ?>
		</table>
		<?php
	}
}