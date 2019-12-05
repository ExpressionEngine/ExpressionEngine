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
		<div class="after-field">
					<a class="button button--secondary-alt js-dropdown-toggle has-sub" href="#">Add Entry</a>
					<div class="dropdown">
						<fieldset class="dropdown__search">
							<input value="" placeholder="filter channels" type="text" data-fuzzy-filter="true">
						</fieldset>
							<?php foreach ($channels as $channel):
								$channel_title = ee('Format')->make('Text', $channel->channel_title)->convertToEntities(); ?>
								<a href="#" class="dropdown__link" rel="add_new" data-channel-id="<?=$channel->getId()?>" data-channel-title="<?=$channel_title?>"><?=$channel_title?></a>
							<?php endforeach; ?>
					</div>
		</div>
	<?php endif ?>
<?php endif ?>
