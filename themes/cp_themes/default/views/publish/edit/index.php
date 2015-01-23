<?php extend_template('wrapper'); ?>

<div class="col-group">
	<div class="col w-16 last">
		<?php if (count($cp_breadcrumbs)): ?>
			<ul class="breadcrumb">
				<?php foreach ($cp_breadcrumbs as $link => $title): ?>
					<li><a href="<?=$link?>"><?=$title?></a></li>
				<?php endforeach ?>
				<li class="last"><?=$cp_page_title?></li>
			</ul>
		<?php endif ?>
			<div class="box">
				<div class="tbl-ctrls">
					<?=form_open($form_url)?>
						<fieldset class="tbl-search right">
							<input placeholder="<?=lang('type_phrase')?>" type="text" name="search" value="<?=$search_value?>">
							<input class="btn submit" type="submit" value="<?=lang('btn_search_entries')?>">
						</fieldset>
						<h1><?=$cp_heading?></h1>
						<?=ee('Alert')->getAllInlines()?>
						<?php if (isset($filters)) echo $filters; ?>
						<?php $this->view('_shared/table', $table); ?>
						<?php $this->view('_shared/pagination'); ?>
						<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
						<fieldset class="tbl-bulk-act">
							<select name="bulk_action">
								<option value="">-- <?=lang('with_selected')?> --</option>
								<option value="edit"><?=lang('edit')?></option>
								<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-entry"><?=lang('remove')?></option>
								<option value="categories"><?=lang('manage_categories')?></option>
							</select>
							<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
						</fieldset>
						<?php endif; ?>
					<?=form_close()?>
				</div>
			</div>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-entry',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>

<?php if (isset($blocks['modals'])) echo $blocks['modals']; ?>