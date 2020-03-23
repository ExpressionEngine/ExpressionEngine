# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Adds PHP7.4 support
   - Adds validation for pages URIs without a selected template
   - Adding `is_system_on` setting caching, so updating does not alter whether the system is on after updating or bailing
   - Fixes variable type notices
   - Added default to same page for consent return
   - Fixes bug where `after_channel_entry_save` hook would run twice.
   - Ignores url_title in fetch param function, for issues related to URL titles called `n`
   - Fixes strpos `Non-string needles will be interpreted as strings in the future.` issue
   - Fixes a bug ([#382](https://github.com/ExpressionEngine/ExpressionEngine/issues/382)) where MSM File Fields breaking because of js error.
   - Fixes error in Pages module that leaves orphaned entries 


EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
