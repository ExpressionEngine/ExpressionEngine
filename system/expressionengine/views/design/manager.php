<?php extend_template('basic') ?>
		<div class="formArea">
			<div>
				<div class="templateEditorTop">
					<h2><?=lang('template_management')?></h2>

					<div class="search">
						<?=form_open('C=design'.AMP.'M=manager')?>
							<input type="text" id="template_keywords" name="template_keywords" value="<?=set_value('template_keywords')?>" placeholder="<?=lang('search_template')?>" maxlength="80" class="input" />
							<?php if ($search_terms):?>
							<div id="template_keywords_reset"><span></span></div>
							<?php endif;?>
						</form>
					</div>
					<?php if ($search_terms):?>
					<div class="templateSearchResults">
						<h3><?=lang('search_terms')?></h3>
						<div><strong class="notice"><?=$search_terms?></strong></div>
						<?=$result_count_lang?>
					</div><?php endif;?>
				</div>
			</div>


			<div id="templateGroups">
				<div class="column">
					<div class="formHeading">
						<div class="newTemplate">
						<?php if ($can_admin_templates): ?>
							<a href="<?=BASE.AMP.'C=design'.AMP.'M=new_template_group'?>"><?=lang('new_template_group')?></a>
						<?php endif; ?></div>

						<?=lang('template_groups')?>
					</div>

					<div class="groupList">
						<h3><?=lang('choose_group')?></h3>

						<ul id="sortable_template_groups">
							<?php foreach ($template_groups as $row): ?>
							<li id="template_group_<?=$row['group_id']?>"><a class="templateGroupName" href="#"><?php if ($row['is_site_default'] == 'y'):?><span class="defaultIndicator">*&nbsp;</span><?php endif?><?=$row['group_name']?></a></li>
							<?php endforeach; ?>
						</ul>
						<?php if ( ! empty($template_groups)):?>
							<div class="defaultTemplateGroup">
								<span class="defaultIndicator">*&nbsp;</span><?=lang('default_template_group')?> <span class="defaultGroupName"><?=$default_group?></span>
							</div>
							<?php if ($can_admin_templates): ?>
							<div class="exportTemplateGroup">
								 <a id="export_group" href="#" title="<?=lang('export_group')?>"><?=lang('export_group')?></a> | <a href="<?=BASE.AMP?>C=design<?=AMP?>M=export_templates" title="<?=lang('export_all')?>"><?=lang('export_all')?></a>
							</div>
							<?php endif; ?>
						<?php endif; ?>
					</div>
				</div>
			</div>

			<div id="templates">
				<div class="column">

				<?=$this->load->view('_shared/message')?>

				<?php if ($can_admin_design): ?>
					<div id="prefRowTemplate" style="display:none">
						<table class="templateTable accessTable" border="0" cellspacing="0" cellpadding="0">
							<tr>
								<th class="template_manager_template_name"><?=lang('name_of_template')?></th>
								<th class="template_manager_template_type"><?=lang('type')?></th>
								<th class="template_manager_enable_caching"><?=lang('cache_enable')?></th>
								<th class="template_manager_refresh_interval">
								    <?=lang('refresh_interval')?><br />
								    <span style="font-weight:normal; font-size:80%">(<?=lang('refresh_in_minutes')?>)</span>
								</th>
								<th class="template_manager_enable_php"><?=lang('enable_php')?></th>
								<th class="template_manager_parse_stage"><?=lang('parse_stage')?></th>
								<th class="template_manager_hits"><?=lang('hit_counter')?></th>
								<th class="protect_javascript"><?=lang('protect_javascript')?></th>
							</tr>
							<tr>
								<td><input name="template_name" class="template_name" type="text" size="15" value="" /></td>
								<td>
									<?=form_dropdown('template_type', $template_types, NULL, 'class="template_type"')?>
								</td>
								<td>
								    <?=form_dropdown('cache', array('y' => lang('yes'), 'n' => lang('no')))?>
								</td>
								<td>
										<input class="refresh" name="refresh" type="text" size="4" value="" />
								</td>
								<td>
								    <?=form_dropdown('allow_php', array('y' => lang('yes'), 'n' => lang('no')))?>
								</td>
								<td>
								    <?=form_dropdown('php_parse_location', array('i' => lang('input'), 'o' => lang('output')))?>
								</td>
								<td><input name="hits" class="hits" type="text" size="8" value="" /></td>
								<td>
									<?=form_dropdown('protect_javascript', array('y' => lang('yes'), 'n' => lang('no')))?>
								</td>
							</tr>
						</table>
					</div>
					<div id="accessRowTemplate" style="display:none">
						<table class="templateTable accessTable" border="0" cellspacing="0" cellpadding="0">
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
								<td><?=lang('yes')?> <input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_y" value="y" /> &nbsp; <?=lang('no')?> <input type="radio" name="access_<?=$id?>" id="access_<?=$id?>_n" value="n" /></td>
							</tr>
							<?php endforeach; ?>
							<tr>
								<td>Select All</td>
								<td><?=lang('yes')?> <input type="radio" name="select_all_bottom" id="select_all_bottom_y" class="ignore_radio" value="y" /> &nbsp; <?=lang('no')?> <input type="radio" name="select_all_bottom" id="select_all_bottom_n" class="ignore_radio" value="n" /></td>
							</tr>
							<tr>
								<td><?=lang('no_access_select_blurb', 'no_auth_bounce')?><div class="subtext"><?=lang('no_access_instructions')?></div></td>
								<td><?=form_dropdown('no_auth_bounce', $no_auth_bounce_options, '', 'class="no_auth_bounce"')?></td>
							</tr>
							<tr>
								<td>
									<?=lang('enable_http_authentication', 'enable_http_auth')?>
									<div class="subtext"><?=lang('enable_http_authentication_subtext')?></div>
								</td>
								<td><?=form_dropdown('enable_http_auth',  array('y' => lang('yes'), 'n' => lang('no')), '', 'class="enable_http_auth"')?></td>
							</tr>
							<?php if ($this->config->item('enable_template_routes') == 'y'): ?>
							<tr>
								<td>
									<?=lang('template_route', 'template_route')?>
									<div class="subtext"><?=lang('template_route_subtext')?></div>
								</td>
								<td><input name="template_route" class="template_route" type="text" value="" /></td>
							</tr>
							<tr>
								<td>
									<?=lang('route_required', 'route_required')?>
									<div class="subtext"><?=lang('route_required_subtext')?></div>
								</td>
								<td><?=form_dropdown('route_required',  array('y' => lang('yes'), 'n' => lang('no')), '', 'class="route_required"')?></td>
							</tr>
							<?php endif; ?>
						</table>
					</div>
				<?php endif; ?>

				<?php if ($no_results):?>
					<div class="noTemplateResultsMessage">
					<?=$no_results?>
					</div>
				<?php endif;?>

					<?php

					$this->table->set_template($table_template);
						foreach ($templates as $group):
							$temp = current($group);
							$group_id = $temp['group_id'];
							unset($temp);
							?>
							<div id="template_group_<?=$group_id?>_templates" class="templateGrouping">
							<div class="formHeading">
								<div style="margin-left:15px" class="newTemplate"><a href="<?=BASE.AMP.'C=design'.AMP.'M=new_template'.AMP.'group_id='.$group_id?>"><?=lang('create_new_template')?></a></div>

							<?php if ($can_admin_templates): ?>
								<div style="margin-left:15px" class="newTemplate"><a href="<?=BASE.AMP.'C=design'.AMP.'M=template_group_delete_confirm'.AMP.'group_id='.$group_id?>"><?=lang('delete_template_group')?></a></div>
								<div class="newTemplate"><a href="<?=BASE.AMP.'C=design'.AMP.'M=edit_template_group'.AMP.'group_id='.$group_id?>"><?=lang('edit_template_group')?></a></div>
							<?php endif; ?>
								<?=lang('name_of_template_group')?>: <?=$groups[$group_id]?>
							</div>

							<?php
							$main_table_headings = array(lang('edit_template'), lang('view'));

							if ($can_admin_design)
							{
								$main_table_headings = array_merge($main_table_headings, array(lang('access'), lang('preferences')));
							}

							$main_table_headings = array_merge($main_table_headings, array(array('data' => lang('hits'), 'style' => 'text-align:right;'), array('data' => lang('delete'), 'style' => 'font-size:11px;', 'class' => 'cellRight')));

							$this->table->set_heading($main_table_headings);

							foreach ($group as $template):

								$delete_link = ($template['template_name'] == 'index') ? '--' :
								'<a href="'.BASE.AMP.'C=design'.AMP.'M=template_delete_confirm'.AMP.
								'template_id='.$template['template_id'].'">'.lang('delete').'</a>';

								$auth_key = ($template['enable_http_auth'] == 'y') ? '<img style="float:right" class="auth_icon" src="'.$cp_theme_url.'images/key_10.gif" /> ' : FALSE;

								$main_table_data = 	array(
									    array('data' => '<a id="templateId_'.$template['template_id'].'" href="'.BASE.AMP.'C=design'.AMP.'M=edit_template'.AMP.'id='.$template['template_id'].'">'.$template['template_name'].'</a> '.$auth_key,
									 'class' => 'templateName '.$template['class']),
									'<a rel="external" href="'.$template['view_path'].'">'.lang('view').'</a>');

								if ($can_admin_design)
								{
									$main_table_data = array_merge($main_table_data, array('<a href="#" class="show_access_link" id="show_access_link_'.$template['template_id'].'">'.lang('access').'</a>',
									'<a href="#" class="show_prefs_link" id="show_prefs_link_'.$template['template_id'].'">'.lang('edit_preferences').'</a>'));
								}

								$main_table_data = array_merge($main_table_data, array(
									array('data' => $template['hits'], 'style' => 'text-align:right;', 'id'=>'hitsId_'.$template['template_id'], 'class' => 'less_important'),
									array('data' => $delete_link, 'class' => 'cellRight')));

								$this->table->add_row($main_table_data);
							endforeach;

							echo $this->table->generate();
							$this->table->clear(); ?>
							</div>
						<?php
						endforeach;
					?>
				</div>
			</div>

			<div class="clear_left">&nbsp;</div>