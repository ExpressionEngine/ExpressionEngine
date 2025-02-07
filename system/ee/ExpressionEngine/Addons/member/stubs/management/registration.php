{layout="<?=$template_group?>/_layout"}
{layout:set name="title"}Member Registration{/layout:set}

<a href="{cp_url}?/cp/design/template/edit/{template_id}" target="_blank">View Template</a>

{if logged_in}
    {redirect="<?=$template_group?>/index"}
{/if}

<div class="result">
{if last_segment == "success"}
    <h4>Account Created!</h4>

    {if logged_in}
        <p>We took the liberty of logging you in already!</p>
        <h5>Go forth and do stuff!</h5>
    {if:else}
        <p>You can now <a href="{path=<?=$template_group?>/login}">login</a>.</p>
        <p>Depending on your member activation settings you will receive an email to confirm your registration.</p>
    {/if}
{if:elseif logged_in}
    <p>You are already registered and logged in.</p>

    <p><a class="btn btn-primary" href="{path=<?=$template_group?>/profile}">Go to Profile</a> &nbsp;&nbsp;&nbsp; <a class="btn btn-sm btn-warning" href="{path=logout}">Logout</a></p>
{if:else}
    {exp:member:registration_form
        return="<?=$template_group?>/registration/success"
        inline_errors="yes"
        }

        {!-- You can display all errors at the top of the page or use the individual field {error:} tags shown later --}
        {!--
        {if errors}
            <fieldset class="error">
                <legend>Errors</legend>
                {errors}
                    <p>{error_key}: {error}</p>
                {/errors}
            </fieldset>
        {/if}
        --}

        <p>* Required fields</p>
        <fieldset>
            <h4>Login details</h4>

            <p>
                <label for="username">Username*:</label><br />
                <input type="text" name="username" id="username" value="{if username}{username}{/if}"/><br />
                {if error:username}
                    <span class="error">{error:username}</span>
                {/if}
            </p>

            <p>
                <label for="email">Email*:</label><br />
                <input type="text" name="email" id="email" value="{if email}{email}{/if}"/><br />
                {if error:email}
                    <span class="error">{error:email}</span>
                {/if}
            </p>

            <p>
                <label for="password">Password*:</label><br />
                <input type="password" name="password" id="password" value="{if password}{password}{/if}"/>
                {if error:password}
                    <span class="error">{error:password}</span>
                {/if}
            </p>

            <p>
                <label for="password_confirm">Confirm password*:</label><br />
                <input type="password" name="password_confirm" id="password_confirm" value="{if password_confirm}{password_confirm}{/if}"/>
                {if error:password_confirm}
                    <span class="error">{error:password_confirm}</span>
                {/if}
            </p>

            <p>
                <label for="terms_of_service">Terms of service:</label><br />
                <div>All messages posted at this site express the views of the author, and do not necessarily reflect the views of the owners and administrators
                    of this site. By registering at this site you agree not to post any messages that are obscene, vulgar, slanderous, hateful, threatening, or that violate any laws. We will
                    permanently ban all users who do so. We reserve the right to remove, edit, or move any messages for any reason.</div>
            </p>

            <p>
                <label><input type="checkbox" name="accept_terms" value="y" {if accept_terms == 'y'}checked="checked"{/if} /> I accept these terms</label>
                {if error:accept_terms}
                    <span class="error">{error:accept_terms}</span>
                {/if}
            </p>

            <?php foreach (array_filter($fields, function ($field) { return ($field['show_registration'] === 'y'); }) as $field) : ?>

                <p>
                    <?php if($show_comments ?? false): ?>

                    {!-- Field: <?=$field['field_label']?> --}
                    {!-- Fieldtype: <?=$field['field_type']?> --}
                    {!-- Docs: <?=$field['docs_url']?> --}
                    <?php endif; ?>
                    <label for="<?=$field['field_name']?>" ><?=$field['field_label']?></label><br/>
                    {field:<?=$field['field_name']?>}
                    {if error:<?=$field['field_name']?>}
                        <span class="error">{error:<?=$field['field_name']?>}</span>
                    {/if}
                    <?php if($show_comments ?? false): ?>

                    {!-- End field: <?=$field['field_label']?> --}
                    <?php endif; ?>
                </p>

            <?php endforeach; ?>

            {if captcha}
            <p>
                <label for="captcha">Please enter the word you see in the image below:</label><br/>
                {captcha}<br/>
                <input type="text" id="captcha" name="captcha" value="" size="20" maxlength="20" style="width:140px;"/>
                {if error:captcha}
                    <span class="error">{error:captcha}</span>
                {/if}
            </p>
            {/if}
        </fieldset>

        <input type="submit" value="Register" class="btn btn-primary" />
    {/exp:member:registration_form}
{/if}
</div>
