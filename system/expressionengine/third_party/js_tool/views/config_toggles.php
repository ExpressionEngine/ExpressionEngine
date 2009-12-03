<?php
echo form_open($config_url);

	$this->table->set_heading(lang('preference'), lang('setting'));

	$preference = lang('use_compressed');
	$controls = lang('yes', 'use_compressed_yes').NBS.form_radio(array('id'=>'use_compressed_yes','name'=>'use_compressed', 'value'=>'y', 'checked' => ($config_setting == 'y')));
	$controls .= NBS.NBS.NBS;
	$controls .= lang('no', 'use_compressed_no').NBS.form_radio(array('id'=>'use_compressed_no','name'=>'use_compressed', 'value'=>'n', 'checked' => ($config_setting == 'n')));
	$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

	echo $this->table->generate();
	$this->table->clear(); // Clear out for the next one
	
echo form_submit(array('name' => 'submit', 'id' => 'update_config', 'value' => lang('update'), 'class' => 'submit'));
	
echo form_close();
?>
