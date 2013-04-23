<?php
/*
    have you seen haiku with Regex?  eh?

    Haiku is an art
    One that Derek(s)? (does|do) not have
    Garth, this IS Haiku
*/

if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="edit" class="current">
	<div class="toolbar">
		<h1><?=$cp_page_title?></h1>
		<a href="<?=BASE.AMP?>C=content_publish" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
	</div>
		<?php $this->load->view('_shared/right_nav')?>
		<?php $this->load->view('_shared/message');?>

<?php if ($message != ''):?>
	<div class="pad container">
		<fieldset class="previewBox" id="previewBox">
			<legend class="previewItemTitle">
				&nbsp;<span class='notice'><?=lang('success')?></span>&nbsp;
			</legend>
		<?php echo $message; ?>
		</fieldset>
	</div>
<?php endif;?>

	<?=form_open_multipart($current_url, array('id' => 'publishForm'), $hidden_fields)?>


	<?php /* TABS */?>
	<ul class="tab_menu" id="tab_menu_tabs">
	<?php foreach($tabs as $tab => $fields):?>
		<li id="menu_<?=$tab?>" title="<?=form_prep($tab_labels[$tab])?>" class="content_tab">
			<a href="#" title="menu_<?=$tab?>" class="menu_<?=$tab?>"><?=lang($tab_labels[$tab])?></a>
		</li>
	<?php endforeach; 
	if ($this->session->userdata('group_id') == 1): /*?>
		<li class="addTabButton"><a class="add_tab_link" href="#">
			<img src="<?=$cp_theme_url?>images/add_publish_tab_button.gif" alt="<?=lang('add_tab')?>" width="10" height="10"/><?=lang('add_tab')?></a>
		</li>
	<?php */ endif?>
	</ul>
	<div class="clear"></div>
	<?php // Sidebar  ?>
	<?php /*
		// admin sidebar, and "hide/show" link
		if ($this->session->userdata('group_id') == 1):
	?>
		<div id="tools">
			<h3><a href="#"><?=lang('fields')?></a></h3>
			<div>
				<ul>
					<?php foreach ($field_definitions as $field):?>
					<li><a href="#" class="field_selector" id="hide_field_<?=$field['field_id']?>"><?=$field['field_label']?></a> <a href="#" class="delete delete_field" id="remove_field_<?=$field['field_id']?>"><img src="<?=$cp_theme_url?>images/open_eye.png" alt="<?=lang('delete')?>" width="15" height="15" /></a></li>
					<?php endforeach;?>
				</ul><br />
			</div>

			<h3><a href="#"><?=lang('tabs')?></a></h3>
			<div>
			<ul id="publish_tab_list">
				<?php foreach($publish_tabs as $tab => $field_list):?>
					<li>
						<a href="#" title="menu_<?=$tab?>" class="menu_focus"><?=lang($tab)?></a> 
						<a href="#<?=$tab?>" class="delete delete_tab"><img src="<?=$cp_theme_url?>images/content_custom_tab_delete.png" alt="<?=lang('delete')?>" width="19" height="18" /></a>
					</li>
				<?php endforeach;?>
			</ul>

			<p class="custom_field_add"><a href="#" class="add_tab_link submit submit_alt"><img src="<?=$cp_theme_url?>images/add_tab.png" width="22" height="14" alt="<?=lang('add_tab')?>" />&nbsp;&nbsp;<?=lang('add_tab')?></a></p>

			</div>

		
		<?php
			// is the user admin? This feature can only be used by admins
			if ($this->session->userdata('group_id') == 1):
			?>
			<h3><a href="#"><?=lang('publish_layout')?></a></h3>
			<div>
				<p style="margin: 0 7px;" id="layout_groups_holder">
+						<?php foreach($member_groups_laylist as $group):?>
+							<label><?=form_checkbox('member_group[]', $group['group_id'], FALSE, 'class="toggle_member_groups"')?> <?=$group['group_title']?></label><br />
					<?php endforeach;?>
					<label><?=form_checkbox('toggle_member_groups', 'toggle_member_groups', FALSE, 'class="toggle_member_groups" id="toggle_member_groups_all"').' '.$this->lang->line('select_all')?></label>
				</p>
				<p class="custom_field_add">
					<a href="#" id="layout_group_submit" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/save_layout.png" width="12" height="14" alt="<?=lang('save_layout')?>" />&nbsp;&nbsp;<?=lang('save_layout')?></a>
					<a href="#" id="layout_group_remove" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/remove_layout.png" width="12" height="14" alt="<?=lang('remove_layout')?>" />&nbsp;&nbsp;<?=lang('remove_layout')?></a>
					<a href="#" id="layout_group_preview" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/preview_layout.png" width="12" height="14" alt="<?=lang('preview_layout')?>" />&nbsp;&nbsp;<?=lang('preview_layout')?></a>
				</p>
			</div>
			<?php endif;?>

		</div>

		<div id="showToolbarLink"><a href="#"><span><?=lang('show_toolbar')?></span>&nbsp;
			<img alt="<?=lang('hide')?>" id="hideToolbarImg" width="20" height="17" src="<?=$cp_theme_url?>images/content_hide_image_toolbar.png" class="js_hide" />
			<img alt="<?=lang('show')?>" id="showToolbarImg" width="20" height="17" src="<?=$cp_theme_url?>images/content_show_image_toolbar.png" style="display: inline" />
		</a></div>

	<?php
		// ends sidebar and "hide/show" link
		endif;
	?>
	<?php */?>

	<div id="holder">
		
		<?php var_dump($tabs);
		foreach ($tabs as $tab => $fields):?>
		<div id="<?=$tab?>" class="main_tab">

			<?php foreach ($fields as $field):?>

				<?php
				
				$f = $field_list[$field];
				
				?>
				
				<div class="publish_field publish_<?=$f['field_type']?>" id="hold_field_<?=$f['field_id']?>">
					<div class="label">
						<label class="hide_field" for="<?=$field?>">
							<?php if ($f['field_required'] == 'y'):?><?=required()?><?php endif?><?=$f['field_label']?>
						</label>
					</div>

					<div id="sub_hold_field_<?=$f['field_id']?>">
						<?php if($f['field_instructions'] != ''):?>
							<div class="instruction_text"><?=auto_typography('<strong>'.$this->lang->line('instructions').'</strong>'.NBS.$f['field_instructions'])?></div>
						<?php endif;?>


						<ul>
							<li><?=($e = form_error('field_id_'.$field)) == '' ? form_error($field) : $e?>

								<?php

								
								switch ($f['field_id'])
								{
									case 'options':
										$f['string_override'] = '<label><input type="checkbox" name="sticky" value="y" id="sticky"  /> Make Entry Sticky</label><br />
										<label><input type="checkbox" name="allow_comments" value="y" checked="checked" id="allow_comments"  /> Allow Comments</label>';
										break;
									case 'category':
										$f['string_override'] = str_replace('</p>', '', $f['string_override']);
										$f['string_override'] = str_replace('</label><label>', '</label><br /><label>', $f['string_override']);
										break;
									case 'ping':
										$f['string_override'] = str_replace('<fieldset>', '', $f['string_override']);
										$f['string_override'] = str_replace('</fieldset>', '', $f['string_override']);
										$f['string_override'] = str_replace('</label><label>', '</label><br /></label>', $f['string_override']);
									default:
										break;
								}
								
								
								?>
							<?php 
							echo isset($f['string_override']) ? $f['string_override'] : $field_output[$field];
							echo form_error($field);
							?></li>
						</ul>
							

						<?php
						// only text field types get these options
						if(($f['field_type'] == 'text' OR $f['field_type'] == 'textarea') 
							&& $f['field_id'] != 'title' && $f['field_id'] != 'url_title' 
							&& $f['field_id'] != 'pages_uri' && $f['field_id'] != 'forum_topic_id' && $f['field_id'] != 'forum_title' 
							&& ( ! isset($f['field_content_type']) 
							OR $f['field_content_type'] == 'any')):
						?>
						<p class="spellcheck">

							<?php if ($f['field_type'] == 'text' && count($file_list) > 0):?>
							<img class="file_manipulate js_show" src="<?=$cp_theme_url?>images/publish_format_picture.gif" alt="<?=lang('file')?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<?php endif;?>
							<?php if ($smileys_enabled AND isset($f['smiley_table'])):?>
							<a href="#" class="smiley_link" title="<?=lang('emoticons')?>"><?=lang('emoticons')?></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
							<?php endif; ?>
							<?php if ($f['field_show_fmt'] == 'y' && count($f['field_fmt_options']) > 0):?>
							<div class="label">	
								<?=lang('formatting', 'formatting')?>
							</div>
							<ul>
								<li><?=form_dropdown('field_ft_'.$f['field_id'], $f['field_fmt_options'], $f['field_fmt'], 'id="formatting"')?> </li>
							</ul>
							<?php endif;?>

					<?php elseif ($f['field_show_fmt'] == 'y' && count($f['field_fmt_options']) > 0):?>
						<p class="spellcheck">
						<?=lang('formatting')?>
						<?=form_dropdown('field_ft_'.$f['field_id'], $f['field_fmt_options'], $f['field_fmt'], 'id="formatting"')?> 
						</p>
					<?php endif;?>

						<?php
						// only text field types get these options
						if(
							(
								$f['field_type'] == 'text' OR 
								$f['field_type'] == 'textarea'
							) 
							&& 
							$f['field_id'] != 'title' && 
							$f['field_id'] != 'url_title' && 
							$f['field_id'] != 'pages_uri' && 
							$f['field_id'] != 'forum_topic_id' 
							&& 
							( 
								! isset($f['field_content_type']) OR 
								$f['field_content_type'] == 'any'
							)
						)
						{
							if ($smileys_enabled AND isset($f['smiley_table']))
							{
								echo $f['smiley_table'];
							}
						}
						?>
					</div>
					
				</div>

			<?php endforeach;?>

			<div class="insertpoint"></div>
			<div class="clear"></div>
		</div>

		<?php endforeach;?>

		<?php if ($show_revision_cluster == 'y'):?>
			<input type="submit" class="whiteButton" name="save_revision" id="revision_button" value="<?=lang('save_revision')?>" /><?php endif?>
			<input type="submit" class="whiteButton" name="submit" id="submit_button" value="<?=lang('submit')?>" />
	</div><!-- close holder-->
		
		
		
	</div>	
		
		
		
		
	<?=form_close()?>


	<div id="write_mode_container">
		<div id="write_mode_close_container">&nbsp;</div>
		<div id="write_mode_writer">
		   <div id="write_mode_header">&nbsp;</div>
		</div>
		<div id="write_mode_footer">&nbsp;</div>
	</div>

<div class="js_hide">
	<?php foreach ($unrevealed_fields as $field):?>
		<div class="publish_field publish_<?=$f['field_type']?>" id="hold_field_<?=$f['field_id']?>">
			<div class="label">
				<label class="hide_field" for="<?=$f['field_id']?>">
					<img class="field_collapse" src="<?=$cp_theme_url?>images/field_expand.png" width="10" height="13" alt="" />
					<?php if ($f['field_required'] == 'y'):?><?=required()?><?php endif;?>
					<?=$f['field_label']?>
				</label>
			</div>

			<div id="sub_hold_field_<?=$f['field_id']?>">

				<?php if($f['field_instructions'] != ''):?>
					<div class="label">
				<?=auto_typography('<strong>'.lang('instructions').'</strong>'.NBS.$f['field_instructions']);?>
					</div>
				<?php endif;?>

				<p>
					<?=custom_field($f)?>
					<?=($e = form_error('field_id_'.$field)) == '' ? form_error($field) : $e?>
				</p>

				<?php
				// only text field types get these options
				if(($f['field_type'] == 'text' OR $f['field_type'] == 'textarea') 
					&& $f['field_id'] != 'title' && $f['field_id'] != 'url_title' 
					&& $f['field_id'] != 'pages_uri' && $f['field_id'] != 'forum_topic_id' && $f['field_id'] != 'forum_title' 
					&& ( ! isset($f['field_content_type']) 
					OR $f['field_content_type'] == 'any')):
				?>
				<p class="spellcheck">

					<?php if ($f['field_type'] == 'text' && count($file_list) > 0):?>
					<img class="file_manipulate js_show" src="<?=$cp_theme_url?>images/publish_format_picture.gif" alt="<?=lang('file')?>" />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<?php endif;?>

					<?php if($spell_enabled):?>
					<a href="#" class="spellcheck_link" id="spelltrigger_field_id_<?=$f['field_id']?>" title="<?=lang('check_spelling')?>"><img src="<?=$cp_theme_url.'images/spell_check_icon.png'?>" style="margin-bottom: -8px;" alt="<?=lang('check_spelling')?>" /></a>
					<?php endif;?>
					<a href="#" class="smiley_link" title="<?=lang('emoticons')?>"><?=lang('emoticons')?></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;

					<?php if ($f['field_show_fmt'] == 'y' && count($f['field_fmt_options']) > 0):?>
					<?=lang('formatting')?>
					<?=form_dropdown('field_ft_'.$f['field_id'], $f['field_fmt_options'], $f['field_fmt'], 'id="formatting"')?> 
					<?php endif;?>

				</p>
			<?php elseif ($f['field_show_fmt'] == 'y' && count($f['field_fmt_options']) > 0):?>
				<p class="spellcheck">
				<?=lang('formatting')?>
				<?=form_dropdown('field_ft_'.$f['field_id'], $f['field_fmt_options'], $f['field_fmt'], 'id="formatting"')?> 
				</p>
			<?php endif;?>

				<?php
				// only text field types get these options
				if(($f['field_type'] == 'text' OR $f['field_type'] == 'textarea') && $f['field_id'] != 'title' && $f['field_id'] != 'url_title' && $f['field_id'] != 'pages_uri' && $f['field_id'] != 'forum_topic_id' && ( ! isset($f['field_content_type']) OR $f['field_content_type'] == 'any'))
				{
					if ($spell_enabled)
					{
						echo build_spellcheck('field_id_'.$f['field_id']);
					}

					echo $smiley_table[$f['field_id']];
				}
				?>
			</div>
			
		</div>

	<?php endforeach;?>
</div>

<?php if ($this->session->userdata('group_id') == 1):?>
	<div id="new_tab_dialog" title="<?=lang('add_tab')?>" style="display: none;">
		<form>
			<p>
				<label><?=lang('tab_name')?></label> 
				<input id="tab_name" type="text" value="" name="tab_name"/>
			</p>
		</form>
	</div>


<?php endif;?>

<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}
/* End of file publish.php */
/* Location: ./themes/cp_themes/mobile/content/publish.php */
