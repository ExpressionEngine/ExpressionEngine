<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=members<?=AMP?>M=member_group_manager" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<div class="container pad">
		<?php if ($group_id == 1):?>
			<?=lang('super_admin_edit_note')?>
		<?php else:?>
			<?=lang('warning').NBS.NBS.lang('be_careful_assigning_groups')?>
		<?php endif;?>
	</div>

	<?=form_open('C=members'.AMP.'M=update_member_group', array('id'=>'edit_member_group'), $form_hidden)?>

	<?php if ($this->config->item('multiple_sites_enabled') == 'y'):?>
	<p>
		<?=form_dropdown('site_list_pulldown', $sites_dropdown, '', 'id="site_list_pulldown"')?>
	</p>
	<?php endif;?>

	<h3><?=lang('group_name')?></h3>
	<ul>
		<li>
			<?=form_input(array('id'=>'group_title','name'=>'group_title','class'=>'field', 'value'=>$group_title))?>
		</li>
		<li>
			<?=lang('group_description', 'group_description')?><br />
			<?=form_textarea(array('id'=>'group_description','name'=>'group_description','class'=>'field', 'width' => '100%', 'value'=>$group_description))?>
		</li>
	</ul>

	<?php if ($group_id != 1):?>
	<h3><?=lang('security_lock')?></h3>
		<ul>
			<li>
				<p><strong class="notice"><?=lang('enable_lock')?></strong><br />
					<?=lang('lock_description')?></p>
					<p>&nbsp;</p>
					<?php
						$controls = '<p>'.lang('locked', 'is_locked_y').NBS.form_radio(array('name'=>'is_locked', 'id'=>'is_locked_y', 'value'=>'y', 'checked'=>($is_locked == 'y') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
						$controls .= lang('unlocked', 'is_locked_n').NBS.form_radio(array('name'=>'is_locked', 'id'=>'is_locked_n', 'value'=>'n', 'checked'=>($is_locked == 'n') ? TRUE : FALSE)).'</p>';
					echo $controls;
					?>
			</li>
		</ul>
	<?php endif;?>



<?php
// each site
foreach($group_data as $key => $site): ?>
	<?php foreach ($site as $prefname=>$prefs): ?>
	<h3><?=lang($prefname)?></h3>
	<ul>
	<?php
	foreach ($prefs as $k=>$pref):?>
		<li>
		<?php
			// channels, modules and templates need their names translated
			$key = $k;
			$k = substr($k, strpos($key, '_') + 1);

			$line = '';

			if (substr($k, 0, 11) == 'channel_id_')
			{
				$line = lang('can_post_in').NBS.NBS.'<span class="notice">'.$channel_names[$k].'</span>';
			}

			if (substr($k, 0, 12) == 'template_id_')
			{
				$line = lang('can_access_tg').NBS.NBS.'<span class="notice">'.$template_names[$k].'</span>';
			}

			if ($line != '')
			{
				$preference = '<strong>'.$line.'</strong>';
			}
			elseif (in_array($k, $alert))
			{
				// Some preferences have serious implications if set to the affirmative.
				// This marks these as such.
				$preference = '<span class="notice">* <strong>'.lang($k, $k).'</strong></span>';
			}
			else
			{
				$preference = '<strong>'.lang($k, $k).'</strong>';
			}

			if (in_array($k, $textbox))
			{
				$controls = form_input($key, $pref, 'class="field"');
			}
			else
			{
				$controls = lang('yes', $k.'_y').NBS.form_radio(array('name'=>$key, 'id'=>$k.'_y', 'value'=>'y', 'checked'=>($pref['options'] == 'y') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
				$controls .= lang('no', $k.'_n').NBS.form_radio(array('name'=>$key, 'id'=>$k.'_n', 'value'=>'n', 'checked'=>($pref['options'] == 'n') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
			}

			echo $preference.'<br />';
			echo $controls;
			?>
		</li>
		<?php endforeach;?>
	</ul>
<?php endforeach;?>
<?php 
endforeach;

// super admins don't need to see modules... they are super admins after all
if ($group_id != 1):
?>				

<h3><?=lang('cp_module_access_privs')?></h3>
<ul>
<?php					
if ( ! empty($module_perms)): ?>

<?php foreach($module_perms as $key => $pref):?>
<?php
$line = lang('can_access_mod').NBS.NBS.'<span class="notice">'.$module_names[$key].'</span>';

$controls = lang('yes', $key.'_y').NBS.form_radio(array('name'=>$key, 'id'=>$key.'_y', 'value'=>'y', 'checked'=>($pref['options'] == 'y') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
$controls .= lang('no', $key.'_n').NBS.form_radio(array('name'=>$key, 'id'=>$key.'_n', 'value'=>'n', 'checked'=>($pref['options'] == 'n') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
?>
	<li>
		<?=$line?><br />
		<?=$controls?>
	</li>
<?php endforeach;
else:?>
	<li><?=lang('no_cp_modules_installed')?></li>
<?php endif; ?>
</ul>

<?php 
endif;
?>

<p>
<?php if($action == 'submit'):?>
<?=form_submit('submit', lang('submit'), 'class="whiteButton"')?>
<?php else:?>
<?=form_submit('submit', lang('update'), 'class="whiteButton"')?>
<?php endif;?>
</p>

<?=form_close()?>

</div>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file edit_member_group.php */
/* Location: ./themes/cp_themes/mobile/members/edit_member_group.php */