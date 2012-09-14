<?php extend_template('basic') ?>

<div class="heading"><h2 class="edit">
	<?php if ($this->config->item('multiple_sites_enabled') == 'y'):?>
		<?=form_dropdown('sites_list_pulldown', $sites_dropdown, $site_id, 'id="site_list_pulldown"')?>
	<?php endif; ?>
	<?=$page_title?></h2></div>
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
			<span id="site_loader" style="display:none;"><img src="<?=PATH_CP_GBL_IMG?>loader.gif" width="16" height="16" style="vertical-align:sub;" /></span>
	<?php endif;?>

	<?php
		$this->table->set_caption(lang('group_name'));
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

	
	<?php
	// each site
	foreach($group_data as $key => $site):
	?>
	<div id="site_options_<?=$key?>" class="site_prefs">
		<?php foreach ($site as $prefname => $prefs): ?>
			<?php
				$this->table->set_caption(lang($prefname));
				$this->table->set_heading(lang('preference'), lang('setting'));

				foreach ($prefs as $pref)
				{
					$this->table->add_row(
						$pref['label'], 
						array(
							'style' => 'width:50%',
							'data' => $pref['controls']
						)
					);
				}

				echo $this->table->generate();
				// Clear out of the next one
				$this->table->clear();
			?>
		<?php endforeach; ?>
	</div>
	
	<?php 
		endforeach;
		
		// super admins don't need to see modules... they are super admins after all
		if ($group_id != 1):
	?>				
		<div id="modules">
			<?php
				$this->table->set_caption(lang('cp_module_access_privs'));
				$this->table->set_heading(lang('preference'), lang('setting'));

				if ( ! empty($module_data))
				{
					foreach($module_data as $module)
					{

						$this->table->add_row(
							$module['label'],
							array(
								'style' => 'width:50%;',
								'data' => $module['controls']
							)
						);
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