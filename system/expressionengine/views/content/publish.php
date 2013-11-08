<?php extend_template('basic') ?>
		
		<div class="heading">
			<h2><?=$cp_page_title?></h2>
		</div>
		
		<?php $this->load->view('_shared/message');?>

		<div class="publishPageContents">

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
			
			<?=form_open_multipart($current_url, array('id' => 'publishForm'), $hidden_fields)?>

				<!-- Tabs -->
				<ul class="tab_menu" id="tab_menu_tabs">
					<?php foreach ($tabs as $tab => $tab_fields):?>
							<?php
								$has_error = FALSE;
								foreach($tab_fields as $_field) {
									if (form_error($_field) != '') {
										$has_error = TRUE;
									}
								}
							?>
						<li id="menu_<?=$tab?>" title="<?=form_prep($tab_labels[$tab])?>" class="content_tab">
							<a href="#" title="menu_<?=$tab?>" class="menu_<?=$tab?>">
								<?php if ($has_error): ?>
									<img src="<?=$cp_theme_url?>images/error.png" alt="" width="12" height="12" />
								<?php endif; ?>
								<?=lang($tab_labels[$tab])?>
							</a>&nbsp;
						</li>
					<?php endforeach;?>
					
					<?php if ($this->session->userdata('group_id') == 1):?>
						<li class="addTabButton"><a class="add_tab_link" href="#"><?=lang('add_tab')?></a>&nbsp;</li>
					<?php endif?>
				</ul>
				
				
				<?php if ($this->session->userdata('group_id') == 1):?>
				<!-- Admin Sidebar -->
				
					<div id="tools">
						
						<!-- Sidebar fields -->
						<h3><a href="#"><?=lang('fields')?></a></h3>
						<div>
							<ul>
								<?php foreach ($field_list as $name => $field):?>
									<li>
									<?php if ($field['field_required'] == 'y'):?>
										<a href="#" class="field_selector" id="hide_field_<?=$field['field_id']?>">
											<?=required()?><?=$field['field_label']?>
										</a>
									<?php else:?>
										<a href="#" class="field_selector" id="hide_field_<?=$field['field_id']?>">
											<?=$field['field_label']?>
										</a> 
										<a href="#" class="delete delete_field" id="remove_field_<?=$field['field_id']?>" data-visible="<?= (isset($field['field_visibility'])) ? $field['field_visibility'] : 'y' ?>">
											<img src="<?=$cp_theme_url?>images/<?php if (isset($field['field_visibility'])): echo ($field['field_visibility'] == "y") ? 'open_eye' : 'closed_eye'; endif ?>.png" alt="<?=lang('delete')?>" width="15" height="15" />
										</a>
									<?php endif;?>
									</li>
								<?php endforeach;?>
							</ul><br />
						</div>
						
						<!-- Sidebar tabs -->
						<h3><a href="#"><?=lang('tabs')?></a></h3>
						<div>
							<ul id="publish_tab_list">
								<?php foreach($tabs as $tab => $_field_list):?>
									<li id="remove_tab_<?=$tab?>">
										<a href="#" title="menu_<?=$tab?>" class="menu_focus"><?=lang($tab_labels[$tab])?></a> 
										<a href="#<?=$tab?>" class="delete delete_tab"><img src="<?=$cp_theme_url?>images/content_custom_tab_delete.png" alt="<?=lang('delete')?>" width="19" height="18" /></a>
									</li>
								<?php endforeach;?>
							</ul>
							<p class="custom_field_add">
								<a href="#" class="add_tab_link submit submit_alt">
									<img src="<?=$cp_theme_url?>images/add_tab.png" width="22" height="14" alt="<?=lang('add_tab')?>" />&nbsp;&nbsp;
									<?=lang('add_tab')?>
								</a>
							</p>
						</div>
						
						<!-- Sidebar layouts -->
						<h3><a href="#"><?=lang('publish_layout')?></a></h3>
						<div>
							<p id="layout_groups_holder">
								<?php foreach($member_groups_laylist as $group):?>
									<label><?=form_checkbox('member_group[]', $group['group_id'], ($layout_group == $group['group_id']), 'class="toggle_member_groups"')?> <?=$group['group_title']?></label><br />
								<?php endforeach;?>
								<label><?=form_checkbox('toggle_member_groups', 'toggle_member_groups', FALSE, 'class="toggle_member_groups" id="toggle_member_groups_all"').' '.$this->lang->line('select_all')?></label>
							</p>
							<p class="custom_field_add">
								<a href="#" id="layout_group_submit" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/save_layout.png" width="12" height="14" alt="<?=lang('save_layout')?>" />&nbsp;&nbsp;<?=lang('save_layout')?></a>
								<a href="#" id="layout_group_remove" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/remove_layout.png" width="12" height="14" alt="<?=lang('remove_layout')?>" />&nbsp;&nbsp;<?=lang('remove_layout')?></a>
							</p>
							<div id="layout_preview">
								<div class="layout_preview_inner">
									<select name="layout_preview">
										<?php foreach($member_groups_laylist as $group):?>
											<option value="<?= $group['group_id'] ?>" <?= ($layout_group == $group['group_id']) ? 'selected' : '' ?>>
												<?= $group['group_title'] ?>
											</option>
										<?php endforeach;?>
									</select>
								</div> <!-- .layout_preview_inner -->
								<a href="<?= BASE.AMP.$preview_url.AMP.'layout_preview=1' ?>" id="layout_group_preview" class="submit submit_alt"><img src="<?=$cp_theme_url?>images/preview_layout.png" width="12" height="14" alt="<?=lang('preview_layout')?>" />&nbsp;&nbsp;<?=lang('preview_layout')?></a>
							</div> <!-- #layout_preview -->
						</div>
						
					</div> <!-- /tools -->
					
					<!-- Hide/Show Link -->
					<div id="showToolbarLink"><a href="#"><span><?=lang('show_toolbar')?></span>&nbsp;
						<img alt="<?=lang('hide')?>" id="hideToolbarImg" src="<?=$cp_theme_url?>images/content_hide_image_toolbar.png"  class="js_hide" />
						<img alt="<?=lang('show')?>" id="showToolbarImg" src="<?=$cp_theme_url?>images/content_show_image_toolbar.png" />
					</a></div>
				
				<?php endif;?>
				
				
				<!-- Main Content -->
				<div id="holder">
					
					<?php foreach ($tabs as $tab => $_fields):?>
						
						<div id="<?=$tab?>" class="main_tab<?=($tab == $first_tab) ? '' : ' js_hide'?>">
							
							<?php foreach($_fields as $_n):?>
								
								<?php
								// There is a rare case where a field may have been deleted but
								// still exists in a publish layout; if so, skip it
								if ( ! isset($layout_styles[$_n]) OR ! isset($field_list[$_n]))
								{
									continue;
								}
								?>
								
								<?php $style = 'width: '.$layout_styles[$_n]['width'].'; '.($layout_styles[$_n]['visible'] ? '' : 'display: none;');?>
								<div class="publish_field publish_<?=$field_list[$_n]['field_type']?>" id="hold_field_<?=$field_list[$_n]['field_id']?>" style="<?=$style?>">
									<div class="handle"></div>
									
									<label class="hide_field">
										<span>
											<img class="field_collapse" src="<?=$cp_theme_url?>images/field_<?=$layout_styles[$_n]['collapse'] ? 'collapse': 'expand'?>.png" alt="" />
											<?php if ($field_list[$_n]['field_required'] == 'y'):?><?=required()?><?php endif?>
												<?=$field_list[$_n]['field_label']?>
										</span>
									</label>
									
									
									<div id="sub_hold_field_<?=$field_list[$_n]['field_id']?>" <?=$layout_styles[$_n]['collapse'] ? 'class="js_hide"': '';?>>
										
										<?php if($field_list[$_n]['field_instructions'] != ''):?>
											<div class="instruction_text">
												<?=auto_typography('<strong>'.$this->lang->line('instructions').'</strong>'.NBS.$field_list[$_n]['field_instructions'])?>
											</div>
										<?php endif;?>
										
										<fieldset class="holder">
											<?=isset($field_list[$_n]['string_override']) ? $field_list[$_n]['string_override'] : $field_output[$_n]?>
											<?=form_error($_n)?>
										</fieldset>
										
										
										<?php if ($field_list[$_n]['has_extras']): ?>
											<p class="spellcheck markitup">

												<?php if ($field_list[$_n]['field_show_writemode'] == 'y'):?>
													<a href="#" class="write_mode_trigger" id="id_<?=$field_list[$_n]['field_id']?>" title="<?=lang('write_mode')?>"><img alt="<?=lang('write_mode')?>" width="22" height="21" src="<?=$cp_theme_url?>images/publish_write_mode.png" /></a> 
												<?php endif;?>

												<?php if ($field_list[$_n]['field_show_file_selector'] == 'y' && count($file_list) > 0):?>
													<a href="#" class="markItUpButton">
													<img class="file_manipulate js_show" src="<?=$cp_theme_url?>images/publish_format_picture.gif" alt="<?=lang('file')?>" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<?php endif;?>

												<?php if($spell_enabled && $field_list[$_n]['field_show_spellcheck'] == 'y'):?>
													<a href="#" class="spellcheck_link" id="spelltrigger_<?=(ctype_digit($field_list[$_n]['field_id']))?'field_id_':''?><?=$field_list[$_n]['field_id']?>" title="<?=lang('check_spelling')?>"><img src="<?=$cp_theme_url.'images/spell_check_icon.png'?>" style="margin-bottom: -8px;" alt="<?=lang('check_spelling')?>" /></a>
												<?php endif;?>

												<?php if($field_list[$_n]['field_show_glossary'] == 'y'):?>
													<a href="#" class="glossary_link" title="<?=lang('glossary')?>"><img src="<?=$cp_theme_url.'images/spell_check_glossary.png'?>" style="margin-bottom: -8px;" alt="<?=lang('glossary')?>" /></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<?php endif;?>

												<?php if ($smileys_enabled && $field_list[$_n]['field_show_smileys'] == 'y'):?>
													<a href="#" class="smiley_link" title="<?=lang('emoticons')?>"><?=lang('emoticons')?></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
												<?php endif;?>
												
												<?php if ($field_list[$_n]['field_show_fmt'] == 'y' && count($field_list[$_n]['field_fmt_options']) > 0):?>
													<?=lang('formatting')?>
													<?=form_dropdown('field_ft_'.$field_list[$_n]['field_id'], $field_list[$_n]['field_fmt_options'], $field_list[$_n]['field_fmt'])?> 
												<?php endif;?>

											</p>

											<?php if($spell_enabled && $field_list[$_n]['field_show_spellcheck'] == 'y'):
												echo build_spellcheck($_n);
											endif;?>

											<?php if($field_list[$_n]['field_show_glossary'] == 'y'):
												echo $glossary_items;
											endif;?>
											<?php if(isset($field_list[$_n]['smiley_table'])):
												echo $field_list[$_n]['smiley_table'];
											endif;?>
										<?php endif; ?>
										
										
									</div> <!-- /sub_hold_field -->
									
								</div> <!-- /publish_field -->
							<?php endforeach;?>
						
							<div class="insertpoint"></div>
							<div class="clear"></div>
							
						</div> <!-- /.main_tab -->
					<?php endforeach;?>
					
					<ul id="publish_submit_buttons">
						<li id="autosave_notice" style="margin-right: 7px; color: #5F6C74;"></li>
						<?php if ($show_revision_cluster == 'y'):?>
						<li><input type="submit" class="submit" name="save_revision" id="revision_button" value="<?=lang('save_revision')?>" /></li>
						<?php endif?>
						<li><input type="submit" class="submit" name="submit" id="submit_button" value="<?=lang('submit')?>" /></li>
					</ul>
					
				</div> <!-- /holder -->
			
			
			<?=form_close()?>
			
		</div> <!-- /publishPageContents -->


<!-- Modals -->
	<div id="write_mode_container">
		<div id="write_mode_close_container">
			<a href="#" class="publish_to_field close"><?=lang('wm_publish_to_field')?></a>&nbsp;
			<a href="#" class="discard_changes close"><?=lang('wm_discard_changes')?></a>
		</div>

		<div id="write_mode_writer">
			<textarea id="write_mode_textarea"></textarea>
		</div>
		<div id="write_mode_footer">
			<a href="#" class="publish_to_field close"><?=lang('wm_publish_to_field')?></a>&nbsp;
			<a href="#" class="discard_changes close"><?=lang('wm_discard_changes')?></a>
		</div>
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
	<?php endif;?>
<!-- /Modals -->
