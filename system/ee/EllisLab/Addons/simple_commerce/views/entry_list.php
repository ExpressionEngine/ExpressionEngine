<div class="box table-list-wrap">
	<?=form_open($form_url, 'class="tbl-ctrls"')?>
		<h1><?=sprintf(lang('create_new_item_step'), 1)?><br><i><?=lang('create_new_item_step_desc')?></i></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="add_item"><?=lang('add_item')?></option>
			</select>
			<button class="btn submit"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
</div>
