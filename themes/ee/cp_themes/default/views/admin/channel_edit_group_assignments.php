<?php extend_template('default') ?>

<?=form_open('C=admin_content'.AMP.'M=channel_update_group_assignments', '', $form_hidden)?>
	<table id="entries" class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
	<thead>
		<tr>
			<th><?=lang('preference')?></th>
			<th><?=lang('value')?></th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td><?=form_label(lang('category_group'), 'category_group')?></td>
			<td><?=form_dropdown('cat_group[]', $cat_group_options, $cat_group, 'id="category_group" multiple="multiple"')?></td>
		</tr>
		<tr>
			<td><?=form_label(lang('status_group'), 'status_group')?></td>
			<td><?=form_dropdown('status_group', $status_group_options, $status_group, 'id="status_group"')?></td>
		</tr>
		<tr>
			<td><?=form_label(lang('field_group'), 'field_group')?></td>
			<td><?=form_dropdown('field_group', $field_group_options, $field_group, 'id="field_group"')?></td>
		</tr>
	</tbody>
	</table>

	<p><?=form_submit('channel_prefs_submit', lang('update'), 'class="submit"')?></p>
<?=form_close()?>