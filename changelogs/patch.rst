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

- Fixed a bug where some member validation language keys may appear unparsed in some third-party contexts.
- Fixed a potential issue (#76) where some jQuery selectors weren't specific enough.
- Fixed a bug where some SVGs in File fields would not render a preview on the publish form.
- Fixed a bug (#64) where the ``absolute_count`` variable in the File Entries tag did not show the correct number.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
