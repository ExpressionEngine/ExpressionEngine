{!-- This template will only include title and search excerpt --}

<?=$channel_title?>

{exp:channel:entries channel="<?=$channel?>" dynamic="no" paginate="bottom"}
    <h3><a href="{path=<?=$template_group?>/entry/{url_title}}">{title}</a></h3>
    <?php foreach ($fields as $field) : ?>

    {!-- Field: <?=$field['field_label']?> --}
    {!-- Fieldtype: <?=$field['field_type']?> --}
    {!-- Docs: <?=$field['docs_url']?> --}

        <?=$this->embed($field['stub'], $field);?>

    {!-- End field: <?=$field['field_label']?> --}

    <?php endforeach; ?>

{/exp:channel:entries}
