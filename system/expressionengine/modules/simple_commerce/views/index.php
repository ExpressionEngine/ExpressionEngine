<h3><?=lang('simple_commerce_module_name')?></h3>
<?php

echo form_open($action_url);

$this->table->set_template($cp_pad_table_template);
$this->table->set_heading(
    array('data' => lang('preference'), 'style' => 'width:50%;'),
    lang('setting')
);

$this->table->add_row(array(
		'colspan' 	=> 2,
		'data'		=> lang('ipn_details')
	)
); 

$this->table->add_row(array(
		lang('ipn_url', 'sc_ipn_url'),
		form_input(array('id'=>'api_url', 'readonly'=>'readonly','class'=>'field','value'=>$api_url))
	)
);

$this->table->add_row(array(
		lang('paypal_account', 'sc_paypal_account'),
		form_input('sc_paypal_account', set_value('sc_paypal_account', $paypal_account)).
		form_error('sc_paypal_account')
	)
);

$this->table->add_row(array(
		lang('encrypt_buttons_links', 'encrypt_buttons_links'),
		'<span class="checks">'.
			form_radio('sc_encrypt_buttons', 'y', $encrypt_y).NBS.lang('yes').'<br />'.
			form_radio('sc_encrypt_buttons', 'n', $encrypt_n).NBS.lang('no').
		'</span>'		
	)
);

$this->table->add_row(array(
		lang('certificate_id', 'sc_certificate_id'),
		form_error('sc_certificate_id').
		form_input('sc_certificate_id', set_value('sc_certificate_id', $certificate_id))
	)
);

$this->table->add_row(array(
		lang('public_certificate', 'sc_public_certificate'),
		form_error('sc_public_certificate').
		form_input('sc_public_certificate', set_value('sc_public_certificate', $public_certificate))
	)
);

$this->table->add_row(array(
		lang('private_key', 'sc_private_key'),
		form_error('sc_private_key').
		form_input('sc_private_key', set_value('sc_private_key', $private_key))
	)
);

$this->table->add_row(array(
		lang('paypal_certificate', 'sc_paypal_certificate'),
		form_error('sc_paypal_certificate').
		form_input('sc_paypal_certificate', set_value('sc_paypal_certificate', $paypal_certificate))
	)
);

$this->table->add_row(array(
		lang('temp_path', 'sc_temp_path'),
		form_error('sc_temp_path').
		form_input('sc_temp_path', set_value('temp_path', $temp_path))
	)
);

echo $this->table->generate();

?>

	<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>

<?=form_close()?>
