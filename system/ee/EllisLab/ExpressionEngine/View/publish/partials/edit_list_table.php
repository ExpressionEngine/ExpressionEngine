<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<h1><?=$cp_heading?></h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if ($can_edit || $can_delete): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($can_delete): ?>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-entry"><?=lang('remove')?></option>
				<?php endif ?>
				<?php if ($can_edit): ?>
					<option value="edit" data-confirm-trigger="selected" rel="modal-edit"><?=lang('edit')?></option>
					<option value="bulk-edit" data-confirm-trigger="selected" rel="modal-bulk-edit"><?=lang('bulk_edit')?></option>
					<option value="add-categories" data-confirm-trigger="selected" rel="modal-bulk-edit"><?=lang('add_categories')?></option>
					<option value="remove-categories" data-confirm-trigger="selected" rel="modal-bulk-edit"><?=lang('remove_categories')?></option>
				<?php endif ?>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	<?=form_close()?>
</div>
