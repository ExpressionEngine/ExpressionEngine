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
					<?=form_open()?>
					<p><?=lang('directory', 'upload_dirs')?><br>
						<?=form_dropdown('upload_dirs', $upload_dirs)?></p>
				
					<p><?=lang('status', 'status')?><br>
						<?=form_dropdown('status', $stati)?></p>
					<div id="file_cats"></div>
					<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
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

<script type="text/javascript">
$(document).ready(function () {
	$('select[name=upload_dirs]').change(function () {
		var val = $(this).val();
		
		$.ajax({
			url: EE.BASE + '&C=content_files&M=get_dir_cats',
			type: 'POST',
			dataType: 'html',
			data: {
				"XID": EE.XID,
				"upload_directory_id": val
			},
			success: function (res) {
				console.log('here');
				console.log(res)
				$('#file_cats').html(res);
			},
			error: function (res) {
				console.log('there');
				$('#file_cats').html('');
			}
		});
	});
});

</script>

<?php
/* End of file delete_confirm.php */
/* Location: ./themes/cp_themes/default/content/files/batch_upload_index.php */