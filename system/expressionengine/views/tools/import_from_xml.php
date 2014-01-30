<?php extend_template('default') ?>

<?=form_open('C=tools_utilities'.AMP.'M=confirm_xml_form')?>
	<h4 style="margin-bottom:10px"><?=lang('import_from_xml_blurb')?></h4>
	<table cellspacing="0" cellpadding="0" border="0" class="mainTable padTable">
	<tbody>
		<tr class="even">
			<td colspan="2"><h3 style="margin:15px 0 0 0"><?=lang('import_info')?></h3></td>
		</tr>
		<tr class="odd">
			<td colspan="2"><?=lang('info_blurb')?></td>
		</tr>
		<tr class="even">
			<td width="50%">
				<?=lang('xml_file_loc', 'xml_file')?>
			</td>
			<td>
				<?=form_error('xml_file')?>
				<?=form_input(array('id'=>'xml_file','name'=>'xml_file', 'class'=>'field', 'value'=>set_value('xml_file')))?>
			</td>
		</tr>
		<tr class="odd">
			<td colspan="2">
				<strong><?=lang('default_settings')?></strong><br />
				<?=lang('default_settings_blurb')?>
			</td>
		</tr>
		<tr class="even">
			<td><?=lang('default_group_id', 'group_id')?></td>
			<td><?=form_dropdown('group_id', $member_groups, set_value('group_id'))?></td>
		</tr>
		<tr class="odd">
			<td><?=lang('language', 'language')?></td>
			<td><?=form_dropdown('language', $language_options, set_value('language'))?></td>
		</tr>
		<tr class="even">
			<td><?=lang('timezone', 'timezone')?></td>
			<td><?=$timezone_menu?></td>
		</tr>
		<tr class="odd">
			<td><?=lang('date_format', 'date_format')?></td>
			<td><?=form_preference('date_format', $date_format)?></td>
		</tr>
		<tr class="even">
			<td><?=lang('time_format', 'time_format')?></td>
			<td><?=form_preference('time_format', $time_format)?></td>
		</tr>
		<tr class="odd">
			<td><?=lang('include_seconds', 'include_seconds')?></td>
			<td><?=form_preference('include_seconds', $include_seconds)?></td>
		</tr>
		<tr class="even">
			<td><?=lang('auto_custom_field', 'auto_custom_field')?></td>
			<td><?=lang('auto_custom_field_blurb')?><br />
			<label for="auto_custom_field"><input type="checkbox" name="auto_custom_field" value="y" <?php echo set_checkbox('auto_custom_field', 'y'); ?> />
			<?=lang('auto_custom_field')?></label></td>
		</tr>
	</tbody>
	</table>

	<p><?=form_submit('convert_from_delimited', lang('submit'), 'class="submit"')?> </p>

<?=form_close()?>
