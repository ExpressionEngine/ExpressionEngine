<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
<div class="fields-upload-btn<?php if ($file) echo " hidden";?>">
	<?= $fp_upload ?>
</div>

<div class="fields-upload-chosen <?php if ( ! $file) echo " hidden";?>">
	<div class="fields-upload-chosen-file">
		<figure<?php if ( ! $is_image): ?> class="no-img"<?php endif ?>>
			<img src="<?=$thumbnail?>" id="<?=$field_name?>" alt="<?=($file) ? $file->title : ''?>" <?php if ( ! $is_image): ?> class="hidden"<?php endif ?>>
		</figure>
		<div class="fields-upload-tools">
			<ul class="toolbar">
				<li class="edit"><?=$fp_edit?></li>
				<li class="remove"><a href="" title="<?=lang('remove')?>"></a></li>
			</ul>
		</div>
	</div>
	<div class="fields-upload-chosen-name">
		<p>
			<?php if ($title): ?>
				<b><?=$title?></b>
			<?php elseif ($file): $file_info = pathinfo($file->file_name); ?>
				<b><?=$file_info['filename']?></b>.<?=$file_info['extension']?>
			<?php endif ?>
		</p>
	</div>
</div>
