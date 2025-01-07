{exp:comment:form channel="<?=$channel?>" inline_errors="yes"}
    {if errors}
        <ul>
        {errors}
            <li>{error}</li>
        {/errors}
        </ul>
    {/if}

    {if logged_out}
        <label for="name">Name:</label> <input type="text" name="name" value="{name}" size="50" /><br />
        <label for="email">Email:</label> <input type="text" name="email" value="{email}" size="50" /><br />
        <label for="location">Location:</label> <input type="text" name="location" value="{location}" size="50" /><br />
        <label for="url">URL:</label> <input type="text" name="url" value="{url}" size="50" /><br />
    {/if}

    <label for="comment">Comment:</label><br />
    <textarea name="comment" cols="70" rows="10">{comment}</textarea>
    <label><input type="checkbox" name="save_info" value="yes" {save_info} /> Remember my personal information</label><br />
    <label><input type="checkbox" name="notify_me" value="yes" {notify_me} /> Notify me of follow-up comments?</label><br />

    {if captcha}
        <label for="captcha">Please enter the word you see in the image below:</label><br />
        <p>{captcha}<br />
        <input type="text" name="captcha" value="{captcha_word}" maxlength="20" /></p>
    {/if}

    <input type="submit" name="submit" value="Submit" />
    <input type="submit" name="preview" value="Preview" />

    {!-- required to prevent EE from outputting form if commenting is disabled or expired --}
    {if comments_disabled}Comments on this entry are currently disabled.{/if}
    {if comments_expired}Commenting on this entry has expired.{/if}
{/exp:comment:form}