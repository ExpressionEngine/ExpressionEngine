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

- Added the ability for URL fields to accept the ``mailto:`` protocol. Or fixed that bug, depending on your 🏔🔭.
- Fixed a bug where some member validation language keys may appear unparsed in some third-party contexts.
- Fixed a potential issue (#76) where some jQuery selectors weren't specific enough.
- Fixed a bug where some SVGs in File fields would not render a preview on the publish form.
- Fixed a bug (#64) where the ``absolute_count`` variable in the File Entries tag did not show the correct number.
- Fixed a bug (#23587) where Markdown links with inline title attributes would not parse correctly.
- Fixed a bug (#94) where the ``:limit`` modifier would not preserve whole words as documented.
- Fixed a bug (#101) where there may be errors on a member profile page after creating a new MSM site.
- Fixed a bug (#104) where pipe characters would not be stripped in the Text formatter's ``urlSlug()`` method.
- Fixed a bug where Relationship fields may appear unparsed.
- Fixed a bug where required Grid columns may not have the proper styling on the publish form.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
