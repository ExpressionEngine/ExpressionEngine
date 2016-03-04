<input type="hidden" name="<?=$field_name?>" value="<?=$value?>">
<p class="solo-btn<?php if ($file) echo " hidden";?>">
	<?= $fp_upload ?>
</p>
<?php
$classes = array();
if ( ! $file)
{
	$classes[] = 'hidden';
}

if ( ! $is_image)
{
	$classes[] = 'no-image';
}
?>
<figure class="file-chosen <?=implode(' ', $classes);?>">
	<img <?php if ( ! $is_image) echo 'class="hidden"' ?> id="<?=$field_name?>" src="<?=$thumbnail?>" alt="<?=($file) ? $file->title : ''?>">
	<ul class="toolbar">
	<?php if ( $file && ! $is_image): ?>
		<li class="txt-only"><a href="#"><b><?=$file->title?></b></a></li>
	<?php endif; ?>
		<li class="edit"><?=$fp_edit?></li>
		<li class="remove"><a href="" title="<?=lang('remove')?>"></a></li>
	</ul>
</figure>
<?php if($file && ! $file->exists()): ?>
<em><?=sprintf(lang('file_ft_cannot_find_file'), $file->file_name)?></em>
<?php endif; ?>
