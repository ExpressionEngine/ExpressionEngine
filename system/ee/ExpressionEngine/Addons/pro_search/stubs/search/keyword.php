{exp:pro_search:form required="keywords" result_page="<?=$template_group?>/keyword"}
    <fieldset>
        <input type="search" name="keywords" placeholder="Search this site...">
        <button type="submit">Go</button>
        {if pro_search_keywords_missing}<em>Keywords are required</em>{/if}
    </fieldset>
{/exp:pro_search:form}

{exp:pro_search:collections}
    {if no_results}
        <p>No search collections are available at the moment. To see results, please create a collection and index it first.</p>
    {/if}
{/exp:pro_search:collections}

{exp:pro_search:results
    query="{segment_3}"
    keywords:lang="en"
    keywords:inflect="yes"
    limit="10"
}
    {if count == 1}
    <p>
        Searched for <strong>{pro_search_keywords}</strong>.
        Search results: <strong>{absolute_results}</strong>
    </p>
    {/if}

    <h3>{title}</h3>
    <p>Found in {pro_search_collection_label}, with a score of {pro_search_score}</p>
    <p>{pro_search_excerpt}</p>
    <p><a href="{auto_path}">{auto_path}</a></p>

    {paginate}
        {current_page}/{total_pages} | {pagination_links}
    {/paginate}

    {if pro_search_no_results}
    <p>
        No results for “{pro_search_keywords}”.
        {exp:pro_search:suggestions keywords="{pro_search_keywords}" keywords:lang="en" limit="2"}
        {if suggestion_count == 1}Did you mean{/if}
        <a href="{pro_search:url keywords=" {suggestion}"}">{suggestion}</a>{if suggestion_count != total_suggestions}&nbsp;or&nbsp;{if:else}?{/if}
        {if no_suggestions}Check your spelling or try a different search term.{/if}
        {/exp:pro_search:suggestions}
    </p>
    {/if}
{/exp:pro_search:results}
