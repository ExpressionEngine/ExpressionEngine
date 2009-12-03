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

		<?=form_open($form_action, '', $form_hidden)?>

		<div class="container pad">
			<p><?=lang('category_order_confirm_text')?></p>
			<p class="notice"><?=lang('category_sort_warning')?></p>
		</div>
		<?=form_submit('submit', lang('update'), 'class="whiteButton"')?>

		<?=form_close()?>


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file category_order_confirm.php */
/* Location: ./themes/cp_themes/default/admin/category_order_confirm.php */