{layout="<?=$template_group?>/_layout"}
{layout:set name="title"}Member Listing{/layout:set}

<a href="{cp_url}?/cp/design/template/edit/{template_id}" target="_blank">View Template</a>

<div class="result">
    {if segment_3 == 'sent'}
        <div class="info">
            <p>If this email address is associated with an account, an email containing your username has just been emailed to you.</p>
            <p>For security reasons, we cannot confirm if this email address matches an existing account.</p>
        </div>
    {/if}

    {exp:member:memberlist
        return="<?=$template_group?>/login/forgot-username"
        inline_errors="yes"
        email_subject="Your Username"
        email_template="<?=$template_group?>/email-forgot-username"
        }

        {if errors}
            <fieldset class="error">
                <legend>Errors</legend>
                {errors}
                    <p>{error}</p>
                {/errors}
            </fieldset>
        {/if}

        {form_declaration}

            <table id="memberlist" class='tableborder' border="0" cellpadding="3" cellspacing="0" style="width:100%;">
            <thead>
            <tr>
                <td class='memberlistHead' style="width:21%; font-weight: bold;">Name</td>
                <td class='memberlistHead' style="width:13%; font-weight: bold;">Forum Posts</td>
                <td class='memberlistHead' style="width:13%; font-weight: bold;">Join Date</td>
                <td class='memberlistHead' style="width:13%; font-weight: bold;">Last Visit</td>
                <td class='memberlistHead' style="width:13%; font-weight: bold;">Primary Role</td>
            </tr>
            </thead>
            <tbody>
            {member_rows}
                <tr>
                    <td class='{member_css}' style="width:20%;">
                        <span class="defaultBold"><a href="{path=<?=$template_group?>/profile/{member_id}}">{name}</a></span>
                        {if avatar}<img src="{path:avatar}" />{/if}
                    </td>
                    <td class='{member_css}'>{total_combined_posts}</td>
                    <td class='{member_css}'>{join_date  format="%m/%d/%Y"}</td>
                    <td class='{member_css}'>{last_visit  format="%m/%d/%Y"}</td>
                    <td class='{member_css}'>{role} ({member_group})</td>
                </tr>
            {/member_rows}
            </tbody>
            <tfoot>
            <tr>
                <td class='memberlistFooter' colspan="6" align='center' valign='middle'>
                    <div class="defaultSmall">
                        <b>show</b>

                        <select name='role_id' class='select'>
                            {role_options}
                        </select>

                        &nbsp; <b>sort</b>

                        <select name='order_by' class='select'>
                            {order_by_options}
                        </select>

                        &nbsp;  <b>order</b>

                        <select name='sort_order' class='select'>
                            {sort_order_options}
                        </select>

                        &nbsp; <b>rows</b>

                        <select name='row_limit' class='select'>
                            {row_limit_options}
                        </select>

                        &nbsp; <input type='submit' value='submit' class='submit' />
                    </div>
                </td>
            </tr>
            </tfoot>
            </table>

            {paginate}
            <div class="itempadbig">
                <table cellpadding="0" cellspacing="0" border="0" class="paginateBorder">
                <tr>
                    <td><div class="paginateStat">{current_page} of {total_pages}</div></td>
                    {pagination_links}
                </tr>
                </table>
            </div>
            {/paginate}

        </form>
    {/exp:member:memberlist}
</div>
