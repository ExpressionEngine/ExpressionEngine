<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL')->make('channels/cat/create-field/'.$group_id)?>"><?=lang('create_new')?></a>
		</fieldset>
		<h1><?=$cp_page_title?></h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php $this->embed('_shared/table', $table); ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option>-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	</form>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('channels/cat/remove-field', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove',
		'group_id'		=> $group_id
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
