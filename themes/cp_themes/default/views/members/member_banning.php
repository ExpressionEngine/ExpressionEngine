<?php extend_template('default') ?>

<?=form_open('C=members'.AMP.'M=update_banning_data')?>

    <?php 
    $this->table->set_template($cp_pad_table_template);
    $this->table->set_heading(
        array('data' => lang('preference'), 'style' => 'width:50%;'),
		lang('setting')
    );
    
    // IP Banning
    $this->table->add_row(array(
            lang('ip_address_banning', 'ip_address_banning').'<br />'.
            '<span class="notice">'.lang('ip_banning_instructions').'</span><br />'.
            lang('ip_banning_instructions_cont'),
            form_textarea(array(
                'id'    => 'banned_ips',
                'name'  => 'banned_ips',
                'cols'  => 70,
                'rows'  => 5, 
                'class' => 'field', 
                'value' => $banned_ips
                )
            )
        )
    );
    
    // Email Banning  :: Splat ::
    $this->table->add_row(array(
            lang('email_address_banning', 'email_address_banning').'<br />'.
            '<span class="notice">'.lang('email_banning_instructions').'</span><br />'.
            lang('email_banning_instructions_cont'),
            form_textarea(array(
                'id'    => 'banned_emails',
                'name'  => 'banned_emails',
                'cols'  => 70,
                'rows'  => 5, 
                'class' => 'field', 
                'value' =>$banned_emails
                )
            )
        )
    );
    
    // Username Banning :: SMACK! ::
    $this->table->add_row(array(
            lang('username_banning', 'username_banning').'<br />'.
            '<span class="notice">'.lang('username_banning_instructions').'</span>',
            form_textarea(array(
                'id'    => 'banned_usernames',
                'name'  => 'banned_usernames',
                'cols'  => 70,
                'rows'  => 5, 
                'class' => 'field', 
                'value' => $banned_usernames
                )
            )
        )
    );
    
    // ScreenName banning :: Wack ::
    $this->table->add_row(array(
            lang('screen_name_banning', 'screen_name_banning').'<br />'.
            '<span class="notice">'.lang('screen_name_banning_instructions').'</span>',
            form_textarea(array(
                'id'    => 'banned_screen_names',
                'name'  => 'banned_screen_names',
                'cols'  => 70,
                'rows'  => 5, 
                'class' => 'field', 
                'value' => $banned_screen_names
                )
            )
        )
    );
    
    // Ban Options
    
    // Restrict to Viewing
    $ban_options = array(
	  'name'        => 'ban_action',
	  'id'          => 'restrict_to_viewing',
	  'value'       => 'restrict'
	);
	
	$ban_options['checked'] = ($ban_action == $ban_options['value']) ? TRUE : FALSE;
    
    $options = '<p>'.form_radio($ban_options).' '.lang('restrict_to_viewing', 'restrict_to_viewing').'</p>';
    
    // Show This Message
    $ban_options = array(
	  'name'        => 'ban_action',
	  'id'          => 'show_this_message',
	  'value'       => 'message'
	);
	
	$ban_options['checked'] = ($ban_action == $ban_options['value']) ? TRUE : FALSE;
    
    
    $options .= '<p>'.form_radio($ban_options).' '.lang('show_this_message', 'show_this_message').'<br />'.
                form_input(array(
                    'id'    => 'ban_message',
                    'name'  => 'ban_message',
                    'class' => 'field',
                    'value' => $ban_message,
                    'style' => 'margin-left:15px')
                    ).'</p>';
    
    // Send them to this site  :: buh bye ::
    $ban_options = array(
	  'name'        => 'ban_action',
	  'id'          => 'send_to_site',
	  'value'       => 'bounce'
	);
	
	$ban_options['checked'] = ($ban_action == $ban_options['value']) ? TRUE : FALSE;
    
    $options .= '<p>'.form_radio($ban_options).' '.lang('send_to_site', 'send_to_site').'<br />'.
                form_input(array(
                    'id'    =>  'ban_destination',
                    'name'  =>  'ban_destination',
                    'class' =>  'field',
                    'value' =>  $ban_destination,
                    'style' => 'margin-left:15px')
                );
    
    $this->table->add_row(array(
            lang('ban_options', 'ban_options'),
            $options
        )
    );
    
    echo $this->table->generate();
    ?>
	<?=form_submit('user_ban_sumbit', lang('update'), 'class="submit"')?>

<?=form_close()?>