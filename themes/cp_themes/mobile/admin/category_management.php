<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>

	<?php $this->load->view('_shared/right_nav')?>
	
	<?php
	if (count($categories) > 0)
	{
		foreach ($categories as $group):?>
		
		<div class="label">
			<strong><?=$group['group_id']?> <?=$group['group_name']?></strong> 
		</div>
		<ul class="rounded">
			<li>(<?=$group['category_count']?>) <a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=category_editor<?=AMP?>group_id=<?=$group['group_id']?>"><?= lang('add_edit_categories')?></a></li>
			<li><a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=edit_category_group<?=AMP?>group_id=<?=$group['group_id']?>"><?=lang('edit_category_group')?></a></li>
			<li>(<?=$group['custom_field_count']?>) <a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=category_custom_field_group_manager<?=AMP?>group_id=<?=$group['group_id']?>"><?= lang('manage_custom_fields')?></a></li>
			<li><a href="<?=BASE.AMP?>C=admin_content<?=AMP?>M=category_group_delete_conf<?=AMP?>group_id=<?=$group['group_id']?>"><?=lang('delete_group')?></a></li>
		</ul>
		<?php endforeach;
	}
	else
	{
		echo '<div class="container pad">'.lang('no_category_group_message').'</div>';
	}	
	?>
	
	
	
	
</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file category_management.php */
/* Location: ./themes/cp_themes/default/admin/category_management.php */