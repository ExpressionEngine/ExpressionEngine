<?php if (isset($file)):?>
	<ul>
		<li class="file_name"><?=$file['name']?></li>
		<li class="file_size"><span><?=lang('size')?>:</span> <?=$file['size']?> KB</li>
		<li class="file_type"><span><?=lang('kind')?>:</span> <?=$file['type']?></li>
		<li class="file_permissions"><span><?=lang('permissions')?>:</span> <?=$file['permissions']?></li>
		
		<li class="file_location"><span><?=lang('where')?>:</span><p><?=$file['location']?></p></li>
	</ul>

	<?php if ($file['src'] != ''):?>
		<p class="preview"><img src="<?=$file['src']?>" alt="<?=$file['name']?>" /></p>
	<?php endif;?>

<?php else:?>

	<p><?=lang('no_file')?></p>

<?php endif;?>