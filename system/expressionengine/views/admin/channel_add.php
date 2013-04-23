<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=channel_add', array('id'=>'channel_edit'))?>

	<table class="mainTable solo" border="0" cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th colspan="2"><?=lang('channel_prefs')?></th>
			</tr>
		</thead>
		<tr>
			<td style="width: 50%;">
				<?=required()?> <?=lang('channel_title', 'channel_title')?>
				<?=form_error('channel_title')?>
			</td>
			<td><?=form_input(array('id'=>'channel_title','name'=>'channel_title','class'=>'fullfield','value'=>set_value('channel_title')))?></td>
		</tr>
		<tr>
			<td>
				<?=required()?> <?=lang('channel_name', 'channel_name')?><br /><?=lang('single_word_no_spaces')?>
				<?=form_error('channel_name')?>
			</td>
			<td><?=form_input(array('id'=>'channel_name','name'=>'channel_name','class'=>'fullfield','value'=>set_value('channel_name')))?></td>
		</tr>
		<tr>
			<td><?=lang('duplicate_channel_prefs', 'duplicate_channel_prefs')?></td>
			<td><?=form_dropdown('duplicate_channel_prefs', $duplicate_channel_prefs_options, '', 'id="duplicate_channel_prefs"')?></td>
		</tr>
	</table>
	
	
	<table class="mainTable solo" id="edit_group_prefs" border="0" cellspacing="0" cellpadding="0">
		<thead>
			<tr>
				<th colspan="2"><?=lang('edit_group_prefs')?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td style="width: 50%;"><?=lang('category_group', 'cat_group')?></td>
				<td><?=form_dropdown('cat_group[]', $cat_group_options, '', 'id="cat_group" multiple="multiple"')?></td>
			</tr>
			<tr>
				<td><?=lang('status_group', 'status_group')?></td>
				<td><?=form_dropdown('status_group', $status_group_options, '', 'id="status_group"')?></td>
			</tr>
			<tr>
				<td><?=lang('field_group', 'field_group')?></td>
				<td><?=form_dropdown('field_group', $field_group_options, '', 'id="field_group"')?></td>
			</tr>
		</tbody>
	</table>

	<p>
		<?=form_submit(array('name' => 'channel_prefs_submit', 'value' => lang('submit'), 'class' => 'submit'))?>
	</p>
<?=form_close()?>
