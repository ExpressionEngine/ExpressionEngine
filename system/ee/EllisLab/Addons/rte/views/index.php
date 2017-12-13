<div class="mb">
	<?php $this->embed('ee:_shared/form')?>
</div>
<div class="snap table-list-wrap">
	<div class="tbl-ctrls">
		<?=form_open(ee('CP/URL')->make('addons/settings/rte/update_toolsets'))?>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="<?=ee('CP/URL')->make('addons/settings/rte/new_toolset')?>"><?=lang('create_new')?></a>
			</fieldset>
			<h1><?=lang('available_tool_sets')?></h1>

			<?=ee('CP/Alert')->get('toolsets-form')?>

			<?php $this->embed('ee:_shared/table', $table); ?>
			<?=$pagination?>
			<fieldset class="tbl-bulk-act hidden">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="enable"><?=lang('enable')?></option>
					<option value="disable"><?=lang('disable')?></option>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
				</select>
				<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		<?=form_close();?>
	</div>
</div>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('addons/settings/rte/update_toolsets'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
