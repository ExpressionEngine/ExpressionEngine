# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed a bug (#<linked issue number>) where <bug behavior>.

    - Fixed a bug([\#234](https://github.com/ExpressionEngine/ExpressionEngine/issues/234))  where deleting a category from the entry form forced a logout if sessions were being used.
    - Fixed a bug in the member manager where an error occured when viewed by member groups without member edit permission.
    - Optimized Member model to reduce potential duplicate queries setting up field structure.
    - Added a config override, `disable_emoji_shorthand` to disable Emoji shorthand parsing, e.g. :rocket: to 🚀


EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
