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
			<h2 class="edit">
				<?=lang('content_files')?>
				<?php $this->load->view('_shared/action_nav') ?>
				<span id="filter_ajax_indicator" style="visibility:hidden; float:right;"><img src="<?=$cp_theme_url?>images/indicator2.gif" style="padding-right:20px;" /></span>
			</h2>
		</div>
		
		<div id="file_manager" class="pageContents group">
			
			<?php if ($no_upload_dirs):?>
					<?=lang('no_upload_dirs_available')?>
			<?php else: ?>
			<div id="filterMenu">
				<?php if ( ! empty($upload_dirs_options)):?>
				<fieldset>
					<legend><?=lang('search_files')?></legend>
				<?=form_open('C=content_files'.AMP.'M=index', array('name'=>'filterform', 'id'=>'filterform'))?>

					<div class="group">
						<?=form_dropdown('dir_id', $upload_dirs_options, $selected_dir, 'id="dir_id"').NBS.NBS?>
						<?=form_dropdown('cat_id', $category_options, $selected_cat_id, 'id="cat_id"').NBS.NBS?>
						<?=form_dropdown('file_type', $type_select_options, $selected_type, 'id="file_type"').NBS.NBS?>
						<?=form_dropdown('date_range', $date_select_options, $selected_date, 'id="date_range"').NBS.NBS?>
					</div>

	        		<div id="custom_date_picker" style="display: none; margin: 0 auto 50px auto;width: 500px; height: 235px; padding: 5px 15px 5px 15px;border: 1px solid black;  background: #FFF;">
						<div id="cal1" style="width:250px; float:left; text-align:center;">
							<p style="text-align:left; margin-bottom:5px"><?=lang('start_date', 'custom_date_start')?>:&nbsp; <input type="text" name="custom_date_start" id="custom_date_start" value="yyyy-mm-dd" size="12" tabindex="1" /></p>
							<span id="custom_date_start_span"></span>
						</div>
		                <div id="cal2" style="width:250px; float:left; text-align:center;">
							<p style="text-align:left; margin-bottom:5px"><?=lang('end_date', 'custom_date_end')?>:&nbsp; <input type="text" name="custom_date_end" id="custom_date_end" value="yyyy-mm-dd" size="12" tabindex="2" /></p>
							<span id="custom_date_end_span"></span>          
						</div>
	                </div>

					<div>
						<label for="keywords" class="js_hide"><?=lang('keywords')?> </label><?=form_input('keywords', $keywords, 'class="field shun" id="keywords" placeholder="'.lang('keywords').'"')?><br />
						<?=form_dropdown('search_in', $search_in_options, $selected_search, 'id="search_in"').NBS.NBS?>
						<?=form_submit('submit', lang('search'), 'class="submit" id="search_button"')?>
					</div>

				<?=form_close()?>
				</fieldset>
				<?php endif; ?>
				<div class="clear_left"></div>
			</div> <!-- filterMenu -->			
			<?=form_open('C=content_files'.AMP.'M=multi_edit_form', array('name'=>'file_form', 'id'=>'file_form'))?>
				<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
					<thead>
						<tr>
							<th>#</th>
							<th><?=lang('title')?></th>
							<th><?=lang('file_name')?></th>
							<th><?=lang('kind')?></th>
							<?php if ($comments_enabled):?>
							<th><?=lang('comments')?></th>							
							<?php endif;?>
							<th><?=lang('dir_name')?></th>
							<th><?=lang('date')?></th>
							<th><?=lang('actions')?></th>
							<th><?=lang('action_delete')?></th>
							<th id="toggle_all"><?=form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')?></th>
						</tr>
					</thead>
					<tbody>
					<?php if ( ! isset($files) OR empty($files)):?>
						<tr>
							<td colspan="8"><?=$no_files_message?></td>
						</tr>
					<?php else: ?>
						<?php foreach ($files as $file):?>
						<tr class="<?=alternator('even', 'odd')?>">
							<td><?=$file[0]?></td>
							<td><?=$file[1]?></td>
							<td><?=$file[2]?></td>
							<td><?=$file[3]?></td>
							<?php if ($comments_enabled):?>
							<td>// Comment stuff here</td>
							<?php endif;?>
							<td><?=$file[4]?></td>
							<td><?=$file[5]?></td>
							<td><?=$file[6]?></td>
							<td><?=$file[7]?></td>
							<td class="file_select"><?=$file[8]?></td>
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
				
				<script type="text/x-jquery-tmpl" id="filemanager_row">
					<tr class="new">
						<td>${file_id}</td>
						<td>${file_name}</td>
						<td>{{html link}}</td>
						<td>${mime_type}</td>
						<?php if ($comments_enabled):?>
							<td><!-- Comment stuff here --></td>
						<?php endif;?>
						<td>${upload_directory_prefs.name}</td>
						<td>${modified_date}</td>
						<td>{{html actions}}</td>
						<td>{{html action_delete}}</td>
						<td class="file_select"><input type="checkbox" name="toggle[]" value="${file_id}" class="toggle" id="toggle_box_${file_id}" /></td>
					</tr>
				</script>
				
				<span class="js_hide"><?=$pagination_links?></span>
				<span class="pagination" id="filter_pagination"></span>
				
				
				
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