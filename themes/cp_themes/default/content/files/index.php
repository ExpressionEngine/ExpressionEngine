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
		
		<div id="file_manager" class="pageContents group">
			
			<?php if ($no_upload_dirs):?>
				<?php if ($this->cp->allowed_group('can_access_content_prefs')): ?>
				<?php echo sprintf(
								lang('no_upload_dirs_create_new'),
								BASE.AMP.'C=admin_content'.AMP.'M=edit_upload_preferences')?>
								
				<?php else: ?>
					<?=lang('no_upload_dirs')?>
				<?php endif; ?>
			<?php else: ?>
			<div id="filterMenu">
				<?php if ( ! empty($upload_dirs_options)):?>
					<?=form_open('', array('id' => 'dir_choice_form'))?>
						<?=form_label('Upload Directory:', 'dir_choice').NBS?>
						<?=form_dropdown('dir_choice', $upload_dirs_options, $selected_dir, 'id="dir_choice"').NBS?>
						<small><?=lang('total_dir_size')?> <?=number_format($dir_size/1000, 1);?> <?=lang('file_size_unit')?></small>
					<?=form_close()?>
					<?=form_open_multipart('C=content_files'.AMP.'M=upload_file', array('id'=>'upload_form', 'class' => 'tableSubmit', ))?>
						<?=form_hidden('upload_dir', $selected_dir, array('id' => 'upload_dir'))?>
						<?=form_label(lang('upload_file'), 'upload_file', array('class' => 'visualEscapism'))?>
						<?=form_upload(array('id'=>'upload_file','name'=>'userfile','size'=>15,'class'=>'field'))?>
						&nbsp;&nbsp;<input type="submit" class="submit" value="<?=lang('upload_file')?>">
					<?=form_close()?>
				<?php endif; ?>
				<div class="clear_left"></div>
			</div>
			
			<?=form_open('C=content_files'.AMP.'M=multi_edit_form')?>
				<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
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
							<td colspan="6"><?=lang('no_uploaded_files')?></td>
						</tr>
					<?php else: ?>
						<?php foreach ($files as $file):?>
						<tr class="<?=alternator('even', 'odd')?>">
							<?php if ($file['is_image']):?>
								<td class="overlay" id="<?=$file['name']?>"><?=$file['link']?></td>
							<?php else: ?>
								<td><?=$file['name']?></td>
							<?php endif; ?>
							<td><?=number_format($file['size']/1000, 1);?> <?=lang('file_size_unit')?></td>
							<td><?=$file['mime']?></td>
							<td><?=$this->localize->set_human_time($file['date'], TRUE)?></td>
							<td>
								<a href="<?=BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'upload_dir='.$selected_dir.AMP.'file='.$file['name'].AMP?>action=download" title="<?=lang('file_download')?>"><img src="<?=$cp_theme_url?>images/icon-download-file.png"></a>
								&nbsp;&nbsp;<a href="<?=BASE.AMP.'C=content_files'.AMP.'M=multi_edit_form'.AMP.'upload_dir='.$selected_dir.AMP.'file='.$file['name'].AMP?>action=delete" title="<?=lang('delete_selected_files')?>"><img src="<?=$cp_theme_url?>images/icon-delete.png"></a>
								<?php if ($file['is_image']):?>
								&nbsp;&nbsp;<a href="<?=BASE.AMP.'C=content_files'.AMP.'M=edit_image'.AMP.'upload_dir='.$selected_dir.AMP.'file='.urlencode($file['name'])?>" title="<?=$file['name']?>"><img src="<?=$cp_theme_url?>images/icon-edit.png" alt="<?=lang('delete')?>" /></a>
								<?php endif; ?>
							</td>
							<td class="file_select"><?=form_checkbox('file[]', urlencode($file['name']), FALSE, 'class="toggle"')?></td>
						</tr>
						<?php endforeach; ?>
					<?php endif;?>
					</tbody>
				</table>
				<div class="tableSubmit">
					<?=form_hidden('upload_dir', $selected_dir)?>
					<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
					<?php if (count($action_options) > 0):?>
					<?=form_dropdown('action', $action_options).NBS.NBS?>
					<?php endif;?>
				</div>
				
				<p id="paginationCount"><?=$pagination_count_text;?></p>
				<?=$pagination_links?>
			<?=form_close()?>
			<?php endif;?>
		</div>
	</div>
</div>

<div class="image_overlay" id="overlay" style="display:none"><a class="close"></a>
	<div class="contentWrap"></div>
</div>
<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file file_browse.php */
/* Location: ./themes/cp_themes/default/tools/file_browse.php */