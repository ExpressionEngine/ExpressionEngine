<?php if ($multiple): ?>
<div data-field="<?=$field_name?>" class="col w-8 relate-wrap<?php if (empty($entries)) echo " empty"; ?>">
	<h4><?=lang('items_to_relate_with')?></h4>
<?php else: ?>
<div data-field="<?=$field_name?>" class="col w-16 relate-wrap<?php if (empty($entries) || empty($related)) echo " empty"; ?>">
	<h4><?=lang('item_to_relate_with')?></h4>
<?php endif; ?>
	<div class="relate-actions">
		<?php if (count($channels) > 1): ?>
		<div class="filters">
			<ul>
				<li>
					<a class="has-sub" href=""><?=lang('channel')?> <?php if (ee()->input->post('channel')): ?><span class="faded" data-channel-id="<?=ee()->input->post('channel')?>">(<?=$channels->filter('channel_id', ee()->input->post('channel'))->first()->channel_title?>)</span><?php endif; ?></a>
					<div class="sub-menu">
						<?php if (count($channels) > 9): ?><div class="scroll-wrap"><?php endif;?>
						<ul>
							<li><a href="" data-channel-id=""><?=lang('all_channels')?></a></li>
							<?php foreach($channels as $channel): ?>
								<li><a href="" data-channel-id="<?=$channel->channel_id?>"><?=$channel->channel_title?></a></li>
							<?php endforeach; ?>
						</ul>
					<?php if (count($channels) > 9): ?></div><?php endif;?>
					</div>
				</li>
			</ul>
		</div>
		<?php endif; ?>
		<input class="relate-search" type="text" name="search" value="<?=ee()->input->post('search')?>" placeholder="<?=lang('search_avilable_entries')?>">
	</div>
	<div class="scroll-wrap" data-template='<label class="choice block chosen relate-manage" data-entry-id="{entry-id}"><a href="" title="<?=lang('remove_relationship')?>" data-entry-id="{entry-id}"></a> {entry-title} <i>&mdash; {channel-title}</i></label>'>
		<?php $chosen = NULL; ?>
			<div class="no-results<?php if ( ! empty($entries)) echo " hidden" ?>">
				<?=lang('no_entries_found')?>
				<?php if (count($channels) == 1): ?>
				<a class="btn action" href="<?=ee('CP/URL')->make('publish/create/' . $channels[0]->channel_id)?>" data-channel-id="<?=$channels[0]->channel_id?>"><?=lang('btn_create_new')?></a>
				<?php else: ?>
					<?php foreach($channels as $channel): ?>
						<a class="btn action hidden" href="<?=ee('CP/URL')->make('publish/create/' . $channel->channel_id)?>" data-channel-id="<?=$channel->channel_id?>"><?=lang('btn_create_new')?></a>
					<?php endforeach; ?>
				<div class="filters">
					<ul>
						<li>
							<a class="has-sub" href=""><?=lang('btn_create_new')?></a>
							<div class="sub-menu">
							<?php if (count($channels) > 9): ?><div class="scroll-wrap"><?php endif;?>
								<ul>
									<?php foreach($channels as $channel): ?>
										<li><a href="<?=ee('CP/URL')->make('publish/create/' . $channel->channel_id)?>"><?=$channel->channel_title?></a></li>
									<?php endforeach; ?>
								</ul>
							<?php if (count($channels) > 9): ?></div><?php endif;?>
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
				$selected = array_diff($selected, array($entry->entry_id));
				$class = 'choice block chosen';
				$checked = TRUE;
				$chosen = $entry;
			}
		?>
		<label class="<?=$class?>" data-channel-id="<?=$entry->Channel->channel_id?>" data-channel-title="<?=$entry->Channel->channel_title?>" data-entry-title="<?=htmlentities($entry->title, ENT_QUOTES, 'UTF-8')?>">
			<?php
				if ($multiple)
				{
					echo form_checkbox($field_name.'[data][]', $entry->entry_id, $checked);
					echo '<input type="hidden" name="'.$field_name.'[sort][]'.'" value="0" disabled="disabled">';
				}
				else
				{
					echo form_radio($field_name.'[data][]', $entry->entry_id, $checked);
				}
			?>
			<?=htmlentities($entry->title, ENT_QUOTES, 'UTF-8')?> <i>&mdash; <?=$entry->Channel->channel_title?></i>
		</label>
		<?php endforeach; ?>
		<?php
			foreach ($selected as $entry_id)
			{
				echo form_hidden($field_name.'[data][]', $entry_id);
				if ($multiple)
				{
					echo '<input type="hidden" name="'.$field_name.'[sort][]'.'" value="0" disabled="disabled">';
				}
			}
		?>
	</div>
	<?php if ( ! $multiple): ?>
		<?php if ( ! $chosen && ! empty($related)) $chosen = $related[0]; ?>
		<div class="relate-wrap-chosen">
			<?php if($chosen): ?>
			<label class="choice block chosen relate-manage">
				<a href="" title="<?=lang('remove_relationship')?>" data-entry-id="<?=$chosen->entry_id?>"></a> <?=htmlentities($chosen->title, ENT_QUOTES, 'UTF-8')?> <i>&mdash; <?=$chosen->Channel->channel_title?></i>
			</label>
			<?php endif; ?>
			<label class="choice <?=($chosen) ? "hidden" : "block"?>">
				<div class="no-results"><?=lang('no_entry_related')?></div>
			</label>
		</div>
	<?php endif;?>
</div>
<?php if ($multiple): ?>
<div class="col w-8 relate-wrap<?php if ( ! count($related)) echo " empty"; ?> last">
	<h4><?=lang('items_related_to')?></h4>
	<div class="relate-actions">
		<input class="relate-search" name="search_related" type="text" value="<?=ee()->input->post('search_related')?>" placeholder="<?=lang('search_related_entries')?>">
	</div>
	<div class="scroll-wrap">
		<?php if (count($related)): ?>
			<?php foreach ($related as $entry): ?>
			<label class="choice block chosen relate-manage" data-entry-id="<?=$entry->entry_id?>">
				<span class="relate-reorder"></span>
				<a href="" title="<?=lang('remove_relationship')?>" data-entry-id="<?=$entry->entry_id?>"></a> <?=htmlentities($entry->title, ENT_QUOTES, 'UTF-8')?> <i>&mdash; <?=$entry->Channel->channel_title?></i>
			</label>
			<?php endforeach; ?>
		<?php else: ?>
			<div class="no-results"><?=lang('no_entries_related')?></div>
		<?php endif;?>
	</div>
</div>
<?php endif; ?>
