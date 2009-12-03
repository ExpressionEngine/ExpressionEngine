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
	
		<div class="heading">
			<h2><?=$cp_page_title?></h2>
		</div>
		
		<div class="pageContents">
							
		<?=form_open('C=addons_accessories'.AMP.'M=update_prefs', '', array('accessory' => $name))?>
		<div style="width:48%; float: right;">
		
			<?php			
				$this->table->set_template($cp_table_template);
				$this->table->set_heading(
										array('data' => lang('member_group_assignment'), 'style' => 'width:95%;'),
										array('data' => form_checkbox('toggle_groups', 'true', FALSE, 'class="toggle_groups"'), 'style' => '5%;')
										);

				foreach ($member_groups as $id => $group)
				{
					$checked = in_array($id, $acc_member_groups);
					
					$this->table->add_row(
											form_label($group, 'group_'.$id, array('style'=>'display:block;')),
											form_checkbox('groups[]', $id, $checked, 'class="toggle_group" id="group_'.$id.'"')
										);
				}
				echo $this->table->generate();
				$this->table->clear();
			?>
			
			<p><?=form_submit('remove_plugins', lang('update'), 'class="submit"')?></p>
			
		</div>
		<div style="width: 48%;">
			<table id="controllers" class="mainTable" cellspacing="0" cellpadding="0" border="0">
				<thead>
					<tr>
						<th><?=lang('page_assignment')?></th>
						<th style="width:5%;"><?=form_checkbox('toggle_controllers', 'true', FALSE, 'class="toggle_controllers"')?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ($controllers as $controller):
				?>
					<tr class="<?=$controller['class']?>">
						<td class="controller_label"><?=form_label($controller['name'], 'controller_'.$controller['file'], array('style'=>'display:block; padding-left: 21px;'))?></td>
						<td><?=form_checkbox('controllers[]', $controller['file'], in_array($controller['file'], $acc_controllers), 'class="toggle_controller" id="controller_'.$controller['file'].'"')?>
					</tr>
				<?php endforeach;?>
				</tbody>
			</table>

		</div>
		<?=form_close()?>
		
		<div class="clear_left"></div>

	</div> <!-- page_contents -->
	</div> <!-- contents -->

</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/accessories/index.php */