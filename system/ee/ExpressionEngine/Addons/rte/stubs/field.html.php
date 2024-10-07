{if <?= $field_name ?>:has_excerpt == 'y'}
    <details>
        <summary>{<?= $field_name ?>:excerpt}</summary>
        {<?= $field_name ?>:extended}
    </details>
{if:else}
    {<?= $field_name ?>}
{/if}