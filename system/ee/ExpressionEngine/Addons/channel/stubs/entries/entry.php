<html>
    <head>
    {exp:channel:entries channel="<?=$channel?>" require_entry="yes"}
        {if no_results}{redirect="404"}{/if}
        <title>{title}</title>
    {/exp:channel:entries}
    </head>
    <body>
        {exp:channel:entries channel="<?=$channel?>" require_entry="yes"}
        <h1>{title}</h1>
        <p>by {author} on <a href="{path=<?=$template_group?>/archive/{entry_date format='%Y'}}">{entry_date format="%F %d, %Y"}</a></p>
        {categories}
            <a href="{site_url}/<?=$template_group?>/category/{category_url_title}">{category_name}</a>
        {/categories}
        <?php foreach ($fields as $field) : ?>

            <div>
                <?php if($show_comments ?? false): ?>

                {!-- Field: <?=$field['field_label']?> --}
                {!-- Fieldtype: <?=$field['field_type']?> --}
                {!-- Docs: <?=$field['docs_url']?> --}
                <?php endif; ?>

                <?=$this->embed($field['stub'], $field);?>
                <?php if($show_comments ?? false): ?>

                {!-- End field: <?=$field['field_label']?> --}
                <?php endif; ?>

            </div>

        <?php endforeach; ?>

        {/exp:channel:entries}

        <hr>
        {embed="<?=$template_group?>/_comment_form"}

        <hr>
        {exp:channel:next_entry channel="<?=$channel?>"}
            <p>Next entry: <a href="{path='<?=$template_group?>/entry'}">{title}</a></p>
        {/exp:channel:next_entry}

        {exp:channel:prev_entry channel="<?=$channel?>"}
            <p>Previous entry: <a href="{path='<?=$template_group?>/entry}">{title}</a></p>
        {/exp:channel:prev_entry}
    </body>
</html>
