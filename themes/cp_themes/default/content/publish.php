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
		<div class="heading">
			<h2 class="edit"><?=$cp_page_title?></h2>
		</div>
		
		<?php $this->load->view('_shared/message');?>

<?php if (isset($submission_error)):?>
		<fieldset class="previewBox" id="previewBox"><legend class="previewItemTitle">&nbsp;<span class='alert'><?=lang('error')?></span>&nbsp;</legend>

		<?php echo $submission_error; ?>
		<?php echo $message; ?>
		</fieldset>

<?php elseif ($message != ''):?>
		<fieldset class="previewBox" id="previewBox"><legend class="previewItemTitle">&nbsp;<span class='notice'><?=lang('success')?></span>&nbsp;</legend>
		<?php echo $message; ?>
		</fieldset>

<?php endif;?>

		<?=form_open_multipart('C=content_publish'.AMP.'M=entry_form'.$BK, $form_additional, $form_hidden)?>

			<ul class="tab_menu" id="tab_menu_tabs">
				<?php foreach($publish_tabs as $tab => $field_list):?><li id="menu_<?=$tab?>" title="<?=form_prep($tab_labels[$tab])?>" class="content_tab"><a href="#" title="menu_<?=$tab?>" class="menu_<?=$tab?>"><?=lang($tab_labels[$tab])?></a>&nbsp;</li><?php endforeach; if ($this->session->userdata('group_id') == 1):?><li class="addTabButton"><a class="add_tab_link" href="#"><img src="<?=$cp_theme_url?>images/add_publish_tab_button.gif" alt="<?=lang('add_tab')?>" width="10" height="10"/><?=lang('add_tab')?></a>&nbsp;</li><?php endif?>
			</ul>

		<?php
			// admin sidebar, and "hide/show" link
			if ($this->session->userdata('group_id') == 1):
		?>
			<div id="tools">
				<h3><a href="#"><?=lang('fields')?></a></h3>
				<div>
					<ul>
						<?php foreach ($field_output as $name => $field):
							$f = is_array($field) ? $field : $this->api_channel_fields->settings[$name];
							if (in_array($name, $required_fields)):?>
							<li><a href="#" class="field_selector" id="hide_field_<?=$f['field_id']?>"><?=required()?><?=$f['field_label']?></a></li> 
							<?php else:?>
							<li><a href="#" class="field_selector" id="hide_field_<?=$f['field_id']?>"><?=$f['field_label']?></a> <a href="#" class="delete delete_field" id="remove_field_<?=$f['field_id']?>"><img src="<?=$cp_theme_url?>images/open_eye.png" alt="<?=lang('delete')?>" width="15" height="15" /></a></li> 
						<?php endif;?>
						
						<?php endforeach;?>
					</ul><br />
				</div>

				<h3><a href="#"><?=lang('tabs')?></a></h3>
				<div>
				<ul id="publish_tab_list">
					<?php foreach($publish_tabs as $tab => $field_list):?>
						<li id="remove_tab_<?=$tab?>">
							<a href="#" title="menu_<?=$tab?>" class="menu_focus"><?=lang($tab_labels[$tab])?></a> 
							<a href="#<?=$tab?>" class="delete delete_tab"><img src="<?=$cp_theme_url?>images/content_custom_tab_delete.png" alt="<?=lang('delete')?>" width="19" height="18" /></a>
						</li>
					<?php endforeach;?>
				</ul>

				<p class="custom_field_add"><a href="#" class="add_tab_link submit submit_alt"><img src="<?=$cp_theme_url?>images/add_tab.png" width="22" height="14" alt="<?=lang('add_tab')?>" />&nbsp;&nbsp;<?=lang('add_tab')?></a></p>

				</div>

				<?php
				// Can the current user assign the entry to a different author?
				if ($this->cp->allowed_group('can_assign_post_authors')):
				?>
					<h3><a href="#"><?=lang('authors')?></a></h3>
					<div>
					<ul id="author_list_sidebar">
					<?php foreach ($author_list->result() as $author):?>
						<li>
							<a href="<?=BASE.AMP.'C=myaccount'.AMP.'id='.$author->member_id?>"><?=$author->screen_name?></a>
							<?php if ($author->in_authorlist == 'y' && $author->include_in_authorlist == 'n'):?>
							<a href="#" class="delete" id="mid<?=$author->member_id?>"><img src="<?=$cp_theme_url?>images/content_custom_tab_delete.png" alt="<?=lang('delete')?>" width="19" height="18" /></a>
							<?php endif;?>
						</li>
					<?php endforeach;?>
					</ul>

					<p class="custom_field_add">
						<a href="#" class="add_author_link submit submit_alt"><img src="<?=$cp_theme_url?>images/add_item.png" width="12" height="14" alt="<?=lang('add_author')?>" />&nbsp;&nbsp;<?=lang('add_author')?></a>
					</p>

					</div>
				<?php endif;?>
				<?php
				// is the user admin? This feature can only be used by admins
				if ($this->session->userdata('group_id') == 1):
				?>
				<h3><a href="#"><?=lang('publish_layout')?></a></h3>
				<div>
					<p style="margin: 0 7px;" id="layout_groups_holder">
						<?php foreach($member_groups->result() as $group):?>
							<label><?=form_checkbox('member_group[]', $group->group_id, FALSE, 'class="toggle_member_groups"')?> <?=$group->group_title?></label><br />
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

			<div id="showToolbarLink">
				<a href="#"><span><?=lang('show_toolbar')?></span>&nbsp;
					<img alt="<?=lang('hide')?>" id="hideToolbarImg" width="20" height="17" src="<?=$cp_theme_url?>images/content_hide_image_toolbar.png" class="js_hide" />
					<img alt="<?=lang('show')?>" id="showToolbarImg" width="20" height="17" src="<?=$cp_theme_url?>images/content_show_image_toolbar.png" style="display: inline" />
				</a>
			</div>

		<?php
			// ends sidebar and "hide/show" link
			endif;
		?>

	<div id="holder">
			<?php reset($publish_tabs); $first_tab = key($publish_tabs); ?>
			<?php foreach ($publish_tabs as $tab => $fields):?>
			<div id="<?=url_title($tab, 'underscore', TRUE)?>" class="main_tab<?=($tab == $first_tab) ? '' : ' js_hide'?>">
				
				<?php foreach ($fields as $field => $values):?>
					
					<?php

					$f = is_array($field_output[$field]) ? $field_output[$field] : $this->api_channel_fields->settings[$field];
					$values['is_hidden'] = isset($values['is_hidden']) ? $values['is_hidden'] : FALSE;
					$values['visible'] = ($values['visible'] === FALSE OR $values['visible'] === 'false') ? FALSE : TRUE;

					?>
					
					
					<div class="publish_field publish_<?=$f['field_type']?>" id="hold_field_<?=$f['field_id']?>" <?=$values['visible'] ? '' : 'style="display: none;"'?>>
						<div class="handle"></div>
							<label class="hide_field">
								<span>
								<img class="field_collapse" src="<?=$cp_theme_url?>images/field_expand.png" width="10" height="13" alt="" />
								<?php if ($f['field_required'] == 'y'):?><?=required()?><?php endif?>
								<?=$f['field_label']?>
								</span>
							</label>

						<div id="sub_hold_field_<?=$field?>" <?=$values['is_hidden']? 'class="js_hide"': '';?>>
							<?php if($f['field_instructions'] != ''):?>
								<div class="instruction_text"><?=auto_typography('<strong>'.$this->lang->line('instructions').'</strong>'.NBS.$f['field_instructions'])?></div>
							<?php endif;?>
							<fieldset class="holder">
							<?php
								echo isset($f['string_override']) ? $f['string_override'] : $field_output[$field];
								echo form_error($f['field_name']);
							?>
							</fieldset>
							<?php
							$defaults = array(
								'field_show_spellcheck'			=> 'n',
								'field_show_smileys'			=> 'n',
								'field_show_glossary'			=> 'n',
								'field_show_formatting_btns'	=> 'n',
								'field_show_writemode'			=> 'n',
								'field_show_file_selector'		=> 'n',
								'field_show_fmt'				=> 'n'
							);

							$has_extras = FALSE;

							foreach($defaults as $key => $val)
							{
								if (isset($f[$key]) && $f[$key] == 'y')
								{
									$has_extras = TRUE;
									continue;
								}

								$f[$key] = $val;
							}
							?>

							<?php if ($has_extras): ?>
								<p class="spellcheck markitup">

									<?php if ($f['field_show_writemode'] == 'y'):?>
										<a href="#" class="write_mode_trigger" rel="#write_mode_container" id="id_<?=$f['field_id']?>" title="<?=lang('write_mode')?>"><img alt="<?=lang('write_mode')?>" width="22" height="21" src="<?=$cp_theme_url?>images/publish_write_mode.png" /></a> 
									<?php endif;?>

									<?php if ($f['field_show_file_selector'] == 'y' && count($file_list) > 0):?>
										<a href="#" class="markItUpButton">
										<img class="file_manipulate js_show" src="<?=$cp_theme_url?>images/publish_format_picture.gif" alt="<?=lang('file')?>" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<?php endif;?>

									<?php if($spell_enabled && $f['field_show_spellcheck'] == 'y'):?>
										<a href="#" class="spellcheck_link" id="spelltrigger_<?=(ctype_digit($f['field_id']))?'field_id_':''?><?=$f['field_id']?>" title="<?=lang('check_spelling')?>"><img src="<?=$cp_theme_url.'images/spell_check_icon.png'?>" style="margin-bottom: -8px;" alt="<?=lang('check_spelling')?>" /></a>
									<?php endif;?>

									<?php if($f['field_show_glossary'] == 'y'):?>
										<a href="#" class="glossary_link" title="<?=lang('glossary')?>"><img src="<?=$cp_theme_url.'images/spell_check_glossary.png'?>" style="margin-bottom: -8px;" alt="<?=lang('glossary')?>" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<?php endif;?>

									<?php if ($smileys_enabled && $f['field_show_smileys'] == 'y'):?>
										<a href="#" class="smiley_link" title="<?=lang('emoticons')?>"><?=lang('emoticons')?></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
									<?php endif;?>

									<?php if ($f['field_show_fmt'] == 'y' && count($f['field_fmt_options']) > 0):?>
										<?=lang('formatting')?>
										<?=form_dropdown('field_ft_'.$f['field_id'], $f['field_fmt_options'], $f['field_fmt'])?> 
									<?php endif;?>

								</p>

								<?php if($spell_enabled && $f['field_show_spellcheck'] == 'y'):
									echo (ctype_digit($f['field_id'])) ? build_spellcheck('field_id_'.$f['field_id']) : build_spellcheck($f['field_id']);
								endif;?>

								<?php if($f['field_show_glossary'] == 'y'):
									echo $glossary_items;
								endif;?>

								<?php if ($smileys_enabled && $f['field_show_smileys'] == 'y'):
									echo $smiley_table[$f['field_id']];									
								endif;?>

							<?php endif; ?>

						</div>
					</div>

				<?php endforeach;?>

				<div class="insertpoint"></div>
				<div class="clear"></div>
			</div>

			<?php endforeach;?>

			<ul id="publish_submit_buttons">
			<?php if ($show_revision_cluster == 'y'):?><li><input type="submit" class="submit" name="save_revision" id="revision_button" value="<?=lang('save_revision')?>" /></li><?php endif?>
				<li><input type="submit" class="submit" name="submit" id="submit_button" value="<?=lang('submit')?>" /></li>
			</ul>

	</div><!-- close holder-->
			<div class="clear"></div>
		<?=form_close()?>
	</div><!-- contents -->
</div><!-- mainContent -->


<div id="write_mode_container">
	<div id="write_mode_close_container"><a href="#" class="closeWindowButton"><img alt="<?=lang('close')?>" width="13" height="13" src="<?=$cp_theme_url?>images/write_mode_close.png" /></a><a href="#" class="publish_to_field"><img alt="Publish to Field" width="103" height="18" src="<?=$cp_theme_url?>images/write_mode_publish_to_field.png" /></a>&nbsp;</div>
  
	<div id="write_mode_writer">
		<div id="write_mode_header"><a href="#" class="reveal_formatting_buttons"><img class="show_tools" alt="<?=lang('show_tools')?>" width="109" height="18" src="<?=$cp_theme_url?>images/write_mode_show_tools.png" /></a></div>
		<textarea id="write_mode_textarea"></textarea>
	</div>
	<div id="write_mode_footer"><a href="#" class="publish_to_field"><img alt="<?=lang('publish_to_field')?>" width="103" height="18" src="<?=$cp_theme_url?>images/write_mode_publish_to_field.png" /></a></div>
</div>

	<div class="js_hide">
		<?php foreach ($unrevealed_fields as $field):?>
			<?php

			$f = is_array($field_output[$field]) ? $field_output[$field] : $this->api_channel_fields->settings[$field];
			$values['is_hidden'] = isset($values['is_hidden']) ? $values['is_hidden'] : FALSE;

			?>
			
			
			<div class="publish_field publish_<?=$f['field_type']?>" id="hold_field_<?=$f['field_id']?>">
				<div class="handle"></div>
				<p>
					<label class="hide_field" for="<?=$field?>">
						<img class="field_collapse" src="<?=$cp_theme_url?>images/field_expand.png" width="10" height="13" alt="" />
						<?php if ($f['field_required'] == 'y'):?><?=required()?><?php endif?>
						<?=$f['field_label']?>
					</label>
				</p>

				<div id="sub_hold_field_<?=$field?>" <?=$values['is_hidden']? 'class="js_hide"': '';?>>
					<p>
					<?php
						echo isset($f['string_override']) ? $f['string_override'] : $field_output[$field];
						echo form_error($f['field_name']);
					?>
					</p>
				</div>
			</div>

		<?php endforeach;?>
	</div>

<?php if ($this->session->userdata('group_id') == 1):?>
	<div id="new_tab_dialog" title="<?=lang('add_tab')?>" style="display: none;">
		<form action="#">
			<p>
				<label><?=lang('tab_name')?></label> 
				<input id="tab_name" type="text" value="" name="tab_name"/>
			</p>
		</form>
	</div>

	<div id="add_author_dialog" title="<?=lang('add_author')?>" style="display: none;">
		<form id="add_authors_dialog_form" action="#">
			<div><img src="<?=PATH_CP_GBL_IMG?>indicator.gif" alt="" /></div>
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
/* Location: ./themes/cp_themes/default/content/publish.php */
