<?php extend_template('default') ?>
		
<?=form_error('general_error')?>

<?=form_open($form_url, '', $form_hidden)?>

		<table id="entries" class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
		<tbody>
			<tr>
				<td width="40%">
					<?=required().lang('site_label', 'site_label')?>
				</td>
				<td>
					<?=form_input(array('id'=>'site_label','name'=>'site_label','class'=>'field','value'=>set_value('site_label', $values['site_label'])))?>
					<?=form_error('site_label')?>
				</td>
			</tr>
			<tr>
				<td>
					<?=required().lang('site_name', 'site_name')?>
				</td>
				<td>
					<?=form_input(array('id'=>'site_name','name'=>'site_name','class'=>'field','value'=>set_value('site_name', $values['site_name'])))?>
					<?=form_error('site_name')?>
				</td>
			</tr>
			<tr>
				<td>
					<?=lang('site_description', 'site_description')?>
				</td>
				<td>
					<?=form_input(array('id'=>'site_description','name'=>'site_description','class'=>'field','value'=> $values['site_description']))?>
				</td>
			</tr>
			
			<?php if($values['site_id'] == ''): ?>
			
			<tr>
				<th colspan="2"><strong><?=lang('move_data')?></strong></th>
			</tr>
			<tr>
				<td colspan="2" class="notice"><?=lang('timeout_warning')?></td>
			</tr>
			<tr>
				<th><?=lang('channels')?></th>
				<th><?=lang('move_options')?></th>
			</tr>
			
			<?php foreach($channels->result() as $channel):?>
			<tr>
				<td>
					<?=$sites[$channel->site_id]['site_label'].NBS.'-'.NBS.$channel->channel_title?>
				</td>
				<td>
					<?=form_dropdown('channel_'.$channel->channel_id, $channel_options)?><br />
				</td>
			</tr>
			<?php endforeach;?>
			
			<tr>
				<th><?=lang('file_upload_preferences')?></th>
				<th><?=lang('move_options')?></th>
			</tr>
			
			<?php foreach($upload_directories as $upload):?>
			<tr>
				<td>
					<?=$sites[$upload['site_id']]['site_label'].NBS.'-'.NBS.$upload['name']?>
				</td>
				<td>
					<?=form_dropdown('upload_'.$upload['id'], $upload_directory_options)?>
				</td>
			</tr>
			<?php endforeach;?>

			<tr>
				<th><?=lang('template_groups')?></th>
				<th><?=lang('move_options')?></th>
			</tr>
			
			<?php foreach($template_groups->result() as $group):?>
			<tr>
				<td>
					<?=$sites[$group->site_id]['site_label'].NBS.'-'.NBS.$group->group_name?>
				</td>
				<td>
					<?=form_dropdown('template_group_'.$group->group_id, $template_group_options)?>
				</td>
			</tr>
			<?php endforeach;?>

			<tr>
				<th><?=lang('design').NBS.'-'.NBS.lang('global_variables')?></th>
				<th><?=lang('move_options')?></th>
			</tr>
			
			<?php foreach($sites as $row):?>
			<tr>
				<td>
					<?=$row['site_label'].NBS.'-'.NBS.lang('global_variables')?>
				</td>
				<td>
					<?=form_dropdown('global_variables_'.$row['site_id'], $global_variable_options)?>
				</td>
			</tr>
			<?php endforeach;?>

			<?php endif; ?>
		</tbody>
		</table>

	<p><?=form_submit('site_edit_submit', lang('submit'), 'class="submit"')?></p>
	
<?=form_close()?>
