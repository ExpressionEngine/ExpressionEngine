<?php

require_once SYSPATH . 'ee/ExpressionEngine/Boot/boot.common.php';
require_once APPPATH . 'helpers/string_helper.php';
require_once APPPATH . 'libraries/Typography.php';
require_once APPPATH . 'libraries/typography/Markdown/Michelf/MarkdownExtra.inc.php';

if (!defined('PATH_ADDONS')) {
    define('PATH_ADDONS', APPPATH . 'modules/');
}

use PHPUnit\Framework\TestCase;

class TypographyTest extends TestCase
{
    private $typography;

    public function setUp(): void
    {
        $this->typography = new TypographyStub();
    }

    public function testCodeFence()
    {
        $str = $this->typography->markdown(CODE_FENCE);

        // Make sure we've removed all code fences
        $this->assertStringNotContainsString('~~~', $str);
        $this->assertStringNotContainsString('```', $str);

        // The ~~``~~ turns to code (`` => <code>)
        $this->assertStringContainsString('~~<code>~~', $str);
        $this->assertStringContainsString('~~</code>~~', $str);

        // Must contain unaffected opening and close php tags
        $this->assertStringContainsString('&lt;?php', $str);
        $this->assertStringContainsString('?&gt;', $str);
    }

    public function testCodeBlock()
    {
        $str = $this->typography->markdown(CODE_FENCE);

        // Should contain no tabs
        $this->assertStringNotContainsString("\t", $str);

        // Must contain unaffected opening and close php tags
        $this->assertStringContainsString('&lt;?php', $str);
        $this->assertStringContainsString('?&gt;', $str);
    }

    public function testCodeBlockAndFence()
    {
        $str = $this->typography->markdown(CODE_BLOCK_AND_FENCE);

        // Should contain no tabs
        $this->assertStringNotContainsString("\t", $str);

        // Make sure we've removed all code fences
        $this->assertStringNotContainsString('~~~', $str);
        $this->assertStringNotContainsString('```', $str);

        // The ~~``~~ turns to code (`` => <code>)
        $this->assertStringContainsString('~~<code>~~', $str);
        $this->assertStringContainsString('~~</code>~~', $str);

        // Must contain unaffected opening and close php tags
        $this->assertStringContainsString('&lt;?php', $str);
        $this->assertStringContainsString('?&gt;', $str);
    }

    public function testSmartyPants()
    {
        $str = $this->typography->markdown(SMARTYPANTS);

        // The em and en dashes should be where you'd expect them
        $this->assertStringContainsString("dashes&#8212;they", $str);
        $this->assertStringContainsString("thoughts&#8212;with", $str);
        $this->assertStringContainsString("2004&#8211;2014", $str);

        // Fancy quotes should be around "fancy quotes" and 'there'
        $this->assertStringContainsString("&#8220;fancy quotes&#8221;", $str);
        $this->assertStringContainsString("&#8216;there&#8217;", $str);

        // Test WITHOUT SmartyPants
        $str = $this->typography->markdown(SMARTYPANTS, array('smartypants' => false));

        // dashes and quotes should not be converted
        $this->assertStringContainsString("dashes---they", $str);
        $this->assertStringContainsString("thoughts---with", $str);
        $this->assertStringContainsString("2004--2014", $str);
        $this->assertStringContainsString("\"fancy quotes\"", $str);
        $this->assertStringContainsString("'there'", $str);
    }

    public function testNoMarkup()
    {
        $str = $this->typography->markdown(MARKDOWN, array('no_markup' => true));

        // Make sure markup is not parsed
        $this->assertStringNotContainsString('<div', $str);
        $this->assertStringNotContainsString('<span>really</span>', $str);
        $this->assertStringNotContainsString('<em>just</em>', $str);
    }

    public function testLinksWithSpaces()
    {
        $str = $this->typography->markdown(MARKDOWN);
        $this->assertStringContainsString('<a href="https://packagecontrol.io/packages/Marked%20App%20Menu">Marked App Menu</a>', $str);
    }

    public function testMarkdownNormalizesOnlyMatchedInlineLinkUrl()
    {
        $originalUrl = 'https://unicode.example/path';
        $normalizedUrl = 'https://normalized.example/path';
        $this->typography->decodedUrls[$originalUrl] = $normalizedUrl;

        $markdown = 'Plain parenthetical URL: (' . $originalUrl . '). Linked URL: [site](' . $originalUrl . ').';

        $str = $this->typography->markdown($markdown, array('smartypants' => false));

        $this->assertStringContainsString('Plain parenthetical URL: (' . $originalUrl . ').', $str);
        $this->assertStringContainsString('<a href="' . $normalizedUrl . '">site</a>', $str);
        $this->assertStringNotContainsString('Plain parenthetical URL: (' . $normalizedUrl . ').', $str);
    }

    public function testMarkdownEncodesSpacesInAngleBracketInlineLinkUrl()
    {
        $markdown = 'Read [release notes](<https://example.com/release notes?name=big deal> "Release notes").';

        $str = $this->typography->markdown($markdown, array('smartypants' => false));

        $this->assertStringContainsString('<a href="https://example.com/release%20notes?name=big%20deal" title="Release notes">release notes</a>', $str);
    }

    public function testMarkdownDoesNotNormalizeLinkTextThatLooksLikeUrl()
    {
        $label = 'https://label.example';
        $targetUrl = 'https://target.example/path';
        $normalizedLabel = 'https://normalized-label.example';
        $normalizedTargetUrl = 'https://normalized-target.example/path';
        $this->typography->decodedUrls[$label] = $normalizedLabel;
        $this->typography->decodedUrls[$targetUrl] = $normalizedTargetUrl;

        $str = $this->typography->markdown('See [' . $label . '](' . $targetUrl . ').', array('smartypants' => false));

        $this->assertStringContainsString('<a href="' . $normalizedTargetUrl . '">' . $label . '</a>', $str);
        $this->assertStringNotContainsString($normalizedLabel, $str);
    }

    public function testMarkdownNormalizesOnlyHrefWhenUrlAppearsInLabelOrTitle()
    {
        $originalUrl = 'https://unicode.example/path';
        $normalizedUrl = 'https://normalized.example/path';
        $this->typography->decodedUrls[$originalUrl] = $normalizedUrl;

        $regular = $this->typography->markdown(
            '[see (' . $originalUrl . ')](' . $originalUrl . ' "Title ' . $originalUrl . '")',
            array('smartypants' => false)
        );

        $this->assertStringContainsString('<a href="' . $normalizedUrl . '" title="Title ' . $originalUrl . '">see (' . $originalUrl . ')</a>', $regular);
        $this->assertStringNotContainsString('Title ' . $normalizedUrl, $regular);
        $this->assertStringNotContainsString('see (' . $normalizedUrl . ')', $regular);

        $angle = $this->typography->markdown(
            '[angle](<' . $originalUrl . '> "Title <' . $originalUrl . '>")',
            array('smartypants' => false)
        );

        $this->assertStringContainsString('<a href="' . $normalizedUrl . '" title="Title &lt;' . $originalUrl . '>">angle</a>', $angle);
        $this->assertStringNotContainsString('Title &lt;' . $normalizedUrl . '>', $angle);
    }

    public function testMarkdownNormalizesUtf8HostsInInlineLinkUrls()
    {
        if (! function_exists('idn_to_ascii')) {
            $this->markTestSkipped('IDN normalization requires the intl extension.');
        }

        $unicodeHost = 't' . "\xC3\xA4" . 'st.example';
        $encodedHost = idn_to_ascii($unicodeHost, 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : INTL_IDNA_VARIANT_2003);

        if ($encodedHost !== 'xn--tst-qla.example') {
            $this->markTestSkipped('IDN normalization is unavailable in this PHP environment.');
        }

        $path = 'caf' . "\xC3\xA9";
        $query = 'na' . "\xC3\xAF" . 've';
        $unicodeUrl = 'https://' . $unicodeHost . '/' . $path . '?q=' . $query;
        $expectedUrl = 'https://' . $encodedHost . '/' . $path . '?q=' . $query;

        $str = $this->typography->markdown(
            'Visit [regular](' . $unicodeUrl . ') and [angle](<' . $unicodeUrl . '>).',
            array('smartypants' => false)
        );

        $this->assertStringContainsString('<a href="' . $expectedUrl . '">regular</a>', $str);
        $this->assertStringContainsString('<a href="' . $expectedUrl . '">angle</a>', $str);
        $this->assertStringNotContainsString('href="' . $unicodeUrl . '"', $str);
    }

    public function testEmoticonConversionOn()
    {
        ee()->session->setUserdata('parse_smileys', 'y');
        $sample = 'Smileys like ;) should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';
        $expected = 'Smileys like :wink: should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';

        $str = $this->typography->emoticon_replace($sample);
        $this->assertEquals($expected, $str);
    }

    public function testEmoticonConversionOff()
    {
        ee()->session->setUserdata('parse_smileys', 'n');
        $sample = 'Smileys like ;) should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';
        $expected = 'Smileys like ;) should be converted but not character entities like &rdquo;, even if they end in parenthesis &rdquo;)';

        $str = $this->typography->emoticon_replace($sample);
        $this->assertEquals($expected, $str);
    }
}

class TypographyStub extends EE_Typography
{
    public $decodedUrls = array();

    public function __construct()
    {
        // Skipping initialize and autoloader
    }

    public function decodeIDN($url)
    {
        if (isset($this->decodedUrls[$url])) {
            return $this->decodedUrls[$url];
        }

        return parent::decodeIDN($url);
    }
}

// Define the codeblocks to test with
$code_fence = <<<'MD'
```
<?php
$no_parse = TRUE;
$value = ($no_parse) ? 5 : 6;
?>
```

In between

~~~
<?php $test = 3; ?>
~~~

In between

~~``~~
not code
~~``~~

~~~~~~
$last_block = TRUE;
~~~~~~
MD;
define('CODE_FENCE', $code_fence);

$code_block = <<<'MD'
	<?php
	$no_parse = TRUE;
	$value = ($no_parse) ? 5 : 6;
	?>

In between

	<?php $test = 3; ?>

In between

  not code

    $last_block = TRUE;
MD;
define('CODE_BLOCK', $code_block);

$code_block_and_fence = <<<'MD'
	<?php
	$no_parse = TRUE;
	$value = ($no_parse) ? 5 : 6;
	?>

```
<?php
$no_parse = TRUE;
$value = ($no_parse) ? 5 : 6;
?>
```

In between

~~~
<?php $test = 3; ?>
~~~

	<?php $test = 3; ?>

In between

  not code

    $last_block = TRUE;

In between

~~``~~
not code
~~``~~

In between

~~~~~~
$last_block = TRUE;
~~~~~~
MD;
define('CODE_BLOCK_AND_FENCE', $code_block_and_fence);

$smartypants = <<<'MD'
Testing out em and en dashes---they're the fancy long dashes that separate
thoughts---with this string. Our copyright is 2004--2014.

Let's try out some "fancy quotes" here and 'there'.
MD;
define('SMARTYPANTS', $smartypants);

$markdown = <<<'MD'
Markdown
-------------

...is <span>really</span> <em>just</em> ordinary text, *plain and simple*. How is it good for you?

- You just **type naturally**, and the result looks good.
- You **don't have to worry** about clicking formatting buttons.
  - Or fiddling with indentation. (Two spaces is all you need.)

<div class="something">This shouldn't be parsed in Markdown with the no_markup
	option set.</div>

We also have a URL here that has spaces in it: [Marked App Menu](https://packagecontrol.io/packages/Marked%20App%20Menu).

To see what else you can do with Markdown (including **tables**, **images**, **numbered lists**, and more) take a look at the [Cheatsheet][1]. And then try it out by typing in this box!

[1]: https://github.com/adam-p/markdown-here/wiki/Markdown-Here-Cheatsheet
MD;
define('MARKDOWN', $markdown);

// EOF
