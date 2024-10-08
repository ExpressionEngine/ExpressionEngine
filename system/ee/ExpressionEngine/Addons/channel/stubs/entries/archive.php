<?php if($show_comments ?? false): ?>
{!-- This template will only include title and search excerpt --}
{!-- URL Format template_group/archive/YEAR/MONTH --}
<?php endif; ?>
<html>
<head>
    <title><?= $channel_title ?> Archives</title>
</head>
<body>
    <div>
        <h1><?= $channel_title ?> Archives</h1>
        {if segment_3 == ''}
            {exp:channel:month_links channel="<?= $channel ?>" limit="24"}
                {year_heading}
                    <h2><a href="{path='<?= $template_group ?>/archive/{year}'}">{year}</a></h2>
                {/year_heading}
                <a href="{path='<?= $template_group ?>/archive/{year}/{month_num}'}">{month}</a><br/>
            {/exp:channel:month_links}
        {if:else}
            <h2>{segment_3}{if segment_4 != ''}/{segment_4}{/if}</h2>
            {exp:channel:entries channel="<?= $channel ?>" dynamic="no" limit="10" paginate="bottom" year="{segment_3}" {if segment_4 != ''}month="{segment_4}"{/if}}
                {if no_results}
                <p>No entries.</p>
                {/if}
                <div>
                    <span>{entry_date format="%Y/%m/%d"}</span>
                    <h3><a href="{path='<?= $template_group ?>/entry/{url_title}}">{title}</a></h3>
                    <?php foreach (array_filter($fields, function ($field) { return $field['is_search_excerpt']; }) as $field) : ?>
                        <?php $field['modifiers'] = ['limit' => ['characters' => 120]]; ?>
                        <?php if($show_comments ?? false): ?>

                            {!-- Field: <?= $field['field_label'] ?> --}
                            {!-- Fieldtype: <?= $field['field_type'] ?> --}
                            {!-- Docs: <?= $field['docs_url'] ?> --}
                        <?php endif; ?>
                        <?= $this->embed($field['stub'], $field); ?>

                        <?php if($show_comments ?? false): ?>

                        {!-- End field: <?= $field['field_label'] ?> --}
                        <?php endif; ?>

                    <?php endforeach; ?>

                </div>

                {paginate}
                    <p>Page {current_page} of {total_pages} pages {pagination_links}</p>
                {/paginate}
            {/exp:channel:entries}
        {/if}
    </div>
</body>
</html>