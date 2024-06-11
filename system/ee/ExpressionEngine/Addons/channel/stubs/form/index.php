<html>
    <head>
        <title>Create entry in <?=$channel_title?></title>
        {!-- https://docs.expressionengine.com/latest/channels/channel-form/overview.html --}
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" integrity="sha512-z3gLpd7yknf1YoNbCzqRKc4qyor8gaKU1qmn+CShxbuBusANI9QpRohGBreCFkKxLhei6S9CQXFEbbKuqLg0DA==" crossorigin="anonymous" referrerpolicy="no-referrer" />

<style type="text/css">
/* Common styles */
.ee-cform .element-wrapper {
    margin: 0;
    padding: 0;
    margin-bottom: 20px;
    border: 0;
    min-width: 0;
    font-family: -apple-system, BlinkMacSystemFont, segoe ui, helvetica neue, helvetica, Cantarell, Ubuntu, roboto, noto, arial, sans-serif;
}

.ee-cform .element-wrapper fieldset {
    border:none;
    padding: 0;
}

.ee-cform .element-label {
    display: block;
    max-width: 100%;
    font-weight: 600;
    color: #0d0d19;
    margin-bottom: 10px;
    font-size: 1rem;
}

.ee-cform .element-wrapper textarea,
.ee-cform .element-wrapper input[type=text],
.ee-cform .element-wrapper input[type=email],
.ee-cform .element-wrapper input[type=number],
.ee-cform .element-wrapper input[type=password],
.ee-cform .element-wrapper input[type=url],
.ee-cform .element-wrapper input[type=search],
.ee-cform .element-wrapper input[type=date] {
    display: block;
    width: 100% !important;
    padding: 8px 15px !important;
    font-size: 1rem !important;
    line-height: 1.6 !important;
    color: #0d0d19 !important;
    background-color: #fff !important;
    background-image: none !important;
    transition: border-color 200ms ease, box-shadow 200ms ease !important;
    -webkit-appearance: none !important;
    border: 1px solid #cbcbda !important;
    border-radius: 5px !important;
    box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
}

.ee-cform .element-wrapper textarea:focus,
.ee-cform .element-wrapper input[type=text]:focus,
.ee-cform .element-wrapper input[type=email]:focus,
.ee-cform .element-wrapper input[type=number]:focus,
.ee-cform .element-wrapper input[type=password]:focus,
.ee-cform .element-wrapper input[type=url]:focus,
.ee-cform .element-wrapper input[type=search]:focus,
.ee-cform .element-wrapper input[type=date]:focus {
    border-color: #5d63f1 !important;
    outline: none !important;
    box-shadow: 0 0 0 2px #bbbdf9 !important;
}

.ee-cform textarea {
    height: 210px;
}

/* END Common styles */
</style>
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
                    {!-- Field: <?=$field['field_label']?> --}
                    {!-- Fieldtype: <?=$field['field_type']?> --}
                    {!-- Docs: <?=$field['docs_url']?> --}
                    <fieldset class="element-wrapper <?=$field['field_type']?>-wrap">
                        <label for="<?=$field['field_name']?>" class="element-label"><?=$field['field_label']?></label>
                        {field:<?=$field['field_name']?>}
                    </fieldset>
                    {!-- End field: <?=$field['field_label']?> --}
                <?php endforeach; ?>
                <button type="submit">Submit</button>
            {/exp:channel:form}
        </div>
    </body>
</html>