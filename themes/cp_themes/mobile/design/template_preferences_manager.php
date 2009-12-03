<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/header');
}
?>
<div id="home" class="current">
    <div class="toolbar">
        <h1><?=$cp_page_title?></h1>
        <a href="<?=BASE.AMP?>C=design" class="back"><?=lang('back')?></a>
        <a class="button" id="infoButton" href="<?=BASE.AMP.'C=login'.AMP.'M=logout'?>"><?=lang('logout')?></a>
    </div>

	<?php if ($show_template_manager !== FALSE):?>

		<?=form_open('C=design'.AMP.'M=update_manager_prefs')?>

		<div class="label">
			<?=lang('template_groups', 'template_groups')?>
		</div>
		<ul>
			<li><?=$groups?></li>
		</ul>

		<div class="label">
			<?=lang('selected_templates', 'selected_templates')?>
		</div>
		<ul class="rounded">
			<?php foreach($templates as $div_id => $opts): ?>
				<li id="<?=$div_id?>" <?=$opts['active'] ? '' : 'style="display:none; padding:0;"'?>>
					<?=$opts['select']?>
				</li>
			<?php endforeach; ?>			
		</ul>

		<?php
			foreach ($headings as $num => $heading):?>
			<div class="label">
				<label><?=$heading?></label>
			</div>
			<ul>
				<li><?=$template_prefs[$num]?></li>
			</ul>

			<?php endforeach;

		?>

		<?php if ($this->session->userdata['group_id'] == 1):?>
			<p class="pad"><?=lang('security_warning')?></p>
		<?php endif;?>

		<h3 class="pad"><?=lang('template_access')?></h3>

		<?php
			$this->table->clear(); // from the last table, remove data
			$this->table->set_template($cp_pad_table_template);
			$this->table->set_heading(array(lang('member_group'), lang('can_view_template')));
			
			foreach ($template_access as $key => $val):?>
			<?php 
			$input = str_replace('&nbsp;', '', $val[1]);
			$input = str_replace('/>', '/>&nbsp;', $input);
			$input = str_replace('</label>', '</label><br />', $input);
			?>

			<div class="label">
				<label><?=$val[0]?></label>
			</div>

			<ul>
				<li>
					<?=$input?>
				</li>
			</ul>
			<?php endforeach;
		?>

		<h3 class="pad"><?=lang('no_access_select_blurb')?></h3>

		<div class="label">
			<?=lang('no_access_instructions', 'no_auth_bounce')?>
		</div>
		<ul>
			<li><?=form_dropdown('no_auth_bounce', $no_auth_bounce_options, 'null', 'id="no_auth_bounce"')?></li>
		</ul>
		<div class="label">
			<?=lang('enable_http_authentication', 'enable_http_auth')?>
		</div>
		<ul>
			<li><?=form_dropdown('enable_http_auth', $enable_http_auth_options, 'null', 'id="enable_http_auth"')?></li>
		</ul>

		<?=form_submit('template_preferences_manager', lang('update'), 'class="whiteButton"')?>

		<?=form_close()?>

	<?php endif;?>




</div>
<?php
if ($EE_view_disable !== TRUE)
{
	$this->load->view('_shared/accessories');
	$this->load->view('_shared/footer');
}

/* End of file template_preferences_manager.php */
/* Location: ./themes/cp_themes/mobile/design/template_preferences_manager.php */