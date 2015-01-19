<div class="col w-4">
	<div class="box sidebar">
		<h2><?=lang('upload_directories')?> <a class="btn action" href="<?=cp_url('files/directory/create')?>"><?=lang('new')?></a></h2>
		<div class="scroll-wrap">
			<ul class="folder-list">
				<?php foreach ($upload_directories as $dir): ?>
				<li<?php if (isset($dir['class'])): ?> class="<?=$dir['class']?>"<?php endif; ?>>
					<a href="<?=$dir['url']?>"><?=$dir['name']?></a>
					<ul class="toolbar">
						<li class="edit"><a href="<?=$dir['edit_url']?>" title="<?=lang('edit')?>"></a></li>
						<li class="remove"><a class="m-link" rel="modal-confirm-remove-directory" href="" title="<?=lang('remove')?>" data-confirm="<?=lang('upload_directory')?>: <b><?=$dir['name']?></b>" data-dir-id="<?=$dir['id']?>"></a></li>
					</ul>
				</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
</div>

<?php $this->startOrAppendBlock('modals'); ?>

<?php

$modal_vars = array(
	'name'		=> 'modal-confirm-remove-directory',
	'form_url'	=> cp_url('design/group/remove'),
	'hidden'	=> array(
		'group_name'	=> ''
	)
);

$this->ee_view('_shared/modal_confirm_remove', $modal_vars);
?>

<?php $this->endBlock(); ?>