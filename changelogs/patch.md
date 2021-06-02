# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Fix #917, allows for attributes, rows, and cols to be set for textarea in shared form view
   - Fixed a bug in the control panel menu where channel names showed for members who did not have access to them.
   - Fixed a PHP 7.3+ warning that occurred when non-members triggered email notifications.
   - Fixed a bug where the initial sorting of content when populating a custom field based on other fields was incorrect.
   - Fixed a bug that prevented the instal wizard from auto-renaming the installer folder after install.
   - Fixed install exception when using MySQL 8 with unsuported authentication type.
   - Fixed PSR-12 lint error for SELF constant by adding and swapping it out for new EESELF constant.
   - Fixed "Select Dropdown" "Populate the menu from another channel field" not showing any field options.
   - Fixed an 'Invalid parameter count' error message, switching in a more friendly permission message on the publish edit page.
   - Fixed a bug ([#687](https://github.com/ExpressionEngine/ExpressionEngine/issues/687) where no valid channels were available in the channel field on the publish page.
   - Fixed several missing language variables in the control panel.
   - Fixed template HTTP Authentication not recognizing Super Admin.
   - Added CLI command file
   - Fixed a bug with user lang translations in the CP.
   - Fix addon icon for png and svg
   - Fix bug in the Template Profiler when it attempts to parse an empty array
   - Fixed issue with super admins seeing unauthorized message when accessing empty entry manager
   - Changed how permission check for members with CP access is handled
   - Fixed a bug (#910) where date picker wasn't following the last day of the month when switching.
   - Fixed a bug in the category group name filter

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
