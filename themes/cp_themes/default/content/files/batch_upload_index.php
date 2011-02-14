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
				<?=form_open()?>
				<p><?=lang('directory', 'upload_dirs')?><br>
					<?=form_dropdown('upload_dirs', $upload_dirs)?></p>
				
				<p><?=lang('status', 'status')?><br>
					<?=form_dropdown('status', $stati)?></p>
				
				<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
				</form>
			</div>
		
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/default/content/files/batch_upload_index.php */