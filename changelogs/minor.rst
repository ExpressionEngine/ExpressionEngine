##########################
ExpressionEngine Changelog
##########################

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for MINOR version changes only.

.. note:: Move all changes to User Guide upon public release ``/changelog.rst``

.. note:: Please keep bug fixes separate from features and modifications


*************
Minor Release
*************

.. Bullet list below, e.g.
   - Added <new feature>
   - Fixed Bug (#<issue number>) where <bug behavior>.

- Added a `channel_form_overwrite` [system configuration override](general/system-configuration-override.md#channel_form_overwrite) that allows Channel Form authors to replace files that they have uploaded, if they upload one with the same name as an existing file. No warnings, use with caution!
- Fixed a bug where Markdown inline code HTML would be double encoded and global variables were being parsed.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
