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

			<?=form_open($form_action, '', $form_hidden)?>

			<p><?=lang('category_order_confirm_text')?></p>

			<p class="notice"><?=lang('category_sort_warning')?></p>

	<p><?=form_submit('submit', lang('update'), 'class="submit"')?></p>

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

/* End of file category_order_confirm.php */
/* Location: ./themes/cp_themes/default/admin/category_order_confirm.php */