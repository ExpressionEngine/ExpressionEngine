<div class="panel">
  <div class="panel-body">
      <div class="tab-wrap">
          <div class="tab-bar">
              <div class="tab-bar__tabs">
                  <button type="button" class="tab-bar__tab js-tab-button active" rel="t-0"><?=lang('blockedlist')?></button>
                  <button type="button" class="tab-bar__tab js-tab-button" rel="t-1"><?=lang('allowedlist')?></button>
                  <button type="button" class="tab-bar__tab js-tab-button" rel="t-2"><?=lang('settings')?></button>
              </div>
          </div>
          <?=form_open(ee('CP/URL')->make('addons/settings/block_and_allow/save_lists'), 'class="settings"')?>
              <?=ee('CP/Alert')->get('lists-form')?>
              <div class="tab t-0 tab-open">
                  <?=ee('CP/Alert')
                      ->makeInline()
                      ->asAttention()
                      ->addToBody(lang('blockedlist_desc'))
                      ->render()?>
                  <fieldset>
                      <div class="field-instruct">
                          <label><?=lang('ip_address')?></label>
                          <em><?=lang('ip_address_desc')?></em>
                      </div>
                      <div class="field-control">
                          <textarea name="blockedlist_ip" cols="" rows=""><?=$blockedlist_ip?></textarea>
                      </div>
                  </fieldset>
                  <fieldset>
                      <div class="field-instruct">
                          <label><?=lang('user_agent')?></label>
                          <em><?=lang('user_agent_desc')?></em>
                      </div>
                      <div class="field-control">
                          <textarea name="blockedlist_agent" cols="" rows=""><?=$blockedlist_agent?></textarea>
                      </div>
                  </fieldset>
                  <fieldset>
                      <div class="field-instruct">
                          <label><?=lang('url')?></label>
                          <em><?=lang('url_desc')?></em>
                      </div>
                      <div class="field-control">
                          <textarea name="blockedlist_url" cols="" rows=""><?=$blockedlist_url?></textarea>
                      </div>
                  </fieldset>
              <fieldset class="form-ctrls">
                  <?=cp_form_submit('btn_save_list', $save_btn_text_working)?>
                  <a class="button button--secondary" href="<?=ee('CP/URL')->make('addons/settings/block_and_allow/ee_blockedlist', ['token' => CSRF_TOKEN])?>"><?=lang('btn_download_blockedlist')?></a>
              </fieldset>
              </div>
              <div class="tab t-1">
                  <?=ee('CP/Alert')
                      ->makeInline()
                      ->asAttention()
                      ->addToBody(lang('allowedlist_desc'))
                      ->render()?>
                  <fieldset>
                      <div class="field-instruct">
                          <label><?=lang('ip_address')?></label>
                          <em><?=lang('ip_address_desc')?></em>
                      </div>
                      <div class="field-control">
                          <textarea name="allowedlist_ip" cols="" rows=""><?=$allowedlist_ip?></textarea>
                      </div>
                  </fieldset>
                  <fieldset>
                      <div class="field-instruct">
                          <label><?=lang('user_agent')?></label>
                          <em><?=lang('user_agent_desc')?></em>
                      </div>
                      <div class="field-control">
                          <textarea name="allowedlist_agent" cols="" rows=""><?=$allowedlist_agent?></textarea>
                      </div>
                  </fieldset>
                  <fieldset>
                      <div class="field-instruct">
                          <label><?=lang('url')?></label>
                          <em><?=lang('url_desc')?></em>
                      </div>
                      <div class="field-control">
                          <textarea name="allowedlist_url" cols="" rows=""><?=$allowedlist_url?></textarea>
                      </div>
                  </fieldset>
              <fieldset class="form-ctrls">
                  <?=cp_form_submit('btn_save_list', $save_btn_text_working)?>
                  <a class="button button--secondary" href="<?=ee('CP/URL')->make('addons/settings/block_and_allow/ee_allowedlist', ['token' => CSRF_TOKEN])?>"><?=lang('btn_download_allowedlist')?></a>
              </fieldset>
              </div>
          <?=form_close();?>
          <div class="tab t-2">
              <?php if ($allow_write_htaccess) :?>
              <div class="mb-s">
                  <?php $this->embed('ee:_shared/form')?>
              </div>
              <?php endif;?>
          </div>
      </div>
  </div>
</div>
