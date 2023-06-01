<div class="fields-keyvalue<?=(isset($classes)) ? $classes : ''?>">
	<div class="field-no-results hidden">
		<p><?=lang($no_results['text'])?>
			<?php if (! empty($no_results['action_text'])): ?>
				<a<?=$no_results['external'] ? ' rel="external"' : '' ?> rel="add_row" href="<?=$no_results['action_link']?>"><?=lang($no_results['action_text'])?></a>
			<?php endif ?>
		</p>
	</div>
	<div class="fields-keyvalue-header">
		<?php foreach ($columns as $settings): ?>
			<div class="field-instruct">
				<label><?=$settings['label']?></label>
			</div>
		<?php endforeach ?>
	</div>
	<div class="keyvalue-item-container">
		<?php foreach ($data as $heading => $rows):
            $rows = array($rows); ?>
			<?php foreach ($rows as $row): ?>
				<?php
                if (! isset($row['attrs']['class'])) {
                    $row['attrs']['class'] = '';
                }
                $row['attrs']['class'] .= ' fields-keyvalue-item';
                ?>
				<div<?php foreach ($row['attrs'] as $key => $value):?> <?=$key?>="<?=$value?>"<?php endforeach ?>>
					<?php if ($reorder): ?>
						<ul class="toolbar">
							<li class="reorder"><a href="#" title="reorder row"><span class="sr-only"><?=lang('reorder_row')?></span></a></li>
						</ul>
					<?php endif ?>
					<?php foreach ($row['columns'] as $column): ?>
						<div class="field-control">
							<?=$column['html']?>
						</div>
					<?php endforeach ?>
					<ul class="toolbar">
						<li class="remove"><a href="#" rel="remove_row" title="remove row"><span class="sr-only"><?=lang('remove_row')?></span></a></li>
					</ul>
				</div>
			<?php endforeach ?>
		<?php endforeach ?>
	</div>
	<a class="button button--default button--small" rel="add_row" href=""><?=lang('add_a_row')?></a>
</div>
