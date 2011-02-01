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
		
		<div id="file_manager">

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
					&nbsp; &nbsp;<input type="submit" class="submit" value="<?=lang('upload_file')?>">
				<?=form_close()?>
			<?php endif; ?>
			</div>  
			
			<div class="clear"></div>

			
			<?=form_open('C=content_files'.AMP.'M=multi_edit_form')?>
			<?php 
					$this->table->set_template($cp_pad_table_template);
			
					$this->table->set_heading(
						array('data' => lang('file_name')),
						array('data' => lang('file_size')),
						array('data' => lang('kind')),
						array('data' => lang('date')),
						array('data' => lang('actions')),
						array('data' => form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"'), 'style' => 'width:2%', 'class' => 'file_select')
						);					
					
					if ( ! isset($files) OR empty($files)):?>
						<tr>
							<td colspan="5"><?=lang('no_uploaded_files')?></td>
						</tr>
					<?php else: ?>
						<?php
						// Create a row for each file
						foreach ($files as $file)
						{
							$this->table->add_row($file);
						}
						
						echo $this->table->generate();
						?>
					<?php endif;?>
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