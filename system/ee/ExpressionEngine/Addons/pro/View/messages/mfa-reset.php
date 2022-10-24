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
    <div class="field-instruct">
        <label for="backup_mfa_code"><?=lang('backup_mfa_code')?></label>
    </div>
    <?=form_input(array('dir' => 'ltr', 'name' => "backup_mfa_code", 'id' => "backup_mfa_code", 'value' => ''
    , 'maxlength' => 16, 'autocomplete' => 'off'))?>
</fieldset>
<fieldset class="last">
    <?=form_submit('submit', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . $btn_disabled)?>
</fieldset>
