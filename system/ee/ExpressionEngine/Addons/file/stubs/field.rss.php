{<?= $field_name ?>}
    {if mime_type ^= 'image/'}
        <media:group>
            <?php foreach ($dimensions as $dimension) : ?>
                <media:content medium="image" fileSize="{file_size}" url="{url:<?= $dimension ?>}" width="{width:<?= $dimension ?>}" height="{height:<?= $dimension ?>}" />
            <?php endforeach; ?>
            <media:content isDefault="true" medium="image" fileSize="{file_size}" url="{url}" width="{width}" height="{height}">
                <media:title type="plain">{title}</media:title>
                <media:description type="plain">{description}</media:description>
                <media:credit>{credit}</media:credit>
            </media:content>
        </media:group>
    {if:else}
        <media:content url="{url}" fileSize="{file_size}" type="{mime_type}" isDefault="true">
            <media:title type="plain">{title}</media:title>
            <media:description type="plain">{description}</media:description>
            <media:credit>{credit}</media:credit>
        </media:content>
    {/if}
{/<?= $field_name ?>}