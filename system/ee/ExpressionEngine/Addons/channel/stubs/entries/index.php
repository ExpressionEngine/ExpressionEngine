{!-- This template will only include title and search excerpt --}
<html>
    <head>
        <title><?=$channel_title?></title>
    </head>
    <body>
        <div>
            <h1><?=$channel_title?></h1>
            {exp:channel:entries channel="<?=$channel?>" dynamic="no" paginate="bottom"}
                <p><a href="{path=<?=$template_group?>/entry/{url_title}}">{title}</a></p>
                <?php foreach (array_filter($fields, function ($field) { return $field['is_search_excerpt']; }) as $field) : ?>
                    <div>
                        {!-- Fieldtype: <?=$field['field_type']?> --}
                        {!-- Docs: <?=$field['docs_url']?> --}
                        <h6><?=$field['field_label']?></h6>
                        <?=$this->embed($field['stub'], $field);?>
                    </div>
                <?php endforeach; ?>
            {/exp:channel:entries}
        </div>
    </body>
</html>