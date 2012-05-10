<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('edit_photo')?></h3>

	<?=form_open_multipart('C=myaccount'.AMP.'M=upload_photo', '', $form_hidden)?>

	<p>
		<span><?=lang('current_photo')?></span>
		<?=$photo?>
	</p>

	<p>
		<?=lang('upload_photo', 'userfile')?>
		<?=form_upload(array('userfile'=>'url','name'=>'userfile','class'=>'field'))?>
		<br ><?=lang('allowed_image_types')?>
		<br /><?=$max_size?>
	</p>

	<p class="submit"><?=form_submit('upload_avatar', lang('upload_photo'), 'class="submit"')?></p>

	<?php if($remove_photo):?>
	<p class="submit"><?=form_submit('remove', lang('remove_photo'), 'class="submit"')?></p>
	<?php endif;?>

	<?=form_close()?>
</div>