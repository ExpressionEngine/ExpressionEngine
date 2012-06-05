<?php extend_template('default') ?>

<?php if (count($added_fields) > 0):?>
	<p class="notice"><?=lang('new_fields_success')?></p>
	<p><?=implode('<br />', $added_fields)?></p>
<?php endif;?>

<?=form_open($post_url, '', $form_hidden)?>

	<p><?=lang('confirm_details_blurb')?></p>		

	<?php
	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
							lang('option'),
							lang('value')
						);

	foreach ($data_display as $type => $value)
	{
		$this->table->add_row(
								lang($type),
								$value
							);
	}
	?>

	<?=$this->table->generate()?>
	
	
	<p><?=lang('member_id_warning')?></p>		

	<p><?=form_submit('import_from_xml', lang('import'), 'class="submit"')?></p>

<?=form_close()?>