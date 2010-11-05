<?php $this->load->view('account/_header')?>

<ul class="cp_button">
	<li><a class="animate" href="<?=BASE.AMP.'C=myaccount'.AMP.'M=edit_profile_field'?>"><?=lang('add_member')?></a></li>
</ul>

<?=form_open('C=myaccount'.AMP.'M=ignore_list', '', $form_hidden)?>

<div id="add_member">
	<div class="label"><?=lang('Member Screen Name', 'name')?></div>
	<ul>
		<li><?=form_input(array('id'=>'name','name'=>'name','class'=>'field','value'=>'','maxlength'=>50))?></li>
	</ul>

	<?=form_submit('daction', lang('add_member'), 'class="whiteButton"')?>
</div>

	<div class="label">
		<?=form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')?> <?=lang('mbr_screen_name', 'mbr_screen_name')?>
	</div>
	
	<?php 
	if (count($ignored_members) == 0) // No results?  Bah, how boring...
	{
		echo "<p class='notice pad'>".lang('ignore_list_empty')."</p>";
	}
	else
	{
		foreach ($ignored_members as $member):?>
			<ul>
				<li><input class="toggle" type="checkbox" name="toggle[]" value="<?=$member['member_id']?>" /> <?=$member['member_name']?></li>
			</ul>
		<?php endforeach;
	}
	?>

<?php if (count($ignored_members) > 0):?>
	<?=form_submit('unignore', lang('unignore'), 'class="whiteButton"')?>
<?php endif;?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file ignore_list.php */
/* Location: ./themes/cp_themes/default/account/ignore_list.php */