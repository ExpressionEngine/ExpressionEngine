{layout="<?=$template_group?>/_layout"}

{if logged_out}
    {redirect="<?=$template_group?>/login"}
{/if}

<h1>Logout</h1>

{exp:member:logout_form return="<?=$template_group?>/login"}
    <input type="submit" value="Logout">
{/exp:member:logout_form}
