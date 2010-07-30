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

		<div class="heading"><h2 class="edit"><?=$cp_page_title?></h2></div>
		<div class="pageContents">

			<?=form_open('C=admin_content'.AMP.'M=channel_add', array('id'=>'channel_edit'))?>

			<table class="mainTable solo" border="0" cellspacing="0" cellpadding="0">
				<tr>
					<td style="width: 50%;">
						<?=required()?> <?=lang('channel_title', 'channel_title')?>
						<?=form_error('channel_title')?>
					</td>
					<td><?=form_input(array('id'=>'channel_title','name'=>'channel_title','class'=>'fullfield','value'=>set_value('channel_title')))?></td>
				</tr>
				<tr>
					<td>
						<?=required()?> <?=lang('channel_name', 'channel_name')?><br /><?=lang('single_word_no_spaces')?>
						<?=form_error('channel_name')?>
					</td>
					<td><?=form_input(array('id'=>'channel_name','name'=>'channel_name','class'=>'fullfield','value'=>set_value('channel_name')))?></td>
				</tr>
				<tr>
					<td><?=lang('duplicate_channel_prefs', 'duplicate_channel_prefs')?></td>
					<td><?=form_dropdown('duplicate_channel_prefs', $duplicate_channel_prefs_options, '', 'id="duplicate_channel_prefs"')?></td>
				</tr>
				<tr>
					<td><strong><?=lang('edit_group_prefs')?></strong></td>
					<td>
						<?=form_radio('edit_group_prefs', 'y', FALSE, 'id="edit_group_prefs_y"')?> <?=lang('yes', 'edit_group_prefs_y')?> &nbsp;&nbsp;&nbsp;
						<?=form_radio('edit_group_prefs', 'n', TRUE, 'id="edit_group_prefs_n"')?> <?=lang('no', 'edit_group_prefs_n')?>
					</td>
				</tr>
			</table>
			
			
			<table class="mainTable solo" id="edit_group_prefs" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th colspan="2"><?=lang('edit_group_prefs')?></th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td style="width: 50%;"><?=lang('category_group', 'cat_group')?></td>
						<td><?=form_dropdown('cat_group[]', $cat_group_options, '', 'id="cat_group" multiple="multiple"')?></td>
					</tr>
					<tr>
						<td><?=lang('status_group', 'status_group')?></td>
						<td><?=form_dropdown('status_group', $status_group_options, '', 'id="status_group"')?></td>
					</tr>
					<tr>
						<td><?=lang('field_group', 'field_group')?></td>
						<td><?=form_dropdown('field_group', $field_group_options, '', 'id="field_group"')?></td>
					</tr>
				</tbody>
			</table>
			
		<?php if ($this->cp->allowed_group('can_admin_templates')):?>

			<table class="mainTable solo" border="0" cellspacing="0" cellpadding="0">
				<thead>
					<tr>
						<th colspan="2"><?=lang('template_creation')?></th>
					</tr>
				</thead>
				<tbody>
				
					<tr>
						<td style="width: 50%;"><?=form_radio('create_templates', 'no', TRUE)?> <?=lang('no', 'create_templates')?></td>
						<td>&nbsp;</td>
					</tr>					
				
				<?php if (count($themes) > 0):?>
					<tr>
						<td><?=form_radio('create_templates', 'theme', FALSE)?> <?=lang('use_a_theme', 'create_templates')?></td>
						<td>
							<p><?=form_dropdown('template_theme', $themes, '', 'id="template_theme"')?></p>
							<?=form_checkbox('add_rss', 'y', FALSE)?> <?=lang('include_rss_templates', 'include_rss_templates')?>
						</td>
					</tr>
				<?php endif;?>
				
					<tr>
						<td><?=form_radio('create_templates', 'duplicate', FALSE, 'id="create_templates_dupe"')?> <?=lang('duplicate_group', 'create_templates_dupe')?></td> 
						<td><?=form_dropdown('old_group_id', $old_group_id, '', 'id="old_group_id"')?></td>
					</tr>
					<tr>
						<td><?=required()?> <?=lang('template_group_name', 'group_name')?><br /><?=lang('new_group_instructions')?></td>
						<td><?=form_input(array('id'=>'group_name','name'=>'group_name','class'=>'fullfield'))?></td>
					</tr>
				</tbody>
			</table>

		<?php endif;?>

			<p>
				<?=form_submit(array('name' => 'channel_prefs_submit', 'value' => lang('submit'), 'class' => 'submit'))?>
			</p>
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

/* End of file channel_add.php */
/* Location: ./themes/cp_themes/default/admin/channel_add.php */