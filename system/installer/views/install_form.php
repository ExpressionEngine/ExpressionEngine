<?php 
if ($errors != '')
{
	echo '<div class="shade">';
	echo '<h2 class="important">'.$this->lang->line('error').'</h2>';
	echo '<h5 class="important">'.$this->lang->line('error_occurred').'</h5>';
	echo $errors;
	echo '</div>';
}
else
{
	echo '<h2>'.$this->lang->line('enter_settings').'</h2>';
	echo '<p class="important">'.$this->lang->line('contact_host').'</p>';
}
?>

<form method='post' action='<?php echo $action; ?>' id="installForm">

<div class="shade">
<h5><?php echo $this->lang->line('license_number'); ?></h5>
<p><input type='text' name='license_number' value='<?php echo $license_number; ?>' size='40' <?php if (IS_CORE) { echo 'disabled="disabled"';} ?>/></p>
<p><?php echo $this->lang->line('locate_license_number'); ?></p>
</div>

<div class="shade">
<h2><?php echo $this->lang->line('server_settings'); ?></h2>

<h5><?php echo $this->lang->line('name_of_index'); ?></h5>
<p><?php echo $this->lang->line('normally_index'); ?></p>
<p><input type='text' name='site_index' value='<?php echo $site_index; ?>' size='40' /></p>


<h5><?php echo $this->lang->line('url_of_index'); ?></h5>
<p><?php echo $this->lang->line('normally_root'); ?></p>
<p><em><?php echo $this->lang->line('do_not_include_index'); ?></em></p>
<p><input type='text' name='site_url' value='<?php echo $site_url; ?>' size='70'  /></p>


<h5><?php echo $this->lang->line('url_of_admin'); ?></h5>
<p><?php echo $this->lang->line('url_of_admin_info'); ?></p>
<p><input type='text' name='cp_url' value='<?php echo $cp_url; ?>' size='70'  class='input'></p>


<h5><?php echo $this->lang->line('webmaster_email'); ?></h5>
<p><input type='text' name='webmaster_email' id='webmaster_email' value='<?php echo $webmaster_email; ?>' size='40'  class='input'></p>


</div>



<div class="shade">
<h2><?php echo $this->lang->line('database_settings'); ?></h2>

<?php if (count($databases) == 1):?>
	<?=form_hidden('dbdriver', key($databases))?>
<?php else:?>
	<h5><?=$this->lang->line('database_type')?></h5>
	<p><?=form_dropdown('dbdriver', $databases, $dbdriver, 'class="select"')?></p>
<?php endif;?>

<h5><?php echo $this->lang->line('sql_server_address'); ?></h5>
<p><?php echo $this->lang->line('usually_localhost'); ?></p>
<p><input type='text' name='db_hostname' value='<?php echo $db_hostname; ?>' size='40' class='input' /></p>


<h5><?php echo $this->lang->line('sql_username'); ?></h5>
<p><?php echo $this->lang->line('sql_username_info'); ?></p>
<p><input type='text' name='db_username' value='<?php echo $db_username; ?>' size='40' class='input' /></p>


<h5><?php echo $this->lang->line('sql_password'); ?></h5>
<p><?php echo $this->lang->line('sql_password_info'); ?></p>
<p><input type='password' name='db_password' value='<?php echo $db_password; ?>' size='40' class='input' /></p>


<h5><?php echo $this->lang->line('sql_dbname'); ?></h5>
<p><?php echo $this->lang->line('sql_dbname_info'); ?></p>
<p><em><?php echo $this->lang->line('sql_dbname_note'); ?></em></p>
<p><input type='text' name='db_name' value='<?php echo $db_name; ?>' size='40' class='input' /></p>


<h5><?php echo $this->lang->line('sql_prefix'); ?></h5>
<p><?php echo $this->lang->line('sql_prefix_info'); ?></p>
<p><input type='text' name='db_prefix' value='<?php echo $db_prefix; ?>' size='12'  maxlength='30' class='input' /></p>


<h5><?php echo $this->lang->line('sql_conntype'); ?></h5>
<p>
<input type="radio" class='radio' name="db_conntype" value="nonpersistent" id="db_conntype_nonpersistent" <?php echo $nonpersistent; ?> /> <label for="db_conntype_nonpersistent"><?php echo $this->lang->line('nonpersistent'); ?></label><br />
<input type="radio" class='radio' name="db_conntype" value="persistent" id="db_conntype_persistent" <?php echo $persistent; ?> /> <label for="db_conntype_persistent"><?php echo $this->lang->line('persistent'); ?></label>
</p>

</div>




<div class="shade">
<h2><?php echo $this->lang->line('create_account'); ?></h2>

<p><?php echo $this->lang->line('admin_info'); ?></p>


<h5><?php echo $this->lang->line('username'); ?></h5>
<p><?php echo $this->lang->line('4_chars'); ?></p>
<p><input type='text' name='username' value='<?php echo $username; ?>' size='40' maxlength='50' class='input' /></p>

<script type="text/javascript" charset="utf-8">
	function confirm_password()
	{
		if (document.getElementById('password_confirm').value != document.getElementById('password').value)
		{
			str = '<p class="important"><?php echo $this->lang->line('password_mismatch'); ?></p>';
		}
		else
		{
			str = '<p class="success"><?php echo $this->lang->line('password_confirmed'); ?></p>';
		}

		document.getElementById('password_mismatch').innerHTML = str;
	}
</script>

<h5><?php echo $this->lang->line('password'); ?></h5>
<p><?php echo $this->lang->line('5_chars'); ?></p>
<p><input type='password' id='password' name='password' value='<?php echo $password; ?>' size='40' maxlength='40' class='input' /></p>

<h5><?php echo $this->lang->line('password_confirm'); ?></h5>
<p><?php echo $this->lang->line('pw_retype'); ?></p>
<p><input type='password' id='password_confirm' name='password_confirm' value='<?php echo $password_confirm; ?>' size='40' maxlength='40' class='input' onkeyup="confirm_password();return false;"/></p>

<div id="password_mismatch" class="pad"></div>

<h5><?php echo $this->lang->line('email'); ?></h5>
<p><input type='text' id='email_address' name='email_address' value='<?php echo $email_address; ?>' size='40'  maxlength='72' class='input' /></p>


<h5><?php echo $this->lang->line('screen_name'); ?></h5>
<p><?php echo $this->lang->line('screen_name_info'); ?></p>
<p><input type='text' name='screen_name' value='<?php echo $screen_name; ?>' size='40' maxlength='50' class='input' /></p>



<h5><?php echo $this->lang->line('site_label'); ?></h5>
<p><input type='text' name='site_label' value='<?php echo $site_label; ?>' size='40' class='input'></p>


</div>



<div class="shade">
<h2><?php echo $this->lang->line('deft_template'); ?></h2>

<p><?php echo $this->lang->line('site_theme_info')?></p>

<p>
<select name='theme' class='select' id="theme_select">
	<option value=''>None - Empty Installation</option>
<?php

	foreach ($themes as $key => $val)
	{
		$selected = ($theme == $key) ? " selected" : "";
		?><option value='<?php echo $key;?>'<?php echo $selected; ?>><?php echo $val; ?></option><?php echo "\n";
	}
?>

</select>
</p>

</div>



<div class="shade">
<h2><?php echo $this->lang->line('optional_modules'); ?></h2>
<p><?php echo $this->lang->line('optional_modules_info'); ?></p>
<table>
<tr>
<?php unset($modules['rte'], $modules['ip_to_nation']) ?>
<?php $i = 0; foreach ($modules as $key => $name): ?>
<?php if ($i++ % 3 == 0):?></tr><tr><?php endif; ?>
<?php $checked = ($name['checked'] === TRUE) ? "checked='checked'" : ''; ?>
<td><input type='checkbox' name='modules[]' value='<?php echo $key; ?>' id='<?php echo $key; ?>' <?php echo $checked; ?>/> <label for='<?php echo $key; ?>'><?php echo $name['name']; ?><span class="req_module"> *</span></label></td>
<?php endforeach; ?>
</tr>
</table>
<p><?php echo $this->lang->line('template_required_modules'); ?></p>

</div>



<div class="shade">
<h2><?php echo $this->lang->line('local_settings'); ?></h2>


<h5><?php echo $this->lang->line('timezone'); ?></h5>

<p><?=timezone_menu($server_timezone, 'select', 'server_timezone')?></p>

<p><?php echo $this->lang->line('dst_active'); ?></p>
<input  class='radio' type="radio" name="daylight_savings" id="daylight_savings_y" value="y" <?php echo $dst1; ?> /> <label for='daylight_savings_y'><?php echo $this->lang->line('yes'); ?></label> &nbsp;&nbsp;
<input type="radio"  class='radio' name="daylight_savings" id="daylight_savings_n" value="n" <?php echo $dst2; ?> /> <label for='daylight_savings_n'><?php echo $this->lang->line('no'); ?></label>
</p>

</div>





<p><?php echo form_submit('', $this->lang->line('install_ee'), 'class="submit"'); ?></p>

<?php echo form_close(); 
/* End of file install_form.php */
/* Location: ./system/expressionengine/installer/views/install_form.php */