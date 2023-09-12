{exp:channel:entries channel="<?=$channel?>" require_entry="yes"}
{if no_results}{redirect="404"}{/if}
<html>
    <head>
        <title>{title}</title>
    </head>
    <body>
        <h1>{title}</h1>
        <p>by {author} on {entry_date format="%F %d, %Y"}</p>
        {!-- categories --}
        <?php
        foreach ($fields as $field) {
            $this->embed($field['stub'], $field);
        }
        ?>
    </body>
</html>
{/exp:channel:entries}