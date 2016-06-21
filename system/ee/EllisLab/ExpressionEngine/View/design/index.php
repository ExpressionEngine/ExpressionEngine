<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<?php if($show_new_template_button): ?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL')->make('design/template/create/' . $group_id)?>"><?=lang('create_new_template')?></a>
		</fieldset>
		<?php endif; ?>
		<h1><?=$cp_heading?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) echo $pagination; ?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($show_bulk_delete): ?>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-template"><?=lang('remove')?></option>
				<?php endif; ?>
				<option value="export"><?=lang('export_templates')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
</div>

<?php ee('CP/Modal')->startModal('template-settings'); ?>

<div class="modal-wrap modal-template-settings hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
				</div>
			</div>
		</div>
	</div>
</div>

<?php ee('CP/Modal')->endModal(); ?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-template',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-template', $modal);
?>
