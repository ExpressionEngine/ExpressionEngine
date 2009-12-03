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
		

		<?=form_open('C=admin_content'.AMP.'M=field_group_update', '', $form_hidden)?>

		<div class="label">
			<?=form_label(lang('field_group_name'), 'group_name')?>
		</div>
		<ul>
			<li><?=form_input(array('id'=>'group_name','name'=>'group_name','class'=>'field','value'=>$group_name))?></li>
		</ul>

		<?=form_submit('edit_field_group_name', lang($submit_lang_key), 'class="whiteButton"')?>

		<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file field_group_edit.php */
/* Location: ./themes/cp_themes/default/admin/field_group_edit.php */