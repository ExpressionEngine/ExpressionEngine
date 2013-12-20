<h2><?=lang('ee_has_been_installed')?></h2>

<div class="shade">

<p class="important"><?=lang('delete_via_ftp')?></p>

<p class="pad"><?=lang('folder_is_located_at')?> <em><?=$installer_path?></em></p>

<p class="important"><strong><?=lang('no_access_until_delete')?></strong></p>

</div>

<?php if($errors > 0):?>
	<h3 class="important"><?=lang('module_errors_occurred')?></h3>
	<ul>
		<?php foreach($error_messages as $module=>$messages):?>
			<li><strong><?=$module_names[$module]['name']?></strong>
				<ul>
					<?php foreach($messages as $message):?>
					<li><?=$message?></li>
					<?php endforeach;?>
				</ul>
			</li>
		<?php endforeach;?>
	</ul>
<?php endif;?>

<h2><?=lang('bookmark_links')?></h2>

<p><a href="<?=$cp_url?>" target="_blank"><?=lang('cp_located_here')?></a></p>

<p><a href="<?=$site_url?>" target="_blank"><?=lang('site_located_here')?></a></p>