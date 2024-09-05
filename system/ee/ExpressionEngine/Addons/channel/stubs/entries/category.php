{!-- This template will only include title and search excerpt --}
{!-- URL Format template_group/category/CATEGORY_URL_TITLE --}


<html>

<head>
    <title><?= $channel_title ?></title>
</head>

<body>
    <div>
        {!-- If a category url_title is not provided list all of the categories --}
        {if segment_3 == ''}
        <h1><?= $channel_title ?> Categories</h1>
        {exp:channel:categories channel="<?= $channel ?>"}
            {if no_results}
            <p>No categories.</p>
            {/if}
        <div>
            <a href="{path='<?= $template_group ?>/category/{category_url_title}'}">{category_name}</a>
            {if category_description}{category_description}{/if}
        </div>
        {/exp:channel:categories}
        {!-- Otherwise show entries for this category --}
        {if:else}
        {exp:channel:category_heading channel="<?= $channel ?>" category_url_title="{segment_3}" <?=(strpos($channel, '|') !== false) ? 'relaxed_categories="yes"' : ''?>}
            <h1>{category_name}</h1>
            {if category_description}
            <p>{category_description}</p>
            {/if}

            {exp:channel:entries channel="<?= $channel ?>" dynamic="no" limit="10" paginate="bottom" category="{category_id}"}
                {if no_results}<p>No entries for this category.</p>{/if}
            <h3><a href="{path=<?= $template_group ?>/entry/{url_title}}">{title}</a></h3>
            <?php foreach (array_filter($fields, function ($field) { return $field['is_search_excerpt']; }) as $field) : ?>
                <?php $field['modifiers'] = ['limit' => ['characters' => 120]]; ?>

                {!-- Field: <?= $field['field_label'] ?> --}
                {!-- Fieldtype: <?= $field['field_type'] ?> --}
                {!-- Docs: <?= $field['docs_url'] ?> --}
                <?= $this->embed($field['stub'], $field); ?>

                {!-- End field: <?= $field['field_label'] ?> --}

            <?php endforeach; ?>

            {/exp:channel:entries}
        {/exp:channel:category_heading}
        {/if}
    </div>
</body>

</html>
