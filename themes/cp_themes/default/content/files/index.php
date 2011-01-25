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
		<?php //var_dump($files->files[$selected_dir])?>
		<div class="pageContents">
			<?php if ( ! empty($upload_dirs_options)):?>
				<?=form_dropdown('dir_choice', $upload_dirs_options, $selected_dir)?>			
			<?php endif; ?>
			<table class="mainTable" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th><?=lang('name')?></th>
						<th><?=lang('size')?></th>
						<th><?=lang('kind')?></th>
						<th><?=lang('date')?></th>
						<th><?=lang('actions')?></th>
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
						<td><?=lang('actions go here')?></td>
					</tr>
					<?php endforeach; ?>
					<?php endif;?>
				</tbody>
				<tfoot>
					<tr>
						<td style="text-align:right">Total Directory Size</td>
						<td colspan="4"><?=number_format($dir_size/1000, 1);?> <?=lang('file_size_unit')?></td>
					</tr>
				</tfoot>
			</table>
			<?=$pagination_links?>
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