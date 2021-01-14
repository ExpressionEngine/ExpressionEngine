# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed a bug (#<linked issue number>) where <bug behavior>.

   - Fixed a bug that prevented the instal wizard from auto-renaming the installer folder after install.
   - Fixed install exception when using MySQL 8 with unsuported authentication type.
   - Fixed PSR-12 lint error for SELF constant by adding and swapping it out for new EESELF constant.
   - Fixed "Select Dropdown" "Populate the menu from another channel field" not showing any field options.
   - Fixed an 'Invalid parameter count' error message, switching in a more friendly permission message on the publish edit page.
   - Fixed a bug ([#687](https://github.com/ExpressionEngine/ExpressionEngine/issues/687) where no valid channels were available in the channel field on the publish page.
   - Fixed several missing language variables in the control panel.
   - Added CLI command file

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
