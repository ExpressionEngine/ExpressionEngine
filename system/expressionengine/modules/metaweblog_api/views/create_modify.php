
<h3><?=lang('configuration_options')?></h3>

<?=form_open($action_url, '', $form_hidden)?>

<table class="mainTable solo" border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td style="width: 50%;"><?=lang('metaweblog_pref_name', 'metaweblog_pref_name')?></td>
		<td>
			<?=form_input(array('id'=>'metaweblog_pref_name','name'=>'metaweblog_pref_name','class'=>'field','value'=>set_value('metaweblog_pref_name', $pref_name)))?>
			<?=form_error('metaweblog_pref_name')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_parse_type', 'metaweblog_parse_type')?><br/> <?=lang('metaweblog_parse_type_subtext')?></td>
		<td>
			<?=form_dropdown('metaweblog_parse_type', $metaweblog_parse_type_options, set_value('metaweblog_parse_type', $metaweblog_parse_type_selected), 'id="metaweblog_parse_type"')?>
			<?=form_error('metaweblog_parse_type')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_entry_status', 'entry_status')?></td>
		<td>
			<?=form_dropdown('entry_status', $entry_status_options, set_value('entry_status', $entry_status), 'id="entry_status"')?>
			<?=form_error('entry_status')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_field_group', 'field_group_id')?></td>
		<td>
			<?=form_dropdown('field_group_id', $field_group_id_options, set_value('field_group_id', $field_group_id), 'id="field_group_id"')?>
			<?=form_error('field_group_id')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_excerpt_field', 'excerpt_field_id')?></td>
		<td>
			<?=form_dropdown('excerpt_field_id', $excerpt_field_id_options, set_value('excerpt_field_id', $excerpt_field_id), 'id="excerpt_field_id"')?>
			<?=form_error('excerpt_field_id')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_content_field', 'content_field_id')?></td>
		<td>
			<?=form_dropdown('content_field_id', $content_field_id_options, set_value('content_field_id', $content_field_id), 'id="content_field_id"')?>
			<?=form_error('content_field_id')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_more_field', 'more_field_id')?></td>
		<td>
			<?=form_dropdown('more_field_id', $more_field_id_options, set_value('more_field_id', $more_field_id), 'id="more_field_id"')?>
			<?=form_error('more_field_id')?>
		</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_keywords_field', 'keywords_field_id')?></td>
		<td>
		<?=form_dropdown('keywords_field_id', $keywords_field_id_options, set_value('keywords_field_id', $keywords_field_id), 'id="keywords_field_id"')?>
		<?=form_error('keywords_field_id')?>
	</td>
	</tr>
	<tr>
		<td><?=lang('metaweblog_upload_dir', 'upload_dir')?><br/> <?=lang('metaweblog_upload_dir_subtext')?></td>
		<td>
			<?=form_dropdown('upload_dir', $upload_dir_options, set_value('upload_dir', $upload_dir), 'id="upload_dir"')?>
			<?=form_error('upload_dir')?>
		</td>
	</tr>
</table>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang($submit_text), 'class' => 'submit'))?>
</p>

<?=form_close()?>

