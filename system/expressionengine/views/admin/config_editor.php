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

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

		<?php $this->load->view('_shared/message');?>

		<p class="notice"><?=lang('advanced_users_only')?></p>

		<?=form_open ('C=admin_system'.AMP.'M=config_editor_process', '', $hidden)?>

		<table id="entries" class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
			<thead>
				<tr>
					<th style="cursor: default;"><?=lang('preference')?></th>
					<th style="cursor: default;"><?=lang('setting')?></th>
				</tr>
			</thead>
			<tbody>

		<?php foreach ($config_items as $config_item=>$config_value):?>
				<tr>

			<?php if ($config_value === TRUE):?>

					<td><strong><?='$config[\''.$config_item.'\']'?></strong></td>
					<td>
					<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'TRUE', 'checked'=>TRUE))?>
					<?=form_label(lang('yes'), $config_item.'_true')?>
					<?=repeater(NBS, 5)?>
					<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'FALSE'))?>
					<?=form_label(lang('no'), $config_item.'_false')?>
					</td>

			<?php elseif ($config_value === FALSE):?>

					<td><strong><?='$config[\''.$config_item.'\']'?></strong></td>
					<td>
					<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'TRUE'))?>
					<?=form_label(lang('yes'), $config_item.'_true')?>
					<?=repeater(NBS, 5)?>
					<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'FALSE', 'checked'=>TRUE))?>
					<?=form_label(lang('no'), $config_item.'_false')?>
					</td>

			<?php elseif ($config_value == 'y' OR $config_value == 'Y'):?>

					<td><strong><?='$config[\''.$config_item.'\']'?></strong></td>
					<td>
					<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'y', 'checked'=>TRUE))?>
					<?=form_label(lang('yes'), $config_item.'_true')?>
					<?=repeater(NBS, 5)?>
					<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'n'))?>
					<?=form_label(lang('no'), $config_item.'_false')?>
					</td>

			<?php elseif ($config_value == 'n' OR $config_value == 'N'):?>

					<td><strong><?='$config[\''.$config_item.'\']'?></strong></td>
					<td>
					<?=form_radio(array('id'=>$config_item.'_true','name'=>$config_item,'value'=>'y'))?>
					<?=form_label(lang('yes'), $config_item.'_true')?>
					<?=repeater(NBS, 5)?>
					<?=form_radio(array('id'=>$config_item.'_false','name'=>$config_item,'value'=>'n', 'checked'=>TRUE))?>
					<?=form_label(lang('no'), $config_item.'_false')?>
					</td>

			<?php else:?>

					<td><?=form_label('$config[\''.$config_item.'\']', $config_item)?></td>
					<td><?=form_input(array('id'=>$config_item,'name'=>$config_item,'style'=>'width:100%;','value'=>$config_value))?></td>

			<?php endif;?>

				</tr>
		<?php endforeach;?>

				<tr>
					<td><label>$config['<?=form_input('config_name')?>']</label></td>
					<td><label style="display:none;" for="config_setting"><?=lang('setting')?></label><?=form_input(array('name'=>'config_setting', 'id'=>'config_setting', 'style'=>'width:100%;'))?></td>
				</tr>
			</tbody>
		</table>

		<p><?=form_submit('update', lang('update'), 'class="submit"')?></p>

		<?=form_close()?>
		</div>


	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file config_editor.php */
/* Location: ./themes/cp_themes/default/admin_system/config_editor.php */