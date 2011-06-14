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

		<div class="heading"><h2 class="edit"><?=$cp_page_title?>: <?=$channel_title?></h2></div>
		<div class="pageContents">

			<?=form_open('C=admin_content'.AMP.'M=channel_update_group_assignments', '', $form_hidden)?>
			<table id="entries" class="mainTable padTable" cellspacing="0" cellpadding="0" border="0">
			<thead>
				<tr>
					<th><?=lang('preference')?></th>
					<th><?=lang('value')?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?=form_label(lang('category_group'), 'category_group')?></td>
					<td><?=form_dropdown('cat_group[]', $cat_group_options, $cat_group, 'id="category_group" multiple="multiple"')?></td>
				</tr>
				<tr>
					<td><?=form_label(lang('status_group'), 'status_group')?></td>
					<td><?=form_dropdown('status_group', $status_group_options, $status_group, 'id="status_group"')?></td>
				</tr>
				<tr>
					<td><?=form_label(lang('field_group'), 'field_group')?></td>
					<td><?=form_dropdown('field_group', $field_group_options, $field_group, 'id="field_group"')?></td>
				</tr>
			</tbody>
			</table>

			<p><?=form_submit('channel_prefs_submit', lang('update'), 'class="submit"')?></p>

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

/* End of file status_group_edit.php */
/* Location: ./themes/cp_themes/default/admin/status_group_edit.php */