<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/header');
	$this->load->view('_shared/main_menu');
	$this->load->view('_shared/sidebar');
	$this->load->view('_shared/breadcrumbs');
}
?>

<div id="mainContent">
	<?php $this->load->view('_shared/right_nav')?>
	<div class="contents">

		<div class="heading">
			<h2 class="edit"><?=lang('synchronize_directory')?></h2>
		</div>
		
		<div id="sync" class="pageContents group">
			
			<?=form_open('C=content_files'.AMP.'M=do_sync_files')?>
			<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th><?=lang('directory')?></th>
						<th><?=lang('file_count')?></th>
						<th><?=lang('file_types')?></th>

					</tr>
				</thead>
				<tbody>
				<?php foreach ($dirs as $dir):?>
					<tr class="<?=alternator('even', 'odd')?>" data-id="<?=$dir['id']?>">
						<td><?=$dir['name']?></td>
						<td><?=$dir['count']?></td>
						<td><?=$dir['allowed_types']?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<br />
			<?php if ( ! empty($sizes)):?>
			<h3><?=lang('image_sizes')?></h3>
			<p><?=lang('image_sizes_rebuild')?></p>	
			<table id="dimensions" class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th><?=lang('size')?></th>
						<th><?=lang('resize_type')?></th>
						<th><?=lang('width')?></th>
						<th><?=lang('height')?></th>
						<th><?=lang('wm_watermark')?></th>
						<th id="toggle_all"><?=form_checkbox('select_all', 'true', FALSE, 'class="toggle_all"')?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($sizes as $data):?>
					<tr class="<?=alternator('even', 'odd')?>">
						<td><?=$data['title']?></td>
						<td><?=$data['resize_type']?></td>
						<td><?=$data['width']?></td>
						<td><?=$data['height']?></td>
						<td><?=$data['wm_name']?></td>
						<td class="file_select"><?='<input class="toggle" type="checkbox" name="toggle[]" value="'.$data['id'].'" />'?></td>						
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif;?>
			
			<div class="tableSubmit">
				<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
				<div id="progress">
					<h4><?=lang('sync_progress')?>:</h4>
					<div id="progress_bar"></div>
				</div>
			</div>
			
			<script type="text/x-jquery-tmpl" id="sync_complete_template">
				<div id="sync_complete">
					<p><?=lang('sync_complete')?></p>
					<table cellspacing="0" cellpadding="0" class="padTable mainTable">
						<tbody>
							<tr>
								<th><?=lang('directory')?></th>
								<th><?=lang('files_processed')?></th>
								<th><?=lang('error_count')?></th>
								<th><?=lang('errors')?></th>
							</tr>
							<tr>
								<td>${directory_name}</td>
								<td>${files_processed}</td>
								<td>${error_count}</td>
								<td>
									<ul>
										{{each errors}}
											<li>{{html $value}}</li>
										{{/each}}
									</ul>
									<span><?=lang('no_errors')?></span>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</script>
		</div>
	</div>
</div> 

<?php
if ( ! $EE_view_disable)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file sync.php */
/* Location: ./themes/cp_themes/default/tools/sync.php */
