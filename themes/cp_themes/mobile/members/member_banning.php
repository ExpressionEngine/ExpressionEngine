<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>
	
	<?=form_open('C=members'.AMP.'M=update_banning_data')?>
	<ul>
		<li>
			<?=lang('ip_address_banning', 'ip_address_banning').'<br />'.
	        '<span class="notice">'.lang('ip_banning_instructions').'</span><br />'.
	        lang('ip_banning_instructions_cont')?><br />
	        <?=form_textarea(array(
	            'id'    => 'banned_ips',
	            'name'  => 'banned_ips',
	            'cols'  => 70,
	            'rows'  => 5, 
	            'class' => 'field', 
	            'value' => $banned_ips
	            )
	        );?>
		</li>
		<li>
			<?=lang('email_address_banning', 'email_address_banning').'<br />'.
	        '<span class="notice">'.lang('email_banning_instructions').'</span><br />'.
	        lang('email_banning_instructions_cont')?><br />
			<?=form_textarea(array(
	            'id'    => 'banned_emails',
	            'name'  => 'banned_emails',
	            'cols'  => 70,
	            'rows'  => 5, 
	            'class' => 'field', 
	            'value' =>$banned_emails
	            )
	        );?>
		</li>
		<li>
			<?=lang('username_banning', 'username_banning').'<br />'.
	        '<span class="notice">'.lang('username_banning_instructions').'</span>'?>
			<?=form_textarea(array(
	            'id'    => 'banned_usernames',
	            'name'  => 'banned_usernames',
	            'cols'  => 70,
	            'rows'  => 5, 
	            'class' => 'field', 
	            'value' => $banned_usernames
	            )
	        );?>
		</li>
		<li>
			<?=lang('screen_name_banning', 'screen_name_banning').'<br />'.
	        '<span class="notice">'.lang('screen_name_banning_instructions').'</span>';?>
			<?=form_textarea(array(
	            'id'    => 'banned_screen_names',
	            'name'  => 'banned_screen_names',
	            'cols'  => 70,
	            'rows'  => 5, 
	            'class' => 'field', 
	            'value' => $banned_screen_names
	            )
	        );?>
		</li>
	</ul>
	<h2><?=lang('ban_options')?></h2>
	<?php
	$ban_options = array(
	  'name'        => 'ban_action',
	  'id'          => 'restrict_to_viewing',
	  'value'       => 'restrict'
	);
	
	$ban_options['checked'] = ($ban_action == $ban_options['value']) ? TRUE : FALSE;

	$options = '<li>'.form_radio($ban_options).' '.lang('restrict_to_viewing', 'restrict_to_viewing').'</li>';

	// Show This Message
	$ban_options = array(
	  'name'        => 'ban_action',
	  'id'          => 'show_this_message',
	  'value'       => 'message'
	);

	$ban_options['checked'] = ($ban_action == $ban_options['value']) ? TRUE : FALSE;


	$options .= '<li>'.form_radio($ban_options).' '.lang('show_this_message', 'show_this_message').'<br />'.
	            form_input(array(
	                'id'    => 'ban_message',
	                'name'  => 'ban_message',
	                'class' => 'field',
	                'value' => $ban_message)
	                ).'</li>';

	// Send them to this site  :: buh bye ::
	$ban_options = array(
	  'name'        => 'ban_action',
	  'id'          => 'send_to_site',
	  'value'       => 'bounce'
	);

	$ban_options['checked'] = ($ban_action == $ban_options['value']) ? TRUE : FALSE;

	$options .= '<li>'.form_radio($ban_options).' '.lang('send_to_site', 'send_to_site').'<br />'.
	            form_input(array(
	                'id'    =>  'ban_destination',
	                'name'  =>  'ban_destination',
	                'class' =>  'field',
	                'value' =>  $ban_destination)
	            ).'</li>';	
	
	
	?>
	<ul>
			<?=lang('ban_options', 'ban_options')?>
			<?=$options?>
	</ul>	

	<?=form_submit('user_ban_sumbit', lang('update'), 'class="whiteButton"')?>

	<?=form_close()?>
</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file member_banning.php */
/* Location: ./themes/cp_themes/mobile/members/member_banning.php */	