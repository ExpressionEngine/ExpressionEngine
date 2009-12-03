<?php $this->load->view('account/_header')?>


<?=form_open_multipart('C=myaccount'.AMP.'M=upload_avatar', '', $form_hidden)?>

<div class="label" style="margin-top:15px">
	<?=lang('current_avatar', 'current_avatar')?>
</div>
<ul>
	<li><?=$avatar?></li>
</ul>

<div class="label">
	<?=lang('choose_installed_avatar', 'choose_installed_avatar')?>
</div>
<ul>
	<?php
		foreach($avatar_dirs as $dir=>$file):
	?>
		<li><a href="<?=BASE.AMP.'C=myaccount'.AMP.'M=browse_avatars'.AMP.'folder='.$dir.AMP.'id='.$id?>"><?=ucwords(str_replace("_", " ", $dir))?></a></li>
	<?php
		if (++$i < count($avatar_dirs)) {echo ' | ';}
		endforeach;
	?>
</ul>
<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_avatar.php */
/* Location: ./themes/cp_themes/default/account/edit_avatar.php */