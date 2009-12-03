<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=admin_content&amp;M=channel_management" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>
	
	
	
	
			<?=form_open('C=admin_content'.AMP.'M=channel_add', array('id'=>'channel_edit'))?>

			<div class="label">
				<?=required()?> <?=lang('channel_title', 'channel_title')?>
				<?=form_error('channel_title')?>				
			</div>
			<ul>				<li><?=form_input(array('id'=>'channel_title','name'=>'channel_title','class'=>'fullfield','value'=>set_value('channel_title')))?></li>
			</ul>

			<div class="label">
				<?=required()?> <?=lang('channel_name', 'channel_name')?><br /><?=lang('single_word_no_spaces')?>
				<?=form_error('channel_name')?>
			</div>
			<ul>
				<li><?=form_input(array('id'=>'channel_name','name'=>'channel_name','class'=>'fullfield','value'=>set_value('channel_name')))?></li>
			</ul>

			<div class="label">
				<?=lang('duplicate_channel_prefs', 'duplicate_channel_prefs')?>
			</div>
			<ul>
				<li><?=form_dropdown('duplicate_channel_prefs', $duplicate_channel_prefs_options, '', 'id="duplicate_channel_prefs"')?></li>
			</ul>

			<div class="label">
				<strong><?=lang('edit_group_prefs')?></strong>
			</div>
			<ul>
				<li><?=form_radio('edit_group_prefs', 'y', FALSE, 'id="edit_group_prefs_y"')?> <?=lang('yes', 'edit_group_prefs_y')?> &nbsp;&nbsp;&nbsp;
						<?=form_radio('edit_group_prefs', 'n', TRUE, 'id="edit_group_prefs_n"')?> <?=lang('no', 'edit_group_prefs_n')?>
					</li>
			</ul>

			<div id="edit_group_prefs">
				<div class="label">
					<?=lang('category_group', 'cat_group')?>
				</div>
				<ul>
					<li><?=form_dropdown('cat_group[]', $cat_group_options, '', 'id="cat_group"')?></li>
				</ul>
				<div class="label">
					<?=lang('status_group', 'status_group')?>
				</div>
				<ul>
					<li><?=form_dropdown('status_group', $status_group_options, '', 'id="status_group"')?></li>
				</ul>
				<div class="label">
					<?=lang('field_group', 'field_group')?>
				</div>
				<ul>
					<li><?=form_dropdown('field_group', $field_group_options, '', 'id="field_group"')?></li>
				</ul>
			</div>
			
		<?php if ($this->cp->allowed_group('can_admin_templates')):?>
			<div class="label">
				<?=lang('template_creation')?>
			</div>
			<ul>
				<li><?=form_radio('create_templates', 'no', TRUE)?> <?=lang('no', 'create_templates')?></li>
			</ul>
				
			<?php if (count($themes) > 0):?>
				<div class="label">
					<?=form_radio('create_templates', 'theme', FALSE)?> <?=lang('use_a_theme', 'create_templates')?>
				</div>
				<ul>
					<li><?=form_dropdown('template_theme', $themes, '', 'id="template_theme"')?></li>
					<li><?=form_checkbox('add_rss', 'y', FALSE)?> <?=lang('include_rss_templates', 'include_rss_templates')?></li>
				</ul>
			<?php endif;?>
				
			<div class="label">
				<?=form_radio('create_templates', 'duplicate', FALSE, 'id="create_templates_dupe"')?> <?=lang('duplicate_group', 'create_templates_dupe')?>
			</div> 
			<ul>
				<li><?=form_dropdown('old_group_id', $old_group_id, '', 'id="old_group_id"')?></li>
			</ul>
			<div class="label">
				<?=required()?> <?=lang('template_group_name', 'group_name')?><br /><?=lang('new_group_instructions')?>
			</div>
			<ul>
				<li><?=form_input(array('id'=>'group_name','name'=>'group_name','class'=>'fullfield'))?></li>
			</ul>

		<?php endif;?>

			<p>
				<?=form_submit(array('name' => 'channel_prefs_submit', 'value' => lang('submit'), 'class' => 'whiteButton'))?>
			</p>
			<?=form_close()?>	
	
	
	
		


</div>	
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file channel_add.php */
/* Location: ./themes/cp_themes/default/admin/channel_add.php */