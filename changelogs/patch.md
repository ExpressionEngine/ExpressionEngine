# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release

Bullet list below, e.g.
- Added <new feature>
- Fixed a PHP 7.4 warning, `Trying to access array offset on value of type bool` that could occur when uploading images.


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
   - Fixed a bug ([#416](https://github.com/ExpressionEngine/ExpressionEngine/issues/416)) PHP error was thrown in some cases during check for available updates.
   - Implemented different approach to trigger `before_channel_entry_delete` extension hook. Fixes a bug ([#487](https://github.com/ExpressionEngine/ExpressionEngine/issues/487)) where custom fields data were not available for extensions when deleting entry.



EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
