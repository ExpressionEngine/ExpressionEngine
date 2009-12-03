<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=category_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
	<?php $this->load->view('_shared/right_nav')?>
	<?php $this->load->view('_shared/message');?>

	<ul>
		<li><a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=edit_custom_category_field'.AMP.'group_id='.$group_id?>"><?=lang('create_new_custom_field')?></a></li>
	</ul>

	<?php
	if (count($custom_fields) > 0)
	{
		foreach ($custom_fields as $field):?>
			<div class="label">
				<?=$field['field_name']?><br />
				<?=$field['field_type']?>
			</div>
			<ul>
				<li><a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=edit_custom_category_field<?=AMP?>group_id=<?=$group_id.AMP?>field_id=<?=$field['field_id']?>"><?=$field['field_id'].' '.$field['field_label']?></a></li>
				<li>
					<a href="<?=BASE.AMP.'C=admin_content'.AMP.'M=delete_custom_category_field_confirm'.AMP.'group_id='.$group_id.AMP.'field_id='.$field['field_id']?>"><?=lang('delete');?></a>
				</li>
			</ul>
		<?php endforeach;
	}
	else
	{
		$this->table->add_row(array('data' => lang('no_field_groups'), 'colspan' => 4));
	}
	?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file category_custom_field_group_manager.php */
/* Location: ./themes/cp_themes/default/admin/category_custom_field_group_manager.php */