<?php if ($multiple): ?>
<div class="col w-8 relate-wrap<?php if (empty($entries)) echo " empty"; ?>">
	<h4><?=lang('items_to_relate_with')?></h4>
	<?php else: ?>
<div class="col w-16 relate-wrap<?php if (empty($entries)) echo " empty"; ?>">
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
							<li><a href="" data-channel-id=""><?=lang('all_channels')?></a></li>
							<?php foreach($channels as $channel): ?>
								<li><a href="" data-channel-id="<?=$channel->channel_id?>"><?=$channel->channel_title?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</li>
			</ul>
		</div>
		<?php endif; ?>
		<input class="relate-search" type="text" value="" placeholder="<?=lang('search_avilable_entries')?>">
	</div>
	<div class="scroll-wrap" data-template='<label class="choice block chosen relate-manage"><a href="" title="<?=lang('remove_relationship')?>" data-entry-id="{entry-id}"></a> {entry-title} <i>&mdash; {channel-title}</i></label>'>
		<?php $chosen = NULL; ?>
			<div class="no-results<?php if ( ! empty($entries)) echo " hidden" ?>">
				<?=lang('no_entries_found')?>
				<?php if (count($channels) == 1): ?>
				<a class="btn action" href="<?=ee('CP/URL', 'publish/create/' . $channels[0]->channel_id)?>" data-channel-id="<?=$channels[0]->channel_id?>"><?=lang('btn_create_new')?></a>
				<?php else: ?>
					<?php foreach($channels as $channel): ?>
						<a class="btn action hidden" href="<?=ee('CP/URL', 'publish/create/' . $channel->channel_id)?>" data-channel-id="<?=$channel->channel_id?>"><?=lang('btn_create_new')?></a>
					<?php endforeach; ?>
				<div class="filters">
					<ul>
						<li>
							<a class="has-sub" href=""><?=lang('btn_create_new')?></a>
							<div class="sub-menu">
								<ul>
									<?php foreach($channels as $channel): ?>
										<li><a href="<?=ee('CP/URL', 'publish/create/' . $channel->channel_id)?>"><?=$channel->channel_title?></a></li>
									<?php endforeach; ?>
								</ul>
							</div>
						</li>
					</ul>
				</div>
				<?php endif; ?>
			</div>
			<?php
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
		<label class="<?=$class?>" data-channel-id="<?=$entry->getChannel()->channel_id?>" data-channel-title="<?=$entry->getChannel()->channel_title?>" data-entry-title="<?=$entry->title?>">
			<?php
				if ($multiple)
				{
					echo form_checkbox($field_name.'[data][]', $entry->entry_id, $checked);
					echo form_hidden($field_name.'[sort][]', 0);
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
			<?php if($chosen): ?>
			<label class="choice block chosen relate-manage">
				<a href="" title="<?=lang('remove_relationship')?>" data-entry-id="<?=$chosen->entry_id?>"></a> <?=$chosen->title?> <i>&mdash; <?=$chosen->getChannel()->channel_title?></i>
			</label>
			<?php endif; ?>
			<label class="choice block chosen no-results<?php if ($chosen) echo " hidden" ?>">
				<?=lang('no_entry_related')?>
			</label>
		</div>
	<?php endif;?>
</div>
<?php if ($multiple): ?>
<div class="col w-8 relate-wrap<?php if ( ! count($related)) echo " empty"; ?> last">
	<h4><?=lang('items_related_to')?></h4>
	<div class="relate-actions">
		<input class="relate-search" type="text" value="" placeholder="<?=lang('search_related_entries')?>">
	</div>
	<div class="scroll-wrap">
		<?php if (count($related)): ?>
			<?php foreach ($related as $entry): ?>
			<label class="choice block chosen relate-manage">
				<span class="relate-reorder"></span>
				<a href="" title="<?=lang('remove_relationship')?>" data-entry-id="<?=$chosen->entry_id?>"></a> <?=$entry->title?> <i>&mdash; <?=$entry->getChannel()->channel_title?></i>
			</label>
			<?php endforeach; ?>
		<?php else: ?>
			<div class="no-results"><?=lang('no_entries_related')?></div>
		<?php endif;?>
	</div>
</div>
<?php endif; ?>