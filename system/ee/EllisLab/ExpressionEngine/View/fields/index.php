<?php $this->extend('_templates/default-nav', [], 'outer_box'); ?>

	<div class="tbl-ctrls">
		<?=form_open($base_url)?>
			<h1><?=$cp_page_title?></h1>
			<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
			<?php $this->embed('_shared/table-list', ['data' => $fields]); ?>
			<?php if (isset($pagination)) echo $pagination; ?>
			<fieldset class="bulk-action-bar hidden">
				<select name="bulk_action">
					<option>-- <?=lang('with_selected')?> --</option>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-delete"><?=lang('delete')?></option>
				</select>
				<input class="button button--primary" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		</form>
	</div>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> ee('CP/URL')->make('fields', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete', $modal);
?>
