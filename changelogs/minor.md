# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for MINOR version changes only.

## Minor Release

- Added relationship_entries_tagdata hook, which is functionally identical to the channel_entries_tagdata hook 
- Fixed a bug (#383) where Moblog wasn't functioning.
- Fixed a bug where checking for updates might produce an error.
- Fixed a bug where removing database record for template that is used as "No access redirect" would cause error
- Added support for SameSite cookies
- Fixed a bug (#438) where JS combo loader was throwing error if extra `v` was passed into URL.
- Fixed a bug (#91, #417) where link button was not working and formatting not displayed in RTE field on frontend.
- Added validation for category parent (#411)
- Fixed a bug (#428) where Grid was throwing error PHP with certain fieldtypes.
- Fixed a bug (#421) where attachments were not sent from Communicate page.
- Added config override to ignore channel stats
- Add stats module action to run stats
- Fixed a bug where searching entries in CP in content only could produce SQL error.
- Added dabatase column type selector for textarea and RTE fields (#464)
- Added post-upgrade and utility check for broken template tags and missing fieldtypes
- Fixed a bug (#480) where there has been no notice when extensions are disabled.
- Fixed a bug (#499) where categories hidden from channel layout might get lost upon saving the entry.
- Fixed a bug where unsaved entried were not pulled in for live preview when using `status="open|closed"` parameter.
- Adds namespacing to v2 upgrades for ease of upgrading from v2 to v5
- Adds CLI feature

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
