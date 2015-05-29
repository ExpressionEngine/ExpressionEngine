<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
<figure class="file-chosen<?php if ( ! $file) echo " hidden";?>" id="<?=$field_name?>">
	<img src="<?=$file->getThumbnailURL()?>" alt="<?=$file->title?>">
	<ul class="toolbar">
		<li class="edit"><a class="m-link filepicker" href="<?=$fp_url?>" rel="modal-file" data-field-name="<?=$field_name?>" data-input-image="<?=$field_name?>" data-input-value="<?=$field_name?>" title="<?=lang('edit')?>"></a></li>
		<li class="remove"><a href="" title="<?=lang('remove')?>"></a></li>
	</ul>
</figure>
<?php if($file && ! $file->exists()): ?>
<em><?=sprintf(lang('file_ft_cannot_find_file'), $file->file_name)?></em>
<?php endif; ?>
<p class="solo-btn<?php if ($file) echo " hidden";?>"><a class="btn action m-link filepicker" href="<?=$fp_url?>" rel="modal-file" data-field-name="<?=$field_name?>" data-input-image="<?=$field_name?>" data-input-value="<?=$field_name?>"><?=lang('upload_file')?></a></p>