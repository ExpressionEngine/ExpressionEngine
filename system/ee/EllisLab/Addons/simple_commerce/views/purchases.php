<div class="box">
	<?=form_open($base_url, 'class="tbl-ctrls"')?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL', 'addons/settings/simple_commerce/create-purchase')?>"><?=lang('create_new')?></a>
		</fieldset>
		<h1><?=lang('purchases')?></h1>
		<?=ee('Alert')->get('purchases-table')?>
		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
		<?php endif; ?>
	<?=form_close();?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
$modal_vars = array(
	'name'      => 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL', 'addons/settings/simple_commerce/remove-purchase'),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->embed('ee:_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>
