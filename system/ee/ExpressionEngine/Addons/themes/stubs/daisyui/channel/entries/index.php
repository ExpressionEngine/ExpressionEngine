{!-- This template will only include title and search excerpt --}
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1" />
        <link href="https://cdn.jsdelivr.net/npm/daisyui@3.7.7/dist/full.css" rel="stylesheet" type="text/css" />
        <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
        <title><?=$channel_title?>: {site_name}</title>
    </head>
    <body>
        <div class="max-w-[100vw] px-6 pb-16 xl:px-2">
            <div class="flex flex-col-reverse justify-between gap-6 xl:flex-row">
                <div class="prose prose-sm md:prose-base w-full max-w-5xl flex-grow pt-10 mx-auto">
                    <h1><?=$channel_title?></h1>
                    {exp:channel:entries channel="<?=$channel?>" dynamic="no" paginate="bottom" limit="1"}
                        <section class="px-4 py-10 border rounded-xl border-blue-600 my-4">
                            <h3><a href="{path=<?=$template_group?>/entry/{url_title}}" class="link link-primary">{title}</a></h3>
                            <?php foreach (array_filter($fields, function ($field) { return $field['is_search_excerpt']; }) as $field) : ?>
                                {!-- Field: <?=$field['field_label']?> --}
                                {!-- Fieldtype: <?=$field['field_type']?> --}
                                {!-- Docs: <?=$field['docs_url']?> --}
                                <?=$this->embed($field['stub'], $field);?>

                                {!-- End field: <?=$field['field_label']?> --}
                            <?php endforeach; ?>
                        </section>
                        {paginate}
                        {pagination_links}
                            <div class="join">
                            {page}
                                <a href="{pagination_url}" class="join-item link link-hover btn{if current_page} btn-active{/if}">{pagination_page_number}</a>
                            {/page}
                            </div>
                        {/pagination_links}
                        {/paginate}
                    {/exp:channel:entries}
                </div>
            </div>
        </div>
    </body>
</html>