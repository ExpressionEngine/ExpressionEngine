<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
		<div class="contents">

			<div class="heading"><h2><?=lang('import_from_xml')?></h2></div>

			<div class="pageContents">
				
			<?php $this->load->view('_shared/message');?>

			<?=form_open('C=tools_utilities'.AMP.'M=confirm_xml_form')?>
	
			<p><?=lang('import_from_xml_blurb')?></p>		

			<p class="go_notice"><?=lang('info_blurb')?></p>

			<h3><?=lang('xml_file_loc')?></h3>
			<p>
				<label for="xml_file"><?=lang('xml_file_loc')?></label> 
				<?=form_input(array('id'=>'xml_file','name'=>'xml_file', 'class'=>'field', 'value'=>set_value('xml_file')))?>
				<br /><?=form_error('xml_file')?>
			</p>

			<h3><?=lang('default_settings')?></h3>
			<p class="instructional_notice"><?=lang('default_settings_blurb')?></p>	
				
			<table class="mainTable" width="100%" border="0" cellspacing="0" cellpadding="0" summary="Default Options">
				<thead>
					<tr>
						<th width="50%"><?=lang('preference')?></th>
						<th width="50%"><?=lang('Options')?></th>
					</tr>
				</thead>
				<tbody>
				<tr>
					<td><strong><?=lang('default_group_id')?></strong></td>
					<td><?=form_dropdown('group_id', $member_groups, set_value('group_id'))?></td>
				</tr>
				<tr>
					<td><strong><?=lang('language')?></strong></td>
					<td><?=form_dropdown('language', $language_options, set_value('language'))?></td>
				</tr>
				<tr>
					<td><strong><?=lang('timezone')?></strong></td>
					<td class="timezoneSelect"><?=timezone_menu()?></td>
				</tr>
				<tr>
					<td><strong><?=lang('time_format')?></strong></td>
					<td><?=form_dropdown('time_format', array(lang('united_states'),lang('european')), set_value('time_format'))?></td>
				</tr>
				<tr>
					<td><strong><?=lang('daylight_savings')?></strong></td>
					<td><input type="checkbox" name="daylight_savings" value="y" <?php echo set_checkbox('daylight_savings', 'y', $dst_enabled); ?> />&nbsp;<?=lang('dst_enabled')?>	</td>
				</tr>
				<tr>
					<td><strong><?=lang('auto_custom_field')?></strong><br />
					<div class="subtext"><?=lang('auto_custom_field_blurb')?></div></td>
					<td><input type="checkbox" name="auto_custom_field" value="y" <?php echo set_checkbox('auto_custom_field', 'y'); ?> />&nbsp;<?=lang('auto_custom_field')?>	</td>
				</tr>
				</tbody>
			</table>

			<p class="centerSubmit"><?=form_submit('convert_from_delimited', lang('submit'), 'class="submit"')?></p> 

			<?=form_close()?>

			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file import_from_xml.php */
/* Location: ./themes/cp_themes/corporate/tools/import_from_xml.php */