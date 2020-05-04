<?php $this->extend('_templates/default-nav'); ?>

<?=form_open($form_url)?>
		 	<div class="app-notice-wrap"><?=ee('CP/Alert')->getAllInlines()?></div>

			 <div class="title-bar">
				<h2 class="title-bar__title"><?php echo isset($cp_heading) ? $cp_heading : $cp_page_title?></h2>
				<?php if (isset($filters)) echo $filters; ?>
				<div class="title-bar__extra-tools">
					<a class="button button--small button--action" href="<?=$new?>"><?= lang('new') ?></a>
				</div>
			</div>

			 <?php $this->embed('_shared/table', $table); ?>

			 <?php if ( ! empty($pagination)) $this->embed('_shared/pagination', $pagination); ?>

			 <?php if ( ! empty($table['data'])): ?>
			 <fieldset class="bulk-action-bar hidden">
			<select name="bulk_action">
									<option value="">-- <?=lang('with_selected')?> --</option>
									<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-delete"><?=lang('delete')?></option>
					</select>
				<button class="button button--primary" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
			 </fieldset>
			 <?php endif; ?>
<?=form_close()?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-delete',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_delete')->render($modal_vars);
ee('CP/Modal')->addModal('remove', $modal);
?>
