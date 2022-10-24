<?php 
if ((isset($hide_topbar) && $hide_topbar) || ee('Request')->get('hide_closer') == 'y') :
    $this->extend('ee:_shared/iframe-modal', ['hide_topbar' => true]);
else :
    $this->extend('ee:_templates/login');
?>
    <div class="login__logo">
        <?php if (ee()->config->item('login_logo')) : ?>
        <img src="<?=ee()->config->item('login_logo')?>" alt="Powered by ExpressionEngine&reg;">
        <?php else :
            $this->embed('ee:_shared/ee-logo');
        endif; ?>
    </div>
<?php endif; ?>
<div class="login__content">
    <?=ee('CP/Alert')->getAllInlines()?>

    <?=form_open(ee('CP/URL')->make('login/mfa', ['hide_closer' => ee('Security/XSS')->clean(ee('Request')->get('hide_closer'))]), [], ['return_path' => $return_path, 'after' => ee('Security/XSS')->clean(ee()->input->get_post('after'))])?>
        <fieldset>
            <div class="field-instruct">
                <label for="mfa_code"><?=lang('mfa_code')?> &ndash; <a href="<?=ee('CP/URL')->make('/login/mfa_reset')?>"><?=lang('reset')?></a></label>
            </div>
            <?=form_input(array('dir' => 'ltr', 'name' => "mfa_code", 'id' => "mfa_code", 'value' => ''
            , 'maxlength' => 6, 'autocomplete' => 'off'))?>
        </fieldset>
        <fieldset class="last">
            <?=form_submit('submit', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . '" tabindex="4" ' . $btn_disabled)?>
        </fieldset>


    <?=form_close()?>
</div>
