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
			<h2 class="edit"><?=lang('content_files')?></h2>
		</div>
		
		<div class="pageContents group">
			<?php if ( ! empty($upload_dirs_options)):?>
				<?=form_label('Upload Directory:', 'dir_choice').NBS?>
				<?=form_dropdown('dir_choice', $upload_dirs_options, $selected_dir, 'id="dir_choice"').NBS?>
				<?=NBS?><small>Total Directory Size: <?=number_format($dir_size/1000, 1);?> <?=lang('file_size_unit')?></small>
				<?=form_open_multipart('C=content_files'.AMP.'M=upload_file', array('id'=>'upload_form', 'class' => 'tableSubmit', ))?>
					<?=form_hidden('upload_dir', $selected_dir, array('id' => 'upload_dir'))?>
					<?=form_label(lang('upload_file'), 'upload_file', array('class' => 'visualEscapism'))?>
					<?=form_upload(array('id'=>'upload_file','name'=>'userfile','size'=>15,'class'=>'field'))?>
					<p id="progress"><img src="<?=$cp_theme_url?>images/indicator.gif" alt="<?=lang('loading')?>..." /><br /><?=lang('loading')?>...</p>
					<a href="#" class="upload_file submit">Upload File</a>
				<?=form_close()?>
			<?php endif; ?>
			<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th><?=lang('name')?></th>
						<th><?=lang('size')?></th>
						<th><?=lang('kind')?></th>
						<th><?=lang('date')?></th>
						<th><?=lang('actions')?></th>
						<th><?=form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')?></th>
					</tr>
				</thead>
				<tbody>
				<?php if ( ! isset($files) OR empty($files)):?>
					<tr>
						<td colspan="5"><?=lang('no_uploaded_files')?></td>
					</tr>
				<?php else: ?>
					<?php foreach ($files as $file):?>
					<tr>
						<td><a class="less_important_link" href="#"><?=$file['name']?></a></td>
						<td><?=number_format($file['size']/1000, 1);?> <?=lang('file_size_unit')?></td>
						<td><?=$file['mime']?></td>
						<td><?=$this->localize->set_human_time($file['date'], TRUE)?></td>
						<td>
							<a href="<?=BASE.AMP.'C=content_files'.AMP.'M=download_files'.AMP.'dir='.$selected_dir.AMP.'file='.base64_encode($file['name'])?>" title="<?=lang('file_download')?>"><img src="<?=$cp_theme_url?>images/icon-download-file.png"></a>
							&nbsp;&nbsp;<a href="<?=BASE.AMP.'C=content_files'.AMP.'M=delete_files'.AMP.'dir='.$selected_dir.AMP.'file='.base64_encode($file['name'])?>" title="<?=lang('file_download')?>"><img src="<?=$cp_theme_url?>images/icon-delete.png"></a>

							
						</td>
						<td class="file_select"><?=form_checkbox('file[]', $file['name'], FALSE, 'class="toggle"')?></td>
					</tr>
					<?php endforeach; ?>
				<?php endif;?>
				</tbody>
			</table>
			<p id="paginationCount"><?=$pagination_count_text;?></p>
			<?=$pagination_links?>
			<div class="tableSubmit">
				<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
				<?php if (count($action_options) > 0):?>
				<?=form_dropdown('action', $action_options).NBS.NBS?>
				<?php endif;?>
			</div>
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