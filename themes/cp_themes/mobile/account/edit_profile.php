<?php $this->load->view('account/_header')?>


<?=form_open('C=myaccount'.AMP.'M=update_profile', '', $form_hidden)?>

<div class="label" style="margin-top:15px">
	<?=lang('birthday', 'birthday')?>
</div>
<ul>
	<li><?=form_dropdown('bday_y', $bday_y_options, $bday_y, 'id="bday_y"')?></li>
	<li><?=form_dropdown('bday_m', $bday_m_options, $bday_m, 'id="bday_m"')?></li> 
	<li><?=form_dropdown('bday_d', $bday_d_options, $bday_d, 'id="bday_d"')?></li>
</ul>

<div class="label">
	<?=lang('url', 'url')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'url','name'=>'url','class'=>'field','value'=>$url,'maxlength'=>75))?></li>
</ul>

<div class="label">
	<?=lang('location', 'location')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'location','name'=>'location','class'=>'field','value'=>$location,'maxlength'=>50))?></li>
</ul>

<div class="label">
	<?=lang('occupation', 'occupation')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'occupation','name'=>'occupation','class'=>'field','value'=>$occupation,'maxlength'=>80))?></li>
</ul>

<div class="label">
	<?=lang('interests', 'interests')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'interests','name'=>'interests','class'=>'field','value'=>$interests,'maxlength'=>75))?></li>
</ul>

<div class="label">
	<?=lang('aol_im', 'aol_im')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'aol_im','name'=>'aol_im','class'=>'field','value'=>$aol_im,'maxlength'=>50))?></li>
</ul>

<div class="label">
	<?=lang('icq', 'icq')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'icq','name'=>'icq','class'=>'field','value'=>$icq,'maxlength'=>50))?></li>
</ul>

<div class="label">
	<?=lang('yahoo_im', 'yahoo_im')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'yahoo_im','name'=>'yahoo_im','class'=>'field','value'=>$yahoo_im,'maxlength'=>50))?></li>
</ul>

<div class="label">
	<?=lang('msn_im', 'msn_im')?>
</div>
<ul>
	<li><?=form_input(array('id'=>'msn_im','name'=>'msn_im','class'=>'field','value'=>$msn_im,'maxlength'=>50))?></li>
</ul>

<div class="label">
	<?=lang('bio', 'bio')?>
</div>
<ul>
	<li><?=form_textarea(array('id'=>'bio','rows'=> 12,'name'=>'bio','class'=>'field','value'=>$bio))?></li>
</ul>

<?php foreach($custom_profile_fields as $field):?>
<?php if (preg_match('/<label for(.*)?<\/label>/', $field, $label) && 
		  preg_match('/<(input|textarea)(.*)>/', $field, $input)):

?>	
	<div class="label">
		<?=$label[0]?>
	</div>
	<ul>
		<li><?=$input[0]?></li>
	</ul>
	
<?php endif; ?>
<?php endforeach;?>

<?=form_submit('edit_profile', lang('update'), 'class="whiteButton"')?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_profile.php */
/* Location: ./themes/cp_themes/default/account/edit_profile.php */