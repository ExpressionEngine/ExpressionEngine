<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
<?php if ($file): ?>
<figure class="file-chosen">
	<img src="<?=$file['url']?>" alt="<?=$file['title']?>">
	<ul class="toolbar">
		<li class="edit"><a href="" title="<?=lang('edit')?>"></a></li>
		<li class="remove"><a href="" title="<?=lang('remove')?>"></a></li>
	</ul>
</figure>
<?php else: ?>
<p class="solo-btn"><a class="btn action m-link" href="#" rel="modal-file" data-field-name="<?=$field_name?>"><?=lang('upload_file')?></a></p>
<?php endif; ?>