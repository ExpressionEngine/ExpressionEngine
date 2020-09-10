# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed a bug (#53) where previous month link was not clickable in Channel Form datepicker.
   - Fixed a bug (#72) where Maximum rows limit was not respected in File Grid field.
   - Fixed a bug (#283) where "field required" indicator was not showing a Grid column.
   - Fixed a bug (#432) where parent entries were not fetched for relationship field inside grid.
   - Fixed a bug (#450) where pagination on tables was not working correctly when performing search for html tags.
   - Fixed a bug (#[457](https://github.com/ExpressionEngine/ExpressionEngine/issues/457)) where accented characters in variables were not truncated properly


   - Fixed a PHP error that could occur on publish if Pages was installed and hidden via layouts.
   - Fixed a bug ([#230](https://github.com/ExpressionEngine/ExpressionEngine/issues/230)) where accepting checksum in CP might result in wrong redirect when session type is "Session ID only".
   - Fixed a bug ([#496](https://github.com/ExpressionEngine/ExpressionEngine/issues/496)) where file was sent twice when using drag&drop upload.
   - Fixed a bug ([#399](https://github.com/ExpressionEngine/ExpressionEngine/issues/399)) in the Page's tab where setting a default template forced the Page URI field to be required.
   - Fixed a rare PHP warning in the typography class.
   - Fixed a bug where some member pages did not display in the forums when using the forum tag on regular templates.
   - Fixed a bug ([#419](https://github.com/ExpressionEngine/ExpressionEngine/issues/419)) where deprecated pagination code in member templates could cause a PHP error.
   - Updates additional files for PHP 7.4 compatibility.
   - Fixed a PHP warning in the control panel when IDN variants weren't available on the server.
   - Fixed a bug where a query string could be added to URLs erroneously.
   - Fixed a bug ([#379](https://github.com/ExpressionEngine/ExpressionEngine/issues/379)) where comment subscription emails contained an invalid unsubscribe link.

   - Altered a javascript filename that mod_security tended to object to.
   - Fixed a bug where input data were assumed to be URL encoded, causing certain character sequences to be stripped when cleaned.
   - Implemented different approach to trigger `before_channel_entry_delete` extension hook. Fixes a bug ([#487](https://github.com/ExpressionEngine/ExpressionEngine/issues/487)) where custom fields data were not available for extensions when deleting entry.



EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
