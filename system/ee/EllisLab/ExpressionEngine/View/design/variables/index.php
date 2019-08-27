<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
			<?php if (isset($filters)) echo $filters; ?>
			<div class="title-bar__extra-tools">
				<a class="button button--action button--small" href="<?=ee('CP/URL')->make('design/variables/create')?>"><?=lang('create_new')?></a>
			</div>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?php if (isset($pagination)) echo $pagination; ?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
				<option value="export"><?=lang('export_variables')?></option>
			</select>
			<button class="button button--primary" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
