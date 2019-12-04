# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for MAJOR version changes only.

## Major Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed Bug (#<issue number>) where <bug behavior>.


- Default avatars have been removed.
- The system avatar settings "Allow avatar uploads?" and "Allow avatars?" have been removed.

- Template editor improvements
    - You can now comment EE code with `command + /` in the template editor
    - You can now select a single line of text when clicking on a gutter number in the template editor
    - Improved EE syntax highlighting
- Fixed a bug where the debugger code highlighter would also highlight and overwrite other code blocks on a site's page
- Added support for third-party add-on icons to Add-on Manager

Developers
- Updated CodeMirror to version 5.48
- Deprecated the Channel Status controller `getForegroundColor()`
- The member property `display_avatars` has been removed
- The config options `enable_avatars` and `allow_avatar_uploads` have been removed
   - Fixed a bug (#<linked issue number>) where <bug behavior>.
- Removed the deprecated jQuery add-on
- Removed the deprecated Emoticon add-on

- Forgot Password emails will now respect your "Mail Format" preference (essentially enabling the ability to use HTML in Forgot Password emails).
- Fixed a bug where table bulk selections can be saved by the browser on page reload, but don't show in the UI.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
