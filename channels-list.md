# CLI Channels List

Join us at EEConf 2025! {ee:u} Forums

v7

v7 v6 v5 v4 v3 v2 v1

## Getting Started

* Welcome
* Installation & Updates
* Quick Start
* General Information
* Localization
* The Fundamentals
* Control Panel
* Templates
* Channels
* Fieldtypes
* Member Management
* Comments
* Advanced Usage
* Add-Ons
* Add-On Development
* Command Line Interface (CLI)
  * Introduction
  * Usage
  * Built In Commands
    * Add-on Management
    * Add-on Generator
    * Backup Database
    * Clear Cache
    * Command Generator
    * Config Management
    * **Channels List**
    * Migrations
    * Model Generator
    * Sync Conditional Fields
    * Sync Reindex Content
    * Sync Upload Directory
    * Update ExpressionEngine
    * Prolet Generator
    * Widget Generator
  * Creating a Command
  * Defining Input
  * Displaying Output

## CLI Channels List

Lists all channels in the system with their details in various formats.

If you would like to create or manage channels programmatically, see the Channel Model documentation.

## php eecli.php channels:list

### Options list:

```
    --site=<value>
    -s <value>
        Site ID to list channels for

    --format=<value>
    -f <value>
        Output format: table, json, or csv

    --channel_id=<value>
    -c <value>
        Filter by specific channel ID
```

## Listing all channels:

The following commands will list all channels in table format (default):

`php eecli.php channels:list`

`php eecli.php channels:list --format=table`

`php eecli.php channels:list -f table`

## Listing channels for a specific site:

`php eecli.php channels:list --site=1`

`php eecli.php channels:list -s 1`

## Filtering by channel ID:

`php eecli.php channels:list --channel_id=5`

`php eecli.php channels:list -c 5`

## Output in JSON format:

`php eecli.php channels:list --format=json`

`php eecli.php channels:list -f json`

## Output in CSV format:

`php eecli.php channels:list --format=csv`

`php eecli.php channels:list -f csv`

## Combining filters:

You can combine multiple filters to narrow down your results:

`php eecli.php channels:list --site=1 --format=json`

`php eecli.php channels:list -s 1 -c 5 -f table`

---

ExpressionEngine 7 Docs
©2002–2024 Packet Tide,LLC. Edit this page
