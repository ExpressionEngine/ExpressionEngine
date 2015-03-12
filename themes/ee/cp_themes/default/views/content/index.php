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

		<div class="contentMenu addonsContentMenu">

			<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>

			<ul>
				<li><a href="<?=BASE.AMP.'C=content_publish'?>"><?=lang('publish')?></a></li>
				<li><a href="<?=BASE.AMP.'C=content_edit'?>"><?=lang('edit')?></a></li>
				<li><a href="<?=BASE.AMP.'C=content_files'?>"><?=lang('file_manager')?></a></li>
			</ul>

		</div>

	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file index.php */
/* Location: ./themes/cp_themes/default/content/index.php */