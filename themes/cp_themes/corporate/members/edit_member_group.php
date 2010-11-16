<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent"<?=$maincontent_state?>>
	<?php $this->load->view('_shared/right_nav')?>
		<div class="contents">

		<div class="heading"><h2 class="edit"><?=lang('member_cfg')?> <?=lang('general_cfg')?></h2></div>
		<div class="pageContents">

			<?php $this->load->view('_shared/message');?>

			<p>
				<?php if ($group_id == 1):?>
					<?=lang('super_admin_edit_note')?>
				<?php else:?>
					<?=lang('warning').NBS.NBS.lang('be_careful_assigning_groups')?>
				<?php endif;?>
			</p>

			<?=form_open('C=members'.AMP.'M=update_member_group', array('id'=>'edit_member_group'), $form_hidden)?>
			<?php
				// setup table template
				$this->table->set_template($cp_pad_table_template);
				$this->table->template['thead_open'] = '<thead class="visualEscapism">';
			?>

			<?php if ($this->config->item('multiple_sites_enabled') == 'y'):?>
					<?=form_dropdown('site_list_pulldown', $sites_dropdown, '', 'id="site_list_pulldown"')?>
					<span id="site_loader" style="display:none;"><img src="<?=PATH_CP_GBL_IMG?>loader.gif" width="16" height="16" style="vertical-align:sub;" /></span>
			<?php endif;?>

						
			<div>
				<h3 class="accordion"><?=lang('group_name')?></h3>
				<div style="padding: 5px 1px;">
						<?php
							$this->table->set_heading(lang('preference'), lang('setting'));

							$preference = lang('group_name', 'group_title');
							$controls = form_input(array('id'=>'group_title','name'=>'group_title','class'=>'field', 'value'=>$group_title));
							$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));

							$preference = lang('group_description', 'group_description');
							$controls = form_textarea(array('id'=>'group_description','name'=>'group_description','cols'=>70,'rows'=>10,'class'=>'field', 'value'=>$group_description));
							$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));
							
							echo $this->table->generate();
							// Clear out of the next one
							$this->table->clear();
						?>
				</div>

				<?php if ($group_id != 1):?>
	
					<h3 class="accordion"><?=lang('security_lock')?></h3>
					<div style="padding: 5px 1px;">
						<?php
							$preference = '<p><strong class="notice">'.lang('enable_lock').'</strong><br />'.lang('lock_description').'</p>';
							$controls = lang('locked', 'is_locked_y').NBS.form_radio(array('name'=>'is_locked', 'id'=>'is_locked_y', 'value'=>'y', 'checked'=>($is_locked == 'y') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
							$controls .= lang('unlocked', 'is_locked_n').NBS.form_radio(array('name'=>'is_locked', 'id'=>'is_locked_n', 'value'=>'n', 'checked'=>($is_locked == 'n') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
				
							$this->table->set_heading(lang('preference'), lang('setting'));
							$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));
							echo $this->table->generate();
							// Clear out of the next one
							$this->table->clear();
						?>
					</div>
				
				<?php endif;?>
				
			</div>
			
				
				<?php
				// each site
				foreach($group_data as $key => $site):
				?>
			<div id="site_options_<?=$key?>" class="site_prefs">
					<?php
										
					foreach ($site as $prefname=>$prefs):
					?>

						<h3 class="accordion"><?=lang($prefname)?></h3>
						<div style="padding: 5px 1px;">
							<?php

								foreach ($prefs as $k=>$pref)
								{
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
								
									$this->table->set_heading(lang('preference'), lang('setting'));
									$this->table->add_row($preference, array('style'=> 'width:50%;', 'data'=>$controls));
								}
		
								echo $this->table->generate();
								// Clear out of the next one
								$this->table->clear();

							?>
						</div>

					<?php endforeach;?>
			
			</div>
			
			<?php 
				endforeach;
				
				// super admins don't need to see modules... they are super admins after all
				if ($group_id != 1):
			?>				
			
				<div id="modules">
							<h3 class="accordion"><?=lang('cp_module_access_privs')?></h3>
							<div style="padding: 5px 1px;">
							<?php					
								if ( ! empty($module_perms))
								{
									foreach($module_perms as $key => $pref)
									{
										$line = lang('can_access_mod').NBS.NBS.'<span class="notice">'.$module_names[$key].'</span>';

										$controls = lang('yes', $key.'_y').NBS.form_radio(array('name'=>$key, 'id'=>$key.'_y', 'value'=>'y', 'checked'=>($pref['options'] == 'y') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;
										$controls .= lang('no', $key.'_n').NBS.form_radio(array('name'=>$key, 'id'=>$key.'_n', 'value'=>'n', 'checked'=>($pref['options'] == 'n') ? TRUE : FALSE)).NBS.NBS.NBS.NBS.NBS;

										$this->table->set_heading(lang('preference'), lang('setting'));
										$this->table->add_row($line, array('style'=> 'width:50%;', 'data'=>$controls));
									}									
								}
								else
								{
									$this->table->add_row(lang('no_cp_modules_installed'));
								}

								echo $this->table->generate();
								// Clear out of the next one
								$this->table->clear();

							?>
							</div>
				</div>

			<?php 
				endif;
			?>

				<p>
					<?php if($action == 'submit'):?>
						<?=form_submit('submit', lang('submit'), 'class="submit"')?>
					<?php else:?>
						<?=form_submit('submit', lang('update'), 'class="submit"')?>
					<?php endif;?>
				</p>
			<div class="shun"></div>
			<?=form_close()?>
			</div>



		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file preferences.php */
/* Location: ./themes/cp_themes/default/members/preferences.php */