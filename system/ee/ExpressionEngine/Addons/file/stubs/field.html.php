{<?=$field_name?>}
    {if mime_type ^= 'image/'}
        <picture>
            <?php foreach ($dimensions as $dimension) : ?>
            <source type="{mime_type}" srcset="{url:<?=$dimension?>}" width="{width:<?=$dimension?>}" height="{height:<?=$dimension?>}" alt="{title}">
            <?php endforeach; ?>

            <img src="{url}" width="{width}" height="{height}" alt="{title}">
        </picture>
    {if:else}
        <b><a href="{url}" target="_blank">View {title}</a></b>
    {/if}
    <br>Credit: {credit}
    <br>Location: {location}
    <br>File Name: {file_name}
    <br>File Size: {file_size}
    <br>Description: {description}
    <br>Upload Directory: {directory_title} (<a href="{path}">#{directory_id}</a>)
    <br>Upload Date: {upload_date format="%Y %m %d"}
    <br>Modified Date: {modified_date format="%Y %m %d"}
{/<?=$field_name?>}