<div class="col w-8 relate-wrap">
	<h4><?=lang('items_to_relate_with')?></h4>
	<div class="relate-actions">
		<div class="filters">
			<ul>
				<li>
					<a class="has-sub" href="">channel <span class="faded">(Blog Entries)</span></a>
					<div class="sub-menu">
						<fieldset class="filter-search">
							<input type="text" value="" placeholder="filter channels">
						</fieldset>
						<ul>
							<li><a href="">[Allowed Channel]</a></li>
							<li><a href="">[Allowed Channel]</a></li>
							<li><a href="">[Allowed Channel]</a></li>
							<li><a href="">[Allowed Channel]</a></li>
							<li><a href="">[Allowed Channel]</a></li>
						</ul>
					</div>
				</li>
			</ul>
		</div>
		<input class="relate-search" type="text" value="" placeholder="<?=lang('search_avilable_entries')?>">
	</div>
	<div class="scroll-wrap">

		<?php
		foreach ($entries as $entry):
			$class = 'choice block';
			$checked = FALSE;
			if (in_array($entry, $selected))
			{
				$class = 'choice block chosen';
				$checked = TRUE;
			}
		?>
		<label class="<?=$class?>">
			<?=form_checkbox($field_name.'[]', $entry->entry_id, $checked)?> <?=$entry->title?> <i>&mdash; <?=$entry->getChannel()->channel_title?></i>
		</label>
		<?php endforeach; ?>
	</div>
</div>
<div class="col w-8 relate-wrap last">
	<h4><?=lang('items_related_to')?></h4>
	<div class="relate-actions">
		<input class="relate-search" type="text" value="" placeholder="<?=lang('search_related_entries')?>">
	</div>
	<div class="scroll-wrap">
		<?php foreach ($related as $entry): ?>
		<label class="choice block chosen">
			<?=$entry->title?> <i>&mdash; <?=$entry->getChannel()->channel_title?></i>
		</label>
		<?php endforeach; ?>
	</div>
</div>