<?php
if ( ! $EE_view_disable)
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
			<h2 class="edit"><?= $title ?></h2>
		</div>
		<div class="pageContents group">
			<?=form_open('C=content_files'.AMP.'M=edit_file', '', $form_hiddens)?>
				<p>
					<?=lang('file_name', 'file_name')?>
					<span class="fake_input"><?=$file_name?></span>
				</p>
				<p>
					<?=lang('file_title', 'file_title')?>
					<?=form_input('file_title', $title, 'id="file_title"')?>
				</p>
				<p>
					<?=lang('caption', 'caption')?>
					<?=form_textarea('caption', $caption, 'id="caption"')?>
				</p>
				<p class="submit_button">
					<?=form_submit('save_file', lang('save_file'), 'id="save_file" class="submit"')?><br />
				</p>
			<?=form_close()?>
		</div>
	</div>
</div>

<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_browse.php */
/* Location: ./themes/cp_themes/default/tools/file_browse.php */