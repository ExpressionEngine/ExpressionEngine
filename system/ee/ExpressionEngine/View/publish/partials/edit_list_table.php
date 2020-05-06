<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar js-filters-collapsable">
			<h2 class="title-bar__title"><?=$cp_heading?></h2>
			<?php if (isset($filters)) echo $filters; ?>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if ($can_edit || $can_delete): ?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($can_delete): ?>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-delete-entry"><?=lang('delete')?></option>
				<?php endif ?>
				<?php if ($can_edit): ?>
					<option value="edit" data-confirm-trigger="selected" rel="modal-edit"><?=lang('edit')?></option>
					<option value="bulk-edit" data-confirm-trigger="selected" rel="modal-bulk-edit"><?=lang('bulk_edit')?></option>
					<option value="add-categories" data-confirm-trigger="selected" rel="modal-bulk-edit"><?=lang('add_categories')?></option>
					<option value="remove-categories" data-confirm-trigger="selected" rel="modal-bulk-edit"><?=lang('remove_categories')?></option>
				<?php endif ?>
			</select>
			<button class="button button--primary" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	<?=form_close()?>
</div>
