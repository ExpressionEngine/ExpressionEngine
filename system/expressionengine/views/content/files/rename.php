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

				<p><?=lang('file_exists')?></p>
				<p><?=lang('overwrite_instructions')?></p>
				<?=form_open('C=content_files'.AMP.'M=rename_file', array('id'=>'rename_file_form'), $hidden)?>		
					<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
						<tr>
							<td><?=lang('file_name', 'file_name')?></td>
							<td><?=form_input('file_name', $duped_name)?></td>
						</tr>
					</table>
					<p class="submit_button">
						<?=form_submit('save_image', lang('save_image'), 'class="submit" id="rename_file_submit"')?>
					</p>
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

/* End of file rename.php */
/* Location: ./themes/cp_themes/default/content/rename.php */