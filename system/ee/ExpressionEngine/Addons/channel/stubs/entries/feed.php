<?='<?xml version="1.0"?>'."\n"?>
<rss version="2.0" xmlns:media="http://search.yahoo.com/mrss/">
    <channel>
        <title>{site_name}</title>
        <link>{site_url}</link>
        <description>{site_description}</description>
        {exp:channel:entries channel="<?= $channel ?>" dynamic="no"}
        <item>
            <title>{title}</title>
            <link>{path='<?=$template_group?>/entry/{url_title}'}</link>
            <pubDate>{entry_date format="%r"}</pubDate>
            {categories}
                <category>{category_name}</category>
            {/categories}
            <?php foreach (array_filter($fields, function ($field) { return $field['is_search_excerpt']; }) as $field) : ?>
                <?php $field['modifiers'] = ['limit' => ['characters' => 120]]; ?>
                <description>
                    <?=$this->embed($field['stub'], $field);?>
                </description>
            <?php endforeach; ?>

        </item>
        {/exp:channel:entries}
    </channel>
</rss>