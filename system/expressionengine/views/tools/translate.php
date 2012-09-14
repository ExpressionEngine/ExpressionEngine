<?php extend_template('default') ?>

<?php if ( ! $language_list): ?>
	<p class="notice"><?=lang('no_lang_keys')?></p>
<?php else: ?>	
	<?=form_open('C=tools_utilities'.AMP.'M=translation_save', '', $form_hidden)?>

		<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(
									array('data'=>lang('english'), 'class'=>'translatePhrase'),
									lang('translation')
								);
								
		foreach ($language_list as $label => $value)
		{
			$this->table->add_row(
				array('data' => form_label($value['original'], $label), 'style' => 'text-align:right;'),
				form_input(array('id' => $label,
					 'name' => $label,
					 'value' => $value['trans'],
					 'class'=>'field translate_field'))
			);
		}
			
		echo $this->table->generate();
		?>
		
		<p><?=form_submit('translate', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>
<?php endif; ?>