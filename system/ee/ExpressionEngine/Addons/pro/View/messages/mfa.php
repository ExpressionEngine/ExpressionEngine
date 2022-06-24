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
        <label for="mfa_code"><?=lang('mfa_code')?> &ndash; <a href="<?=$reset_mfa_link?>"><?=lang('reset')?></a></label>
    </div>
    <?=form_input(array('dir' => 'ltr', 'name' => "mfa_code", 'id' => "mfa_code", 'value' => ''
    , 'maxlength' => 6, 'autocomplete' => 'off'))?>
</fieldset>
<fieldset class="last">
    <?=form_submit('submit', $btn_label, 'class="' . $btn_class . '" data-work-text="' . lang('authenticating') . '" tabindex="4" ' . $btn_disabled)?>
</fieldset>
