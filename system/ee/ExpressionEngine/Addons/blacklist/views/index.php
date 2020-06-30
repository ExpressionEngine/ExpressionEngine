<div class="panel">
  <div class="panel-body">
  	<div class="tab-wrap">
  		<div class="tab-bar">
  			<div class="tab-bar__tabs">
  				<button type="button" class="tab-bar__tab js-tab-button active" rel="t-0"><?=lang('blacklist')?></button>
  				<button type="button" class="tab-bar__tab js-tab-button" rel="t-1"><?=lang('whitelist')?></button>
  				<button type="button" class="tab-bar__tab js-tab-button" rel="t-2"><?=lang('settings')?></button>
  			</div>
  		</div>
  		<?=form_open(ee('CP/URL')->make('addons/settings/blacklist/save_lists'), 'class="settings"')?>
  			<?=ee('CP/Alert')->get('lists-form')?>
  			<div class="tab t-0 tab-open">
  				<?=ee('CP/Alert')
  					->makeInline()
  					->asAttention()
  					->addToBody(lang('blacklist_desc'))
  					->render()?>
  				<fieldset>
  					<div class="field-instruct">
  						<label><?=lang('ip_address')?></label>
  						<em><?=lang('ip_address_desc')?></em>
  					</div>
  					<div class="field-control">
  						<textarea name="blacklist_ip" cols="" rows=""><?=$blacklist_ip?></textarea>
  					</div>
  				</fieldset>
  				<fieldset>
  					<div class="field-instruct">
  						<label><?=lang('user_agent')?></label>
  						<em><?=lang('user_agent_desc')?></em>
  					</div>
  					<div class="field-control">
  						<textarea name="blacklist_agent" cols="" rows=""><?=$blacklist_agent?></textarea>
  					</div>
  				</fieldset>
  				<fieldset>
  					<div class="field-instruct">
  						<label><?=lang('url')?></label>
  						<em><?=lang('url_desc')?></em>
  					</div>
  					<div class="field-control">
  						<textarea name="blacklist_url" cols="" rows=""><?=$blacklist_url?></textarea>
  					</div>
  				</fieldset>
  			<fieldset class="form-ctrls">
  				<?=cp_form_submit('btn_save_list', $save_btn_text_working)?>
  				<a class="button button--secondary" href="<?=ee('CP/URL')->make('addons/settings/blacklist/ee_blacklist', ['token' => CSRF_TOKEN])?>"><?=lang('btn_download_blacklist')?></a>
  			</fieldset>
  			</div>
  			<div class="tab t-1">
  				<?=ee('CP/Alert')
  					->makeInline()
  					->asAttention()
  					->addToBody(lang('whitelist_desc'))
  					->render()?>
  				<fieldset>
  					<div class="field-instruct">
  						<label><?=lang('ip_address')?></label>
  						<em><?=lang('ip_address_desc')?></em>
  					</div>
  					<div class="field-control">
  						<textarea name="whitelist_ip" cols="" rows=""><?=$whitelist_ip?></textarea>
  					</div>
  				</fieldset>
  				<fieldset>
  					<div class="field-instruct">
  						<label><?=lang('user_agent')?></label>
  						<em><?=lang('user_agent_desc')?></em>
  					</div>
  					<div class="field-control">
  						<textarea name="whitelist_agent" cols="" rows=""><?=$whitelist_agent?></textarea>
  					</div>
  				</fieldset>
  				<fieldset>
  					<div class="field-instruct">
  						<label><?=lang('url')?></label>
  						<em><?=lang('url_desc')?></em>
  					</div>
  					<div class="field-control">
  						<textarea name="whitelist_url" cols="" rows=""><?=$whitelist_url?></textarea>
  					</div>
  				</fieldset>
  			<fieldset class="form-ctrls">
  				<?=cp_form_submit('btn_save_list', $save_btn_text_working)?>
  				<a class="button button--secondary" href="<?=ee('CP/URL')->make('addons/settings/blacklist/ee_whitelist')?>"><?=lang('btn_download_whitelist')?></a>
  			</fieldset>
  			</div>
  		<?=form_close();?>
  		<div class="tab t-2">
  			<?php if ($allow_write_htaccess):?>
  			<div class="mb-s">
  				<?php $this->embed('ee:_shared/form')?>
  			</div>
  			<?php endif;?>
  		</div>
  	</div>
  </div>
</div>