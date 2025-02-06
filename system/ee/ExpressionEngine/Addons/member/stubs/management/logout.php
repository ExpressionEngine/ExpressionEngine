{layout="<?=$template_group?>/_layout"}
{layout:set name="title"}Logout{/layout:set}

{if logged_out}
    {redirect="<?=$template_group?>/login"}
{/if}

<a href="{cp_url}?/cp/design/template/edit/{template_id}" target="_blank">View Template</a>

{exp:member:logout_form return="<?=$template_group?>/login"}
    <input type="submit" value="Logout">
{/exp:member:logout_form}
