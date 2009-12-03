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

		<?=form_open('C=admin_content'.AMP.'M=channel_update_group_assignments', '', $form_hidden)?>
		
		<div class="label">
			<?=form_label(lang('category_group'), 'category_group')?>
		</div>
		<ul class="rounded">
			<li><?=form_dropdown('cat_group[]', $cat_group_options, $cat_group, 'id="category_group" multiple="multiple"')?></li>
		</ul>
		
		<div class="label">
			<?=form_label(lang('status_group'), 'status_group')?>
		</div>
		<ul class="rounded">
			<li><?=form_dropdown('status_group', $status_group_options, $status_group, 'id="status_group"')?></li>
		</ul>
		
		<div class="label">
			<?=form_label(lang('field_group'), 'field_group')?>
		</div>
		<ul class="rounded">
			<li><?=form_dropdown('field_group', $field_group_options, $field_group, 'id="field_group"')?></li>
		</ul>
		<?=form_submit('channel_prefs_submit', lang('update'), 'class="whiteButton"')?>

		<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file channel_management.php */
/* Location: ./themes/cp_themes/default/admin/channel_management.php */