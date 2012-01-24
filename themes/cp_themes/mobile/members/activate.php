<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="view_members" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?php if ($message):?>
		<div class="container pad"><?=$message?></div>
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
		
		$options = array(
              'activate'  => lang('validate_selected'),
              'delete'    => lang('delete_selected')
            );

		echo '<p>'.form_dropdown('action', $options, 'activate').'</p>';
		
		echo '<p><label>'.form_checkbox(array('id'=>'send_notification','name'=>'send_notification','value'=>'y', 'checked'=>TRUE)). ' ' .lang('send_email_notification').'</label></p>';

		echo '<p>'.form_submit('activate', lang('submit'), 'class="submit"').'</p>';

		echo form_close();

			endif;
		
		?>


</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file activate.php */
/* Location: ./themes/cp_themes/mobile/members/activate.php */