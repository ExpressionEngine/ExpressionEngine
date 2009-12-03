<?php $this->load->view('account/_header')?>


<?=form_open('C=myaccount'.AMP.'M=update_preferences', '', $form_hidden)?>

<ul>
<?php foreach($checkboxes as $checkbox):?>
	<li><?=form_checkbox(array('id'=>$checkbox,'name'=>$checkbox,'value'=>$checkbox, 'checked'=>($$checkbox=='y') ? TRUE : FALSE))?> <?=lang($checkbox)?></li>
<?php endforeach;?>
</ul>

<?=form_submit('update_preferences', lang('update'), 'class="whiteButton"')?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_preferences.php */
/* Location: ./themes/cp_themes/default/account/edit_preferences.php */