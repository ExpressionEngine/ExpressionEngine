<ul>
    {<?=$field_name?>}
    <li{if '{<?=$field_name?>:count}' == '{<?=$field_name?>:total_rows}'} class="last"{/if}><a href="{path={segment_1}/details/{<?=$field_name?>:url_title}}">{<?=$field_name?>:title}</a></li>
    {/<?=$field_name?>}
</ul>