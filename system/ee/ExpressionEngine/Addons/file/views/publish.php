<input type="hidden" class="js-file-input" name="<?=$field_name?>" value="<?=$value?>" data-id="<?=($file ? $file->file_id : '')?>">

<div class="fields-upload-chosen list-item <?php if (empty($value)) {
    echo " hidden";
}?>">
	<div class="fields-upload-chosen-name" data-id="<?=($file ? $file->file_id : '')?>">
		<div title="<?=$title?>">
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
				<a href="" class="edit-meta button button--default" title="<?=lang('edit_meta')?>"><i class="fal fa-money-check-pen"></i></a>
				<?=$fp_edit?>
				<a href="" class="remove button button--default" title="<?=lang('remove')?>"><i class="fa fa-times"></i></a>
			</div>
		</div>
	</div>

	<div class="fields-upload-chosen-file">
		<figure class="<?php if (! $is_image): ?>no-img<?php endif ?> <?php if ($file && $file->isSVG()): ?>is-svg<?php endif ?>"">
			<img src="<?=$thumbnail?>" id="<?=$field_name?>" alt="<?=($file) ? $file->title : ''?>" class="js-file-image<?php if ($file && !$is_image): ?> hidden<?php endif ?>" loading="lazy">
			<?php if (!$is_image) : ?>
				<?=ee('Thumbnail')->get($file)->tag?>
			<?php endif; ?>
		</figure>
	</div>

</div>

<?php
$component = [
    'allowedDirectory' => $allowed_directory,
	'roleAllowedDirectoryIds' => isset($role_allowed_dirs) ? $role_allowed_dirs : [],
    'contentType' => $content_type,
    'file' => $file,
    'createNewDirectory' => false,
    'ignoreChild' => false,
    'addInput' => false,
    'imitationButton' => false
];
?>

<?php $this->embed('ee:_shared/file/upload-widget', ['component' => $component]); ?>
