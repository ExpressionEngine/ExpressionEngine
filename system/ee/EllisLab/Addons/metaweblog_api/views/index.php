<div class="box">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL', 'addons/settings/metaweblog_api/create')?>"><?=lang('create_new')?></a>
		</fieldset>
		<h1><?=lang('metaweblog_settings')?></h1>

		<?=ee('Alert')->get('metaweblog-form')?>

		<?php $this->ee_view('_shared/table', $table); ?>
		<?=$pagination?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	<?=form_close();?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL', 'addons/settings/metaweblog_api'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>