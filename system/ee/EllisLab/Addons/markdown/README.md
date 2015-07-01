# My Addon

An add-on that does such and such.

## Example Usage

This plugin parses text using Markdown and Smartypants. To use this plugin warp any text in this text pair.

```
{exp:markdown}
	Text to be **parsed**.
{/exp:markdown}
```

## Available Parameters

### encode_ee_tags

Defaults to **yes**. When set to **no** allows EE code to be rendered.

```
{exp:markdown encode_ee_tags="no"}
	Text to be **parsed**.
{/exp:markdown}
```

### smartypants

Defaults to **yes**. When set to *no* stops SmartyPants from running which leaves your quotes and hyphens alone.

Here are three reasons why you want to keep SmartyPants enabled:

* Smart quotes
* Dash conversion to en- and em-dashes
* Three dots (`...`) conversion to an ellipsis

```
{exp:markdown smartypants="no"}
	Text to be **parsed**.
{/exp:markdown}
```