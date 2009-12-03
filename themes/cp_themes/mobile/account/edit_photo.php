<?php $this->load->view('account/_header')?>


<?=form_open_multipart('C=myaccount'.AMP.'M=upload_photo', '', $form_hidden)?>

<div class="label" style="margin-top:15px">
<?=lang('current_photo', 'current_photo')?>
</div>
<ul>
	<li><?=$photo?></li>
</ul>

<?php if($remove_photo):?>
<p class="submit"><?=form_submit('remove', lang('remove_photo'), 'class="whiteButton"')?></p>
<?php endif;?>

<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_photo.php */
/* Location: ./themes/cp_themes/default/account/edit_photo.php */