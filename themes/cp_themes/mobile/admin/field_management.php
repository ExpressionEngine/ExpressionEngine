<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=field_group_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>

		<?php
		
		if (count($custom_fields) > 0)
		{
			foreach ($custom_fields as $field): ?>
			<div class="label">
				<strong><?=lang('field_name')?>:</strong> <?=$field['field_name']?><br />
				<strong><?=lang('order')?>:</strong> <?=$field['field_order']?><br />
				<strong><?=lang('field_type')?>:</strong> <?=$field['field_type']?>
			</div>
			
			<ul>
				<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=field_edit'.AMP.'field_id='.$field['field_id'].AMP.'group_id='.$group_id?>"><?=$field['field_label']?></a></li>
				<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=field_delete_confirm'.AMP.'field_id='.$field['field_id']?>"><?=lang('delete')?></a></li>
			</ul>
			
			<?php endforeach;
		}
		else
		{ ?>
		<div class="container pad">
			<?=lang('no_field_groups')?>
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

/* End of file field_management.php */
/* Location: ./themes/cp_themes/default/admin/field_management.php */