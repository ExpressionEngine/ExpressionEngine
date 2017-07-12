<?php

require_once SYSPATH.'ee/EllisLab/ExpressionEngine/Boot/boot.common.php';
require_once APPPATH.'helpers/string_helper.php';
require_once APPPATH.'libraries/Typography.php';
require_once APPPATH.'libraries/typography/Markdown/Michelf/MarkdownExtra.inc.php';

define('PATH_ADDONS', APPPATH.'modules/');

class MarkdownTest extends \PHPUnit_Framework_TestCase {

	private $typography;

	public function setUp()
	{
		$this->typography = new TypographyStub();
	}

	private function getContentForMarkup($name)
	{
		$path = realpath(__DIR__.'/../../../support/typography/' . $name);
		return file_get_contents($path);
	}

	public function testCodeFence()
	{
		$str = $this->typography->markdown($this->getContentForMarkup('code-fence.md'));

		// Make sure we've removed all code fences
		$this->assertNotContains('~~~', $str);
		$this->assertNotContains('```', $str);

		// The ~~``~~ turns to code (`` => <code>)
		$this->assertContains('~~<code>~~', $str);
		$this->assertContains('~~</code>~~', $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testCodeBlock()
	{
		$str = $this->typography->markdown($this->getContentForMarkup('code-block.md'));

		// Should contain no tabs
		$this->assertNotContains("\t", $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testCodeBlockAndFence()
	{
		$str = $this->typography->markdown($this->getContentForMarkup('code-block-and-fence.md'));

		// Should contain no tabs
		$this->assertNotContains("\t", $str);

		// Make sure we've removed all code fences
		$this->assertNotContains('~~~', $str);
		$this->assertNotContains('```', $str);

		// The ~~``~~ turns to code (`` => <code>)
		$this->assertContains('~~<code>~~', $str);
		$this->assertContains('~~</code>~~', $str);

		// Must contain unaffected opening and close php tags
		$this->assertContains('&lt;?php', $str);
		$this->assertContains('?&gt;', $str);
	}

	public function testSmartyPants()
	{
		$smartypants = $this->getContentForMarkup('smartypants.md');
		$str = $this->typography->markdown($smartypants);

		// The em and en dashes should be where you'd expect them
		$this->assertContains("dashes&#8212;they", $str);
		$this->assertContains("thoughts&#8212;with", $str);
		$this->assertContains("2004&#8211;2014", $str);

		// Fancy quotes should be around "fancy quotes" and 'there'
		$this->assertContains("&#8220;fancy quotes&#8221;", $str);
		$this->assertContains("&#8216;there&#8217;", $str);

		// Test WITHOUT SmartyPants
		$str = $this->typography->markdown($smartypants, array('smartypants' => FALSE));

		// dashes and quotes should not be converted
		$this->assertContains("dashes---they", $str);
		$this->assertContains("thoughts---with", $str);
		$this->assertContains("2004--2014", $str);
		$this->assertContains("\"fancy quotes\"", $str);
		$this->assertContains("'there'", $str);
	}

	public function testNoMarkup()
	{
		$markdown = $this->getContentForMarkup('markdown.md');
		$str = $this->typography->markdown($markdown, array('no_markup' => TRUE));

		// Make sure markup is not parsed
		$this->assertNotContains('<div', $str);
		$this->assertNotContains('<span>really</span>', $str);
		$this->assertNotContains('<em>just</em>', $str);
	}

	public function testLinksWithSpaces()
	{
		$markdown = $this->getContentForMarkup('markdown.md');
		$str = $this->typography->markdown($markdown);
		$this->assertContains('<a href="https://packagecontrol.io/packages/Marked%20App%20Menu">Marked App Menu</a>', $str);
	}

	/**
	 * @dataProvider markdownData
	 */
	public function testMarkdown($description, $in, $out)
	{
		$str = $this->typography->markdown($in);
		$this->assertEquals($str, $out, $description);
	}

	public function markdownData()
	{
		$data = array(
			// Automatic Escaping
			array('Ampersands', 'AT&T', "<p>AT&amp;T</p>\n"),
			array('HTML Entity', '&copy;', "<p>&copy;</p>\n"),
			array('Angle brackets', '4 < 5 and 3 > 4', "<p>4 &lt; 5 and 3 > 4</p>\n"),

			// Links
			// array('Link with title attribute', 'This is [an example](http://example.com/ "Title") inline link.', ""),
			array('Link without title attribute', '[This link](http://example.net/) has no title attribute.', "<p><a href=\"http://example.net/\">This link</a> has no title attribute.</p>\n"),
			array('Relative URLs', 'See my [About](/about/) page for details.', "<p>See my <a href=\"/about/\">About</a> page for details.</p>\n"),
			array('Refernce style link', "This is [an example][id] reference-style link.\n\n[id]: http://example.com/  \"Optional Title Here\"\n", "<p>This is <a href=\"http://example.com/\" title=\"Optional Title Here\">an example</a> reference-style link.</p>\n"),
			array('Refernce style link (with space)', "This is [an example] [id] reference-style link.\n\n[id]: http://example.com/  \"Optional Title Here\"\n", "<p>This is <a href=\"http://example.com/\" title=\"Optional Title Here\">an example</a> reference-style link.</p>\n"),
			// array('Refernce style link (with single quote)', "This is [an example][id] reference-style link.\n\n[id]: http://example.com/  'Optional Title Here'", "<p>This is <a href=\"http://example.com/\" title=\"Optional Title Here\">an example</a> reference-style link.</p>\n"),
			array('Refernce style link (with parenthesis)', "This is [an example][id] reference-style link.\n\n[id]: http://example.com/  (Optional Title Here)", "<p>This is <a href=\"http://example.com/\" title=\"Optional Title Here\">an example</a> reference-style link.</p>\n"),
			array('Refernce style link (with angle brackets)', "This is [an example][id] reference-style link.\n\n[id]: <http://example.com/>  \"Optional Title Here\"\n", "<p>This is <a href=\"http://example.com/\" title=\"Optional Title Here\">an example</a> reference-style link.</p>\n"),
			array('Refernce style link (with title on newline)', "This is [an example][id] reference-style link.\n\n[id]: http://example.com/\n\t\"Optional Title Here\"\n", "<p>This is <a href=\"http://example.com/\" title=\"Optional Title Here\">an example</a> reference-style link.</p>\n"),
			array('Refernce style implicit link', "Visit [Daring Fireball][] for more information.\n\n[Daring Fireball]: http://daringfireball.net/", "<p>Visit <a href=\"http://daringfireball.net/\">Daring Fireball</a> for more information.</p>\n"),
			array('Automatic link', '<http://example.com>', '<p><a href="http://example.com">http://example.com</a></p>' . "\n"),
			array('Automatic email ink', '<address@example.com>', '<p><a href="&#109;&#x61;&#x69;&#108;&#x74;&#x6f;&#58;&#x61;&#x64;&#100;&#114;&#x65;&#115;&#115;&#x40;&#101;&#120;&#x61;m&#112;&#x6c;e&#46;&#x63;&#x6f;&#109;">&#x61;&#x64;&#100;&#114;&#x65;&#115;&#115;&#x40;&#101;&#120;&#x61;m&#112;&#x6c;e&#46;&#x63;&#x6f;&#109;</a></p>' . "\n"),

			// Emphasis
			array('Single asterisks', '*single asterisks*', "<p><em>single asterisks</em></p>\n"),
			array('Single underscores', '_single underscores_', "<p><em>single underscores</em></p>\n"),
			array('Double asterisks', '**double asterisks**', "<p><strong>double asterisks</strong></p>\n"),
			array('Double underscores', '__double underscores__', "<p><strong>double underscores</strong></p>\n"),
			array('Single asterisks in the middle of a word', 'un*frigging*believable', "<p>un<em>frigging</em>believable</p>\n"),
			array('Double asterisks in the middle of a word', 'un**frigging**believable', "<p>un<strong>frigging</strong>believable</p>\n"),
			array('Literal asterisk', '8 * 7 = 56', "<p>8 * 7 = 56</p>\n"),
			array('Literal underscore', 'Literal _ underscore', "<p>Literal _ underscore</p>\n"),
			array('Escaped asterisk', '\*this text is surrounded by literal asterisks\*', "<p>&#42;this text is surrounded by literal asterisks&#42;</p>\n"),
			array('Escaped underscore', '\_this text is surrounded by literal asterisks\_', "<p>&#95;this text is surrounded by literal asterisks&#95;</p>\n"),

			// Code
			array('Span of code', 'Use the `printf()` function.', "<p>Use the <code>printf()</code> function.</p>\n"),
			array('Literal backtick', '``There is a literal backtick (`) here.``', "<p><code>There is a literal backtick (`) here.</code></p>\n"),
			array('Encoded angle brackets inside code span', "Please don't use any `<blink>` tags.", "<p>Please don&#8217;t use any <code>&lt;blink&gt;</code> tags.</p>\n"),
			array('Encoded ampersands inside code span', '`&#8212;` is the decimal-encoded equivalent of `&mdash;`.', "<p><code>&amp;#8212;</code> is the decimal-encoded equivalent of <code>&amp;mdash;</code>.</p>\n"),

			// Images
			array('Image tag', '![Alt text](/path/to/img.jpg)', "<p><img src=\"/path/to/img.jpg\" alt=\"Alt text\" /></p>\n"),
			// array('Image tag with title', '![Alt text](/path/to/img.jpg "Optional title")', "\n"),
			array('Reference style image tag', "![Alt text][id]\n\n[id]: url/to/image  \"Optional title attribute\"\n", "<p><img src=\"url/to/image\" alt=\"Alt text\" title=\"Optional title attribute\" /></p>\n"),

			// Escapes
			array('Escaped backslash', '\\\\', "<p>&#92;</p>\n"),
			array('Escaped backtick', '\`', "<p>&#96;</p>\n"),
			array('Escaped asterisk', '\*', "<p>&#42;</p>\n"),
			array('Escaped underscore', '\_', "<p>&#95;</p>\n"),
			array('Escaped curly braces', '\{\}', "<p>&#123;&#125;</p>\n"),
			array('Escaped square brackets', '\[\]', "<p>&#91;&#93;</p>\n"),
			array('Escaped parentheses', '\(\)', "<p>&#40;&#41;</p>\n"),
			array('Escaped hash mark', '\#', "<p>&#35;</p>\n"),
			array('Escaped plus sign', '\+', "<p>&#43;</p>\n"),
			array('Escaped hyphen', '\-', "<p>&#45;</p>\n"),
			array('Escaped dot', '\.', "<p>&#46;</p>\n"),
			array('Escaped exclamation mark', '\!', "<p>&#33;</p>\n"),

			// Horizontal rules
			array('HR by "* * *"', '* * *', "<hr />\n"),
			array('HR by "***"', '***', "<hr />\n"),
			array('HR by "*****"', '*****', "<hr />\n"),
			array('HR by "---"', '---', "<hr />\n"),
			array('HR by "- - -"', '- - -', "<hr />\n"),
			array('HR by "-----"', '-----', "<hr />\n"),
			array('HR by "___"', '___', "<hr />\n"),
			array('HR by "_ _ _"', '_ _ _', "<hr />\n"),
			array('HR by "_____"', '_____', "<hr />\n"),

			// Headers
			array('# H1', '# This is an H1', "<h1>This is an H1</h1>\n"),
			array('## H2', '## This is an H2', "<h2>This is an H2</h2>\n"),
			array('### H3', '### This is an H3', "<h3>This is an H3</h3>\n"),
			array('#### H4', '#### This is an H4', "<h4>This is an H4</h4>\n"),
			array('##### H5', '##### This is an H5', "<h5>This is an H5</h5>\n"),
			array('###### H6', '###### This is an H6', "<h6>This is an H6</h6>\n"),
			array('# H1 #', '# This is an H1 #', "<h1>This is an H1</h1>\n"),
			array('## H2 ##', '## This is an H2 ##', "<h2>This is an H2</h2>\n"),
			array('### H3 ###', '### This is an H3 ###', "<h3>This is an H3</h3>\n"),
			array('#### H4 ####', '#### This is an H4 ####', "<h4>This is an H4</h4>\n"),
			array('##### H5 #####', '##### This is an H5 #####', "<h5>This is an H5</h5>\n"),
			array('###### H6 ######', '###### This is an H6 ######', "<h6>This is an H6</h6>\n"),
			array('H1 by underscore', "This is an H1\n=============", "<h1>This is an H1</h1>\n"),
			array('H2 by underscore', "This is an H2\n-------------", "<h2>This is an H2</h2>\n"),

			// Blockquotes
			array('Email style blockquote', $this->getContentForMarkup('email-style-blockquote.in.md'), $this->getContentForMarkup('email-style-blockquote.out.md')),
			array('Lazy style blockquote', $this->getContentForMarkup('lazy-style-blockquote.in.md'), $this->getContentForMarkup('lazy-style-blockquote.out.md')),
			array('Nested blockquotes', $this->getContentForMarkup('nested-blockquotes.in.md'), $this->getContentForMarkup('nested-blockquotes.out.md')),
			array('Markdown inside blockquote', $this->getContentForMarkup('markdown-in-blockquote.in.md'), $this->getContentForMarkup('markdown-in-blockquote.out.md')),

			// Lists
			array('List by asterisk', $this->getContentForMarkup('list-by-asterisk.in.md'), $this->getContentForMarkup('list-by-asterisk.out.md')),
			array('List by plus', $this->getContentForMarkup('list-by-plus.in.md'), $this->getContentForMarkup('list-by-plus.out.md')),
			array('List by hyphen', $this->getContentForMarkup('list-by-hyphen.in.md'), $this->getContentForMarkup('list-by-hyphen.out.md')),
			array('Ordered list', $this->getContentForMarkup('ordered-list.in.md'), $this->getContentForMarkup('ordered-list.out.md')),
			array('Wrapped lists', $this->getContentForMarkup('wrapped-lists.in.md'), $this->getContentForMarkup('wrapped-lists.out.md')),
			array('Lists with paragraph tags', $this->getContentForMarkup('lists-with-paragraph-tags.in.md'), $this->getContentForMarkup('lists-with-paragraph-tags.out.md')),
			array('Lists with paragraphs', $this->getContentForMarkup('lists-with-paragraphs.in.md'), $this->getContentForMarkup('lists-with-paragraphs.out.md')),
			array('Lists with blockquotes', $this->getContentForMarkup('lists-with-blockquotes.in.md'), $this->getContentForMarkup('lists-with-blockquotes.out.md')),
			array('Lists with code blocks', $this->getContentForMarkup('lists-with-code-blocks.in.md'), $this->getContentForMarkup('lists-with-code-blocks.out.md')),
			array('Not a list', '1986\. What a great season.', "<p>1986&#46; What a great season.</p>\n"),

			// Code blocks
			array('Code block by 4 spaces', $this->getContentForMarkup('codeblock-by-4-spaces.in.md'), $this->getContentForMarkup('codeblock-by-4-spaces.out.md')),
			array('Code block by 5 spaces', $this->getContentForMarkup('codeblock-by-5-spaces.in.md'), $this->getContentForMarkup('codeblock-by-5-spaces.out.md')),
			array('Code block by 1 tab', $this->getContentForMarkup('codeblock-by-1-tab.in.md'), $this->getContentForMarkup('codeblock-by-1-tab.out.md')),
			array('Code block by 2 tabs', $this->getContentForMarkup('codeblock-by-2-tabs.in.md'), $this->getContentForMarkup('codeblock-by-2-tabs.out.md')),
			array('Code block with encoded ampersands', $this->getContentForMarkup('codeblock-with-encoded-ampersands.in.md'), $this->getContentForMarkup('codeblock-with-encoded-ampersands.out.md')),
			array('Code block with encoded angle brackets', $this->getContentForMarkup('codeblock-with-encoded-angle-brackets.in.md'), $this->getContentForMarkup('codeblock-with-encoded-angle-brackets.out.md')),
		);

    return array_merge($data, $this->markdownExtraData());
	}

  public function markdownExtraData()
  {
    return array(
      array('Markdown inside HTML blocks', $this->getContentForMarkup('markdown-inside-html-blocks.in.md'), $this->getContentForMarkup('markdown-inside-html-blocks.out.md')),

      // Special Attributes
      array('Header: ID', '## Header 2 {#header2}', "<h2 id=\"header2\">Header 2</h2>\n"),
      array('Header: Class names', '## Header 2 {.main}', "<h2 class=\"main\">Header 2</h2>\n"),
      array('Header: Custom attributes', '## Le Header 2 {lang=fr}', "<h2 lang=\"fr\">Le Header 2</h2>\n"),
      array('Header: Multiple attributes', '## Le Header 2 {.main .shine lang=fr #header2}', "<h2 id=\"header2\" class=\"main shine\" lang=\"fr\">Le Header 2</h2>\n"),
      array('Link: ID', '[link](/url){#header2}', "<p><a href=\"/url\" title=\"\" id=\"header2\">link</a></p>\n"),
      array('Link: Class names', '[link](/url){.main}', "<p><a href=\"/url\" title=\"\" class=\"main\">link</a></p>\n"),
      array('Link: Custom attributes', '[link](/url){lang=fr}', "<p><a href=\"/url\" title=\"\" lang=\"fr\">link</a></p>\n"),
      array('Link: Multiple attributes', '[link](/url){.main .shine lang=fr #header2}', "<p><a href=\"/url\" title=\"\" id=\"header2\" class=\"main shine\" lang=\"fr\">link</a></p>\n"),
      array('Reference Link: ID', "[link][linkref]\n\n[linkref]: /url {#header2}", "<p><a href=\"/url\" title=\"\" id=\"header2\">link</a></p>\n"),
      array('Reference Link: Class names', "[link][linkref]\n\n[linkref]: /url {.main}", "<p><a href=\"/url\" title=\"\" class=\"main\">link</a></p>\n"),
      array('Reference Link: Custom attributes', "[link][linkref]\n\n[linkref]: /url {lang=fr}", "<p><a href=\"/url\" title=\"\" lang=\"fr\">link</a></p>\n"),
      array('Reference Link: Multiple attributes', "[link][linkref]\n\n[linkref]: /url {.main .shine lang=fr #header2}", "<p><a href=\"/url\" title=\"\" id=\"header2\" class=\"main shine\" lang=\"fr\">link</a></p>\n"),
      array('Image: ID', '![link](/url){#header2}', "<p><img src=\"/url\" alt=\"link\" title=\"\" id=\"header2\" /></p>\n"),
      array('Image: Class names', '![link](/url){.main}', "<p><img src=\"/url\" alt=\"link\" title=\"\" class=\"main\" /></p>\n"),
      array('Image: Custom attributes', '![link](/url){lang=fr}', "<p><img src=\"/url\" alt=\"link\" title=\"\" lang=\"fr\" /></p>\n"),
      array('Image: Multiple attributes', '![link](/url){.main .shine lang=fr #header2}', "<p><img src=\"/url\" alt=\"link\" title=\"\" id=\"header2\" class=\"main shine\" lang=\"fr\" /></p>\n"),
      array('Fenced code block: ID', "~~~ {#header2}\ncode block\n~~~\n", "<pre><code id=\"header2\">code block\n</code></pre>\n"),
      array('Fenced code block: Class names', "~~~ {.main}\ncode block\n~~~\n", "<pre><code class=\"main\">code block\n</code></pre>\n"),
      array('Fenced code block: Custom attributes', "~~~ {lang=fr}\ncode block\n~~~\n", "<pre><code lang=\"fr\">code block\n</code></pre>\n"),
      array('Fenced code block: Multiple attributes', "~~~ {.main .shine lang=fr #header2}\ncode block\n~~~\n", "<pre><code id=\"header2\" class=\"main shine\" lang=\"fr\">code block\n</code></pre>\n"),

      // Fenced Code Blocks
			array('Fenced code block by 3 tildes', $this->getContentForMarkup('codeblock-by-3-tildes.in.md'), $this->getContentForMarkup('codeblock-by-3-tildes.out.md')),
			array('Fenced code block by 4 tildes', $this->getContentForMarkup('codeblock-by-4-tildes.in.md'), $this->getContentForMarkup('codeblock-by-4-tildes.out.md')),
			array('Fenced code block by 3 backticks', $this->getContentForMarkup('codeblock-by-3-backticks.in.md'), $this->getContentForMarkup('codeblock-by-3-backticks.out.md')),
			array('Fenced code block by 4 backticks', $this->getContentForMarkup('codeblock-by-4-backticks.in.md'), $this->getContentForMarkup('codeblock-by-4-backticks.out.md')),
			array('Fenced code block beginning and ending with blank lines', $this->getContentForMarkup('codeblock-begin-end-blank-lines.in.md'), $this->getContentForMarkup('codeblock-begin-end-blank-lines.out.md')),
			array('Fenced code block after a list', $this->getContentForMarkup('codeblock-after-list.in.md'), $this->getContentForMarkup('codeblock-after-list.out.md')),

      // Tables
      array('Table', $this->getContentForMarkup('table.in.md'), $this->getContentForMarkup('table.out.md')),
      array('Table with leading and tailing pipes', $this->getContentForMarkup('table-with-pipes.in.md'), $this->getContentForMarkup('table-with-pipes.out.md')),
      array('Table with alignment', $this->getContentForMarkup('table-with-alignment.in.md'), $this->getContentForMarkup('table-with-alignment.out.md')),
      array('Table with span-level formatting', $this->getContentForMarkup('table-with-formatting.in.md'), $this->getContentForMarkup('table-with-formatting.out.md')),

      // Definition Lists
      array('Definition list', $this->getContentForMarkup('definition-list.in.md'), $this->getContentForMarkup('definition-list.out.md')),
      array('Definition list wrapped', $this->getContentForMarkup('definition-list-wrapped.in.md'), $this->getContentForMarkup('definition-list-wrapped.out.md')),
      array('Definition list indented', $this->getContentForMarkup('definition-list-indented.in.md'), $this->getContentForMarkup('definition-list-indented.out.md')),
      array('Definition list multiple definitions', $this->getContentForMarkup('definition-list-multiple.in.md'), $this->getContentForMarkup('definition-list-multiple.out.md')),
      array('Definition list with paragraph tags', $this->getContentForMarkup('definition-list-with-paragraph-tags.in.md'), $this->getContentForMarkup('definition-list-with-paragraph-tags.out.md')),
      array('Definition list with multiple paragraphs', $this->getContentForMarkup('definition-list-with-multiple-paragraphs.in.md'), $this->getContentForMarkup('definition-list-with-multiple-paragraphs.out.md')),

      // Footnotes
      array('Footnote first', $this->getContentForMarkup('footnote-first.in.md'), $this->getContentForMarkup('footnote-first.out.md')),
      array('Footnote intermingled', $this->getContentForMarkup('footnote-intermingled.in.md'), $this->getContentForMarkup('footnote-intermingled.out.md')),
      array('Footnote last', $this->getContentForMarkup('footnote-last.in.md'), $this->getContentForMarkup('footnote-last.out.md')),
      array('Footnote with multiple paragraphs', $this->getContentForMarkup('footnote-with-multiple-paragraphs.in.md'), $this->getContentForMarkup('footnote-with-multiple-paragraphs.out.md')),

      // Abbreviations
      array('Abbreviation definition first', "*[HTML]: Hyper Text Markup Language\n\nThe HTML specification", "<p>The <abbr title=\"Hyper Text Markup Language\">HTML</abbr> specification</p>\n"),
      array('Abbreviation definition middle', "The HTML specification\n\n*[HTML]: Hyper Text Markup Language\n\nis dry", "<p>The <abbr title=\"Hyper Text Markup Language\">HTML</abbr> specification</p>\n\n<p>is dry</p>\n"),
      array('Abbreviation definition last', "The HTML specification\n\n*[HTML]: Hyper Text Markup Language", "<p>The <abbr title=\"Hyper Text Markup Language\">HTML</abbr> specification</p>\n"),
      array('Multiword abbreviation', "*[Foo Bar]: Fubar\n\nI saw a Foo Bar once", "<p>I saw a <abbr title=\"Fubar\">Foo Bar</abbr> once</p>\n"),
      array('Empty abbreviateion', "Operation Tigra Genesis is going well.\n\n*[Tigra Genesis]:", "<p>Operation <abbr>Tigra Genesis</abbr> is going well.</p>\n"),

      array('Single underscores in the middle of a word', 'un_frigging_believable', "<p>un_frigging_believable</p>\n"),
      array('Double underscores in the middle of a word', 'un__frigging__believable', "<p>un__frigging__believable</p>\n"),
    );
  }

}

class TypographyStub extends EE_Typography
{
	public function __construct()
	{
		// Skipping initialize and autoloader
	}
}
// EOF
