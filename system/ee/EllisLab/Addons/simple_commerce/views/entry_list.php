	<?=form_open($form_url, 'class="tbl-ctrls"')?>
		<h1><?=sprintf(lang('create_new_item_step'), 1)?><br><i><?=lang('create_new_item_step_desc')?></i></h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->embed('ee:_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="add_item"><?=lang('add_item')?></option>
			</select>
			<button class="button button--primary"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
