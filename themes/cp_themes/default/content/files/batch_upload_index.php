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
			
				<?php if ($no_sync_needed):?>
					<h2><?=lang('no_sync_title')?></h2>
					<p><?=lang('no_file_sync_needed')?></p>
				<?php else: ?>
					<?=form_open('C=content_files'.AMP.'M=batch_upload')?>
					<p><?=lang('directory', 'upload_dirs')?><br>
						<?=form_dropdown('upload_dirs', $upload_dirs)?></p>
				
					<p><?=lang('status', 'status')?><br>
						<?=form_dropdown('status', $stati)?></p>
					
					<p><?=lang('allow_comments', 'allow_comments')?>&nbsp;&nbsp;
						<?=form_checkbox('allow_comments', 'allow_comments')?></p>
					
					<p><?=lang('categories', 'categories')?></p>
					<div id="file_cats" class="publish_field"></div>
					<div class="clear_left"></div>
					<p style="margin-top:25px"><?=form_submit('manual_batch', lang('manual_batch'), 'class="submit"')?>&nbsp;&nbsp;
												<?=form_submit('auto_batch', lang('auto_batch'), 'class="submit"')?></p>
					</form>
				<?php endif; ?>
			</div>
		
	</div> <!-- contents -->
</div> <!-- mainContent -->

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
?>

<?php
/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/default/content/files/batch_upload_index.php */