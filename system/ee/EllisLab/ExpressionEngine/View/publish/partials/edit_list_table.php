<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<?php if (ee()->cp->allowed_group('can_create_entries')): ?>
		<fieldset class="tbl-search right">
			<?= $create_button ?>
		</fieldset>
		<?php endif; ?>
		<h1><?=$cp_heading?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if (ee()->cp->allowed_group('can_delete_all_entries') || ee()->cp->allowed_group('can_delete_self_entries')): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-entry"><?=lang('remove')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	<?=form_close()?>
</div>
