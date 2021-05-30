# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for MAJOR version changes only.

## Major Release

Bullet list below, e.g.
   - Added <new feature>
   - Fixed Bug (#<issue number>) where <bug behavior>.

- Added Jump Menu. Navigate ExpressionEngine fast
- Added a new color picker fieldtype

- Member Groups have been replaced with member roles.
  - Members have one primary roles, and can also can have multiple other roles
  - Role permissions are additive

- New Control Panel Design
  - Many new changes and improvements that make the control panel cleaner, and more delightful to use
  - Brand new dark theme.
  - New sidebar navigation
  - Better navigation. Navigation buttons are now in a more consistent location. The member account menu shows the member's primary role. "Manager" has been removed from most of the page names, e.g "Entry Manager" is now "Entries". Navigation works better on mobile.
  - Add-ons and categories have been moved out of the dev menu and into the sidebar
  - The files page has a new thumbnail view
  - Editing and preview files is now easier in the files manager
  - You can now drag to change the width of the live preview panes
  - The add-ons page uses a new card view, shows add-on icons, and has a separate tab for updates
  - The SQL query form has new buttons to insert common used SQL snippets
  - The tabs and save buttons on the edit entry page are now sticky
  - The date picker has a new today button, and days are easier to click.
  - The grid field now collapses on mobile
  - The dashboard has been upgraded to be more useful.
  - "Remove" wording has been changed to the more appropriate "delete" for destructive actions.
  - Deletion confirm dialogs are more scarry
  - Pagination improvements. Pagination shows 8 pages, instead of 3.
  - You can now tab to toggle buttons
  - And many more changes!
  - Template editor improvements
      - You can now comment EE code with `command + /` in the template editor
      - You can now select a single line of text when clicking on a gutter number in the template editor
      - Improved EE syntax highlighting

- Fixed a bug where the debugger code highlighter would also highlight and overwrite other code blocks on a site's page
- Added support for third-party add-on icons to Add-on Manager
- Changed sidebar copyright company name
- Default avatars have been removed
- The system avatar settings "Allow avatar uploads?" and "Allow avatars?" have been removed
- Fixed bug #996 where resizing textareas beyond the parent element was breaking other children of that parent.

Developers
- Updated CodeMirror to version 5.48
- Deprecated the Channel Status controller `getForegroundColor()`
- The member property `display_avatars` has been removed
- The config options `enable_avatars` and `allow_avatar_uploads` have been removed
- Removed the deprecated jQuery add-on
- Removed the deprecated Emoticon add-on

- Forgot Password emails will now respect your "Mail Format" preference (essentially enabling the ability to use HTML in Forgot Password emails).
- Fixed a bug where table bulk selections can be saved by the browser on page reload, but don't show in the UI.
- Resending member activation emails now displays returned errors on failures to send.
- Control panel status column in entry list displays status text casing which matches the actual casing as entered into the system on creation. 

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
