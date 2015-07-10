<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('edit_avatar')?></h3>

	<?=form_open_multipart('C=myaccount'.AMP.'M=upload_avatar', '', $form_hidden)?>

	<p>
		<span><?=lang('current_avatar')?></span>
		<?=$avatar?>
	</p>

	<p>
		<span><?=lang('choose_installed_avatar')?></span>
		<?php
			foreach($avatar_dirs as $dir=>$file):
		?>
			<a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'folder='.$dir.AMP.'id='.$id?>"><?=ucwords(str_replace("_", " ", $dir))?></a>
		<?php
			if (++$i < count($avatar_dirs)) {echo ' | ';}
			endforeach;
		?>
	</p>

	<?php if ($this->config->item('allow_avatar_uploads') == 'y'):?>
	<p>
		<?=lang('upload_an_avatar', 'userfile')?>
		<?=form_upload(array('userfile'=>'url','name'=>'userfile','class'=>'field'))?>
		<br ><?=lang('allowed_image_types')?>
		<br /><?=$max_size?>
	</p>

	<p class="submit"><?=form_submit('upload_avatar', lang('upload_avatar'), 'class="submit"')?></p>

	<?php if($avatar_image_remove):?>
	<p class="submit"><?=form_submit('remove', lang('remove_avatar'), 'class="submit"')?></p>
	<?php endif;?>

	<?php endif;?>

	<?=form_close()?>
</div>