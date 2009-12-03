<?php $this->load->view('account/_header')?>


<div>

	<?=form_open('C=myaccount'.AMP.'M=save_theme', '', $form_hidden)?>

	<div class="label" style="margin-top:15px">
		<?=lang('choose_theme', 'cp_theme')?>
	</div>
	<ul>
		<li><?=form_dropdown('cp_theme', $cp_theme_options, $cp_theme, 'id="cp_theme"')?></li>
	</ul>

	<?=form_submit('save_theme', lang('update'), 'class="whiteButton"')?>

	<?=form_close()?>
</div>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file cp_theme.php */
/* Location: ./themes/cp_themes/default/account/cp_theme.php */