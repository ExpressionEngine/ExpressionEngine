{<?=$field_name?>}
    {if mime_type ^= 'image/'}
        <img class="mask mask-hexagon-2" src="{url}" width="{width}" height="{height}" alt="{title}">
    {if:else}
        <a href="{url}" class="btn btn-lg" target="_blank">View {title}</a></b>
    {/if}
{/<?=$field_name?>}