##########################
ExpressionEngine Changelog
##########################

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for MINOR version changes only.

.. note:: Move all changes to User Guide upon public release ``/changelog.rst``

.. note:: Please keep bug fixes separate from features and modifications


*************
Minor Release
*************

   - Fixed Bug (#139) where on some servers the mime type of SVG is different then we expected.
   - Fixed Bug (#143) where dbforge->add_key(array()) would create individual, non-sequenced keys rather than make a multi-column key.
   - Fixed Bug in the forum RSS feed where a PHP error caused an invalid feed.


EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
