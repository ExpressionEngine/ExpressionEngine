This plugin parses text using Markdown and Smartypants. To use this plugin wrap any text in this tag pair:

	{exp:markdown}
		Text to be **parsed**.
	{/exp:markdown}

There are two parameters you can set:

- convert_curly - ('yes'/'no') defaults to 'yes', when set to 'no' will not
  convert all curly brackets to entities, which can be useful to display
  variables
- smartypants - ('yes'/'no') defaults to 'yes', when set to 'no' stops
  SmartyPants from running which leaves your quotes and hyphens alone

## Change Log

- 1.1
	- Updated plugin to be 3.0 compatible
