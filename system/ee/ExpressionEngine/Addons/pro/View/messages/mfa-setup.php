<style type="text/css">
 /* fix for the larger content that we have here */
</style>
<?php
if (!empty($errors)) :
    foreach ($errors as $error) :
?>
    <div class="app-notice app-notice--inline app-notice---error">
        <div class="app-notice__tag">
            <span class="app-notice__icon"></span>
        </div>
        <div class="app-notice__content">
            <p><?=$error?></p>
        </div>
    </div>
<?php
    endforeach;
endif;
?>
<fieldset>
    <div class="field-instruct ">
        <label><?=lang('mfa_qr_code')?></label>
        <em><?=lang('mfa_qr_code_desc')?></em>
    </div>
    <div class="field-control qr-code-wrap">
        <img src="<?=$qr_link?>" width="250" height="250" alt="<?=lang('mfa_qr_code')?>" />
    </div>
</fieldset>
<fieldset>
    <div class="field-instruct ">
        <label><?=lang('mfa_backup_code')?></label>
        <em><?=lang('mfa_backup_code_desc')?></em>
    </div>
    <div class="field-control">
        <?=form_hidden('backup_mfa_code', $backup_code)?>
        <div class="app-notice app-notice--inline app-notice---important">
            <div class="app-notice__tag">
                <span class="app-notice__icon"></span>
            </div>
            <div class="app-notice__content">
                <p><?=lang('mfa_backup_warning_desc')?></p>
                <p><code><?=$backup_code?></code></p>
            </div>
        </div>
    </div>
</fieldset>

<fieldset>
    <div class="field-instruct">
        <label for="mfa_code"><?=lang('mfa_code')?></label>
        <em><?=lang('mfa_code_desc')?></em>
    </div>
    <?=form_input(array('dir' => 'ltr', 'name' => "mfa_code", 'id' => "mfa_code", 'value' => ''
    , 'maxlength' => 6, 'autocomplete' => 'off'))?>
</fieldset>
<fieldset class="last">
    <?=form_submit('submit', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . '" tabindex="4" ' . $btn_disabled)?>
</fieldset>
