<?php extend_template('default') ?>

<?=form_open($form_action, '', $form_hidden)?>

	<?php
		$this->table->set_heading(
			lang('formatting_options'),
			''
		);

		foreach($format_options as $key => $val)
		{
			$this->table->add_row(
				$val['name'],
				lang('yes').NBS.form_radio($key, 'y', (($val['selected'] == 'y') ? TRUE :  FALSE)).NBS.NBS.lang('no').NBS.form_radio($key, 'n', (($val['selected'] == 'n') ? TRUE : FALSE))
			);
		}

	?>
	<?=$this->table->generate()?>

	<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>

<?=form_close()?>