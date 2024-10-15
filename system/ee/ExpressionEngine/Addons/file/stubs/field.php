{<?= $field_name ?>}
    Title: {title}
    URL: {url}
    Mime Type: {mime_type}
    Credit: {credit}
    Location: {location}
    File Name: {file_name}
    File Size: {file_size}
    Description: {description}
    Upload Directory: {directory_title}
    Upload Date: {upload_date format="%Y %m %d"}
    Modified Date: {modified_date format="%Y %m %d"}

    {if mime_type ^= 'image/'}
        Width: {width}
        Height: {height}
        <?php foreach ($dimensions as $dimension) : ?>
            <?= $dimension ?> URL: {url:<?= $dimension ?>}
            <?= $dimension ?> Width: {width:<?= $dimension ?>}
            <?= $dimension ?> Height: {height:<?= $dimension ?>}
        <?php endforeach; ?>

    {/if}
{/<?= $field_name ?>}
