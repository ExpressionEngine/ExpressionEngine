<?=form_open($action_url, '', $form_hidden)?>

<?php

foreach ($email_template as $key => $val):

	$this->table->set_template($cp_pad_table_template);
	$this->table->set_heading(
	    array('data' => lang('preference'), 'style' => 'width:50%;'),
	    lang('setting')
	);


	$this->table->add_row(array(
			lang('email_name', 'email_name['.$key.']'),
			form_error('email_name['.$key.']').
			form_input('email_name['.$key.']', set_value('email_name['.$key.']', $val['email_name']))
		)
	);
	
	$this->table->add_row(array(
			lang('email_subject', 'email_subject['.$key.']'),
			form_error('email_subject['.$key.']').
			form_input('email_subject['.$key.']', set_value('email_subject['.$key.']', $val['email_subject']))
		)
	);
	
	$this->table->add_row(array(
			'colspan'		=> 2,
			'data'			=> lang('email_body', 'email_body['.$key.']').
			form_textarea(array('id'=>'email_body_'.$key, 'class'=>'module_textarea', 'name'=>'email_body['.$key.']', 'value'=>set_value('email_body['.$key.']', $val['email_body'])))
		)
	);
	
	$this->table->add_row(array(
			'colspan'	=> 2,
			'data'		=> lang('add_email_instructions')
		)
	);
	
	// Glossary
		$glossary = '<div class="glossary_content clear_left" id="directions_'.$key.'">'.$template_directions.'</div>';

	$this->table->add_row(array(
			'colspan'	=> 2,
			'data'		=> lang('email_instructions').
						   $glossary
		)
	);

	echo $this->table->generate();
	$this->table->clear();

?>
<div class='hidden'><?=form_hidden('email_id['.$key.']', $val['email_id'])?></div>

<?php endforeach; ?>

	<?=form_submit(array('name' => 'submit', 'value' => $type, 'class' => 'submit'))?>

<?=form_close()?>