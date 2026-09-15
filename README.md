<p align="center">
  <img src="https://expressionengine.com/asset/img/expressionengine-with-text.svg" alt="ExpressionEngine Logo" height="70" >
</p>

# ExpressionEngine CMS

ExpressionEngine is a self-hosted, open-source PHP CMS for developers and agencies building custom websites. Define your content and give editors a publishing experience tailored to the site.

**[Get started](#how-to-install) · [Documentation](https://docs.expressionengine.com/latest/) · [Community](#help-and-resources)**

## Why ExpressionEngine?

- **Model your content:** Define content types, called [Channels](https://docs.expressionengine.com/latest/getting-started/the-big-picture.html#channels), with custom fields and relationships for articles, staff profiles, or products.
- **Simple templating:** Build custom designs with HTML and readable template tags, without writing PHP.
- **Edit content in context:** Let editors update content directly on the website with built-in [front-end editing](https://docs.expressionengine.com/latest/advanced-usage/front-end/overview.html).
- **Publish with confidence:** Give editors tailored publishing layouts and live preview, with [entry versioning](https://docs.expressionengine.com/latest/control-panel/create.html#revisions-tab) to revisit saved revisions.
- **Built with security in mind:** Granular permissions, [multi-factor authentication](https://docs.expressionengine.com/latest/member/mfa.html), and ongoing security updates help protect your site and its users.
- **Extend your site:** Add ecommerce with [CartThrob](https://expressionengine.com/add-ons/cartthrob) or [Expresso Store](https://expressionengine.com/add-ons/expresso-store), explore other [add-ons](https://expressionengine.com/add-ons), or [build your own](https://docs.expressionengine.com/latest/development/addon-development-overview.html).

## Your markup, powered by your content

Display news entries with a few template tags:

```html
{exp:channel:entries channel="news" limit="3" dynamic="no"}
  <article>
    <h2>{title}</h2>
    <p>{summary}</p>
  </article>
{/exp:channel:entries}
```

This displays up to three entries from the `news` Channel. `summary` is a custom field. See [Channel Entries](https://docs.expressionengine.com/latest/channels/entries.html) for more options.

Work with [template files](https://docs.expressionengine.com/latest/templates/overview.html#saving-templates-as-files) in your editor, and give compatible coding agents and AI assistants access to ExpressionEngine documentation through [Context7](https://context7.com/expressionengine/expressionengine-user-guide).

Learn [how Channels and templates work together](https://docs.expressionengine.com/latest/getting-started/the-big-picture.html).

## How to install

ExpressionEngine is a self-hosted PHP/MySQL application. Check the [system requirements](https://docs.expressionengine.com/latest/installation/requirements.html) before getting started.

**[Download a packaged release](https://expressionengine.com/#ee-download)** with dependencies included, then follow the [installation guide](https://docs.expressionengine.com/latest/installation/installation.html) and [post-installation security steps](https://docs.expressionengine.com/latest/installation/best-practices.html).

<details>
<summary>Source installation and previous versions</summary>

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

**Previous versions:** [GitHub Releases](https://github.com/ExpressionEngine/ExpressionEngine/releases) lists earlier releases. Its source ZIP and tar archives require the source-installation workflow above; use the extracted directory instead of cloning, and check that version's README for its build prerequisites.

</details>

## Help and resources

- **Learn:** [Documentation](https://docs.expressionengine.com/latest/) for reference material and [ExpressionEngine University](https://u.expressionengine.com/) for tutorials.
- **Build with AI:** [ExpressionEngine documentation on Context7](https://context7.com/expressionengine/expressionengine-user-guide) for coding agents and AI assistants that support it.
- **Ask the community:** [Official Slack](https://expressionengine.com/blog/join-us-in-slack) and [forums](https://expressionengine.com/forums) for questions and discussion.
- **Get official help:** [ExpressionEngine Support](https://expressionengine.com/support).
- **Report bugs:** Search existing [GitHub issues](https://github.com/ExpressionEngine/ExpressionEngine/issues), then include reproduction steps in a new report.
- **Report security issues privately:** Follow the [security-reporting guide](https://docs.expressionengine.com/latest/bugs-and-security-reports.html).

## Contributing

Help improve ExpressionEngine through code, documentation, bug reports, or helping other users. Read the [contribution guide](CONTRIBUTING.md) to get involved.

## Copyright and license

ExpressionEngine is copyright (c) [Packet Tide, LLC](https://packettide.com) and licensed under the Apache License, Version 2.0. Subcomponents have separate copyright and license terms, all free and open source and compatible with Apache 2.0. See [LICENSE.txt](LICENSE.txt) for complete terms and copyright information.

“ExpressionEngine” is a registered trademark of Packet Tide, LLC in the United States and around the world. See the [Trademark Use Policy](https://expressionengine.com/about/trademark-use-policy) for logos and acceptable use.
