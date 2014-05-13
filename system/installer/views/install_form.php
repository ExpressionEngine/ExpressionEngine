<?php
if ($errors != '')
{
	echo '<div class="shade">';
	echo '<h2 class="important">'.lang('error').'</h2>';
	echo '<h5 class="important">'.lang('error_occurred').'</h5>';
	echo $errors;
	echo '</div>';
}
else
{
	echo '<h2>'.lang('enter_settings').'</h2>';
	echo '<p class="important">'.lang('contact_host').'</p>';
}
?>

<form method='post' action='<?=$action?>' id="installForm">

<?php if (IS_CORE): ?>
	<input type="hidden" name="license_number" value="<?=$license_number?>" />
<?php else: ?>
	<div class="shade">
	<h2><?=lang('software_registration')?></h2>

	<h5><?=lang('license_contact')?></h5>
	<p><input type='text' name='license_contact' value='<?=$license_contact?>' size='40' /></p>
	<p><?=lang('license_contact_explanation')?></p>
	<h5><?=lang('license_number')?></h5>
	<p><input type='text' name='license_number' value='<?=$license_number?>' size='40' /></p>
	<p><?=lang('locate_license_number')?></p>
	</div>
<?php endif; ?>

<div class="shade">
<h2><?=lang('server_settings')?></h2>

<h5><?=lang('name_of_index')?></h5>
<p><?=lang('normally_index')?></p>
<p><input type='text' name='site_index' value='<?=$site_index?>' size='40' /></p>


<h5><?=lang('url_of_index')?></h5>
<p><?=lang('normally_root')?></p>
<p><em><?=lang('do_not_include_index')?></em></p>
<p><input type='text' name='site_url' value='<?=$site_url?>' size='70'  /></p>


<h5><?=lang('url_of_admin')?></h5>
<p><?=lang('url_of_admin_info')?></p>
<p><input type='text' name='cp_url' value='<?=$cp_url?>' size='70'  class='input'></p>


<h5><?=lang('webmaster_email')?></h5>
<p><input type='text' name='webmaster_email' id='webmaster_email' value='<?=$webmaster_email?>' size='40'  class='input'></p>


</div>



<div class="shade">
<h2><?=lang('database_settings')?></h2>

<?php if (count($databases) == 1):?>
	<?=form_hidden('dbdriver', key($databases))?>
<?php else:?>
	<h5><?=lang('database_type')?></h5>
	<p><?=form_dropdown('dbdriver', $databases, $dbdriver, 'class="select"')?></p>
<?php endif;?>

<h5><?=lang('sql_server_address')?></h5>
<p><?=lang('usually_localhost')?></p>
<p><input type='text' name='db_hostname' value='<?=$db_hostname?>' size='40' class='input' /></p>


<h5><?=lang('sql_username')?></h5>
<p><?=lang('sql_username_info')?></p>
<p><input type='text' name='db_username' value='<?=$db_username?>' size='40' class='input' /></p>


<h5><?=lang('sql_password')?></h5>
<p><?=lang('sql_password_info')?></p>
<p><input type='password' name='db_password' value='<?=$db_password?>' size='40' class='input' /></p>


<h5><?=lang('sql_dbname')?></h5>
<p><?=lang('sql_dbname_info')?></p>
<p><em><?=lang('sql_dbname_note')?></em></p>
<p><input type='text' name='db_name' value='<?=$db_name?>' size='40' class='input' /></p>


<h5><?=lang('sql_prefix')?></h5>
<p><?=lang('sql_prefix_info')?></p>
<p><input type='text' name='db_prefix' value='<?=$db_prefix?>' size='12'  maxlength='30' class='input' /></p>


<h5><?=lang('sql_conntype')?></h5>
<p>
<input type="radio" class='radio' name="db_conntype" value="nonpersistent" id="db_conntype_nonpersistent" <?=$nonpersistent?> /> <label for="db_conntype_nonpersistent"><?=lang('nonpersistent')?></label><br />
<input type="radio" class='radio' name="db_conntype" value="persistent" id="db_conntype_persistent" <?=$persistent?> /> <label for="db_conntype_persistent"><?=lang('persistent')?></label>
</p>

</div>




<div class="shade">
<h2><?=lang('create_account')?></h2>

<p><?=lang('admin_info')?></p>


<h5><?=lang('username')?></h5>
<p><?=lang('4_chars')?></p>
<p><input type='text' name='username' value='<?=$username?>' size='40' maxlength='50' class='input' /></p>

<script type="text/javascript" charset="utf-8">
	function confirm_password()
	{
		if (document.getElementById('password_confirm').value != document.getElementById('password').value)
		{
			str = '<p class="important"><?=lang('password_mismatch')?></p>';
		}
		else
		{
			str = '<p class="success"><?=lang('password_confirmed')?></p>';
		}

		document.getElementById('password_mismatch').innerHTML = str;
	}
</script>

<h5><?=lang('password')?></h5>
<p><?=lang('5_chars')?></p>
<p><input type='password' id='password' name='password' value='<?=$password?>' size='40' maxlength='40' class='input' /></p>

<h5><?=lang('password_confirm')?></h5>
<p><?=lang('pw_retype')?></p>
<p><input type='password' id='password_confirm' name='password_confirm' value='<?=$password_confirm?>' size='40' maxlength='40' class='input' onkeyup="confirm_password();return false;"/></p>

<div id="password_mismatch" class="pad"></div>

<h5><?=lang('email')?></h5>
<p><input type='text' id='email_address' name='email_address' value='<?=$email_address?>' size='40'  maxlength='72' class='input' /></p>


<h5><?=lang('screen_name')?></h5>
<p><?=lang('screen_name_info')?></p>
<p><input type='text' name='screen_name' value='<?=$screen_name?>' size='40' maxlength='50' class='input' /></p>



<h5><?=lang('site_label')?></h5>
<p><input type='text' name='site_label' value='<?=$site_label?>' size='40' class='input'></p>


</div>



<div class="shade">
<h2><?=lang('deft_template')?></h2>

<p><?=lang('site_theme_info')?></p>

<p>
<select name='theme' class='select' id="theme_select">
	<option value=''>None - Empty Installation</option>
<?php

	foreach ($themes as $key => $val)
	{
		$selected = ($theme == $key) ? " selected" : "";
		?><option value='<?=$key?>'<?=$selected?>><?=$val?></option><?php echo "\n";
	}
?>

</select>
</p>

</div>



<div class="shade">
<h2><?=lang('optional_modules')?></h2>
<p><?=lang('optional_modules_info')?></p>
<table>
<tr>
<?php unset($modules['rte'], $modules['ip_to_nation']) ?>
<?php $i = 0; foreach ($modules as $key => $name): ?>
<?php if ($i++ % 3 == 0):?></tr><tr><?php endif; ?>
<?php $checked = ($name['checked'] === TRUE) ? "checked='checked'" : ''; ?>
<td><input type='checkbox' name='modules[]' value='<?=$key?>' id='<?=$key?>' <?=$checked?>/> <label for='<?=$key?>'><?=$name['name']?><span class="req_module"> *</span></label></td>
<?php endforeach; ?>
</tr>
</table>
<p><?=lang('template_required_modules')?></p>

</div>



<div class="shade">
<h2><?=lang('local_settings')?></h2>


<h5><?=lang('timezone')?></h5>

<p><?=$this->localize->timezone_menu($default_site_timezone, 'default_site_timezone')?></p>

</div>





<p><?php echo form_submit('', lang('install_ee'), 'class="submit"'); ?></p>

<?php echo form_close();
/* End of file install_form.php */
/* Location: ./system/expressionengine/installer/views/install_form.php */