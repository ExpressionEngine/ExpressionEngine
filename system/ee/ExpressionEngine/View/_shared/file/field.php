<div class="file_field">
	<div class="file_set <?=$set_class?>">

		<p class="filename">
			<?=$thumb?>
			<br />
			<?=$filename?>
			<?=$remove_file_link?>
		</p>
		<p><?=$hidden?></p>
	</div>

	<div class='file_upload'>
		<p class="sub_filename">
			<?=$undo_link?>
			<?=$filebrowser ? $upload_link : ''?>
		</p>

		<div class="no_file <?=($filebrowser || $filename) ? 'js_hide' : ''?>">
			<p class='sub_filename'><?=$upload?></p>
			<?php if (! $allowed_file_dirs):?>
				<p><?=$dropdown?></p>
			<?php else: ?>
				<p><?=$directory?></p>
			<?php endif; ?>

			<?php if (! empty($existing_files)) : ?>
				<p class="file_existing"><?=$existing_files?></p>
			<?php endif; ?>
		</div>
	</div>
</div>
<div class="clear"></div>
