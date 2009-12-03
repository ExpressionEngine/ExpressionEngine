<?php $this->load->view('account/_header')?>

<?=form_open('C=myaccount'.AMP.'M=localization_update', '', $form_hidden)?>

<div class="label">
	<?=form_label(lang('timezone'), 'timezone')?>
</div>
<ul>
	<li><?=timezone_menu($timezone)?></li>
</ul>

<div class="label">
	<?=lang('daylight_savings_time', 'daylight_savings_time')?><br />
</div>
<ul>
	<li><?=form_radio('daylight_savings', 'y', $daylight_savings_y, 'id="daylight_savings_y"')?> <?=lang('yes')?> <br />
	<?=form_radio('daylight_savings', 'n', $daylight_savings_n, 'id="daylight_savings_n"')?> <?=lang('no')?></li>
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