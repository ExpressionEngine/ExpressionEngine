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

			<div class="heading"><h2><?=$cp_page_title?></h2></div>

			<div class="pageContents">
				<?=form_open('C=design'.AMP.'M=template_delete'.AMP.'tgpref='.$group_id, '', $form_hidden)?>

				<p class="notice"><?=lang('delete_this_template')?></p>
			<ul class="subtext">
			<li>&lsquo;<?=$template_name?>&rsquo;</li>
				</ul>
				<?php if ($file !== FALSE): ?>
				<p><strong><?=lang('file_exists_warning')?></strong></p>
				<?php endif; ?>
		
				<p class="notice"><?=lang('action_can_not_be_undone')?></p>

				<p><?=form_submit(array('name' => 'submit', 'value' => lang('delete'), 'class' => 'delete'))?></p>
				
				<?=form_close()?>

			</div> <!-- pageContents -->
		</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file template_delete_confirm.php */
/* Location: ./themes/cp_themes/corporate/members/template_delete_confirm.php */