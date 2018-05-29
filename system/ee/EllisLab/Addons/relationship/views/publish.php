<?php

if (empty($choices)) {
	return $this->embed('ee:_shared/form/no_results', $no_results);
}
$component = [
	'items' => $choices,
	'selected' => $selected,
	'multi' => $multi,
	'filter_url' => $filter_url,
	'limit' => $limit,
	'no_results' => lang($no_results['text']),
	'no_related' => lang($no_related['text']),
	'select_filters' => $select_filters
];
?>
<div data-relationship-react="<?=base64_encode(json_encode($component))?>" data-input-value="<?=$field_name?>">
	<div class="fields-select">
		<div class="field-inputs">
			<label class="field-loading">
				<?=lang('loading')?><span></span>
			</label>
		</div>
	</div>
</div>

<?php if ( ! $in_modal): ?>
	<?php if ($channels->count() == 1): ?>
		<a class="btn action submit js-modal-link--side" rel="add_new" data-channel-title="<?=ee('Format')->make('Text', $channels->first()->channel_title)->convertToEntities()?>" data-channel-id="<?=$channels->first()->getId()?>" href="#">Add Entry</a>
	<?php elseif ($channels->count() > 1): ?>
		<div class="filters after-field">
			<ul>
				<li>
					<a class="btn-action js-modal-link--side has-sub" href="#">Add Entry</a>
					<div class="sub-menu">
						<fieldset class="filter-search">
							<input value="" placeholder="filter channels" type="text" data-fuzzy-filter="true">
						</fieldset>
						<ul>
							<?php foreach ($channels as $channel):
								$channel_title = ee('Format')->make('Text', $channel->channel_title)->convertToEntities(); ?>
								<li><a href="#" rel="add_new" data-channel-id="<?=$channel->getId()?>" data-channel-title="<?=$channel_title?>"><?=$channel_title?></a></li>
							<?php endforeach; ?>
						</ul>
					</div>
				</li>
			</ul>
		</div>
	<?php endif ?>
<?php endif ?>
