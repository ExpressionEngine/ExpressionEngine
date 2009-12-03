<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=channel_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>
	

		<?php
		
			if ($field_groups->num_rows() > 0)
			{
				foreach($field_groups->result() as $field):?>
				<div class="label">
					<strong><?=lang('field_group')?></strong> <?=$field->group_id?> <?=$field->group_name?>
				</div>
				<ul>
					<li>(<?=$field->count?>) <a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=field_management'.AMP.'group_id='.$field->group_id?>"><?=lang('add_edit_fields')?></a></li>
					<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=field_group_edit'.AMP.'group_id='.$field->group_id?>"><?=lang('edit_field_group_name')?></a></li>
					<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=field_group_delete_confirm'.AMP.'group_id='.$field->group_id?>"><?=lang('delete_field_group')?></a></li>
				</ul>
				
				
				<?php endforeach;
			}
			else
			{
				?>
				<div class="container pad">
					<?=lang('no_field_group_message')?>
				</div>
				<?php
			}
		
		?>

		


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file field_group_management.php */
/* Location: ./themes/cp_themes/default/admin/field_group_management.php */