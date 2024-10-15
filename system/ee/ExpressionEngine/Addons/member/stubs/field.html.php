<ul>
    {<?=$field_name?>}
    <li{if '{<?=$field_name?>:count}' == '{<?=$field_name?>:total_rows}'} class="last"{/if}><a href="{path={segment_1}/member/{<?=$field_name?>:username}}">{<?=$field_name?>:screen_name}</a></li>
    {/<?=$field_name?>}
</ul>