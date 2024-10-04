<?='<?xml version="1.0" encoding="UTF-8"?>'."\n"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    {exp:channel:entries channel="<?=$channel?>" dynamic="no"}
    <url>
        <loc>{path=<?=$template_group?>/entry/{url_title}}</loc>
        <lastmod>{edit_date format="%Y-%m-%d"}</lastmod>
    </url>
    {/exp:channel:entries}
</urlset>