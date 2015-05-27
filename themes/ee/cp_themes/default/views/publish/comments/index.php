<?php extend_template('default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<fieldset class="tbl-search right">
			<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$search_value?>">
			<input class="btn submit" type="submit" value="<?=lang('btn_search_entries')?>">
		</fieldset>
		<h1>
			<?=$cp_heading?>
			<ul class="toolbar">
				<li class="settings"><a href="<?=cp_url('settings/comments')?>" title="<?=lang('comment_settings')?>"></a></li>
			</ul>
		</h1>
		<?=ee('Alert')->getAllInlines()?>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->view('_shared/table', $table); ?>
		<?php $this->view('_shared/pagination'); ?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-comment"><?=lang('remove')?></option>
				<option value="open"><?=lang('set_to_open')?></option>
				<option value="closed"><?=lang('set_to_closed')?></option>
				<option value="pending"><?=lang('set_to_pending')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-comment',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>