<?php $this->extend('ee:_templates/login'); ?>
    <div class="login__logo">
        <?php if (ee()->config->item('login_logo')) : ?>
        <img src="<?=ee()->config->item('login_logo')?>" alt="Powered by ExpressionEngine&reg;">
        <?php else :
            $this->embed('ee:_shared/ee-logo');
        endif; ?>
    </div>

<div class="login__content">
    <?=ee('CP/Alert')->getAllInlines()?>

    <?=form_open(ee('CP/URL')->make('login/mfa_reset'))?>
        <fieldset>
            <div class="field-instruct">
                <label for="backup_mfa_code"><?=lang('backup_mfa_code')?></label>
            </div>
            <?=form_input(array('dir' => 'ltr', 'name' => "backup_mfa_code", 'id' => "backup_mfa_code", 'value' => ''
            , 'maxlength' => 16, 'autocomplete' => 'off'))?>
        </fieldset>
        <fieldset class="last">
            <?=form_submit('reset', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . '" tabindex="4" ' . $btn_disabled)?>
        </fieldset>


    <?=form_close()?>
</div>
