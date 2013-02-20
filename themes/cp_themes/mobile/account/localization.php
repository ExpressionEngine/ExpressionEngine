<?php $this->load->view('account/_header')?>

<?=form_open('C=myaccount'.AMP.'M=localization_update', '', $form_hidden)?>

<div class="label">
	<?=form_label(lang('timezone'), 'timezone')?>
</div>
<ul>
	<li><?=$timezone_menu?></li>
</ul>

<div class="label">
	<?=form_label(lang('time_format'), 'time_format')?>
</div>
<ul>
	<li><?=form_dropdown('time_format', $time_format_options, $time_format, 'id="time_format"')?></li>
</ul>

<div class="label">
	<?=form_label(lang('language'), 'language')?>
</div>
<ul>
	<li><?=form_dropdown('language', $language_options, $language, 'id="language"')?></li>
</ul>

<?=form_submit('localization_update', lang('update'), 'class="whiteButton"')?>

<?=form_close()?>

</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file localization.php */
/* Location: ./themes/cp_themes/default/account/localization.php */