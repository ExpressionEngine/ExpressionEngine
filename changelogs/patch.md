# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed a bug (#<linked issue number>) where <bug behavior>.

- Improved revision listings ([\#87](https://github.com/ExpressionEngine/ExpressionEngine/pull/87)) for entry and template versioning, to sort in reverse chronological order.
- Fixed a bug ([\#55](https://github.com/ExpressionEngine/ExpressionEngine/issues/55) where fields may parse incorrectly if they shared similar names.
- Fixed a bug ([\#119](https://github.com/ExpressionEngine/ExpressionEngine/issues/119)) where Simple Commerce subscription end date was not correctly formatted before output.
- Fixed a bug ([\#114](https://github.com/ExpressionEngine/ExpressionEngine/issues/114)) where dates may be incorrectly localized.
- Fixed a bug ([\#124](https://github.com/ExpressionEngine/ExpressionEngine/issues/124)) where new Channels could not be saved if there were a large number of authors.
- Changed hard-coded system paths to be dynamic in some error messages. ([\#126](https://github.com/ExpressionEngine/ExpressionEngine/pull/126))
- Database connection and SQL errors are now hidden if debug levels aren't sufficient.
- Fixed a bug ([\#134](https://github.com/ExpressionEngine/ExpressionEngine/issues/134)) where Channel Entries tag queries could be malformed if searching by custom field contents across multiple sites.
- Fixed a bug ([\#133](https://github.com/ExpressionEngine/ExpressionEngine/issues/133)) where Channel Entries tag queries could be malformed if ordering by custom field contents across multiple sites.
- Fixed a bug ([\#138](https://github.com/ExpressionEngine/ExpressionEngine/issues/138)) where the JavaScript date picker bind function ignored its elements parameter.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
