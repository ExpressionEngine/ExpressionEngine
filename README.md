<p align="center">
  <img src="https://expressionengine.com/asset/img/expressionengine-with-text.svg" alt="ExpressionEngine Logo" height="70" >
</p>

# ExpressionEngine CMS

ExpressionEngine is an open-source CMS for developers and agencies building custom websites. Model your content with Channels and custom fields, then publish it through templates you control.

**[Get started](#how-to-install) · [Documentation](https://docs.expressionengine.com/latest/) · [Community](#help-and-resources)**

## Why ExpressionEngine?

- **Custom content structures:** [Channels](https://docs.expressionengine.com/latest/getting-started/the-big-picture.html#channels), custom fields, and relationships let you define content around your project, from news articles to staff profiles and product catalogs.
- **Control over the frontend:** Write your own HTML and insert ExpressionEngine tags to display content, keeping control of your site's markup and design.
- **Tools for content authors:** Publishing layouts organize editing fields; rich text editing and live preview help authors prepare content and check it in the site's design.
- **Member management:** Roles and permissions let you manage different types of users and control their access to content and administration tools.
- **Extensibility:** Use add-ons or build your own with documented [extension points](https://docs.expressionengine.com/latest/development/addon-development-overview.html) to meet project-specific needs.

## Your markup, powered by your content

Channels organize content into entries with fields you define. Templates determine how that content appears.

Connect your code to your content with simple template tags:

```html
{exp:channel:entries channel="news" limit="3" dynamic="no"}
  <article>
    <h2>{title}</h2>
    <p>{summary}</p>
  </article>
{/exp:channel:entries}
```

Here, `news` is a Channel and `summary` is a custom field. See the [Channel Entries documentation](https://docs.expressionengine.com/latest/channels/entries.html) for filtering and display options.

## How to install

**For a new website, start with a packaged release.** It includes the dependencies needed to run ExpressionEngine.

1. [Download ExpressionEngine](https://expressionengine.com/#ee-download) from the official website and extract the ZIP.
2. Create an empty database, keep its connection details handy, and upload the extracted files to your site's root directory.
3. Set the [required writable-file and directory permissions](https://docs.expressionengine.com/latest/installation/installation.html#3-set-file-permissions) for your server.
4. Open `/admin.php` on your site and complete the installation wizard.
5. Remove or rename `system/ee/installer/` as described in the [installation guide](https://docs.expressionengine.com/latest/installation/installation.html), then follow the [post-installation security steps](https://docs.expressionengine.com/latest/installation/best-practices.html).

### System requirements

ExpressionEngine is a self-hosted PHP/MySQL application. Check the [EE 7 system requirements](https://docs.expressionengine.com/latest/installation/requirements.html) for supported versions, required PHP extensions, and a server compatibility wizard before choosing hosting.

After installation, read [The Big Picture](https://docs.expressionengine.com/latest/getting-started/the-big-picture.html) for the content and template model, or watch the [10-minute ExpressionEngine Primer](https://www.youtube.com/watch?v=qKaOirMRz2s) for a guided introduction.

### Repository installation

<details>
<summary>Installing from source</summary>

The `7.dev` branch contains development toward the next EE 7 release. For a released installation, use the packaged download above.

1. Create an empty database. Clone the repository, selecting the intended branch; this example uses `7.dev`:

   ```sh
   git clone --branch 7.dev https://github.com/ExpressionEngine/ExpressionEngine.git
   cd ExpressionEngine
   ```

2. Use **PHP 8.2+** for dependency/build tooling, including PHP-Scoper. With Composer installed, run this from the repository root:

   ```sh
   composer install
   ```

   This build-tool requirement differs from the packaged-runtime requirements linked above. Use the checkout as your site's root directory, or build locally and upload the complete result, including hidden files.

3. Create an empty configuration file only if it is missing:

   ```sh
   test -f system/user/config/config.php || touch system/user/config/config.php
   ```

4. Apply the [documented file and directory permissions](https://docs.expressionengine.com/latest/installation/installation.html#3-set-file-permissions), including write access to the configuration file.
5. In [.env.php](.env.php), set `EE_INSTALL_MODE=TRUE` to route requests to the installer.
6. Visit `/admin.php` on your site and complete installation.
7. Restore `EE_INSTALL_MODE=FALSE`, remove or rename `system/ee/installer/`, and follow the post-installation security steps linked above.

</details>

**Previous versions:** [GitHub Releases](https://github.com/ExpressionEngine/ExpressionEngine/releases) lists earlier releases. Its source ZIP and tar archives require the source-installation workflow above; use the extracted directory instead of cloning, and check that version's README for its build prerequisites.

## Help and resources

- **Learn:** [Documentation](https://docs.expressionengine.com/latest/) for reference material and [ExpressionEngine University](https://u.expressionengine.com/) for tutorials.
- **Ask the community:** [Forums](https://expressionengine.com/forums) and the [official Slack](https://expressionengine.com/blog/join-us-in-slack) for questions and discussion.
- **Get official help:** [ExpressionEngine Support](https://expressionengine.com/support).
- **Report bugs:** Search existing [GitHub issues](https://github.com/ExpressionEngine/ExpressionEngine/issues), then include reproduction steps in a new report.
- **Report security issues privately:** Follow the [security-reporting guide](https://docs.expressionengine.com/latest/bugs-and-security-reports.html).

## Contributing

Help improve ExpressionEngine through code, documentation, bug reports, or helping other users. Read the [contribution guide](CONTRIBUTING.md) to get involved.

## Copyright and license

ExpressionEngine is copyright (c) [Packet Tide, LLC](https://packettide.com) and licensed under the Apache License, Version 2.0. Subcomponents have separate copyright and license terms, all free and open source and compatible with Apache 2.0. See [LICENSE.txt](LICENSE.txt) for complete terms and copyright information.

“ExpressionEngine” is a registered trademark of Packet Tide, LLC in the United States and around the world. See the [Trademark Use Policy](https://expressionengine.com/about/trademark-use-policy) for logos and acceptable use.
