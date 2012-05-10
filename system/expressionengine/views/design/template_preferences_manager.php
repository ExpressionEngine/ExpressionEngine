<?php extend_template('default') ?>

<?php if ($show_template_manager !== FALSE):?>

	<?=form_open('C=design'.AMP.'M=update_manager_prefs')?>
	<fieldset>
	<table style="width:100%; text-align:left;">
		<thead>
			<tr>
				<th style="width: 400px;">
					<?=lang('template_groups')?>
				</th>
				<th>
					<?=lang('selected_templates')?>
				</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td valign="top">
					<?=$groups?>
				</td>
				<td valign="top">
				<?php foreach($templates as $div_id => $opts): ?>
					<div class="default" id="<?=$div_id?>" <?=$opts['active'] ? '' : 'style="display:none; padding:0;"'?>>
						<?=$opts['select']?>
					</div>
				<?php endforeach; ?>
				</td>
			</tr>
		</tbody>
	</table>
	</fieldset>

	<?php
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(array(
				array('data' => lang('preference'), 'width' => '50%'),
				lang('setting')
			)
		);
		
		$i = 0; 
		foreach ($template_prefs as $key => $val)
		{
			$this->table->add_row(array(
					lang($headings[$i][1], $headings[$i][0]),
					$val
				)
			);	
			$i++;
		}
		echo $this->table->generate();
	?>

	<?php if ($this->session->userdata['group_id'] == 1):?>
		<p class="notice"><?=str_replace('%s', $this->cp->masked_url(
			$this->config->item('doc_url').'templates/php_templates.html'), 
			lang('security_warning'))?></p>
	<?php endif;?>

	<h3><?=lang('template_access')?></h3>

	<?php
		$this->table->clear(); // from the last table, remove data
		$this->table->set_template($cp_pad_table_template);
		$this->table->set_heading(array(lang('member_group'), lang('can_view_template')));
		echo $this->table->generate($template_access);
	?>
	<fieldset>
		<h3><?=lang('no_access_select_blurb')?></h3>

		<p><?=lang('no_access_instructions', 'no_auth_bounce').NBS.NBS.NBS.NBS.form_dropdown('no_auth_bounce', $no_auth_bounce_options, 'null', 'id="no_auth_bounce"')?></p>

		<p><?=lang('enable_http_authentication', 'enable_http_auth').NBS.NBS.NBS.NBS.form_dropdown('enable_http_auth', $enable_http_auth_options, 'null', 'id="enable_http_auth"')?></p>
	</fieldset>
	<p><?=form_submit('template_preferences_manager', lang('update'), 'class="submit"')?></p>

	<?=form_close()?>

<?php endif;?>