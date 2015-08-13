<li<?=$class?>>
	<a href="<?=$url?>"><?=$text?></a>
	<ul class="toolbar">
		<li class="edit"><a href="<?=$edit_url?>" title="<?=lang('edit')?>"></a></li>
		<?php if ($confirm): ?>
		<li class="remove"><a class="m-link" rel="modal-confirm-<?=$modal_name?>" href="" title="<?=lang('remove')?>" data-confirm="<?=$confirm?>" data-<?=$key?>="<?=$value?>"></a></li>
		<?php endif; ?>
	</ul>
</li>