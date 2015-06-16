<?php

return array(
	'author'      => 'EllisLab',
	'author_url'  => 'http://ellislab.com/',
	'name'        => 'Markdown',
	'description' => 'Parse text using Markdown and Smartypants',
	'version'     => '1.0',
	'namespace'   => 'EllisLab\Addons\Markdown',
	'settings_exist' => FALSE,

	'plugin.typography' => TRUE,
	'plugin.usage' => array(
		'description'	=> 'This plugin parses text using Markdown and Smartypants. To use this plugin wrap any text in this tag pair.',
		'example'		=> <<<'EXAMPLE'
{exp:markdown}
	Text to be **parsed**.
{/exp:markdown}
EXAMPLE
,
		'parameters'	=> array(
			'convert_curly'	=> array(
				'description'	=> "Defaults to <b>yes</b>. When set to <b>no</b> will not convert all curly brackets to entities, which can be useful to display variables.",
				'example'		=> <<<'EXAMPLE'
{exp:markdown convert_curly="no"}
	Text to be **parsed**.
{/exp:markdown}
EXAMPLE
			),
			'smartypants'	=> array(
				'description'	=> "Defaults to <b>yes</b>. When set to <b>no</b> stops SmartyPants from running which leaves your quotes and hyphens alone.",
				'example'		=> <<<'EXAMPLE'
{exp:markdown smartypants="no"}
	Text to be **parsed**.
{/exp:markdown}
EXAMPLE
			)
		)
	)
);