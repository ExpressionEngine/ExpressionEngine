	<div class="tbl-ctrls">
	<?=form_open($table['base_url'])?>
		<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

		<div class="title-bar">
			<h2 class="title-bar__title">
				<?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></br>
			</h2>

			<?php if (isset($filters)) echo $filters; ?>
		</div>

		<?= ee('View')->make('ee:_shared/table')->render($table); ?>

		<?php if ( ! empty($pagination)) echo $pagination; ?>

		<?php if ( ! empty($table['data'])): ?>
		<fieldset class="bulk-action-bar">
			<select name="bulk_action">
				<option value="">-- <?=lang('mark_selected')?> --</option>
				<option value="remove" rel="modal-confirm-remove"><?=lang('deny_spam')?></option>
				<option value="approve" class="yes" rel="modal-confirm-remove"><?=lang('approve_spam')?></option>
			</select>
			<button class="button button--primary"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
	</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$spam = ee('View')->make('spam:modal')->render();
ee('CP/Modal')->addModal('spam', $spam);
?>

<?php $this->endBlock(); ?>
