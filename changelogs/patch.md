# ExpressionEngine Changelog

ExpressionEngine uses semantic versioning. This file contains changes to ExpressionEngine since the last Build / Version release for PATCH version changes only.

## Patch Release
- Updates six (6) files for PHP 7.4 compatibility.
    - system/ee/EllisLab/ExpressionEngine/Model/Channel/ChannelLayout.php
        - Ternary check on $field_name
    - system/ee/EllisLab/Addons/channel/mod.channel.php
        - Swap implode parameter order
    - system/ee/EllisLab/Addons/rte/libraries/Rte_lib.php
        - Ternary check on $include['jquery_ui']
    - system/ee/legacy/libraries/channel_entries_parser/components/Category.php
        - Ternary check on $cat_image['url']
    - system/ee/legacy/libraries/Template.php
        - Ternary checks for $last_time and $last_memory
    - system/ee/legacy/core/Input.php
        - PHP version check for magic_quotes_gpc
- Optimized template syncronization queries.

EOF MARKER: This line helps prevent merge conflicts when things are
added on the bottoms of lists
