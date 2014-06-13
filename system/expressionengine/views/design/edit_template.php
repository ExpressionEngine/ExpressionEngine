<?php extend_template('wrapper', 'ee_right_nav') ?>

	<div id="templateViewLink">
		<?php $this->view('_shared/right_nav')?>
	</div>
	<div class="contents">
        <div class="heading">
            <h2 class="edit">
            	<?=lang('edit_template')?>: <?=$template_group?>/<span id="templateId_<?=$template_id?>"><?=$template_name?></span>
            	<?php enabled('ee_action_nav') && $this->view('_shared/action_nav') ?>
            </h2>
        </div>

        <div class="pageContents">
	<?php $this->view('_shared/message')?>

	<div id="templateEditor" class="formArea">
		<?php if ($message):?>
			<span class="notice"><?=$message?></span>
		<?php endif;?>

		<div id="template_create" class="pageContents">

		<div class="clear_left" id="template_details" style="margin-bottom: 0">
			<?php if ($this->config->item('save_tmpl_revisions') == 'y'):?>
			<span class="button" style="margin-top:-6px">
			<?=form_open('C=design'.AMP.'M=template_revision_history'.AMP.'tgpref='.$group_id, array('id' => 'revisions', 'name' => 'revisions', 'template_id' => $template_id, 'target' => 'Revisions'))?>

			<?=form_dropdown('revision_id', $revision_options, '', 'id="revision_id"')?>

			<?=form_submit('submit', lang('view'), 'class="submit" id="revision_button"')?>
			<?=form_close()?>
			</span>
			<?php endif; ?>
			<p>
			<?php if ($file_synced === FALSE):?>
				<?=lang('from_file')?> [<?=$last_file_edit?>] (<?=lang('save_to_sync')?>)
			<?php else:?>
				<?=lang('from_db')?> <span class="last_edit js_hide">(<?=lang('last_edit')?> <?=$edit_date?> <?=lang('by').NBS.$last_author?>)</span>
			<?php endif;?>
			</p>

		</div>

		<?=form_open('C=design'.AMP.'M=update_template'.AMP.'tgpref='.$group_id, '', array('template_id' => $template_id, 'group_id' => $group_id))?>

		<?=form_textarea(array(
			'name'	=> 'template_data',
			'id'	=> 'template_data',
			'cols'	=> '100',
			'rows'	=> $prefs['template_size'],
			'wrap'	=> 'off',
			'value' => $template_data
		));?>

		<?php if(is_array($warnings) && count($warnings)): ?>
			<?=form_hidden('warnings', 'yes')?>
			<div class="editAccordion open first">
				<h3><?=lang('template_warnings')?></h3>
				<div>
				<table class="templateTable templateEditorTable" id="templateWarningsList" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
					<tr>
						<th><?=lang('template_warnings_blurb')?></th>
						<th><?=lang('template_warnings_actions')?></th>
					</tr>

					<?php foreach($warnings as $tag_name => $info): ?>
						<tr>
							<td>
								<strong>{exp:<?=$tag_name?> &hellip;</strong><br />
								<ul>
									<?php foreach(array_unique($info['errors']) as $error): ?>
									<li><?=($error == 'tag_docs_link_error') ?
										str_replace('%s', $this->menu->generate_help_link('addons_modules', '', FALSE, $tag_name), lang($error)) :
										lang($error)?>
									</li>
									<?php endforeach; ?>
								</ul>
							</td>
							<td style="padding: 5px;">
								<p>
									<?php if (in_array('tag_install_error', $info['errors'])): ?>
									<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=module_installer'.AMP.'module='.ucfirst($tag_name)?>" rel="external" id="install_<?=$tag_name?>" class="submit install_module">Install Module</a>
									<?php endif;?>
									<a href="#" id="replace_<?=$tag_name?>" class="submit find_and_replace">Find and Replace</a>
								</p>
							</td>
						</tr>
					<?php endforeach;?>
				</table>
				</div>
			</div>
		<?php endif; ?>

		<?php if ($can_admin_design): ?>

			<div class="editAccordion">
				<h3><?=lang('preferences')?></h3>
				<div>
					<table class="templateTable templateEditorTable" id="templatePreferences" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
						<tr>
							<th><?=lang('name_of_template')?></th>
							<th><?=lang('type')?></th>
							<th><?=lang('cache_enable')?></th>
							<th><?=lang('refresh_interval')?></th>
							<th><?=lang('enable_php')?></th>
							<th><?=lang('parse_stage')?></th>
							<th><?=lang('hit_counter')?></th>
							<th><?=lang('template_size')?></th>
							<th><?=lang('protect_javascript')?></th>
						</tr>
						<tr>
							<td><input name="template_name" class="template_name" type="text" size="15" value="<?=$template_name?>" <?=($template_name == 'index') ? 'readonly="readonly"' : ''?>/></td>
							<td>
								<?=form_dropdown('template_type', $template_types, $prefs['template_type'])?>
							</td>
							<td>
								<?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')), $prefs['cache'])?>
							</td>
							<td>
								<table><tr><td style="text-align:left;"><?=lang('refresh_in_minutes')?></td></tr><tr>
									<td style="text-align:left;"><input class="refresh" name="refresh" type="text" size="4" value="<?=$prefs['refresh']?>" /></td>
								</tr></table>
							</td>
							<td>
								<?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')), $prefs['allow_php'])?>
							</td>
							<td>
								<?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')), $prefs['php_parse_location'])?>
							</td>
							<td><input name="hits" class="hits" type="text" size="8" value="<?=$prefs['hits']?>" /></td>
							<td><input name="template_size" class="template_size" type="text" size="4" value="<?=$prefs['template_size']?>" /></td>
							<td>
								<?=form_dropdown('protect_javascript', array('y' => lang('yes'), 'n' => lang('no')), $prefs['protect_javascript'])?>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="editAccordion">
				<h3><?=lang('access')?></h3>
				<div>
				<table class="templateTable templateEditorTable" id="templateAccess" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
					<tr>
						<th><?=lang('member_group')?></th>
						<th><?=lang('can_view_template')?></th>
					</tr>
					<tr>
						<td><?=lang('select_all')?></td>
						<td><?=lang('yes')?> <input type="radio" name="select_all_top" id="select_all_top_y" class="ignore_radio" value="y" /> &nbsp; <?=lang('no')?> <input type="radio" name="select_all_top" id="select_all_top_n" class="ignore_radio" value="n" /></td>
					</tr>
					<?php foreach($member_groups as $id => $group):?>
					<tr>
						<td><?=$group->group_title?></td>
						<td><?=lang('yes')?> <input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_y" value="y" <?=$access[$id] ? 'checked="checked"' : ''?> /> &nbsp; <?=lang('no')?> <input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_n" value="n" <?=$access[$id] ? '' : 'checked="checked"'?> /></td>
					</tr>
					<?php endforeach; ?>
					<tr>
						<td>Select All</td>
						<td><?=lang('yes')?> <input type="radio" name="select_all_bottom" id="select_all_bottom_y" class="ignore_radio" value="y" /> &nbsp; <?=lang('no')?> <input type="radio" name="select_all_bottom" id="select_all_bottom_n" class="ignore_radio" value="n" /></td>
					</tr>
					<tr>
						<td><?=lang('no_access_select_blurb', 'no_auth_bounce')?><div class="subtext"><?=lang('no_access_instructions')?></div></td>
						<td><?=form_dropdown('no_auth_bounce', $no_auth_bounce_options, $no_auth_bounce, 'class="no_auth_bounce"')?></td>
					</tr>
					<tr>
						<td>
							<?=lang('enable_http_authentication', 'enable_http_auth')?>
							<div class="subtext"><?=lang('enable_http_authentication_subtext')?></div>
						</td>
						<td><?=form_dropdown('enable_http_auth',  array('y' => lang('yes'), 'n' => lang('no')), $enable_http_auth, 'class="enable_http_auth"')?></td>
					</tr>
					<?php if ($this->config->item('enable_template_routes') == 'y'): ?>
					<tr>
						<td>
							<?=lang('template_route', 'template_route')?>
							<div class="subtext"><?=lang('template_route_subtext')?></div>
						</td>
						<td><input name="template_route" type="text" value="<?=$template_route?>" /></td>
					</tr>
					<tr>
						<td>
							<?=lang('route_required', 'route_required')?>
							<div class="subtext"><?=lang('route_required_subtext')?></div>
						</td>
						<td><?=form_dropdown('route_required',  array('y' => lang('yes'), 'n' => lang('no')), $route_required, 'class="route_required"')?></td>
					</tr>
					<?php endif ?>
				</table>
				</div>
			</div>

		<?php endif; ?>


			<div class="editAccordion shun">
				<h3><?=lang('template_notes')?></h3>
				<div>
					<table class="templateTable templateEditorTable" border="0" cellspacing="0" cellpadding="0" style="margin: 0;">
					<tr>
						<th><?=lang('template_notes_desc')?></th>
					</tr>
					<tr>
						<td>
							<?=form_textarea(array(
								'name'	=> 'template_notes',
								'id'	=> 'template_notes',
								'class'	=> 'notes',
								'rows'	=> '10',
								'value'	=> $template_notes
							))?>
						</td>
					</tr>
					</table>
				</div>
			</div>

			<?php if ($save_template_revision): ?>
			<p><?=form_checkbox('save_template_revision', 'y', $save_template_revision, 'id="save_template_revision"')?> &nbsp;
			<?=form_label(lang('save_template_revision'), 'save_template_revision')?></p>
			<?php endif; ?>

			<input type="hidden" name="columns" id="columns" value = "" />

			<?php if ($can_save_file): ?>
			<p><?=form_checkbox('save_template_file', 'y', $save_template_file, 'id="save_template_file"')?> &nbsp;
			<?=form_label(lang('save_template_file'), 'save_template_file')?></p>
			<?php endif; ?>

			<p><?=form_submit('update', lang('update'), 'class="submit"')?> <?=form_submit('update_and_return', lang('update_and_return'), 'class="submit"')?></p>
			<?=form_close()?>

		</div>
	</div>
    </div> <!-- pageContents -->
	</div> <!-- contents -->
</div> <!-- mainContent -->
