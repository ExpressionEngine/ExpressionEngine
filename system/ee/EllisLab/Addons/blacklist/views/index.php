<?php if ($allow_write_htaccess):?>
<div class="mb">
	<?php $this->embed('ee:_shared/form')?>
</div>
<?php endif;?>

<div class="box">
	<h1><?=lang('lists')?></h1>
	<div class="tab-wrap">
		<ul class="tabs">
			<li><a class="act" href="" rel="t-0"><?=lang('blacklist')?></a></li>
			<li><a href="" rel="t-1"><?=lang('whitelist')?></a></li>
		</ul>
		<?=form_open(ee('CP/URL')->make('addons/settings/blacklist/save_lists'), 'class="settings"')?>
			<?=ee('CP/Alert')->get('lists-form')?>
			<div class="tab t-0 tab-open">
				<?=ee('CP/Alert')
					->makeInline()
					->asAttention()
					->addToBody(lang('blacklist_desc'))
					->render()?>
				<br>
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
				<a class="btn" href="<?=ee('CP/URL')->make('addons/settings/blacklist/ee_blacklist', ['token' => CSRF_TOKEN])?>"><?=lang('btn_download_blacklist')?></a>
			</fieldset>
			</div>
			<div class="tab t-1">
				<?=ee('CP/Alert')
					->makeInline()
					->asAttention()
					->addToBody(lang('whitelist_desc'))
					->render()?>
				<br>
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
				<a class="btn" href="<?=ee('CP/URL')->make('addons/settings/blacklist/ee_whitelist')?>"><?=lang('btn_download_whitelist')?></a>
			</fieldset>
			</div>
		<?=form_close();?>
	</div>
</div>
