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

	<?=form_open('C=admin_content'.AMP.'M=update_category_group', '', $form_hidden)?>


	<div class="label">
		<?=lang('name_of_category_group', 'group_name')?>
	</div>
	<ul>
		<li><?=form_input(array('id'=>'group_name','name'=>'group_name','class'=>'field', 'value'=>$group_name))?></li>
	</ul>

	<div class="label">
		<?=lang('cat_field_html_formatting', 'field_html_formatting')?>
	</div>
	<ul>
		<li><?=form_dropdown('field_html_formatting', $formatting_options, $field_html_formatting)?></li>
	</ul>


	<div class="container pad">
		<strong><?=lang('can_edit_categories')?></strong>
		<?php if(count($can_edit_checks) == 0):?>
			<br /><span class="notice"><?=str_replace('%x', strtolower(lang('edit')), lang('no_member_groups_available'))?>
				<a href="<?=BASE.AMP.'C=members'.AMP.'M=member_group_manager'?>"><?=lang('member_groups')?></a></span>
		<?php else:?>

		<?php foreach($can_edit_checks as $check):?>
			<br /><label style="font-weight:normal;"><?=form_checkbox('can_edit_categories[]', $check['id'], $check['checked'])?> <?=$check['value']?></label>
		<?php endforeach;?>

	<?php endif;?>
	</div>

	<div class="container pad">
		<strong><?=lang('can_delete_categories')?></strong>
		<?php if(count($can_delete_checks) == 0):?>
			<br /><span class="notice"><?=str_replace('%x', strtolower(lang('delete')), lang('no_member_groups_available'))?>
				<a href="<?=BASE.AMP.'C=members'.AMP.'M=member_group_manager'?>"><?=lang('member_groups')?></a></span>
		<?php else:?>

			<?php foreach($can_delete_checks as $check):?>
				<br /><label style="font-weight:normal;"><?=form_checkbox('can_delete_categories[]', $check['id'], $check['checked'])?> <?=$check['value']?></label>
			<?php endforeach;?>
		<?php endif;?>
	</div>

	<p><?=form_submit('submit', lang('submit'), 'class="whiteButton"')?></p>

	<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file preference_delete_confirm.php */
/* Location: ./themes/cp_themes/default/admin/preference_delete_confirm.php */