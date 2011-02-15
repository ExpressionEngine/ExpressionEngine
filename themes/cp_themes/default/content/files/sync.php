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
			<h2 class="edit"><?=lang('content_files')?></h2>
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

					<tr class="<?=alternator('even', 'odd')?>">
						<td><?=$dir['name']?></td><td><?=$dir['count']?></td><td><?=$dir['allowed_types']?></td>
					</tr>

				<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( ! empty($sizes)):?>
			<table class="mainTable padTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th><?=lang('size')?></th>
						<th><?=lang('crop_or_constrain')?></th>
						<th><?=lang('width')?></th>
						<th><?=lang('height')?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ($sizes as $data):?>
					<tr class="<?=alternator('even', 'odd')?>">
						<td><?=$data['title']?></td>
						<td><?=$data['resize_type']?></td>
						<td><?=$data['width']?></td>
						<td><?=$data['height']?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php endif;?>
			
			<div class="tableSubmit">
				<?=form_submit('submit', lang('submit'), 'class="submit"').NBS.NBS?>
				<div id="progress" style="width:200px;"></div>
			</div>

			<table id="sync_complete_template" cellspacing="0" cellpadding="0">
				<tbody>
					<tr>
						<th><?=lang('files_processed')?></th>
						<td>${files_processed}</td>
					</tr>
					<tr>
						<th><?=lang('errors')?></th>
						<td>${errors}</td>
					</tr>
					<tr>
						<th><?=lang('error_count')?></th>
						<td>${error_count}</td>
					</tr>
				</tbody>
			</table>
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