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
        <label for="password_confirm"><?=lang('existing_password')?></label>
        <em><?=lang('existing_password_mfa_reset_desc')?></em>
    </div>
    <?=form_password(array('dir' => 'ltr', 'name' => "password_confirm", 'id' => "password_confirm", 'value' => ''
    , 'autocomplete' => 'off'))?>
</fieldset>
<fieldset class="last">
    <?=form_submit('submit', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . $btn_disabled)?>
</fieldset>
