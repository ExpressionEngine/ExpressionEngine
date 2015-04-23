<?php extend_template('default-nav', 'outer_box'); ?>

<div class="box snap">
	<div class="tbl-ctrls">
		<?=form_open($table['base_url'])?>
			<?=ee('Alert')->getAllInlines()?>
			<fieldset class="tbl-search right">
				<a class="btn tn action" href="<?=cp_url('files/uploads/new-upload')?>"><?=lang('create_new')?></a>
			</fieldset>
			<h1><?=$table_heading?></h1>
			<?php $this->view('_shared/table', $table); ?>
			<?php $this->view('_shared/pagination'); ?>
			<fieldset class="tbl-bulk-act">
				<select name="bulk_action">
					<option value="">-- <?=lang('with_selected')?> --</option>
					<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove"><?=lang('remove')?></option>
				</select>
				<input class="btn submit" data-conditional-modal="confirm-trigger" type="submit" value="<?=lang('submit')?>">
			</fieldset>
		</form>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> cp_url('files/uploads/remove_directory', ee()->cp->get_url_state()),
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>