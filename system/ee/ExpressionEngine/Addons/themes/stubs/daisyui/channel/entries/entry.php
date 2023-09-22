{exp:channel:entries channel="<?=$channel?>" require_entry="yes"}
{if no_results}{redirect="404"}{/if}
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="https://cdn.jsdelivr.net/npm/daisyui@3.7.7/dist/full.css" rel="stylesheet" type="text/css" />
        <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
        <title>{title}</title>
    </head>
    <body>
        <div class="max-w-[100vw] px-6 pb-16 xl:px-2">
            <div class="flex flex-col-reverse justify-between gap-6 xl:flex-row">
                <div class="prose prose-sm md:prose-base w-full max-w-5xl flex-grow pt-10 mx-auto">
                    <h1>{title}</h1>
                    <p>by {author} on {entry_date format="%F %d, %Y"}</p>
                    <?php foreach ($fields as $field) : ?>

                        <section class="px-4 py-10 border rounded-xl border-blue-600 my-4">
                            {!-- Field: <?=$field['field_label']?> --}
                            {!-- Fieldtype: <?=$field['field_type']?> --}
                            {!-- Docs: <?=$field['docs_url']?> --}
                            <h4 class="text-4xl text-black text-center font-bold mb-4"><?=$field['field_label']?></h4>
                            <?=$this->embed($field['stub'], $field);?>

                            {!-- End field: <?=$field['field_label']?> --}
                    </section>

                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </body>
</html>
{/exp:channel:entries}