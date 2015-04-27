<div class="col w-4">
	<div class="box sidebar">
		<?php if ($can_admin_upload_prefs): ?>
		<h2><?=lang('upload_directories')?> <a class="btn action" href="<?=cp_url('files/uploads/new-upload')?>"><?=lang('new')?></a></h2>
		<?php else: ?>
		<h2><?=lang('upload_directories')?></h2>
		<?php endif; ?>
		<div class="scroll-wrap">
			<ul class="folder-list">
				<?php if (empty($upload_directories)): ?>
					<li class="no-results"><?=lang('zero_directories_found')?></li>
				<?php endif ?>
				<?php foreach ($upload_directories as $dir): ?>
				<li<?php if (isset($dir['class'])): ?> class="<?=$dir['class']?>"<?php endif; ?>>
					<a href="<?=$dir['url']?>"><?=$dir['name']?></a>
					<?php if ($can_admin_upload_prefs): ?>
					<ul class="toolbar">
						<li class="edit"><a href="<?=$dir['edit_url']?>" title="<?=lang('edit')?>"></a></li>
						<li class="remove"><a class="m-link" rel="modal-confirm-remove-directory" href="" title="<?=lang('remove')?>" data-confirm="<?=lang('upload_directory')?>: <b><?=$dir['name']?></b>" data-dir-id="<?=$dir['id']?>"></a></li>
					</ul>
					<?php endif; ?>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>

<?php if ($can_admin_upload_prefs): ?>
<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'     => 'modal-confirm-remove-directory',
	'form_url' => cp_url('files/rmdir'),
	'hidden'   => array(
		'return' => base64_encode(ee()->cp->get_safe_refresh()),
		'dir_id' => '',
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>
<?php endif; ?>