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
			<h2 class="edit"><?=lang('batch_upload')?></h2>
		</div>
		
		<div class="pageContents">
			<?php if ($files_count === 0): ?>
			<p><?=lang('no_results')?></p>
			<?php else: ?>
				<h4><?=$count_lang?></h4>
				<?=form_open($form_action, $form_hidden)?>
				<table class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
					<thead>
						<tr>
							<th><?=lang('thumbnail')?></th>
							<th><?=lang('title')?></th>
							<th><?=lang('caption')?></th>
							<th style="width:10%"><?=lang('include')?> <?=form_checkbox('include')?></th>
						</tr>
					</thead>
					<tbody>
					<?php foreach ($files as $file): ?>
						<tr class="<?=alternator('even', 'odd')?>">
							<td><img src="<?=$file['image']?>"><br>
								<?=$file['name']?></td>
							<td><?=form_input('file_name', $file['name'])?></td>
							<td><?=form_textarea(array(
								'name'		=> 'test',
								'cols'		=> 8,
								'rows'		=> 3
							))?></td>
							<td><?=form_checkbox('include')?></td>						
						</tr>
					<?php endforeach; ?>
					</tbody>
					
				</table>
				<p><?=form_submit('submit', lang('submit'), 'class="submit"')?></p>
				</form>
			<?php endif; ?>
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