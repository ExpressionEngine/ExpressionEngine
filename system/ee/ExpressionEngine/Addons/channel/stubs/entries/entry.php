{exp:channel:entries channel="<?=$channel?>" require_entry="yes"}
{if no_results}{redirect="404"}{/if}
<html>
    <head>
        <title>{title}</title>
    </head>
    <body>
        <h1>{title}</h1>
        <p>by {author} on {entry_date format="%F %d, %Y"}</p>
        <?php foreach ($fields as $field) : ?>
            <div>
                {!-- Fieldtype: <?=$field['field_type']?> --}
                {!-- Docs: <?=$field['docs_url']?> --}
                <h6><?=$field['field_label']?></h6>
                <?=$this->embed($field['stub'], $field);?>
            </div>
        <?php endforeach; ?>
    </body>
</html>
{/exp:channel:entries}