<?php $this->extend('_templates/default-nav'); ?>

	<?=form_open($form_url)?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title">
				<?=$cp_heading?>
			</h2>

			<?php if (isset($filters)) echo $filters; ?>
		</div>

		<?php $this->embed('_shared/table', $table); ?>

		<?=$pagination?>

		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
			<?php if ($can_delete || $can_moderate): ?>
		<fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if ($can_delete): ?>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-delete-comment"><?=lang('delete')?></option>
				<?php endif; ?>
				<?php if ($can_moderate): ?>
				<option value="open"><?=lang('set_to_open')?></option>
				<option value="closed"><?=lang('set_to_closed')?></option>
				<option value="pending"><?=lang('set_to_pending')?></option>
				<?php endif; ?>
			</select>
			<button class="button button--primary" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
			<?php endif; ?>
		<?php endif; ?>
	<?=form_close()?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-delete-comment',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('delete-comment', $modal);
?>
