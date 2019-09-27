##########################
ExpressionEngine Changelog
##########################

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for MINOR version changes only.

.. note:: Move all changes to User Guide upon public release ``/changelog.rst``

.. note:: Please keep bug fixes separate from features and modifications


*************
Minor Release
*************

  - Added event hooks to the Channel, ChannelFormSettings, ChannelLayout, Site, Snippet, and Specialty Template models.
  - Fixed a bug ([#306](https://github.com/ExpressionEngine/ExpressionEngine/issues/306)] where {encode} variable output didn't pass the WC3 validator.
  - Added a config override `save_tmpl_globals` to allow separate saving behavior for global variables
  - Added a category_group parameter to the Category Archive tag.


EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
