# Contributing

Hey, welcome! We're excited that you want to get involved in the ExpressionEngine project. There are lots of ways to get started: helping fellow users, reporting and triaging bugs, contributing code, help guide ExpressionEngine's future, and more! If you have an idea for how you can contribute to the ExpressionEngine project that isn't listed here, you are welcome to create a pull request with your changes to this guide.

Welcome aboard!

Table of Contents:

- [Helping Fellow Users](#helping-fellow-users)
- [Reporting Bugs](#reporting-bugs)
- [Contributing Code](#contributing-code)
- [Helping Guide ExpressionEngine's Future](#helping-guide-expressionEngines-future)

## Helping Fellow Users

One of the best and easiest ways to give back to the ExpressionEngine community is by answering questions and helping fellow users. Pay it forward and help new users get up to speed, or help other experts with challenging problems. Here's where you can find each other:

- the [Forums](https://expressionengine.com/forums)
- the official [Slack](https://expressionengine.com/blog/join-us-in-slack)
- over at [StackExchange](https://expressionengine.stackexchange.com)
- on Twitter using the **#eecms** hashtag
- start or join a [local Meetup](https://www.meetup.com/topics/expressionengine/)!

## Reporting Bugs

🚨🔒🚨 *If you have a **security issue** to report, **DO NOT** open an issue on GitHub. Please submit to our security and disclosure platform described in our user guide regarding [Security Reports](https://docs.expressionengine.com/latest/bugs_and_security_reports/index.html). Thanks!*

If you have found a bug, please first search the [existing Issues](https://github.com/ExpressionEngine/ExpressionEngine/issues) to see if your problem has already been reported. If it has, and you have additional helpful information, please add it!

If your problem has not been reported yet, just [open an Issue](https://github.com/ExpressionEngine/ExpressionEngine/issues) in the official GitHub repository. Here are some tips on filing good bug reports that can be addressed by code contributors. The Issue template will help remind of you of these pointers:

- **Use the latest version.** The ExpressionEngine project follows [Semantic Versioning](https://semver.org), so make sure you are on the latest _patch_ release for the current major version. Bugs submitted for Legacy versions will be closed (see [Version Support](https://expressionengine.com/support/version-support)). All security reports, including for Legacy versions should be made through HackerOne (see above).

- **Include a good description of the problem.** For someone to fix your bug, they need to understand exactly what's happening. Describe what you expected to happen, and what actually happened.

- **Include error messages.** If your problem throws error messages, cut and paste them verbatim; paraphrased error messages can make tracking down the problem more difficult. Please include the complete stack trace if the error included it.

- **Show how to reproduce the error.** You can do this by text, image, video, whatever is easiest for you to clearly show someone how to replicate this problem on ther own installation.

- **Include environmental details.** Include the ExpressionEngine, PHP, and MySQL version numbers. If you are working locally, include the web server and OS. If you are using MAMP/WAMP/XAMPP, please see if you can reproduce your problem on a managed environment. These packaged stack solutions while convenient are often configured in a way that creates problems you will not find on production servers.

## Triaging Bugs

Bug reports often need grooming before a code contributor can take ownership of a problem and fix it. Bugs get fixed rapidly when they are easy to reproduce and focus on a single issue. And the Issue tracker can get overwhelmed if there are many duplicate or similar reports.

Even if you can't write code, you can help triage bugs!

- **Confirm bugs.** A code contributor can't fix a bug that they can't reproduce. If the original report includes instructions to replicate, confirm that it is indeed reproducible. If that's been left out, ask the original submitter to add that information to their bug report. If you can reproduce it and the instructions to do so are clear, ping the maintainers in the comments (`@ExpressionEngine/Maintainers`) so they can apply an appropriate label.

- **Help people write good bug reports.** If you notice that a bug report is lacking detail, clarity, or any of the details under [Reporting Bugs](#reporting-bugs), coach the submitter on what is missing and why it's important. Be kind and tactful, so they are encouraged to submit good bug reports in the future!

- **Identify duplicate issues.** If more than one report exists for the same root issue, ping the maintainers (`@ExpressionEngine/Maintainers`) in the comments to let them know the issue is a duplicate, and provide a reference to the original Issue

## Contributing Code

- [Smallest Change Possible](#smallest-change-possible)
    - [Branches / Semantic Versioning](#branches--semantic-versioning)
    - [Unrelated Code Changes](#unrelated-code-changes)
    - [Proposing Large Changes](#proposing-large-changes)
- [Pull Requests](#pull-requests)
- [Documentation](#documentation)
- [Tests](#tests)
- [Code Review](#code-review)
- [PHP Coding Styles](#php-coding-styles)
- [CSS Style Updates](#css-style-updates)
- [JavaScript Updates](#javascript-updates)
- [Attribution](#attribution)
- [Contributor License Agreement](#contributor-license-agreement)

Thousands of web sites rely on ExpressionEngine, and they trust that updates won't break their site. Even major versions rarely introduce backwards-incompatible behavior, so users can easily keep their software up to date.

That trust means that ExpressionEngine has a very high threshhold for quality. The guidelines below are meant to assist in keeping that bar high, and in ensuring that ExpressionEngine's code remains easily and highly maintainable.

### Smallest Change Possible

A general principle to follow is to make the "smallest change possible". Bug fixes should only tackle a single issue whenever possible. Iteration and incrementalism are the names of the game.

Even tiny changes have secondary and tertiary effects, which must be well understood before any contributions are merged and released.

Don't worry about your commit history, you can make small, atomic commits. Frequently, we will use GitHub to squash commits when merging a pull request to maintain clean repo history.

#### Branches / Semantic Versioning

You will want to make sure your fork is easily updated from the upstream repo, so do not make changes on any published branches, including `7.x`. Recommended branch names are namespaced and unique, and should include major version number, e.g.:

- `feature/7.x/my-feature-slug`
- `bug/7.x/bug-description-slug`

| Branch | Purpose |
| ------ | ------- |
| 7.x |  Currently released EE7 version. Never target or branch.
| 7.dev | Next planned EE7 release. |
| 6.x |  Currently released EE6 version. Never target or branch.
| 6.dev | Next planned EE6 release. |

Ensure that any PRs you submit are based against `7.dev` or `6.dev`.

**Legacy Branches**

Older major versions [are supported for a year](https://expressionengine.com/support/version-support) after the release of a new major version, but only for security-related issues or bugs that can result in data-loss or a completely broken site. Pull requests of this nature should always branch off of and target `N.x` where `N` is the major version number being updated, e.g. `5.x`. As with `6.x`, do not make your commits directly to that published branch.

#### Unrelated Code Changes

Avoid making unrelated changes in your branch, or it may result in your pull request being closed without review. This includes whitespace and stylistic changes that are outside of files directly related to your change.

#### Proposing Large Changes

Large changes have a few problems:

- Massive changes are hard to review.
- Large pull requests are regularly ignored by the community due to time commitments.
- They often have multiple components and changes that must be developed in a waterfall fashion.
- They are time consuming to plan, write, document, test, and explain to users.

Therefore if you have a large change you'd like to propose, start with a [Feature Request Issue](https://github.com/ExpressionEngine/ExpressionEngine/issues/new?template=2-feature-request.md). Before spending a lot of time developing your idea, you'll want to make sure that the ExpressionEngine Maintainers will consider the request. They can also help you plot out how to tackle it in a way that is most likely to lead to inclusion in the project.

Often, new features have the best implementation when user-facing documentation is written before any code. This also helps make large-scale proposals easier to understand and digest, with the intended behavior and usage made clear in advance. Feel free to open a [User Guide Pull Request](https://github.com/ExpressionEngine/ExpressionEngine-User-Guide) to accompany your proposal.

If your idea is not useful to 80% of users and typical use cases, it may be better to build your idea as an add-on.

### Pull Requests

Every pull request should have its title set to line that will form a changelog entry, ex. `Resolved #1234 where switching theme to pink did not work for non-superadmins`, referencing the corresponding GitHub issue. When a version is released, these items are moved to the User Guide release branch to publish the changes.

In the pull request body, give a good description of what is the nature of change and reasoning behind it. Provide links to external references/discussions if appropriate.

If your pull request resolves a project issue, provide a link with closing keyword, e.g.
`Resolves https://github.com/ExpressionEngine/ExpressionEngine/issues/1235`

Assign this PR appropriate label depending on what is does, e.g. `Bug:Accepted`, or `enhancement`

If documentation update is needed, provide link to corresponding pull request in ExpressionEngine-User-Guide.

### Documentation

All additive changes and new features should have a corresponding pull request in the [User Guide repository](https://github.com/ExpressionEngine/ExpressionEngine-User-Guide) that documents the changes.

### Tests

Integration testing helps maintain the quality of the application and prevents unintentional regressions. ExpressionEnigne uses [Cypress](https://www.cypress.io/) for behavioral tests, and [PHPUnit](https://phpunit.de/) for unit testing. At a minimum, you should make sure that your changes do not break existing tests. Pull requests will automatically run tests on all supported PHP versions using GitHub actions, and will not be merged if tests fail.

#### Running Cypress Tests

To run the tests locally, you would need to ensure you have NPM and Cypress installed and then follow some steps as outlined below.

1. Back up your existing database and `system/user/config/config.php` file.

2. Copy `tests/cypress/support/config/config.php` over to `system/user/config/config.php`. Update your configuration in file if necessary (we recommend using clean database for the tests).

3. Copy `tests/cypress/cypress.env.example.json` to `tests/cypress/cypress.env.json`. Update the configuration in file if necessary.

4. Using command line interface, change your working directory to `tests/cypress`

5. If this is your first time running ExpressionEngine Cypress tests, ensure you have Node.js installed and then run `npm i`

6. Execute command `npm run cypress:open`

7. In Cypress UI, click on the tests that you know might be affected to run them.

8. If you prefer to have all tests run, excecute command `npm run cypress:run` (Note: running all tests locally might be time-consuming)

#### Writing Tests

Whenever possible, a test should be written or modified and included in your pull request for any bug fix or new feature. This prevents regressions, and provides a baseline expectation for behavior of new features.

### Code Review

All code review takes place on GitHub. Code review is an important process in making sure all contributions maintain ExpressionEngine's high standards of quality and consistency. Everyone is welcome to give feedback and comments, in fact, reviewing other people's pull requests can earn you goodwill with other developers, who will be encouraged to review your pull requests in return. Only ExpressionEngine Maintainers can approve a request for inclusion.

You can ping `@ExpressionEngine/Maintainers` if your change is urgent. Please provide a solid reason for doing so, as it will result in pulling other professional developers off of their tasks to give you attention.

### PHP Coding Styles

As of ExpressionEngine 6 and greater, all new code submitted to the ExpressionEngine core should follow the PSR-12 recommendations. [https://expressionengine.com/blog/expressionengine-adopts-psr-12](https://expressionengine.com/blog/expressionengine-adopts-psr-12)

For specific style guidelines, reference the ExpressionEngine User Guide: [Development Style & Syntax](https://docs.expressionengine.com/latest/development/guidelines/general.html). Feel free to open a pull request against that page to make suggestions or corrections.

Don't feel like you need to memorize these. You're a good developer, and when modifying an existing file are fully capable of keeping the internal style consistent. When adding new files, make sure of the big things, including attribution below.

### CSS Style Updates

ExpressionEngines is using Sass stylesheet language that needs to be compiled to CSS. If you want to make an update to the styles of Control Panel, you should add your styles to appropriate file in `cp-styles/app/styles` directory and then compile it using `npm run build:css` command.

ExpressionEngine is using FontAwesome 6 Pro to create and style icons in the Control Panel.

Note that there is additional README file with some info in `cp-styles` directory.

### JavaScript Updates

For historical reasons, ExpressionEngine is using mix of JavaScript technologies: ECMAScript, ReactJS and jQuery.

If you need to edit a file that is found in `themes/ee/cp/js` folder, make sure to edit the file that's found in `src` directory and then run the build script using `npm run build:js` command.

If you need to edit a file that is found in `themes/ee/asset/javascript` folder, make sure to edit the `.es6` file and then run the build script using `npm run build:old-js` command.

### Attribution

On the off chance that your contribution adds new source files, use the language-appropriate attribution block at the top of the file.

#### PHP Files

<pre><code>/**
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */</code></pre>

#### JavaScript Files

<pre><code>/*!
 * This source file is part of the open source project
 * ExpressionEngine (https://expressionengine.com)
 *
 * @link      https://expressionengine.com/
 * @copyright Copyright (c) 2003-2023, Packet Tide, LLC (https://www.packettide.com)
 * @license   https://expressionengine.com/license Licensed under Apache License, Version 2.0
 */</code></pre>

### Contributor License Agreement

Please note, all code contributors must sign a <a href="https://www.clahub.com/agreements/ExpressionEngine/ExpressionEngine">Contributor License Agreement</a> to ensure all code is legally contributed and able to be redistributed under the project's open-source license.

## Helping Guide ExpressionEngine's Future

Everyone is welcome to add ideas and feedback to help shape ExpressionEngine's future. The ExpressionEngine [Feature Request forum](https://expressionengine.com/forums/topics/136/feature-requests) and [Slack](https://expressionengine.com/community#slack) are both good spots for spitballing new ideas with other users.

When an idea is well understood, along with pros and cons, it should be added as a [Feature Request Issue](https://github.com/ExpressionEngine/ExpressionEngine/issues/new?template=2-feature-request.md) directly on the repository. Features Issues that have been discussed and given positive feedback from the ExpressionEngine Maintainers can be a good spot to get inspired to contribute code.

Thanks for your participation! 🙏
