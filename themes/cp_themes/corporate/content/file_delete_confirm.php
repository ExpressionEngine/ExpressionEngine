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
	
		<div class="heading">
			<h2 class="edit"><?=$cp_page_title?></h2>
		</div>
		<div class="pageContents">

			<?=form_open('C=content_files'.AMP.'M=delete_files')?>

			<?php foreach($files as $file):?>
				<?=form_hidden('file[]', $file)?>
			<?php endforeach;?>

			<p class="notice"><?=lang($del_notice)?></p>

			<p class="notice"><?=lang('action_can_not_be_undone')?></p>

			<p><?=form_submit('delete_file', lang('delete'), 'class="submit"')?></p>

			<?=form_close()?>

		</div>
	</div><!-- contents -->
</div><!-- mainContent -->


<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_delete_confirm.php */
/* Location: ./themes/cp_themes/default/tools/file_delete_confirm.php */