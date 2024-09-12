<html>
    <head>
        <title>Publish Entry in <?=$channel_title?></title>
        {!-- https://docs.expressionengine.com/latest/channels/channel-form/overview.html --}
        <link href="{path='css/_ee_channel_form_css'}" type="text/css" rel="stylesheet" media="screen">
    </head>
    <body>
        <div>
            {if segment_2 != ''}
                {exp:channel:entries channel="<?=$channel?>" url_title="{segment_2}"}
                    <h1>Edit entry {title}</h1>
                    {if no_results}
                        {redirect="<?=$template_group?>"}
                    {/if}
                {/exp:channel:entries}
            {if:else}
                <h1>Create entry in <?=$channel_title?></h1>
            {/if}
            {!-- Use error_handling="inline" if you want to show error messages next to their fields --}
            {exp:channel:form channel="<?=$channel?>" url_title="{segment_2}" error_handling="message"}
                <fieldset>
                    <label for="title">Title</label>
                    <input type="text" name="title" id="title" value="{title}" size="50" maxlength="200" onkeyup="liveUrlTitle(event);">
                    {error:title}
                </fieldset>

                <fieldset>
                    <label for="url_title">URL Title</label>
                    <input type="text" name="url_title" id="url_title" value="{url_title}" maxlength="<?=URL_TITLE_MAX_LENGTH?>" size="50">
                    {error:url_title}
                </fieldset>

                <?php foreach ($fields as $field) : ?>

                    <?php if($show_comments ?? false): ?>

                    {!-- Field: <?=$field['field_label']?> --}
                    {!-- Fieldtype: <?=$field['field_type']?> --}
                    {!-- Docs: <?=$field['docs_url']?> --}
                    <?php endif; ?>
                    <fieldset class="element-wrapper <?=$field['field_type']?>-wrap">
                        <label for="<?=$field['field_name']?>" class="element-label"><?=$field['field_label']?></label>
                        {field:<?=$field['field_name']?>}
                        {error:<?=$field['field_name']?>}
                    </fieldset>
                    <?php if($show_comments ?? false): ?>

                    {!-- End field: <?=$field['field_label']?> --}
                    <?php endif; ?>
                <?php endforeach; ?>

                <button type="submit">Submit</button>
            {/exp:channel:form}
        </div>
    </body>
</html>