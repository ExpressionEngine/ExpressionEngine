<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('edit_signature')?></h3>

	<?=form_open_multipart('C=myaccount'.AMP.'M=update_signature', '', $form_hidden)?>

	<p>
		<?=lang('signature', 'signature')?>
		<?=form_textarea(array('id'=>'signature','rows'=> 8,'name'=>'signature','class'=>'field','value'=>$signature))?>
	</p>

	<?php if ($this->config->item('sig_allow_img_upload') == 'y'):?>
	<p>
		<span><?=lang('signature_image')?></span>
		<?=$sig_img_filename?>
	</p>

	<p>
		<?=lang('upload_image', 'userfile')?>
		<?=form_upload(array('userfile'=>'url','name'=>'userfile','class'=>'field'))?>
		<br ><?=lang('allowed_image_types')?>
		<br /><?=$max_size?>
	</p>

	<?php endif;?>

	<p class="submit"><?=form_submit('update_signature', lang('update_signature'), 'class="submit"')?></p>

	<?php if($sig_image_remove):?>
	<p class="submit"><?=form_submit('remove', lang('remove_image'), 'class="submit"')?></p>
	<?php endif;?>

	<?=form_close()?>
</div>