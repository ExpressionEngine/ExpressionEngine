<input type="hidden" class="js-file-input" name="<?=$field_name?>" value="<?=$value?>">

<div class="fields-upload-chosen <?php if ( ! $file) echo " hidden";?>">
	<div class="fields-upload-chosen-file">
		<figure class="<?php if ( ! $is_image): ?>no-img<?php endif ?> <?php if ($file && $file->isSVG()): ?>is-svg<?php endif ?>"">
			<img src="<?=$thumbnail?>" id="<?=$field_name?>" alt="<?=($file) ? $file->title : ''?>" class="js-file-image<?php if ( ! $is_image): ?> hidden<?php endif ?>">
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

<?php
$component = [
	'allowedDirectory' => $allowed_directory,
	'contentType' => $content_type,
	'file' => $file
];
?>

<div data-file-field-react="<?=base64_encode(json_encode($component))?>">
	<div class="fields-select">
		<div class="field-inputs">
			<label class="field-loading">
				<?=lang('loading')?><span></span>
			</label>
		</div>
	</div>
</div>
