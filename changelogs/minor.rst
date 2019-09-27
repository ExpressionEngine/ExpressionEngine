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
  - Fixed Bug (#139) where on some servers the mime type of SVG is different then we expected.
  - Fixed Bug (#143) where dbforge->add_key(array()) would create individual, non-sequenced keys rather than make a multi-column key.
  - Fixed a bug in the forum RSS feed where a PHP error caused an invalid feed.
  - Fixed a bug in the Channel Form where the unique_url_title and dynamic_title parameters did not work properly together.


EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
