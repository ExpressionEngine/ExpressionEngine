<?php if ($allow_write_htaccess):?>
<div class="box mb">
	<?php $this->view('ee:_shared/form')?>
</div>
<?php endif;?>

<div class="box">
	<h1><?=lang('lists')?></h1>
	<div class="tab-wrap">
		<ul class="tabs">
			<li><a class="act" href="" rel="t-0"><?=lang('blacklist')?></a></li>
			<li><a href="" rel="t-1"><?=lang('whitelist')?></a></li>
		</ul>
		<?=form_open(ee('CP/URL', 'addons/settings/blacklist/save_lists'), 'class="settings"')?>
			<?=ee('Alert')->get('lists-form')?>
			<div class="tab t-0 tab-open">
				<div class="alert inline warn">
					<p><?=lang('blacklist_desc')?></p>
				</div>
				<fieldset class="col-group">
					<div class="setting-txt col w-8">
						<h3><?=lang('ip_address')?></h3>
						<em><?=lang('ip_address_desc')?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea name="blacklist_ip" cols="" rows=""><?=$blacklist_ip?></textarea>
					</div>
				</fieldset>
				<fieldset class="col-group">
					<div class="setting-txt col w-8">
						<h3><?=lang('user_agent')?></h3>
						<em><?=lang('user_agent_desc')?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea name="blacklist_agent" cols="" rows=""><?=$blacklist_agent?></textarea>
					</div>
				</fieldset>
				<fieldset class="col-group last">
					<div class="setting-txt col w-8">
						<h3><?=lang('url')?></h3>
						<em><?=lang('url_desc')?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea name="blacklist_url" cols="" rows=""><?=$blacklist_url?></textarea>
					</div>
				</fieldset>
			<fieldset class="form-ctrls">
				<?=cp_form_submit('btn_save_list', $save_btn_text_working)?>
				<a class="btn" href="<?=ee('CP/URL', 'addons/settings/blacklist/ee_blacklist')?>"><?=lang('btn_download_blacklist')?></a>
			</fieldset>
			</div>
			<div class="tab t-1">
				<div class="alert inline warn">
					<p><?=lang('whitelist_desc')?></p>
				</div>
				<fieldset class="col-group">
					<div class="setting-txt col w-8">
						<h3><?=lang('ip_address')?></h3>
						<em><?=lang('ip_address_desc')?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea name="whitelist_ip" cols="" rows=""><?=$whitelist_ip?></textarea>
					</div>
				</fieldset>
				<fieldset class="col-group">
					<div class="setting-txt col w-8">
						<h3><?=lang('user_agent')?></h3>
						<em><?=lang('user_agent_desc')?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea name="whitelist_agent" cols="" rows=""><?=$whitelist_agent?></textarea>
					</div>
				</fieldset>
				<fieldset class="col-group last">
					<div class="setting-txt col w-8">
						<h3><?=lang('url')?></h3>
						<em><?=lang('url_desc')?></em>
					</div>
					<div class="setting-field col w-8 last">
						<textarea name="whitelist_url" cols="" rows=""><?=$whitelist_url?></textarea>
					</div>
				</fieldset>
			<fieldset class="form-ctrls">
				<?=cp_form_submit('btn_save_list', $save_btn_text_working)?>
				<a class="btn" href="<?=ee('CP/URL', 'addons/settings/blacklist/ee_whitelist')?>"><?=lang('btn_download_whitelist')?></a>
			</fieldset>
			</div>
		<?=form_close();?>
	</div>
</div>



<?php /*
<ul class="bullets" style="margin-bottom:15px">
	<li style="width:25%; float: left;"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=view_blacklist'?>"><?=lang('ref_view_blacklist')?></a></li>
	<li>
	<?php if ($license_number != ''):?>
		<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=ee_blacklist'?>"><?=lang('ee_blacklist')?></a>
	<?php else:?>
		<span class="notice"><?=lang('ee_blacklist')?> (<?=lang('requires_license_number')?>)</span>
	<?php endif;?>
	</li>
	<li style="width:25%; float: left;"><a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=view_whitelist'?>"><?=lang('ref_view_whitelist')?></a></li>
	<li>
	<?php if ($license_number != ''):?>
		<a href="<?=BASE.AMP.'C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=ee_whitelist'?>"><?=lang('ee_whitelist')?></a>
	<?php else:?>
		<span class="notice"><?=lang('ee_whitelist')?> (<?=lang('requires_license_number')?>)</span>
	<?php endif;?>
	</li>
</ul>

<?php if ($allow_write_htaccess):?>

	<h3><?=lang('write_htaccess_file')?></h3>

	<?=form_open('C=addons_modules'.AMP.'M=show_module_cp'.AMP.'module=blacklist'.AMP.'method=save_htaccess_path')?>

	<p>
		<?=lang('htaccess_server_path', 'htaccess_path')?>
		<?=form_input('htaccess_path', set_value('htaccess_path', $htaccess_path), 'size="35"')?>
		<?=form_error('htaccess_path')?>
	</p>

	<p>
		<?=form_submit(array('name' => 'submit', 'value' => lang('submit'), 'class' => 'submit'))?>
	</p>

	<?=form_close()?>

<?php endif;?>
*/?>