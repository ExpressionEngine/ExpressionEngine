<?php $this->load->view('account/_header')?>

<?=form_open_multipart('C=myaccount'.AMP.'M=update_signature', '', $form_hidden)?>

<div class="label" style="margin-top:15px">
	<?=lang('signature', 'signature')?>
</div>
<ul>
	<li><?=form_textarea(array('id'=>'signature','rows'=> 8,'name'=>'signature','class'=>'field','value'=>$signature))?></li>
</ul>

<?php if ($this->config->item('sig_allow_img_upload') == 'y'):?>
<div class="label">
	<?=lang('signature_image', 'signature_image')?>
</div>
<ul>
	<li><?=$sig_img_filename?></li>
</ul>

<?php endif;?>

<?=form_submit('update_signature', lang('update_signature'), 'class="whiteButton"')?>

<?php if($sig_image_remove):?>
<?=form_submit('remove', lang('remove_image'), 'class="whiteButton"')?>
<?php endif;?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_signature.php */
/* Location: ./themes/cp_themes/default/account/edit_signature.php */