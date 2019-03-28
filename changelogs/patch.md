# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed a bug (#<linked issue number>) where <bug behavior>.

- Security: Fixed a potential remote template code execution bug.
- Added a new class to the comment edit field and template notes field, increasing the size of the fields.
- Fixed a bug where searching an AJAX-filtered list in the control panel by something other than its label may not return the expected result.
- Fixed a bug where default value selection of a Select list might show an empty selected value.
- Fixed a bug([\#150](https://github.com/ExpressionEngine/ExpressionEngine/issues/150)) where the Search Module may not filter by category.
- Fixed a bug([\#158](https://github.com/ExpressionEngine/ExpressionEngine/issues/158)) where a link to create new content would appear on the homepage despite content creation permissions.
- Fixed a bug([\#160](https://github.com/ExpressionEngine/ExpressionEngine/issues/160)) where a PHP error may appear on the Search Module's no-results screen.
- Fixed a bug([\#161](https://github.com/ExpressionEngine/ExpressionEngine/issues/161)) where searching for terms wrapped in quotes using the Search Module would return all entries.
- Fixed a bug([\#162](https://github.com/ExpressionEngine/ExpressionEngine/issues/162)) where the `{switch=}` variable would not parse inside the Comment Entries tag.
- Fixed a bug where Channel Form edit forms might not respect the `channel=` parameter.
- Fixed a bug([\#140](https://github.com/ExpressionEngine/ExpressionEngine/issues/140)) where channel field pagination did not recognize fields using the new table structure.
- Fixed a bug([\#164](https://github.com/ExpressionEngine/ExpressionEngine/issues/164)) where upload directories were not ordered alphabetically in the upload modal filter.
- Fixed a bug([\#166](https://github.com/ExpressionEngine/ExpressionEngine/issues/166)) where creating a template group with a period in the name would show an error.
- Fixed a bug where submitting a Channel Form containing a category menu would show an error if there were no category groups assigned to the channel.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
