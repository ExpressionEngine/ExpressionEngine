<?php extend_template('default') ?>

<?php if ($message):?>
	<p class="notice"><?=$message?></p>
<?php
else:
	$this->table->set_heading(
		'',
		form_checkbox(array('class'=>'toggle_all','id'=>'toggle_all', 'name'=>'toggle_all','value'=>'toggle_all','checked'=>FALSE)),
		lang('username'), 
		lang('screen_name'), 
		lang('email_address'), 
		lang('join_date')
	);

	foreach ($member_list->result() as $member)
	{
		$screen = ($member->screen_name == '') ? "--" : '<b>'.$member->screen_name.'</b>';
		
		$this->table->add_row(
							$member->member_id,
							form_checkbox(array('id'=>'delete_box_'.$member->member_id,'name'=>'toggle[]','class'=>'toggle','value'=>$member->member_id, 'checked'=>FALSE)),
							array('class' => 'username', 'data' => '<a href="'.BASE.AMP.'C=myaccount'.AMP.'id='. $member->member_id .'">'.$member->username.'</a>'),
							array('class' => 'screen_name', 'data' => $screen),
							mailto($member->email, $member->email),
							date("Y-m-d", $member->join_date)
						);					
	}

	echo form_open('C=members'.AMP.'M=validate_members');
	
	echo $this->table->generate();
	
	echo '<p>'.form_dropdown('action', $options, 'activate').'</p>';
	
	echo '<p><label>'.form_checkbox(array('id'=>'send_notification','name'=>'send_notification','value'=>'y', 'checked'=>TRUE)). ' ' .lang('send_email_notification').'</label></p>';

	echo '<p>'.form_submit('activate', lang('submit'), 'class="submit"').'</p>';

	echo form_close();

	endif;

?>