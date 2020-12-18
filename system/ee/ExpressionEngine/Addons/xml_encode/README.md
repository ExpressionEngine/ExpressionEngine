# XML Encode Plugin

This plugin converts reserved XML characters to entities.  It is useful in RSS and XML templates.

## Usage

### {exp:xml_encode}

To use this plugin, wrap anything you want to be processed.

**Note: Because quotes are converted into &amp;quot; and &amp;apos; by this plugin, you cannot use ExpressionEngine conditionals inside of this plugin tag.**

#### Example Usage

```
{exp:xml_encode}
    text you want processed
{/exp:xml_encode}
```

#### Available Parameters

##### protect_entities

Values: yes/no (*default no*)

If you have existing entities in the text that you do not wish to be converted, you may use the parameter `protect_entities="yes"`, e.g.:

```
{exp:xml_encode}
    Text &amp; Entities
{/exp:xml_encode}
```

results in `Text &amp;amp; Entities`

```
{exp:xml_encode protect_entities="yes"}
    Text &amp; Entities
{/exp:xml_encode}
```

results in `Text &amp; Entities`

## Change Log

### 1.4

- Updated plugin to be 3.0 compatible

### 1.3

- Updated plugin to be 2.0 compatible

