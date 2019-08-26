<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<fieldset class="tbl-search right">
			<a class="btn tn action" href="<?=ee('CP/URL')->make('files/watermarks/create')?>"><?=lang('create_new')?></a>
		</fieldset>

		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title"><?=$cp_page_title?></h2>
		</div>

		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option>-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
			</select>
			<input class="button button--primary" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
		</fieldset>
	</form>
</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> ee('CP/URL')->make('files/watermarks/remove', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
