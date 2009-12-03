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

			<div class="heading"><h2 class="edit"><?=lang('import_from_xml')?></h2></div>
			<div class="pageContents">
				
			<?php $this->load->view('_shared/message');?>

			<?=form_open('C=tools_utilities'.AMP.'M=confirm_xml_form')?>
	
			<p><?=lang('import_from_xml_blurb')?></p>		

			<h3><?=lang('import_info')?></h3>
			<p class="go_notice"><?=lang('info_blurb')?></p>

			<h3><?=lang('xml_file_loc')?></h3>
			<p>
				<label for="file_loc_blurb"><?=lang('xml_file_loc')?></label> 
				<?=form_input(array('id'=>'xml_file','name'=>'xml_file', 'class'=>'field', 'value'=>set_value('xml_file')))?>
				<?=form_error('xml_file')?>
			</p>

			<h3><?=lang('default_settings')?></h3>
			<p><?=lang('default_settings_blurb')?></p>		

			<p><label>
				<strong><?=lang('default_group_id')?></strong> 
					<?=form_dropdown('group_id', $member_groups, set_value('group_id'))?>
			</label></p>


			<p><label>
				<strong><?=lang('language')?></strong> 
					<?=form_dropdown('language', $language_options, set_value('language'))?>
			</label></p>
			<p><label>
				<strong><?=lang('timezone')?></strong> 
					<?=timezone_menu()?>
			</label></p>

			<p><label>
				<strong><?=lang('time_format')?></strong>			
				<?=form_dropdown('time_format', array(lang('united_states'),lang('european')), set_value('time_format'))?>
			</label></p>

			
			<p>
				<strong><?=lang('daylight_savings')?></strong><br />
				<input type="checkbox" name="daylight_savings" value="y" <?php echo set_checkbox('daylight_savings', 'y', $dst_enabled); ?> />		
				<label for="relationships"><?=lang('dst_enabled')?></label>
			</p>

			<p>
				<strong><?=lang('auto_custom_field')?></strong><br />
				<?=lang('auto_custom_field_blurb')?><br />
				<input type="checkbox" name="auto_custom_field" value="y" <?php echo set_checkbox('auto_custom_field', 'y'); ?> />		
				<label for="relationships"><?=lang('auto_custom_field')?></label>
			</p>

			<p><?=form_submit('convert_from_delimited', lang('submit'), 'class="submit"')?> </p>

			<?=form_close()?>

			</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file import_from_xml.php */
/* Location: ./themes/cp_themes/default/tools/import_from_xml.php */