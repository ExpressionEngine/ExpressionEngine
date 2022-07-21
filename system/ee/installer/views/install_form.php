<div class="panel">
  <div class="panel-heading" style="text-align: center;">
    <h3><?=($header) ?: $title?></h3>
  </div>
  <div class="panel-body">
      <div class="app-notice app-notice--inline app-notice---attention">
          <div class="app-notice__tag">
              <span class="app-notice__icon"></span>
          </div>
          <div class="app-notice__content">
              <p><?=lang('install_note')?></p>
          </div>
      </div>
      <?php if (! empty($errors)) : ?>
          <div class="app-notice app-notice--inline app-notice---error">
              <div class="app-notice__tag">
                  <span class="app-notice__icon"></span>
              </div>
              <div class="app-notice__content">
                  <?php foreach ($errors as $error) : ?>
                      <p><?=$error?></p>
                  <?php endforeach ?>
              </div>
          </div>
      <?php endif ?>
      <form action="<?=$action?>" method="<?=$method?>" class="form-standard">
          <?php if (! is_null($utf8mb4_supported)) : ?>
          <input type="hidden" name="utf8mb4_supported" value="n">
          <?php endif; ?>
          <fieldset class="fieldset-required <?=form_error_class('db_hostname')?>">
        <div class="field-instruct">
          <label><?=lang('db_hostname')?></label>
                <em><?=lang('db_hostname_note')?></em>
        </div>
        <div class="field-control">
          <input name="db_hostname" type="text" autofocus="autofocus" value="<?=set_value('db_hostname', 'localhost')?>">
                <?=form_error('db_hostname')?>
        </div>
          </fieldset>
          <fieldset class="fieldset-required <?=form_error_class('db_name')?>">
        <div class="field-instruct">
          <label><?=lang('db_name')?></label>
                <em><mark><?=lang('db_name_note')?></mark></em>
        </div>
        <div class="field-control">
                <input name="db_name" type="text" value="<?=set_value('db_name')?>">
                <?=form_error('db_name')?>
        </div>
          </fieldset>
          <fieldset class="fieldset-required <?=form_error_class('db_username')?>">
        <div class="field-instruct">
          <label><?=lang('db_username')?></label>
        </div>
        <div class="field-control">
                <input name="db_username" type="text" value="<?=set_value('db_username')?>">
                <?=form_error('db_username')?>
        </div>
          </fieldset>
          <fieldset class="col-group <?=form_error_class('db_password')?>">
        <div class="field-instruct">
          <label><?=lang('db_password')?></label>
        </div>
        <div class="field-control">
                <input name="db_password" type="password" value="<?=set_value('db_password')?>">
                <?=form_error('db_password')?>
        </div>
          </fieldset>
          <fieldset class="fieldset-required <?=form_error_class('db_prefix')?>">
        <div class="field-instruct">
          <label><?=lang('db_prefix')?></label>
                <em><?=lang('db_prefix_note')?></em>
        </div>
        <div class="field-control">
                <input name="db_prefix" type="text" value="<?=set_value('db_prefix', 'exp')?>" maxlength="30">
                <?=form_error('db_prefix')?>
        </div>
          </fieldset>
          <h2><?=lang('default_theme')?></h2>
          <fieldset class="<?=form_error_class('install_default_theme')?>">
              <label class="checkbox-label">
                  <input type="checkbox" name="install_default_theme" value="y" <?=set_checkbox('install_default_theme', 'y')?>>
          <div class="checkbox-label__text"><?=lang('install_default_theme')?></div>
                  <?=form_error('install_default_theme')?>
              </label>
          </fieldset>
          <h2><?=lang('administrator_account')?></h2>
          <fieldset class="fieldset-required <?=form_error_class('email_address')?>">
        <div class="field-instruct">
          <label><?=lang('e_mail')?></label>
        </div>
        <div class="field-control">
                <input name="email_address" type="text" value="<?=set_value('email_address')?>">
                <?=form_error('email_address')?>
        </div>
          </fieldset>
          <fieldset class="fieldset-required <?=form_error_class('username')?>">
        <div class="field-instruct">
          <label><?=lang('username')?></label>
        </div>
        <div class="field-control">
                <input name="username" type="text" value="<?=set_value('username')?>" maxlength="<?=USERNAME_MAX_LENGTH?>">
                <?=form_error('username')?>
        </div>
          </fieldset>
          <fieldset class="fieldset-required <?=form_error_class('password')?>">
        <div class="field-instruct">
          <label><?=lang('password')?></label>
          <em><?=lang('password_note')?></em>
        </div>
        <div class="field-control">
                <input name="password" type="password" value="" maxlength="<?=PASSWORD_MAX_LENGTH?>">
                <?=form_error('password')?>
        </div>
          </fieldset>
          <fieldset class="options <?=form_error_class('license_agreement')?>">
              <label class="checkbox-label">
          <input type="checkbox" name="license_agreement" value="y" <?=set_checkbox('license_agreement', 'y')?>>
          <div class="checkbox-label__text"><?=lang('license_agreement')?></div>
          <div class="field-control" style="margin-left: 25px;"><?=form_error('license_agreement')?></div>
        </label>
          </fieldset>
          <fieldset class="options <?=form_error_class('share_analytics')?>">
              <label class="checkbox-label">
          <input type="checkbox" name="share_analytics" value="y" <?=set_checkbox('share_analytics', 'y')?>>
          <div class="checkbox-label__text"><?=lang('share_analytics')?></div>
          <div class="checkbox-label__text" style="font-size: 12px;"><em><?=lang('share_analytics_desc')?></em></div>
        </label>
          </fieldset>
      <div class="panel-footer" style="margin: 25px -25px -20px;">
        <div class="form-btns">
          <input class="button button--primary button--large button--block" type="submit" value="<?=lang('start_installation')?>">
        </div>
          </div>
      </form>
    </div>

</div>
