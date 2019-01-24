<img src="https://expressionengine.com/asset/img/expressionengine-with-text.svg" alt="ExpressionEngine Logo" height="70" >

# ExpressionEngine CMS

ExpressionEngine is a mature, flexible, secure, free open-source content management system. It is beloved by designers for giving them complete control of all output, and by content authors for enabling reusable, high-performing content. With ExpressionEngine, you can build full-featured websites, create a web app, or serve content to mobile applications. All without requiring complex programming skills.

Visit [expressionengine.com](https://expressionengine.com/) to see what it's all about or, if you prefer, download a ZIP and jump right in!

## System Requirements

ExpressionEngine requires a web server running PHP & MySQL. We recommend:

- PHP 7 or newer
- MySQL 5.6 or newer

ExpressionEngine _can_ run on PHP 5.6+ and MySQL 5.5.3+. For full details and a server compatibility wizard, please visit the system requirements [in the User Guide](https://docs.expressionengine.com/latest/installation/requirements.html).

## How To Install

1. Create a database
2. Upload the files
3. Verify file permissions
4. Point your browser to `/admin.php` and run the Installation Wizard.

Read [Installing ExpressionEngine](https://docs.expressionengine.com/latest/installation/installation.html) in the User Guide for full instructions, tips, and post-install best practices.

Note: If you're installing from the repository, you will also need to:

- add an empty config file, e.g. `touch system/user/config/config.php`
- route requests to the installer app instead of the main app by changing `EE_INSTALL_MODE` to `TRUE` in [.env.php](.env.php). You can change this back when you're done.

## How to Get Started

ExpressionEngine separates your content from your design, enabling you to make small or large changes to your website with ease. Your content is stored in Channels, and your design is kept in Templates, both of which are entirely under your control. ExpressionEngine bends to _your_ needs, not the other way around like many other CMSes.

If you're new to ExpressionEngine, check out:

- [The Big Picture](https://docs.expressionengine.com/latest/intro/the_big_picture.html)
- [Building a Simple News Site from Start to Finish](https://docs.expressionengine.com/latest/how_to/building_a_simple_news_site.html)
- [10-minute ExpressionEngine Primer](https://www.youtube.com/watch?v=qKaOirMRz2s) on ExpressionEngineTV

## How to Contribute

There are many ways you get get involved and contribute to the ExpressionEngine application and it's amazing community. Check out [CONTRIBUTING.md](CONTRIBUTING.md) in the root of this repository to get started!

## Copyright / License Notice

The ExpressionEngine project is copyright (c) 2003-2019 EllisLab, Inc ([https://ellislab.com](https://ellislab.com)) and is licensed under Apache License, Version 2.0. This project contains subcomponents with separate copyright and license terms, all of which are fully FOSS and compatible with Apache-2.0.

Complete license terms and copyright information can be found in [LICENSE.txt](LICENSE.txt) in the root of this repository.

"ExpressionEngine" is a registered trademark of EllisLab Corp. in the United States and around the world. Refer to EllisLab's [Trademark Use Policy](https://ellislab.com/trademark-use-policy) for access to logos and acceptable use.
