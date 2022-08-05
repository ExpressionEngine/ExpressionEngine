<?php $this->extend('ee:_shared/iframe-modal', ['hide_topbar' => true]); ?>
<div class="login__content">
    <?=ee('CP/Alert')->getAllInlines()?>

    <?=form_open(ee('CP/URL')->make('login/authenticate', ['hide_closer' => 'y']), [], ['return_path' => $return_path, 'after' => ee('Security/XSS')->clean(ee()->input->get_post('after'))])?>
        <fieldset>
            <div class="field-instruct">
                <?=lang('username', 'username')?>
            </div>
            <?=form_input(array('dir' => 'ltr', 'name' => "username", 'id' => "username", 'value' => $username, 'maxlength' => USERNAME_MAX_LENGTH, 'tabindex' => 1))?>
        </fieldset>
        <fieldset<?=(($cp_session_type != 'c') ? 'class="last"' : '')?>>
            <div class="field-instruct">
                <label for="password"><?=lang('password')?></label>
            </div>
            <?=form_password(array('dir' => 'ltr', 'name' => "password", 'id' => "password", 'maxlength' => PASSWORD_MAX_LENGTH, 'autocomplete' => 'off', 'tabindex' => 2))?>
        </fieldset>
        <?php if ($cp_session_type == 'c') :?>
        <fieldset class="last">
            <label for="remember_me" class="checkbox-label">
                <input type="checkbox" class="checkbox" name="remember_me" value="1" id="remember_me" tabindex="3">
                <div class="checkbox-label__text"><?=lang('remember_me')?></div>
            </label>
        </fieldset>
        <?php endif;?>

    <?=form_close()?>
</div>

<script type="text/javascript">
setTimeout(function() {
    window.parent.postMessage({type: 'ee-pro-login-form-shown'})
}, 500)

var loginform = document.querySelector('form')

window.addEventListener('message', (event) => {
    if(event.data && event.data.type && event.data.type == 'eeproprocessreauth') {
        window.parent.postMessage({type: 'eereauthenticate-check'})
        loginform.submit()
    }
});
</script>
