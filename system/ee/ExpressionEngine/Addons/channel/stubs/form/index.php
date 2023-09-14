<html>
    <head>
        <title>Create entry in <?=$channel_title?></title>
        {!-- https://docs.expressionengine.com/latest/channels/channel-form/overview.html --}
    </head>
    <body>
        <div>
            <h1>Create entry in <?=$channel_title?></h1>
            {exp:channel:form channel="<?=$channel?>"}
                <fieldset>
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="{title}" size="50" maxlength="200" onkeyup="liveUrlTitle(event);">
                </fieldset>

                <fieldset>
                    <label for="url_title">URL Title</label>
                    <input type="text" name="url_title" id="url_title" value="{url_title}" maxlength="<?=URL_TITLE_MAX_LENGTH?>" size="50">
                </fieldset>

                <?php foreach ($fields as $field) : ?>
                    {!-- Fieldtype: <?=$field['field_type']?> --}
                    {!-- Docs: <?=$field['docs_url']?> --}
                    <fieldset>
                        <label for="<?=$field['field_name']?>"><?=$field['field_label']?></label>
                        <?=$this->embed($field['stub'], $field);?>
                    </fieldset>
                <?php endforeach; ?>
                {pagination}
            {/exp:channel:form}
        </div>
    </body>
</html>