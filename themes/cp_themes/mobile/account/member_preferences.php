<?php $this->load->view('account/_header')?>

		<?=form_open('C=myaccount'.AMP.'M=member_preferences_update', '', $form_hidden)?>

		<div class="label">
			<?=form_label(lang('member_group_assignment'), 'group_id')?>
		</div>
		<ul>
			<li><?=form_dropdown('group_id', $group_id_options, $group_id, 'id="group_id"')?></li>
		</ul>

		<div class="container pad">
			<?=form_checkbox(array('id'=>'in_authorlist','name'=>'in_authorlist','value'=>'y', 'checked'=>($in_authorlist=='y') ? TRUE : FALSE))?>
			<strong><?=lang('include_in_multiauthor_list')?></strong>
		</div>

		<div class="container pad">
			<?=form_checkbox(array('id'=>'localization_is_site_default','name'=>'localization_is_site_default','value'=>'y', 'checked'=>($localization_is_site_default=='y') ? TRUE : FALSE))?>
			<strong><?=lang('localization_is_site_default')?></strong>
		</div>

		<?=form_submit('member_preferences', lang('update'), 'class="whiteButton"')?>

		<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file member_preferences.php */
/* Location: ./themes/cp_themes/default/account/member_preferences.php */