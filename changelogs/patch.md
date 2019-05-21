# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed a bug (#<linked issue number>) where <bug behavior>.

- Fixed a bug ([\#168](https://github.com/ExpressionEngine/ExpressionEngine/issues/168)) where validation errors did not clear in the template partial editor.

- Fixed a bug ([\#86](https://github.com/ExpressionEngine/ExpressionEngine/issues/86)) where the template editor would not highlight EE comment tags correctly on newlines.

- Fixed a bug ([\#180](https://github.com/ExpressionEngine/ExpressionEngine/issues/180)) where the Query Form would run a query two extra times.

- Fixed a bug ([\#170](https://github.com/ExpressionEngine/ExpressionEngine/issues/170)) where member imports with text passwords triggered a password change email upon login.

- Fixed a bug ([\#182](https://github.com/ExpressionEngine/ExpressionEngine/issues/182)) where Nested relationship fields inside of fluid fields go unparsed in some circumstances.

- Fixed a bug where logins to the control panel were not always redirected to the proper page.

- Fixed a bug where Live Preview threw errors if the template used a category parameter in the channel entry tag.

- Fixed a bug in the spam module where approving a Channel Entry that has categories generated PHP errors.


EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
