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

- Improved revision listings (`#87 <https://github.com/ExpressionEngine/ExpressionEngine/pull/87>`__) for entry and template versioning, to sort in reverse chronological order.
- Fixed a bug (`#55 <https://github.com/ExpressionEngine/ExpressionEngine/issues/55>`__) where fields may parse incorrectly if they shared similar names.
- Fixed a bug (`#119 <https://github.com/ExpressionEngine/ExpressionEngine/issues/119>`__) where Simple Commerce subscription end date was not correctly formatted before output.
- Fixed a bug (`#114 <https://github.com/ExpressionEngine/ExpressionEngine/issues/114>`__) where dates may be incorrectly localized.
- Fixed a bug (`#124 <https://github.com/ExpressionEngine/ExpressionEngine/issues/124>`__) where new Channels could not be saved if there were a large number of authors.
- Changed hard-coded system paths to be dynamic in some error messages. (`#126 <https://github.com/ExpressionEngine/ExpressionEngine/pull/126>`__)

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
