##########################
ExpressionEngine Changelog
##########################

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

.. note:: Move all changes to User Guide upon public release ``/changelog.rst``

.. note:: Please keep bug fixes separate from features and modifications


*************
Patch Release
*************

.. Bullet list below, e.g.
   - Added <new feature>
   - Fixed Bug (#<issue number>) where <bug behavior>.

- Optimized the member delete routine's heir assignments.
- Fixed a bug where a PHP error may appear when the CP homepage newsfeed cannot be fetched.
- Fixed a bug where extension hooks may run during a one-click upgrade.
- Fixed a bug where a supplied class was not added to "select" fields in the shared form view.
- Fixed a potential malformed query issue in the relationships_query hook.
- Fixed a potential PHP error (#21) when saving option-type Grid columns.
- Fixed a bug (#20) where the installer checks if the user theme directory is writable even when not installing the default theme.
- Fixed a bug (#13) where `{if fluid_field}` conditionals would not work as expected.
- Fixed a bug (#14) where entries would fail to save when a Toggle field was hidden and MySQL was in strict mode.
- Fixed a bug (#22) where the JavaScript-based URL title generation did not match the PHP version.
- Fixed a bug (#37) where Channel settings could not be saved if the default Channel Form author didn't exist.
- Fixed a bug (#36) where deleting multiple statuses would not reset the confirmation modal button state.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
