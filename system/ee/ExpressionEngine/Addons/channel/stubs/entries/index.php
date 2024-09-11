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
                <?php foreach (array_filter($fields, function ($field) { return $field['is_search_excerpt']; }) as $field) : ?>

                    <div>
                        <?php if($show_comments ?? false): ?>

                        {!-- Field: <?=$field['field_label']?> --}
                        {!-- Fieldtype: <?=$field['field_type']?> --}
                        {!-- Docs: <?=$field['docs_url']?> --}
                        <?php endif; ?>
                        <h4><?=$field['field_label']?></h4>
                        <?=$this->embed($field['stub'], $field);?>
                        <?php if($show_comments ?? false): ?>

                        {!-- End field: <?=$field['field_label']?> --}
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

            {/exp:channel:entries}
        </div>
    </body>
</html>
