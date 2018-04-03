<?php $this->extend('_templates/default-nav-table'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<h1>
			<?=$cp_heading?>
			<ul class="toolbar">
				<li class="settings"><a href="<?=ee('CP/URL')->make('settings/comments')?>" title="<?=lang('comment_settings')?>"></a></li>
			</ul>
		</h1>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if ($can_delete || $can_moderate): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($can_delete): ?>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-comment"><?=lang('remove')?></option>
				<?php endif; ?>
				<?php if ($can_moderate): ?>
				<option value="open"><?=lang('set_to_open')?></option>
				<option value="closed"><?=lang('set_to_closed')?></option>
				<option value="pending"><?=lang('set_to_pending')?></option>
				<?php endif; ?>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	<?=form_close()?>
</div>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-comment',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-comment', $modal);
?>
