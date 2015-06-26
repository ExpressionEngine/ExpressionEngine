# This is a level 1 header

This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

## This is a level 2 header

This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

### This is a level 3 header

This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

#### This is a level 4 header

This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

##### This is a level 5 header

This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

###### This is a level 6 header

This plugin converts reserved XML characters to entities.  It is used in the RSS templates.

## Example Usage

To use this plugin, wrap anything you want to be processed by it between these tag pairs:

	{exp:xml_encode}

		text you want processed

	{/exp:xml_encode}

## Parameters via ul

-  protect_entities

   Note: Because quotes are converted into &amp;amp; by this plugin, you cannot use
ExpressionEngine conditionals inside of this plugin tag.

   If you have existing entities in the text that you do not wish to be converted, you may use the parameter protect_entities="yes", e.g.:

	{exp:xml_encode}Text &amp; Entities{/exp:xml_encode}

   results in: Text &amp;amp; Entities

	{exp:xml_encode protect_entities="yes"}Text &amp; Entities{/exp:xml_encode}

   results in: Text &amp;amp; Entities
-  fake_param

   This is a description of my fake parameter.

	{exp:xml_code fake="blah"}

   And again with the lack of extra line breaks so no p tags.

## Parameters via ol

1  protect_entities

   Note: Because quotes are converted into &amp;amp; by this plugin, you cannot use
ExpressionEngine conditionals inside of this plugin tag.

   If you have existing entities in the text that you do not wish to be converted, you may use the parameter protect_entities="yes", e.g.:

	{exp:xml_encode}
		Text &amp; Entities
	{/exp:xml_encode}

   results in: Text &amp;amp; Entities

	{exp:xml_encode protect_entities="yes"}
		Text &amp; Entities
	{/exp:xml_encode}

   results in: Text &amp;amp; Entities

2  fake_param

   This is a description of my fake parameter.

	{exp:xml_code fake="blah"}

   And again with the lack of extra line breaks so no p tags.



## Let's nested ol with deeper param values and textarea examples

-  protect_entities

   - Because quotes are converted into &amp;amp; by this plugin, you cannot use
ExpressionEngine conditionals inside of this plugin tag.

   -  Values
      - yes
      - no (default)

   - Example

<textarea>
{exp:xml_encode}
	Text &amp; Entities
{/exp:xml_encode}
</textarea>

   results in: Text &amp;amp; Entities

<textarea>
{exp:xml_encode protect_entities="yes"}
	Text &amp; Entities
{/exp:xml_encode}
</textarea>

   results in: Text & Entities




## Change Log shown via ul with 2 levels

- 1.4
	- Updated plugin to be 3.0 compatible

- 1.3
	- Updated plugin to be 2.0 compatible

