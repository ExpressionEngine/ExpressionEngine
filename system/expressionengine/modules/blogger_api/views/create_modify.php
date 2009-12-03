
<h3><?=lang('configuration_options')?></h3>

<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blogger_api'.AMP.'method=save', '', $form_hidden)?>

<table class="mainTable solo">
	<tr>
		<td><?=form_label(lang('blogger_pref_name'), 'pref_name')?><?=form_error('pref_name')?></td>
		<td><?=form_input(array('id'=>'pref_name','name'=>'pref_name','class'=>'field','value'=>$pref_name))?></td>
	</tr>
	<tr>
		<td><?=form_label(lang('blogger_default_field'), 'field_id')?><br/> <?=lang('blogger_default_field_subtext')?>
			<?=form_error('field_id')?></td>
		<td><?=form_dropdown('field_id', $field_id_options, $field_id, 'id="field_id"')?></td>
	</tr>
	<tr>
		<td><?=form_label(lang('blogger_block_entry'), 'block_entry')?><br/> <?=lang('blogger_block_entry')?></td>
		<td><?=form_dropdown('block_entry', $block_entry_options, $block_entry, 'id="block_entry"')?></td>
	</tr>
	<tr>
		<td><?=form_label(lang('blogger_parse_type'), 'parse_type')?><br/> <?=lang('blogger_parse_type_subtext')?></td>
		<td><?=form_dropdown('parse_type', $parse_type_options, $parse_type, 'id="parse_type"')?></td>
	</tr>
	<tr>
		<td><?=form_label(lang('blogger_text_format'), 'text_format')?><br/> <?=lang('blogger_text_format_subtext')?><?=form_error('text_format')?></td>
		<td><?=form_dropdown('text_format', $text_format_options, $text_format, 'id="text_format"')?></td>
	</tr>
	<tr>
		<td><?=form_label(lang('blogger_html_format'), 'html_format')?><br/> <?=lang('blogger_html_format_subtext')?><?=form_error('html_format')?></td>
		<td><?=form_dropdown('html_format', $html_format_options, $html_format, 'id="html_format"')?></td>
	</tr>
</table>

<p>
	<?=form_submit(array('name' => 'submit', 'value' => lang($submit_text), 'class' => 'submit'))?>
</p>

<?=form_close()?>