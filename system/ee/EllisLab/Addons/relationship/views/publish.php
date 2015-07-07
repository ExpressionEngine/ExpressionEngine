<?php if ($multiple): ?>
<div class="col w-8 relate-wrap">
	<h4><?=lang('items_to_relate_with')?></h4>
	<?php else: ?>
<div class="col w-16 relate-wrap">
	<h4><?=lang('item_to_relate_with')?></h4>
	<?php endif; ?>
	<div class="relate-actions">
		<?php if (count($channels) > 1): ?>
		<div class="filters">
			<ul>
				<li>
					<a class="has-sub" href=""><?=lang('channel')?> <span class="faded"></span></a>
					<div class="sub-menu">
						<ul>
							<?php foreach($channels as $channel): ?>
								<li><a href=""><?=$channel->channel_title?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</li>
			</ul>
		</div>
		<?php endif; ?>
		<input class="relate-search" type="text" value="" placeholder="<?=lang('search_avilable_entries')?>">
	</div>
	<div class="scroll-wrap">
		<?php
		$chosen = NULL;
		foreach ($entries as $entry):
			$class = 'choice block';
			$checked = FALSE;
			if (in_array($entry->entry_id, $selected))
			{
				$class = 'choice block chosen';
				$checked = TRUE;
				$chosen = $entry;
			}
		?>
		<label class="<?=$class?>">
			<?php
				if ($multiple)
				{
					echo form_checkbox($field_name.'[]', $entry->entry_id, $checked);
				}
				else
				{
					echo form_radio($field_name.'[]', $entry->entry_id, $checked);
				}
			?>
			<?=$entry->title?> <i>&mdash; <?=$entry->getChannel()->channel_title?></i>
		</label>
		<?php endforeach; ?>
	</div>
	<?php if ( ! $multiple): ?>
		<div class="relate-wrap-chosen">
			<?php if ($chosen): ?>
			<label class="choice block chosen relate-manage">
				<a href="" title="<?=lang('remove_relationship')?>"></a> <?=$chosen->title?> <i>&mdash; <?=$chosen->getChannel()->channel_title?></i>
			</label>
			<?php else: ?>
			<label class="choice block chosen no-results">
				<?=lang('no_entry_related')?>
			</label>
			<?php endif; ?>
		</div>
	<?php endif;?>
</div>
<?php if ($multiple): ?>
<div class="col w-8 relate-wrap last">
	<h4><?=lang('items_related_to')?></h4>
	<div class="relate-actions">
		<input class="relate-search" type="text" value="" placeholder="<?=lang('search_related_entries')?>">
	</div>
	<div class="scroll-wrap">
		<?php if (count($related)): ?>
			<?php foreach ($related as $entry): ?>
			<label class="choice block chosen relate-manage">
				<span class="relate-reorder"></span>
				<?=$entry->title?> <i>&mdash; <?=$entry->getChannel()->channel_title?></i>
			</label>
			<?php endforeach; ?>
		<?php else: ?>
			<div class="no-results"><?=lang('no_entries_related')?></div>
		<?php endif;?>
	</div>
</div>
<?php endif; ?>