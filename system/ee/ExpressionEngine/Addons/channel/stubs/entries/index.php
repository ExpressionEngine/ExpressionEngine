{!-- This template will only include title and search excerpt --}
<html>
    <head>
        <title>{channel_name}</title>
    </head>
    <body>
        <div>
            <h1>{channel_name}</h1>
            {exp:channel:entries channel="<?=$channel?>" dynamic="no" paginate="bottom"}
                <a href="{link}">{title}</a>
                <?php
                foreach ($fields as $field) {
                    if ($field['is_search_excerpt']) {
                        $this->embed($field['stub'], $field);
                    }
                }
                ?>
                {pagination}
            {/exp:channel:entries}
        </div>
    </body>
</html>