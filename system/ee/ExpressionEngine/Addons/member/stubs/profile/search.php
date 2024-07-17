{layout="<?=$template_group?>/_layout"}
<h1>Search for Members</h1>

<div class="result">

    {exp:member:member_search return="<?=$template_group?>/index"}
        <input type="text" name="search_keywords_1" />
        <select name='search_field_1' class='select' >
            <option value='screen_name'>Search Field</option>
            <option value='screen_name'>Screen Name</option>
            <option value='email'>Email Address</option>
            {custom_profile_field_options}
        </select>

        <select name='search_group_id' class='select' >
            {group_id_options}
        </select>

        <div class="itempadbig">&nbsp; <input type='submit' value='search' class='submit' /></div>

    {/exp:member:member_search}
</div>
