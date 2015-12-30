<?php $this->extend('_templates/default-nav'); ?>

<div class="tbl-ctrls">
	<?=form_open($form_url)?>
		<?php if (ee()->cp->allowed_group('can_upload_new_files')): ?>
		<fieldset class="tbl-search right">
			<div class="filters">
				<ul>
					<li>
						<a class="has-sub" href=""><?=lang('upload_new_file')?></a>
						<div class="sub-menu">
							<?php if ($directories->count()): ?>
								<fieldset class="filter-search">
									<input type="text" value="" placeholder="<?=lang('filter_upload_directories')?>">
								</fieldset>
							<?php endif ?>
							<?php if (count($directories) > 9): ?><div class="scroll-wrap"><?php endif;?>
							<ul>
								<?php foreach ($directories as $dir): ?>
									<li><a href="<?=ee('CP/URL')->make('files/upload/' . $dir->id)?>"><?=$dir->name?></a></li>
								<?php endforeach ?>
								<?php if (ee()->cp->allowed_group('can_create_upload_directories')): ?>
									<li class="last"><a class="add" href="<?=ee('CP/URL', 'files/uploads/create')?>"><?=lang('new_upload_directory')?></a></li>
								<?php endif ?>
							</ul>
							<?php if (count($directories) > 9): ?></div><?php endif;?>
						</div>
					</li>
				</ul>
			</div>
		</fieldset>
		<?php endif; ?>
		<h1>
			<?=$cp_heading?>
		</h1>
		<?=ee('CP/Alert')->getAllInlines()?>
		<?php if (isset($filters)) echo $filters; ?>
		<?php $this->embed('_shared/table', $table); ?>
		<?=$pagination?>
		<?php if ( ! empty($table['columns']) && ! empty($table['data'])): ?>
		<fieldset class="tbl-bulk-act hidden">
			<select name="bulk_action">
				<option value="">-- <?=lang('with_selected')?> --</option>
				<?php if (ee()->cp->allowed_group('can_delete_files')): ?>
				<option value="remove" data-confirm-trigger="selected" rel="modal-confirm-remove-file"><?=lang('remove')?></option>
				<?php endif; ?>
				<option value="download"><?=lang('download')?></option>
			</select>
			<button class="btn submit" data-conditional-modal="confirm-trigger"><?=lang('submit')?></button>
		</fieldset>
		<?php endif; ?>
	<?=form_close()?>
</div>

<?php ee('CP/Modal')->startModal('view-file'); ?>

<div class="modal-wrap modal-view-file hidden">
	<div class="modal">
		<div class="col-group">
			<div class="col w-16">
				<a class="m-close" href="#"></a>
				<div class="box">
				</div>
			</div>
		</div>
	</div>
</div>

<?php ee('CP/Modal')->endModal(); ?>

<?php
$modal_vars = array(
	'name'		=> 'modal-confirm-remove-file',
	'form_url'	=> $form_url,
	'hidden'	=> array(
		'bulk_action'	=> 'remove'
	)
);

$modal = $this->make('ee:_shared/modal_confirm_remove')->render($modal_vars);
ee('CP/Modal')->addModal('remove-file', $modal);
?>
