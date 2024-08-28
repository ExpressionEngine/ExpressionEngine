{!-- This template will only include title and search excerpt --}
<html>
    <head>
        <title><?=$channel_title?></title>
    </head>
    <body>
        <div>
            <h1><?=$channel_title?></h1>
            {exp:channel:entries channel="<?=$channel?>" dynamic="no" paginate="bottom"}
                <h3><a href="{path=<?=$template_group?>/entry/{url_title}}">{title}</a></h3>
                <?php foreach ($fields as $field) : ?>
                    <div>
                        {!-- Field: <?=$field['field_label']?> --}
                        {!-- Fieldtype: <?=$field['field_type']?> --}
                        {!-- Docs: <?=$field['docs_url']?> --}
                        <h4><?=$field['field_label']?></h4>
                        <?=$this->embed($field['stub'], $field);?>

                        {!-- End field: <?=$field['field_label']?> --}
                    </div>
                <?php endforeach; ?>
            {/exp:channel:entries}
        </div>
    </body>
</html>
