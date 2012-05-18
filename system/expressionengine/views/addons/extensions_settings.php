<?php extend_template('default');?>

<?php
echo form_open('C=addons_extensions'.AMP.'M=save_extension_settings', '', $hidden);
	
$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
	array('data' => lang('preference'), 'style' => 'width:50%;'),
	lang('setting')
);

foreach ($fields as $name => $details)
{
	$pref = '';

	switch ($details['type'])
	{
		case 's':
			$label = lang($name);
			$pref = form_dropdown($name, $details['value'], $details['selected'], 'id="'.$name.'"');
			break;
		case 'ms':
			$label = lang($name);
			$pref = form_multiselect($name.'[]', $details['value'], $details['selected'], 'id="'.$name.'" size="8"');
			break;
		case 'r':
			$label = lang($name);
			foreach ($details['value'] as $options)
			{
				$pref .= form_radio($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
			}
			break;
		case 'c':
			$label = lang($name);
			foreach ($details['value'] as $options)
			{
				$pref .= form_checkbox($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
			}
			break;
		case 't':
			$label = lang($name, $name);
			$pref = form_textarea($details['value']);
			break;
		case 'f':
			$label = lang($name, $name);
			break;
		case 'i':
			$label = lang($name, $name);
			$pref = form_input(array_merge($details['value'], array('id' => $name, 'class' => 'input', 'size' => 20, 'maxlength' => 120, 'style' => 'width:100%')));
			break;
	}

	$this->table->add_row(
						"<strong>{$label}</strong>".(($details['subtext'] != '') ? "<div class='subtext'>{$details['subtext']}</div>" : ''),
						$pref
						);
}

echo $this->table->generate();
?>
<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
<?=form_close()?>
	
<div class="clear_right"></div>