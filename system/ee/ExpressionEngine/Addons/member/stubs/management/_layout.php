<html>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>{layout:title} | Member Management</title>
        <style>
            html, body {
                margin: 0;
                tab-size: 4;
                font-family: -apple-system,BlinkMacSystemFont,Segoe UI,Helvetica,Roboto,Arial,sans-serif;
                line-height: 1.65rem;
                font-size: 1em;
                text-rendering: optimizeLegibility;
                -webkit-font-smoothing: antialiased;
                box-sizing: inherit;
            }
            .container {
                display: flex;
                flex: 2;
                max-width: 1440px;
                margin: 0 auto;
            }
            hr {
                margin: 4em 0;
            }
            label {
                font-weight: bold;
            }
            fieldset {
                border: 0;
                margin: 20px 0;
            }
            fieldset > legend {
                font-size: 1.5em;
                font-weight: bold;
            }
            .error {
                background-color: #f8b8b9;
            }
            .sidebar {
                display: flex;
                position: -webkit-sticky;
                position: sticky;
                flex: 0 0 200px;
                flex-direction: column;
                z-index: 10;
                height: 100vh;
                padding: 60px 20px 20px;
                margin: 0 20px 0 0;
                overflow-y: auto;
                box-sizing: border-box;
                border-right: 1px solid #ebecf4;
            }
            .sidebar ul {
                list-style-type: none;
                padding-inline-start: 0;
            }
            .sidebar li {
                margin-bottom: 5px;
                color: #544e72;
            }
            .sidebar ul a {
                color: #757698;
                font-weight: 700;
            }
            .sidebar li a {
                transition: all 250ms ease;
                display: block;
            }
            .sidebar ul li ul {
                padding: 5px 0 5px 20px;
                font-size: 0.8em;
                line-height: 1.2em;
            }

            .content h1 {
                margin-bottom: 1em;
                padding-bottom: 0.5em;
                font-weight: 600;
                font-size: 2em;
                color: #240b38;
                border-bottom: 1px solid #c1bdc5;
            }
            .content h3 {
                color: #62627e;
            }
            .content input[type="text"], .content input[type="email"], .content input[type="password"] {
                font-size: 1em;
                padding: 10px 15px 10px 15px;
                border-radius: 5px;
                border: 1px solid rgb(196, 205, 222);
                box-shadow: rgb(242, 243, 245) 0px 1px 0px 0px;
            }
            .content input[type="submit"], .content input[type="button"] {
                display: inline-block;
                font-size: 0.9rem;
                border-radius: 5px;
                border: none;
                text-align: center;
                cursor: pointer;
                height: 30px;
                line-height: 30px !important;
                padding: 0 10px;
                margin: 10px 0;
                color: #fff;
                background-color: #0097f5;
            }
            .info {
                padding: 15px 20px;
                background-color: #b8e4f8;
            }
        </style>
    </head>
    <body>
        <div class="container">
        <?php if($include_navigation): ?>
            <nav class="sidebar">
                {if logged_in}
                    <p>Logged in as <b>{username}</b></p>
                    <ul>
                        <?php foreach($privateTemplates as $slug => $title): ?>
                            <li><a href="{path=<?=$template_group?>/<?=$slug?>}"><?=$title?></a></li>
                        <?php endforeach; ?>
                    </ul>
                {if:else}
                    <p>Logged out</p>
                    <ul>
                        <?php foreach($publicTemplates as $slug => $title): ?>
                            <li><a href="{path=<?=$template_group?>/<?=$slug?>}"><?=$title?></a></li>
                        <?php endforeach; ?>
                    </ul>
                {/if}
            </nav>
        <?php endif; ?>
            <main class="content">
                <h1>{layout:title}</h1>
                {layout:contents}
            </main>
        </div>
    </body>
</html>