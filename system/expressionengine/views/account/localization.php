<?php extend_view('account/_wrapper') ?>

<div>
	<h3><?=lang('localization')?></h3>

	<?=form_open('C=myaccount'.AMP.'M=localization_update', '', $form_hidden)?>

	<p class="cf">
		<?=form_label(lang('timezone'), 'timezone')?>
		<span class="timezone">
			<?=$timezone?>
		</span>
	</p>

	<?php
	foreach ($fields as $name => $details)
	{
		$pref = '';

		switch ($details['type'])
		{
			case 's':
				$label = lang($name, $name);

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
				$label = form_label(lang($name));

				if (is_array($details['value']))
				{
					foreach ($details['value'] as $options)
					{
						$pref .= form_radio($options).NBS.lang($options['label']).NBS.NBS.NBS.NBS;
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

		echo "<p>" . $label . $pref . "</p>";
	}
	?>

	<p>
		<?=form_label(lang('language'), 'language')?>
		<?=form_dropdown('language', $language_options, $language, 'id="language"')?>
	</p>

	<p class="submit"><?=form_submit('localization_update', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>
</div>