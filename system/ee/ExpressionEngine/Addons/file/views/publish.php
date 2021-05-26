<input type="hidden" class="js-file-input" name="<?=$field_name?>" value="<?=$value?>">

<div class="fields-upload-chosen list-item <?php if (empty($value)) {
    echo " hidden";
}?>">

  <div class="fields-upload-chosen-name">
		<div>
			<?php if ($title): ?>
				<?=$title?>
			<?php elseif ($file): $file_info = pathinfo($file->file_name); ?>
				<?=$file_info['filename']?></b>.<?=$file_info['extension']?>
			<?php endif ?>
		</div>
		<!--<div class="list-item__secondary">File Size</div>-->
	</div>

  <div class="fields-upload-chosen-controls">
		<div class="fields-upload-tools">
			<div class="button-group button-group-small">
				<?=$fp_edit?>
				<a href="" class="remove button button--default" title="<?=lang('remove')?>"><i class="fa fa-times"></i></a>
			</div>
		</div>
	</div>

  <div class="fields-upload-chosen-file">
		<figure class="<?php if (! $is_image): ?>no-img<?php endif ?> <?php if ($file && $file->isSVG()): ?>is-svg<?php endif ?>"">
			<img src="<?=$thumbnail?>" id="<?=$field_name?>" alt="<?=($file) ? $file->title : ''?>" class="js-file-image<?php if ($file && !$is_image): ?> hidden<?php endif ?>">
		</figure>
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
