{!-- this template will display all assigned custom fields --}
{exp:channel:entries dynamic="yes"}
<html>
    <head>
        <title>{title}</title>
    </head>
    <body>
        <div>
            <h1>{title}</h1>
            {fields}
                {field}
            {/fields}
        </div>
    </body>
</html>
{/exp:channel:entries}