{layout="<?=$template_group?>/_layout"}

{if logged_out}
    {redirect="<?=$template_group?>/login"}
{/if}

<h1>Logout</h1>
<a href="{cp_url}?/cp/design/template/edit/{template_id}" target="_blank">View Template</a>

{exp:member:logout_form return="<?=$template_group?>/login"}
    <input type="submit" value="Logout">
{/exp:member:logout_form}
