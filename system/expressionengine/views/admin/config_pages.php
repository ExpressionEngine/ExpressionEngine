<?php extend_template('default') ?>

<?=form_open($form_action)?>
	<?php if (isset($return_loc)):
		echo form_hidden(array('return_location' => $return_loc));
	endif; ?>
	<?php
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
				
				if (is_array($details['value']))
				{
					$pref = form_dropdown($name, $details['value'], $details['selected'], 'id="'.$name.'"');
				}
				else
				{
					$pref = '<span class="notice">'.lang('not_available').'</span>';
				}
				
				break;
			case 'r':
				$label = lang($name);
				
				if (is_array($details['value']))
				{
					foreach ($details['value'] as $options)
					{
						$pref .= form_radio($options).NBS.lang($options['label'], $options['id']).NBS.NBS.NBS.NBS;
					}
				}
				else
				{
					$pref = '<span class="notice">'.lang('not_available').'</span>';
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
				
				$extra = ($name == 'license_number' && IS_CORE) ? array('value' => 'CORE LICENSE', 'disabled' => 'disabled') : array();
				$pref = form_input(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120), $extra));
				break;
			case 'p':
				$label = lang($name, $name);
				
				$pref = form_password(array_merge($details['value'], array('id' => $name, 'class' => 'input fullfield', 'size' => 20, 'maxlength' => 120)));
				break;
			case 'c':
				$label = lang($name, $name);
				$pref = $details['value'];
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
